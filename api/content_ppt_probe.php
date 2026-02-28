<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$url = trim((string)($_GET['url'] ?? ''));
if ($url === '') {
    jsonResponse(['ok' => false, 'error' => 'Нужен url'], 400);
}

$prefix = '/uploads/content_ppt/';
if (substr($url, 0, strlen($prefix)) !== $prefix) {
    jsonResponse(['ok' => false, 'error' => 'Недопустимый путь файла'], 400);
}

$encodedName = substr($url, strlen($prefix));
$decodedName = rawurldecode($encodedName);
$fileName = basename($decodedName);
if ($fileName === '' || $fileName !== $decodedName) {
    jsonResponse(['ok' => false, 'error' => 'Некорректное имя файла'], 400);
}

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось определить каталог проекта'], 500);
}

$path = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content_ppt' . DIRECTORY_SEPARATOR . $fileName;
if (!is_file($path)) {
    jsonResponse(['ok' => false, 'error' => 'Файл не найден'], 404);
}

function detectPdfPageCount(string $path): int
{
    $raw = @file_get_contents($path);
    if (!is_string($raw) || $raw === '') return 0;

    $maxCount = 0;
    if (preg_match_all('/\/Count\s+(\d+)/', $raw, $m) && isset($m[1])) {
        foreach ($m[1] as $v) {
            $n = (int)$v;
            if ($n > $maxCount) $maxCount = $n;
        }
    }
    if ($maxCount > 0) return $maxCount;

    if (preg_match_all('/\/Type\s*\/Page\b/', $raw, $mm)) {
        return max(0, (int)count($mm[0]));
    }
    return 0;
}

function probePdfSizePx(string $path): array
{
    $fh = @fopen($path, 'rb');
    if (!$fh) return [0, 0];
    $chunk = @fread($fh, 2 * 1024 * 1024);
    @fclose($fh);
    if (!is_string($chunk) || $chunk === '') return [0, 0];

    $patterns = [
        '/\/MediaBox\s*\[\s*(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)\s*\]/',
        '/\/CropBox\s*\[\s*(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)\s*\]/',
    ];
    foreach ($patterns as $pattern) {
        if (!preg_match($pattern, $chunk, $m)) continue;
        $x1 = (float)$m[1];
        $y1 = (float)$m[2];
        $x2 = (float)$m[3];
        $y2 = (float)$m[4];
        $wPt = abs($x2 - $x1);
        $hPt = abs($y2 - $y1);
        if ($wPt <= 0 || $hPt <= 0) continue;
        $wPx = (int)round($wPt * 96 / 72);
        $hPx = (int)round($hPt * 96 / 72);
        if ($wPx > 0 && $hPx > 0) return [$wPx, $hPx];
    }
    return [0, 0];
}

function findBinary(array $candidates): string
{
    foreach ($candidates as $candidate) {
        if ($candidate !== '' && preg_match('/[\/\\\\]/', $candidate) && is_file($candidate)) {
            return $candidate;
        }
    }
    foreach ($candidates as $candidate) {
        if ($candidate !== '' && !preg_match('/[\/\\\\]/', $candidate)) {
            return $candidate;
        }
    }
    return '';
}

function generatePdfPreviewImages(string $pdfPath, string $previewDir, string $baseName, string $soffice): array
{
    $pattern = $previewDir . DIRECTORY_SEPARATOR . $baseName . '*.png';

    $pdftoppm = findBinary([
        trim((string)getenv('PDFTOPPM_BIN')),
        'C:\Program Files\poppler\Library\bin\pdftoppm.exe',
        'C:\Program Files (x86)\poppler\Library\bin\pdftoppm.exe',
        '/usr/bin/pdftoppm',
        '/usr/local/bin/pdftoppm',
        'pdftoppm',
    ]);
    if ($pdftoppm !== '') {
        $prefix = $previewDir . DIRECTORY_SEPARATOR . $baseName;
        $cmd = escapeshellarg($pdftoppm)
            . ' -png -r 150 '
            . escapeshellarg($pdfPath) . ' '
            . escapeshellarg($prefix)
            . ' 2>&1';
        $out = [];
        $code = 1;
        @exec($cmd, $out, $code);
        $files = (array)(@glob($pattern) ?: []);
        if (count($files) > 0) {
            natsort($files);
            return array_values($files);
        }
    }

    $gs = findBinary([
        trim((string)getenv('GS_BIN')),
        'C:\Program Files\gs\gs10.05.1\bin\gswin64c.exe',
        'C:\Program Files\gs\gs10.04.0\bin\gswin64c.exe',
        '/usr/bin/gs',
        '/usr/local/bin/gs',
        'gs',
    ]);
    if ($gs !== '') {
        $outPattern = $previewDir . DIRECTORY_SEPARATOR . $baseName . '_%d.png';
        $cmd = escapeshellarg($gs)
            . ' -dSAFER -dBATCH -dNOPAUSE -sDEVICE=pngalpha -r150'
            . ' -sOutputFile=' . escapeshellarg($outPattern)
            . ' ' . escapeshellarg($pdfPath)
            . ' 2>&1';
        $out = [];
        $code = 1;
        @exec($cmd, $out, $code);
        $files = (array)(@glob($pattern) ?: []);
        if (count($files) > 0) {
            natsort($files);
            return array_values($files);
        }
    }

    $cmd = escapeshellarg($soffice)
        . ' --headless --nologo --nolockcheck --nodefault'
        . ' --convert-to png'
        . ' --outdir ' . escapeshellarg($previewDir)
        . ' ' . escapeshellarg($pdfPath)
        . ' 2>&1';
    $out = [];
    $code = 1;
    @exec($cmd, $out, $code);
    $files = (array)(@glob($pattern) ?: []);
    if (count($files) > 0) {
        natsort($files);
        return array_values($files);
    }

    return [];
}

