<?php
declare(strict_types=1);

function projectVersion(): string
{
    static $version = null;

    if (is_string($version)) {
        return $version;
    }

    $path = __DIR__ . '/../VERSION';
    if (!is_file($path)) {
        $version = '0.0.0-dev';
        return $version;
    }

    $raw = trim((string)file_get_contents($path));
    $version = $raw !== '' ? $raw : '0.0.0-dev';

    return $version;
}
