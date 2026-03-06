<?php
declare(strict_types=1);

require_once __DIR__ . '/../modules/content_repository.php';

function scheduleApiLoadConfig(): array
{
    $path = __DIR__ . '/../config/schedule_api.php';
    if (!is_file($path)) {
        return [
            'endpoint' => '',
            'doctor_id_param' => 'doctor_id',
            'point_default' => 0,
            'days_default' => 7,
            'timeout_sec' => 15,
            'headers' => [],
        ];
    }

    $raw = require $path;
    $cfg = is_array($raw) ? $raw : [];

    $headers = [];
    if (is_array($cfg['headers'] ?? null)) {
        foreach ($cfg['headers'] as $headerLine) {
            $line = trim((string)$headerLine);
            if ($line !== '') {
                $headers[] = $line;
            }
        }
    }

    return [
        'endpoint' => trim((string)($cfg['endpoint'] ?? '')),
        'doctor_id_param' => trim((string)($cfg['doctor_id_param'] ?? 'doctor_id')) ?: 'doctor_id',
        'point_default' => in_array((int)($cfg['point_default'] ?? 0), [0, 1], true) ? (int)($cfg['point_default'] ?? 0) : 0,
        'days_default' => max(1, (int)($cfg['days_default'] ?? 7)),
        'timeout_sec' => max(3, min(120, (int)($cfg['timeout_sec'] ?? 15))),
        'headers' => $headers,
    ];
}

function scheduleBuildRequestUrl(string $endpoint, string $doctorParam, int $doctorId): string
{
    $endpoint = trim($endpoint);
    if ($endpoint === '') {
        return '';
    }

    if (strpos($endpoint, '{doctor_id}') !== false) {
        return str_replace('{doctor_id}', rawurlencode((string)$doctorId), $endpoint);
    }

    $separator = strpos($endpoint, '?') === false ? '?' : '&';
    return $endpoint . $separator . rawurlencode($doctorParam) . '=' . rawurlencode((string)$doctorId);
}

function scheduleFetchHttpJson(string $url, int $timeoutSec, array $headers): array
{
    if ($url === '') {
        throw new RuntimeException('schedule_api_endpoint_empty');
    }

    $headers = array_values(array_filter(array_map(static fn($h): string => trim((string)$h), $headers), static fn(string $h): bool => $h !== ''));

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('schedule_api_curl_init_failed');
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(10, $timeoutSec));
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSec);
        if (count($headers) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('schedule_api_http_error: ' . $error);
        }
        $statusCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('schedule_api_http_status_' . $statusCode);
        }
        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('schedule_api_invalid_json');
        }
        return $decoded;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeoutSec,
            'ignore_errors' => true,
            'header' => implode("\r\n", $headers),
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    if (!is_string($raw) || $raw === '') {
        throw new RuntimeException('schedule_api_http_error');
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('schedule_api_invalid_json');
    }
    return $decoded;
}

function scheduleNormalizeApiPayload(array $decoded): array
{
    $payload = $decoded;
    if (is_array($decoded['data'] ?? null)) {
        $payload = $decoded['data'];
    }

    if (array_keys($payload) === range(0, count($payload) - 1)) {
        $rowsByDay = [];
        foreach ($payload as $row) {
            if (!is_array($row)) {
                continue;
            }
            $dayRaw = trim((string)($row['dt'] ?? $row['date'] ?? ''));
            if ($dayRaw === '') {
                continue;
            }
            $from = trim((string)($row['time_start'] ?? $row['from'] ?? ''));
            $to = trim((string)($row['time_end'] ?? $row['to'] ?? ''));
            $time = trim((string)($row['time'] ?? ''));
            if ($time === '' && ($from !== '' || $to !== '')) {
                $time = $from . ($to !== '' ? ('-' . $to) : '');
            }
            $isFree = ($row['free'] ?? null) === true;
            $status = $isFree ? 'free' : 'busy';
            $slot = [
                'time' => $time !== '' ? $time : 'Слот',
                'status' => $status,
                'busy' => !$isFree,
                'from' => $from,
                'to' => $to,
            ];
            if (!isset($rowsByDay[$dayRaw])) {
                $rowsByDay[$dayRaw] = [];
            }
            $rowsByDay[$dayRaw][] = $slot;
        }

        $days = [];
        ksort($rowsByDay);
        foreach ($rowsByDay as $day => $slots) {
            usort($slots, static function (array $a, array $b): int {
                return strcmp((string)($a['from'] ?? ''), (string)($b['from'] ?? ''));
            });
            $days[] = [
                'day' => (string)$day,
                'slots' => $slots,
            ];
        }
        return ['days' => $days];
    }

    if (!isset($payload['days']) || !is_array($payload['days'])) {
        $payload['days'] = [];
    }

    return $payload;
}

function scheduleLimitPayloadByDays(array $payload, int $days): array
{
    $days = max(1, min(31, $days));
    $list = is_array($payload['days'] ?? null) ? $payload['days'] : [];
    $payload['days'] = array_slice($list, 0, $days);
    return $payload;
}