$pdfPages = max(1, detectPdfPageCount($path));
[$widthPx, $heightPx] = probePdfSizePx($path);

$baseName = pathinfo($fileName, PATHINFO_FILENAME);
$metaDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content_ppt_meta';
$metaPath = $metaDir . DIRECTORY_SEPARATOR . $baseName . '.json';
if (is_file($metaPath)) {
    $rawMeta = @file_get_contents($metaPath);
    $meta = is_string($rawMeta) ? json_decode($rawMeta, true) : null;
    if (is_array($meta)) {
        $previewPages = [];
        if (isset($meta['preview_pages']) && is_array($meta['preview_pages'])) {
            foreach ($meta['preview_pages'] as $p) {
                if (is_string($p) && $p !== '') $previewPages[] = $p;
            }
        }
        if (count($previewPages) >= $pdfPages || $pdfPages <= 1) {
            jsonResponse([
                'ok' => true,
                'data' => [
                    'width_px' => max(0, (int)($meta['width_px'] ?? $widthPx)),
                    'height_px' => max(0, (int)($meta['height_px'] ?? $heightPx)),
                    'pages' => max($pdfPages, (int)($meta['pages'] ?? 0)),
                    'preview_pages' => $previewPages,
                    'preview_url' => $previewPages[0] ?? '',
                ],
            ]);
        }
    }
}

$previewDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content_ppt_preview';
if (!is_dir($previewDir)) {
    @mkdir($previewDir, 0775, true);
}

if (is_dir($previewDir)) {
    $existingPreview = @glob($previewDir . DIRECTORY_SEPARATOR . $baseName . '*.png') ?: [];
    if (count($existingPreview) < $pdfPages) {
        $soffice = trim((string)getenv('SOFFICE_BIN'));
        if ($soffice === '') $soffice = 'soffice';
        if (!preg_match('/[\/\\\\]/', $soffice)) {
            $candidates = [
                'C:\Program Files\LibreOffice\program\soffice.exe',
                'C:\Program Files (x86)\LibreOffice\program\soffice.exe',
                '/usr/bin/soffice',
                '/usr/local/bin/soffice',
            ];
            foreach ($candidates as $candidate) {
                if (is_file($candidate)) {
                    $soffice = $candidate;
                    break;
                }
            }
        }
        $generated = generatePdfPreviewImages($path, $previewDir, $baseName, $soffice);
    }

    $generatedPreview = @glob($previewDir . DIRECTORY_SEPARATOR . $baseName . '*.png') ?: [];
    if (count($generatedPreview) > 0) {
        natsort($generatedPreview);
        $previewPages = [];
        foreach ($generatedPreview as $full) {
            if (!is_file($full)) continue;
            $previewPages[] = '/uploads/content_ppt_preview/' . rawurlencode(basename($full));
        }
        $first = $generatedPreview[array_key_first($generatedPreview)];
        $sizeInfo = @getimagesize($first);
        $width = is_array($sizeInfo) ? (int)($sizeInfo[0] ?? $widthPx) : $widthPx;
        $height = is_array($sizeInfo) ? (int)($sizeInfo[1] ?? $heightPx) : $heightPx;

        if (!is_dir($metaDir)) {
            @mkdir($metaDir, 0775, true);
        }
        $meta = [
            'width_px' => max(0, $width),
            'height_px' => max(0, $height),
            'pages' => max($pdfPages, count($previewPages)),
            'preview_pages' => $previewPages,
            'preview_url' => $previewPages[0] ?? '',
        ];
        @file_put_contents($metaPath, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        jsonResponse(['ok' => true, 'data' => $meta]);
    }
}

jsonResponse([
    'ok' => true,
    'data' => [
        'width_px' => max(0, $widthPx),
        'height_px' => max(0, $heightPx),
        'pages' => $pdfPages,
        'preview_pages' => [],
        'preview_url' => '',
    ],
]);
