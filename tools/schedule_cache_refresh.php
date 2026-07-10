<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/schedule_cache_service.php';

$force = false;

foreach (array_slice($_SERVER['argv'] ?? [], 1) as $arg) {
    $arg = trim((string)$arg);
    if ($arg === '--force') {
        $force = true;
    }
}

try {
    $pdo = dbMysql();
    $results = scheduleRefreshCaches($pdo, $force, null);

    $ok = 0;
    $skipped = 0;
    $error = 0;

    foreach ($results as $row) {
        $status = (string)($row['status'] ?? 'error');
        if ($status === 'ok') {
            $ok++;
        } elseif ($status === 'skipped') {
            $skipped++;
        } else {
            $error++;
        }
        $line = sprintf(
            '[%s] content_id=%d %s',
            strtoupper($status),
            (int)($row['content_id'] ?? 0),
            (string)($row['message'] ?? '')
        );
        echo $line . PHP_EOL;
    }

    echo sprintf('SUMMARY: ok=%d skipped=%d error=%d total=%d', $ok, $skipped, $error, count($results)) . PHP_EOL;
    exit($error > 0 ? 2 : 0);
} catch (Throwable $e) {
    $message = trim((string)$e->getMessage());
    if ($message === '') {
        $message = 'schedule_cache_refresh_failed';
    }
    fwrite(STDERR, 'ERROR: ' . $message . PHP_EOL);
    exit(1);
}
