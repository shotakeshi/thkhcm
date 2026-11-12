<?php

namespace App\Log;

use Throwable;

/**
 * Ghi log lỗi dạng JSON Lines (mỗi dòng một JSON) vào {thư mục}/error-YYYY-MM-DD.log, mỗi ngày một file.
 *
 * Ghi tay: Logger::error('...'), Logger::error($exception), Logger::warning(), Logger::notice(), Logger::info().
 * Tham số thứ hai (tuỳ chọn) là context để trace, vd Logger::error($e, ['uuid' => $uuid]).
 * Tự bắt lỗi: Logger::register() (gọi một lần trong bootstrap.php) đăng ký error / exception / shutdown handler.
 *
 * Không lưu dữ liệu request (GET/POST/header/cookie/body), chỉ method và URI đã bỏ query string;
 * nội dung dạng password=..., token=... trong message/context và key context nhạy cảm (password, token, ...)
 * bị che. Ghi file thất bại => chuyển sang error_log() của PHP, không bao giờ throw ra ứng dụng.
 */
final class Logger
{
    /**
     * Lỗi set_error_handler() không nhận được, chỉ thấy qua error_get_last() lúc shutdown.
     * E_USER_ERROR, E_RECOVERABLE_ERROR đã qua handleError() nên không có ở đây (tránh ghi trùng).
     */
    private const FATAL_TYPES = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

    private const ERROR_TYPES = [
        E_ERROR             => ['E_ERROR', 'ERROR'],
        E_PARSE             => ['E_PARSE', 'ERROR'],
        E_CORE_ERROR        => ['E_CORE_ERROR', 'ERROR'],
        E_COMPILE_ERROR     => ['E_COMPILE_ERROR', 'ERROR'],
        E_USER_ERROR        => ['E_USER_ERROR', 'ERROR'],
        E_RECOVERABLE_ERROR => ['E_RECOVERABLE_ERROR', 'ERROR'],
        E_WARNING           => ['E_WARNING', 'WARNING'],
        E_CORE_WARNING      => ['E_CORE_WARNING', 'WARNING'],
        E_COMPILE_WARNING   => ['E_COMPILE_WARNING', 'WARNING'],
        E_USER_WARNING      => ['E_USER_WARNING', 'WARNING'],
        E_NOTICE            => ['E_NOTICE', 'NOTICE'],
        E_USER_NOTICE       => ['E_USER_NOTICE', 'NOTICE'],
        E_DEPRECATED        => ['E_DEPRECATED', 'NOTICE'],
        E_USER_DEPRECATED   => ['E_USER_DEPRECATED', 'NOTICE'],
    ];

    /**
     * Che giá trị sau password=, token: , "api_key":"...", Authorization: Bearer ... trong message.
     */
    private const SENSITIVE_PATTERN = '/\b(password|passwd|pwd|secret|token|api[_-]?key|authorization|cookie|phpsessid|session[_-]?id)\b'
        . '(["\']?\s*[=:]\s*)(["\']?)(?:(?:Bearer|Basic)\s+)?[^\s"\'&,;]+/i';

    /**
     * Key context có tên khớp mẫu này => giá trị bị thay bằng [REDACTED].
     */
    private const SENSITIVE_KEY_PATTERN = '/password|passwd|pwd|secret|token|api[_-]?key|authorization|cookie|session/i';

    private const MAX_MESSAGE_LENGTH = 4000;
    private const MAX_TRACE_FRAMES = 20;

    private static ?string $directory = null;
    private static bool $registered = false;

    /**
     * Đang ghi log: lỗi phát sinh trong lúc ghi không được ghi tiếp (tránh vòng lặp).
     */
    private static bool $writing = false;

    /**
     * Bộ nhớ dự phòng, giải phóng lúc shutdown để vẫn ghi được log khi fatal do hết memory_limit.
     */
    private static ?string $reservedMemory = null;

