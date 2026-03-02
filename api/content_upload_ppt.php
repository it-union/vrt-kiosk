<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';

requireTemplateApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Method not allowed'], 405);
}

if (!isset($_FILES['ppt']) || !is_array($_FILES['ppt'])) {
    jsonResponse(['ok' => false, 'error' => 'File is not provided'], 400);
}

$file = $_FILES['ppt'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    jsonResponse(['ok' => false, 'error' => 'Upload error'], 400);
}

$tmpPath = (string)($file['tmp_name'] ?? '');
if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
    jsonResponse(['ok' => false, 'error' => 'Invalid upload source'], 400);
}

$size = (int)($file['size'] ?? 0);
if ($size <= 0 || $size > 250 * 1024 * 1024) {
    jsonResponse(['ok' => false, 'error' => 'File size must be between 1 byte and 250 MB'], 400);
}

$origName = (string)($file['name'] ?? '');
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$allowedExt = ['pdf'];
if (!in_array($ext, $allowedExt, true)) {
    jsonResponse(['ok' => false, 'error' => 'Only .pdf is allowed'], 400);
}

$originalBase = trim((string)pathinfo($origName, PATHINFO_FILENAME));
$safeBase = preg_replace('/[^\p{L}\p{N}\-_\s]+/u', '', $originalBase);
$safeBase = preg_replace('/\s+/u', '_', (string)$safeBase);
$safeBase = trim((string)$safeBase, '._- ');
if ($safeBase === '') {
    $safeBase = 'presentation';
}
if (function_exists('mb_substr')) {
    $safeBase = mb_substr($safeBase, 0, 80, 'UTF-8');
} else {
    $safeBase = substr($safeBase, 0, 80);
}

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    jsonResponse(['ok' => false, 'error' => 'Project root is not resolved'], 500);
}

$targetDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content_ppt';
if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
    jsonResponse(['ok' => false, 'error' => 'Cannot create upload directory'], 500);
}
$previewDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content_ppt_preview';
if (!is_dir($previewDir) && !mkdir($previewDir, 0775, true) && !is_dir($previewDir)) {
    jsonResponse(['ok' => false, 'error' => 'Cannot create preview directory'], 500);
}
$metaDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content_ppt_meta';
if (!is_dir($metaDir) && !mkdir($metaDir, 0775, true) && !is_dir($metaDir)) {
    jsonResponse(['ok' => false, 'error' => 'Cannot create metadata directory'], 500);
}

$sourceDir = $targetDir . DIRECTORY_SEPARATOR . '_src';
if (!is_dir($sourceDir) && !mkdir($sourceDir, 0775, true) && !is_dir($sourceDir)) {
    jsonResponse(['ok' => false, 'error' => 'Cannot create temp directory'], 500);
}

try {
    $rand = bin2hex(random_bytes(4));
} catch (Throwable $e) {
    $rand = (string)mt_rand(10000000, 99999999);
}

$baseName = $safeBase . '_' . date('Ymd_His') . '_' . $rand;
$sourcePath = $sourceDir . DIRECTORY_SEPARATOR . $baseName . '.' . $ext;
if (!move_uploaded_file($tmpPath, $sourcePath)) {
    jsonResponse(['ok' => false, 'error' => 'Cannot store source file'], 500);
}

$soffice = trim((string)getenv('SOFFICE_BIN'));
if ($soffice === '') {
    $soffice = 'soffice';
}
if (!preg_match('/[\/\\]/', $soffice)) {
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

$pdfName = $baseName . '.pdf';
$pdfPath = $targetDir . DIRECTORY_SEPARATOR . $pdfName;
if (!@rename($sourcePath, $pdfPath)) {
    if (!@copy($sourcePath, $pdfPath)) {
        @unlink($sourcePath);
        jsonResponse(['ok' => false, 'error' => 'Cannot store PDF file'], 500);
    }
    @unlink($sourcePath);
}

/**
 * Detect PDF page count using page tree counters with fallback by /Type /Page markers.
 */
function detectPdfPageCount(string $path): int
{
    $raw = @file_get_contents($path);
    if (!is_string($raw) || $raw === '') {
        return 0;
    }
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

function generatePdfPreviewImages(string $pdfPath, string $previewDir, string $baseName, int $pageCount, string $soffice): array
{
    $pattern = $previewDir . DIRECTORY_SEPARATOR . $baseName . '*.png';
    foreach ((array)(@glob($pattern) ?: []) as $oldFile) {
        if (is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

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

    $previewCmd = escapeshellarg($soffice)
        . ' --headless --nologo --nolockcheck --nodefault'
        . ' --convert-to png'
        . ' --outdir ' . escapeshellarg($previewDir)
        . ' ' . escapeshellarg($pdfPath)
        . ' 2>&1';
    $previewOutput = [];
    $previewCode = 1;
    @exec($previewCmd, $previewOutput, $previewCode);
    $files = (array)(@glob($pattern) ?: []);
    if (count($files) > 0) {
        natsort($files);
        return array_values($files);
    }

    return [];
}

$pagesCount = max(1, detectPdfPageCount($pdfPath));
$generatedFiles = generatePdfPreviewImages($pdfPath, $previewDir, $baseName, $pagesCount, $soffice);
$previewFiles = [];
foreach ($generatedFiles as $full) {
    if (is_file($full)) {
        $previewFiles[] = basename($full);
    }
}

$widthPx = 0;
$heightPx = 0;
if (count($previewFiles) > 0) {
    $firstPath = $previewDir . DIRECTORY_SEPARATOR . $previewFiles[0];
    $sizeInfo = @getimagesize($firstPath);
    if (is_array($sizeInfo)) {
        $widthPx = (int)($sizeInfo[0] ?? 0);
        $heightPx = (int)($sizeInfo[1] ?? 0);
    }
}
$previewPages = array_map(static fn(string $name): string => '/uploads/content_ppt_preview/' . rawurlencode($name), $previewFiles);

$meta = [
    'width_px' => $widthPx,
    'height_px' => $heightPx,
    'pages' => $pagesCount,
    'preview_pages' => $previewPages,
];
@file_put_contents(
    $metaDir . DIRECTORY_SEPARATOR . $baseName . '.json',
    json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

$url = '/uploads/content_ppt/' . rawurlencode($pdfName);
jsonResponse([
    'ok' => true,
    'data' => [
        'url' => $url,
        'size' => (int)(@filesize($pdfPath) ?: 0),
        'name' => $pdfName,
        'kind' => 'ppt',
        'pages' => $pagesCount,
        'width_px' => $widthPx,
        'height_px' => $heightPx,
        'preview_pages' => $previewPages,
        'preview_url' => $previewPages[0] ?? '',
    ],
]);