function scheduleFetchForDoctorId(array $apiConfig, int $doctorId, ?int $pointOverride = null, ?int $daysOverride = null): array
{
    if ($doctorId <= 0) {
        throw new RuntimeException('doctor_id_missing');
    }
    if (trim((string)($apiConfig['endpoint'] ?? '')) === '') {
        throw new RuntimeException('В config/schedule_api.php не заполнен endpoint');
    }
    $url = trim((string)$apiConfig['endpoint']);
    $headers = is_array($apiConfig['headers']) ? $apiConfig['headers'] : [];
    $headers[] = 'Content-Type: application/json; charset=utf-8';
    $point = $pointOverride !== null ? $pointOverride : (int)($apiConfig['point_default'] ?? 0);
    if (!in_array($point, [0, 1], true)) {
        $point = 0;
    }
    $days = $daysOverride !== null ? $daysOverride : (int)($apiConfig['days_default'] ?? 7);
    $days = max(1, min(31, $days));
    $jsonBody = json_encode([
        'point' => $point,
        'iddoctor' => $doctorId,
        'days' => $days,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($jsonBody)) {
        throw new RuntimeException('schedule_api_request_json_encode_failed');
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('schedule_api_curl_init_failed');
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(10, (int)$apiConfig['timeout_sec']));
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$apiConfig['timeout_sec']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('schedule_api_http_error: ' . $error);
        }
        $statusCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('schedule_api_http_status_' . $statusCode);
        }
        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('schedule_api_invalid_json');
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => (int)$apiConfig['timeout_sec'],
                'ignore_errors' => true,
                'header' => implode("\r\n", $headers),
                'content' => $jsonBody,
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
        if (!is_string($raw) || $raw === '') {
            throw new RuntimeException('schedule_api_http_error');
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('schedule_api_invalid_json');
        }
    }
    $normalized = scheduleNormalizeApiPayload($decoded);
    return scheduleLimitPayloadByDays($normalized, $days);
}

function scheduleShouldRefresh(?string $updatedAtIso, int $ttlMin): bool
{
    if ($ttlMin <= 0) {
        return true;
    }
    $updatedAtIso = trim((string)$updatedAtIso);
    if ($updatedAtIso === '') {
        return true;
    }
    $updatedTs = strtotime($updatedAtIso);
    if ($updatedTs === false) {
        return true;
    }
    return (time() - $updatedTs) >= ($ttlMin * 60);
}

function scheduleRefreshCacheForContent(PDO $pdo, array $apiConfig, array $content, bool $force = false): array
{
    $contentId = (int)($content['id'] ?? 0);
    $rawData = (string)($content['data_json'] ?? '');
    $data = json_decode($rawData, true);
    if (!is_array($data)) {
        $data = [];
    }
    $schedule = is_array($data['schedule'] ?? null) ? $data['schedule'] : [];

    $doctorId = (int)($schedule['doctor_id'] ?? 0);
    if ($doctorId <= 0) {
        return ['content_id' => $contentId, 'status' => 'error', 'message' => 'doctor_id_missing'];
    }

    $point = (int)($schedule['point'] ?? ($apiConfig['point_default'] ?? 0));
    if (!in_array($point, [0, 1], true)) {
        $point = 0;
    }
    $days = (int)($schedule['days'] ?? ($apiConfig['days_default'] ?? 7));
    $days = max(1, min(31, $days));

    $payload = scheduleFetchForDoctorId($apiConfig, $doctorId, $point, $days);

    $schedule['cached_payload'] = $payload;
    $schedule['cached_updated_at'] = gmdate('c');
    $schedule['point'] = $point;
    $schedule['days'] = $days;
    $data['schedule'] = $schedule;
    $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) {
        return ['content_id' => $contentId, 'status' => 'error', 'message' => 'json_encode_failed'];
    }

    $stmt = $pdo->prepare('UPDATE content_items SET data_json = :data_json, updated_at = NOW() WHERE id = :id');
    $stmt->execute([
        ':data_json' => $encoded,
        ':id' => $contentId,
    ]);

    return ['content_id' => $contentId, 'status' => 'ok', 'message' => 'updated', 'doctor_id' => $doctorId, 'point' => $point, 'days' => $days];
}

function scheduleRefreshCaches(PDO $pdo, bool $force = false, ?int $onlyContentId = null): array
{
    contentEnsureOwnershipSchema($pdo);
    $apiConfig = scheduleApiLoadConfig();
    if (trim((string)$apiConfig['endpoint']) === '') {
        throw new RuntimeException('В config/schedule_api.php не заполнен endpoint');
    }

    $params = [];
    $sql = "SELECT id, data_json FROM content_items WHERE type = 'schedule'";
    if ($onlyContentId !== null && $onlyContentId > 0) {
        $sql .= ' AND id = :id';
        $params[':id'] = $onlyContentId;
    }
    $sql .= ' ORDER BY id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($rows as $row) {
        try {
            $results[] = scheduleRefreshCacheForContent($pdo, $apiConfig, $row, $force);
        } catch (Throwable $e) {
            $results[] = [
                'content_id' => (int)($row['id'] ?? 0),
                'status' => 'error',
                'message' => trim((string)$e->getMessage()) ?: 'refresh_failed',
            ];
        }
    }
    return $results;
}
