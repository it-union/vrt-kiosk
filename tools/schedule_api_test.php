<?php
declare(strict_types=1);

require_once __DIR__ . '/../modules/schedule_cache_service.php';

$doctorId = 1;
$point = 1;
$days = 7;

foreach (array_slice($_SERVER['argv'] ?? [], 1) as $arg) {
    $arg = trim((string)$arg);
    if (strpos($arg, '--doctor=') === 0) {
        $doctorId = (int)substr($arg, strlen('--doctor='));
        continue;
    }
    if (strpos($arg, '--point=') === 0) {
        $point = (int)substr($arg, strlen('--point='));
        continue;
    }
    if (strpos($arg, '--days=') === 0) {
        $days = (int)substr($arg, strlen('--days='));
        continue;
    }
}

if ($doctorId <= 0) {
    fwrite(STDERR, "ERROR: doctor must be > 0\n");
    exit(1);
}
if ($point <= 0) {
    fwrite(STDERR, "ERROR: point must be > 0\n");
    exit(1);
}
if ($days <= 0) {
    fwrite(STDERR, "ERROR: days must be > 0\n");
    exit(1);
}

$cfg = scheduleApiLoadConfig();
$endpoint = trim((string)($cfg['endpoint'] ?? ''));
if ($endpoint === '') {
    fwrite(STDERR, "ERROR: endpoint is empty in config/schedule_api.php\n");
    exit(1);
}

$headers = is_array($cfg['headers'] ?? null) ? $cfg['headers'] : [];
$headers = array_values(array_filter(array_map(static fn($h): string => trim((string)$h), $headers), static fn(string $h): bool => $h !== ''));
$headers[] = 'Content-Type: application/json; charset=utf-8';

$payload = [
    'point' => $point,
    'iddoctor' => $doctorId,
    'days' => $days,
];
$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (!is_string($json)) {
    fwrite(STDERR, "ERROR: failed to encode request JSON\n");
    exit(1);
}

$timeoutSec = max(3, min(120, (int)($cfg['timeout_sec'] ?? 15)));
$responseBody = '';
$statusCode = 0;

if (function_exists('curl_init')) {
    $ch = curl_init($endpoint);
    if ($ch === false) {
        fwrite(STDERR, "ERROR: curl init failed\n");
        exit(1);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(10, $timeoutSec));
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSec);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $raw = curl_exec($ch);
    if ($raw === false) {
        $error = curl_error($ch);
        curl_close($ch);
        fwrite(STDERR, "ERROR: curl request failed: " . $error . "\n");
        exit(1);
    }
    $statusCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $responseBody = (string)$raw;
} else {
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'timeout' => $timeoutSec,
            'ignore_errors' => true,
            'header' => implode("\r\n", $headers),
            'content' => $json,
        ],
    ]);
    $raw = @file_get_contents($endpoint, false, $context);
    $responseBody = is_string($raw) ? $raw : '';
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $line) {
            if (preg_match('~^HTTP/\S+\s+(\d{3})~i', (string)$line, $m)) {
                $statusCode = (int)$m[1];
                break;
            }
        }
    }
}

echo 'URL: ' . $endpoint . PHP_EOL;
echo 'REQUEST: ' . $json . PHP_EOL;
echo 'HTTP: ' . $statusCode . PHP_EOL;
echo 'RESPONSE:' . PHP_EOL;
echo ($responseBody !== '' ? $responseBody : '<empty>') . PHP_EOL;

exit(($statusCode >= 200 && $statusCode < 300) ? 0 : 2);
