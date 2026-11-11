<?php

namespace App\Storage;

use JsonException;
use RuntimeException;

/**
 * File JSON lưu trạng thái của ứng dụng.
 * Đọc/khoá/ghi lỗi thì throw kèm nhãn để biết file nào hỏng.
 */
class JsonFile
{
    /**
     * Thời gian tối đa chờ lock (giây). Process khác giữ lock lâu hơn => throw thay vì treo request.
     */
    private const LOCK_TIMEOUT_SECONDS = 10;

    /**
     * @param string $label tên file trong thông báo lỗi, ví dụ 'nhật ký gửi'
     * @param bool $required file bắt buộc phải có dữ liệu: thiếu file hoặc file rỗng => throw thay vì coi là []
     *                       (dùng cho dữ liệu nghiệp vụ, không bao giờ khởi tạo lại từ rỗng)
     */
    public function __construct(
        private string $path,
        private string $label,
        private bool $required = false
    ) {
    }

    /**
     * Chưa có file hoặc file rỗng => [] ($required => throw).
     * Nội dung không phải JSON object/array => throw, không bao giờ coi là rỗng
     * để tránh ghi đè mất dữ liệu cũ.
     */
    public function read(): array
    {
        if (!is_file($this->path)) {
            if ($this->required) {
                throw new RuntimeException("Không tìm thấy {$this->label}: {$this->path}");
            }

            return [];
        }

        if (!is_readable($this->path)) {
            throw new RuntimeException("Không có quyền đọc {$this->label}: {$this->path}");
        }

        $content = file_get_contents($this->path);

        if ($content === false) {
            throw new RuntimeException(
                "Không đọc được {$this->label}: {$this->path}"
            );
        }

        if (trim($content) === '') {
            if ($this->required) {
                throw new RuntimeException(ucfirst($this->label) . " bị rỗng: {$this->path}");
            }

            return [];
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(
                ucfirst($this->label) . " bị hỏng ({$this->path}): " . $e->getMessage()
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException(
                ucfirst($this->label) . " bị hỏng ({$this->path}): không phải JSON object"
            );
        }

        return $data;
    }

    public function ensureDirectory(): void
    {
        $dir = dirname($this->path);

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException(
                "Không tạo được thư mục {$this->label}: {$dir}"
            );
        }
    }

    /**
     * Thư mục phải ghi được (file tạm + rename), file hiện có phải đọc/ghi được.
     * Owner khác user đang chạy PHP => rename sẽ đổi owner của file, ghi log để
     * người vận hành biết (không chặn vì quyền ghi đã đủ).
     */
    public function ensureWritable(): void
    {
        $this->ensureDirectory();

        $dirWritable = is_writable(dirname($this->path));
        $fileWritable = !is_file($this->path)
            || (is_writable($this->path) && is_readable($this->path));

        if (!$dirWritable || !$fileWritable) {
            throw new RuntimeException(
                "Không có quyền ghi {$this->label}: {$this->path}"
            );
        }

        if (
            is_file($this->path)
            && function_exists('posix_geteuid')
            && fileowner($this->path) !== posix_geteuid()
        ) {
            error_log("JsonFile: owner của {$this->path} khác user đang chạy PHP, rename sẽ đổi owner");
        }
    }

    /**
     * Đọc - sửa - ghi trong cùng một exclusive lock (file {path}.lock riêng,
     * không khoá trực tiếp file dữ liệu vì file đó bị thay bằng rename()).
     * Dữ liệu luôn được đọc lại sau khi đã có lock nên không ghi đè bản mới
     * của request khác. Callback không đổi dữ liệu => không ghi file.
     *
     * @template T
     * @param callable(array &$data): T $modify sửa $data tại chỗ, giá trị trả về được trả lại cho caller
     * @return T
     */
    public function update(callable $modify): mixed
    {
        $this->ensureWritable();

        // Lock file không bao giờ bị xoá: xoá trong lúc request khác đang giữ
        // lock sẽ làm hai request khoá trên hai inode khác nhau.
        $lock = @fopen($this->path . '.lock', 'c');

        if ($lock === false) {
            throw new RuntimeException(
                "Không mở được file lock của {$this->label}: {$this->path}.lock"
            );
        }

        try {
            $this->acquireLock($lock);

            $data = $this->read();
            $original = $data;
            $result = $modify($data);

            if ($data !== $original) {
                $this->writeAtomic($data);
            }

            return $result;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * LOCK_EX có giới hạn thời gian chờ: lock bị process khác giữ quá LOCK_TIMEOUT_SECONDS => throw.
     *
     * @param resource $lock
     */
    private function acquireLock($lock): void
    {
        $deadline = microtime(true) + self::LOCK_TIMEOUT_SECONDS;

        while (!flock($lock, LOCK_EX | LOCK_NB, $wouldBlock)) {
            if (!$wouldBlock || microtime(true) >= $deadline) {
                throw new RuntimeException(
                    "Không khoá được {$this->label}" . ($wouldBlock ? ' (chờ lock quá lâu)' : '') . ": {$this->path}"
                );
            }

            usleep(20_000);
        }
    }

    /**
     * Ghi ra file tạm (tên riêng, cùng thư mục) rồi rename, nên file thật luôn là
     * bản cũ hoặc bản mới đầy đủ, không bị ghi dở. Lỗi ở bất kỳ bước nào => xoá
     * file tạm, giữ nguyên file cũ.
     */
    private function writeAtomic(array $data): void
    {
        try {
            $json = json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ) . "\n";
            // Nội dung sắp ghi phải parse lại được, không thì không thay file cũ
            json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException("Không encode được {$this->label}: " . $e->getMessage());
        }

        $tmp = $this->path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        $handle = @fopen($tmp, 'x');

        if ($handle === false) {
            throw new RuntimeException("Không tạo được file tạm cho {$this->label}: {$tmp}");
        }

        $written = @fwrite($handle, $json);
        $flushed = @fflush($handle);
        // Đẩy dữ liệu xuống đĩa trước khi rename (mất điện không để lại file mới rỗng)
        $synced = !function_exists('fsync') || @fsync($handle);
        $closed = @fclose($handle);

        // Giữ quyền của file cũ (file tạm tạo theo umask của PHP)
        if (is_file($this->path)) {
            @chmod($tmp, fileperms($this->path) & 0777);
        }

        if ($written !== strlen($json) || !$flushed || !$synced || !$closed || !@rename($tmp, $this->path)) {
            @unlink($tmp);

            throw new RuntimeException(
                "Không ghi được {$this->label}: {$this->path}"
            );
        }
    }
}
