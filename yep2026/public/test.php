<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo 'START<br>';

try {
    require_once dirname(__DIR__) . '/bootstrap.php';

    echo 'BOOTSTRAP OK<br>';
} catch (Throwable $e) {
    echo '<pre>';
    echo get_class($e) . ': ' . $e->getMessage();
    echo "\nFile: " . $e->getFile();
    echo "\nLine: " . $e->getLine();
    echo '</pre>';
}