    /**
     * Đăng ký handler bắt lỗi tự động; gọi nhiều lần chỉ có lần đầu có tác dụng.
     */
    public static function register(string $directory): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;
        self::$directory = rtrim($directory, '/\\');
        self::$reservedMemory = str_repeat(' ', 32 * 1024);

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function error(string|Throwable $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function warning(string|Throwable $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function notice(string $message, array $context = []): void
    {
        self::log('NOTICE', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    /**
     * Ghi lại PHP error rồi trả false để PHP xử lý tiếp như bình thường (hiển thị theo display_errors,
     * ghi log server, error_get_last()), không đổi hành vi hiện tại của ứng dụng.
     * Lỗi bị chặn bằng @ hoặc nằm ngoài error_reporting thì bỏ qua.
     */
    public static function handleError(int $type, string $message, string $file = '', int $line = 0): bool
    {
        if (error_reporting() & $type) {
            [$typeName, $level] = self::ERROR_TYPES[$type] ?? ["E_{$type}", 'ERROR'];

            self::write([
                'level'   => $level,
                'type'    => $typeName,
                'message' => $message,
                'file'    => $file,
                'line'    => $line,
            ]);
        }

        return false;
    }

    /**
     * Exception / Error chưa được catch: ghi log, trả HTTP 500 như PHP mặc định.
     * Chỉ hiện chi tiết khi display_errors bật (môi trường dev); CLI in ra STDERR và thoát mã 255.
     */
    public static function handleException(Throwable $e): void
    {
        self::write(self::throwableEntry('ERROR', $e, 'Uncaught '));

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, 'PHP Fatal error:  Uncaught ' . $e . PHP_EOL);

            exit(255);
        }

        if (!headers_sent()) {
            http_response_code(500);
        }

        if (filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN)) {
            echo '<pre>' . htmlspecialchars('Uncaught ' . $e, ENT_QUOTES, 'UTF-8') . '</pre>';
        }
    }

    /**
     * Fatal error (E_ERROR, E_PARSE, ...) không đi qua handleError(): đọc từ error_get_last() lúc kết thúc.
     */
    public static function handleShutdown(): void
    {
        self::$reservedMemory = null;

        $error = error_get_last();

        if ($error === null || !in_array($error['type'], self::FATAL_TYPES, true)) {
            return;
        }

        self::write([
            'level'   => 'ERROR',
            'type'    => self::ERROR_TYPES[$error['type']][0],
            // "Uncaught ... Stack trace: #0 ..." có thể kèm tham số hàm => chỉ giữ dòng lỗi
            'message' => explode("\nStack trace:", $error['message'], 2)[0],
            'file'    => $error['file'],
            'line'    => $error['line'],
        ]);
    }

    private static function log(string $level, string|Throwable $message, array $context): void
    {
        if ($message instanceof Throwable) {
            $entry = self::throwableEntry($level, $message);
        } else {
            // [0] là lời gọi log() trong class này, [1] là nơi gọi Logger::error()/warning()/...
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];

            $entry = [
                'level'   => $level,
                'message' => $message,
                'file'    => $caller['file'] ?? null,
                'line'    => $caller['line'] ?? null,
            ];
        }

        if ($context !== []) {
            $entry['context'] = self::sanitizeContext($context);
        }

        self::write($entry);
    }

    /**
     * Che giá trị của key nhạy cảm và chuỗi dạng password=...; object chỉ giữ tên class (không dump thuộc tính).
     */
    private static function sanitizeContext(array $context): array
    {
        foreach ($context as $key => $value) {
            $context[$key] = match (true) {
                is_string($key) && preg_match(self::SENSITIVE_KEY_PATTERN, $key) === 1 => '[REDACTED]',
                is_array($value)            => self::sanitizeContext($value),
                is_string($value)           => self::redact($value),
                $value instanceof Throwable => get_class($value) . ': ' . self::redact($value->getMessage()),
                is_object($value)           => get_class($value),
                default                     => $value,
            };
        }

        return $context;
    }

    private static function redact(string $text): string
    {
        return preg_replace(self::SENSITIVE_PATTERN, '$1$2$3[REDACTED]', $text) ?? $text;
    }

    private static function throwableEntry(string $level, Throwable $e, string $prefix = ''): array
    {
        $entry = [
            'level'   => $level,
            'type'    => get_class($e),
            'message' => $prefix . get_class($e) . ': ' . $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => self::trace($e),
        ];

        $previous = $e->getPrevious();

        if ($previous !== null) {
            $entry['previous'] = get_class($previous) . ': ' . $previous->getMessage();
        }

        return $entry;
    }

    /**
     * Stack trace không kèm giá trị tham số (có thể chứa password, dữ liệu khách).
     */
    private static function trace(Throwable $e): array
    {
        $frames = [];

        foreach (array_slice($e->getTrace(), 0, self::MAX_TRACE_FRAMES) as $frame) {
            $location = isset($frame['file']) ? "{$frame['file']}:" . ($frame['line'] ?? 0) : '[internal]';
            $function = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
            $frames[] = "{$location} {$function}()";
        }

        return $frames;
    }

    private static function write(array $entry): void
    {
        if (self::$writing) {
            return;
        }

        self::$writing = true;

        try {
            $message = self::redact((string) $entry['message']);

            $record = [
                'timestamp' => date('Y-m-d H:i:s'),
                'level'     => $entry['level'],
                'message'   => substr($message, 0, self::MAX_MESSAGE_LENGTH),
            ] + $entry + self::requestInfo();

            $line = json_encode(
                $record,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
            ) . PHP_EOL;

            $directory = self::$directory ?? dirname(__DIR__, 2) . '/storage/logs';
            $file = $directory . '/error-' . date('Y-m-d') . '.log';

            $written = (is_dir($directory) || @mkdir($directory, 0775, true) || is_dir($directory))
                && @file_put_contents($file, $line, FILE_APPEND | LOCK_EX) !== false;

            if (!$written) {
                error_log("Logger: không ghi được {$file}, nội dung: " . rtrim($line));
            }
        } catch (Throwable $e) {
            error_log('Logger: ' . $e->getMessage());
        } finally {
            self::$writing = false;
        }
    }

    /**
     * HTTP method và URI đã bỏ query string; chạy CLI => null.
     */
    private static function requestInfo(): array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        $uri = $_SERVER['REQUEST_URI'] ?? null;

        return [
            'method' => is_string($method) ? substr($method, 0, 16) : null,
            'uri'    => is_string($uri) ? substr(explode('?', $uri, 2)[0], 0, 500) : null,
        ];
    }
}
