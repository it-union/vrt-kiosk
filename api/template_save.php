<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/template_service.php';

requireTemplateApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$id = isset($_POST['template_id']) ? (int)$_POST['template_id'] : 0;
$name = trim((string)($_POST['name'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$status = trim((string)($_POST['status'] ?? 'draft'));
$blocksRaw = (string)($_POST['blocks_json'] ?? '');
$screenStyleRaw = trim((string)($_POST['screen_style_json'] ?? ''));

if ($name === '' || $blocksRaw === '') {
    jsonResponse(['ok' => false, 'error' => 'Нужны name и blocks_json'], 400);
}

if ($status === 'active') {
    $status = 'work';
}
if ($status === 'archived') {
    $status = 'archive';
}

if (!in_array($status, ['draft', 'work', 'archive'], true)) {
    $status = 'draft';
}

$blocks = json_decode($blocksRaw, true);
if (!is_array($blocks)) {
    jsonResponse(['ok' => false, 'error' => 'blocks_json должен быть корректным JSON-массивом'], 400);
}

$screenStyle = null;
if ($screenStyleRaw !== '') {
    $screenStyle = json_decode($screenStyleRaw, true);
    if (!is_array($screenStyle)) {
        jsonResponse(['ok' => false, 'error' => 'screen_style_json должен быть корректным JSON-объектом'], 400);
    }
}

function ensureTemplateBlockContentTypeEnum(PDO $pdo): void
{
    $required = ['text', 'image', 'html', 'video', 'ppt'];
    $stmt = $pdo->query("SHOW COLUMNS FROM template_blocks LIKE 'content_type'");
    if (!$stmt) {
        return;
    }
    $row = $stmt->fetch();
    if (!is_array($row)) {
        return;
    }
    $typeRaw = (string)($row['Type'] ?? '');
    if (!preg_match('/^enum\\((.*)\\)$/i', $typeRaw, $m)) {
        return;
    }
    preg_match_all("/'([^']+)'/", $m[1], $matches);
    $values = $matches[1] ?? [];

    $changed = false;
    foreach ($required as $candidate) {
        if (!in_array($candidate, $values, true)) {
            $values[] = $candidate;
            $changed = true;
        }
    }
    if (!$changed) {
        return;
    }

    $enumSql = implode(',', array_map(static fn(string $v): string => "'" . str_replace("'", "''", $v) . "'", $values));
    $pdo->exec("ALTER TABLE template_blocks MODIFY COLUMN content_type ENUM($enumSql) NOT NULL DEFAULT 'image'");
}

try {
    $pdo = dbMysql();
    ensureTemplateStatusEnum($pdo);
    ensureTemplateBlockContentTypeEnum($pdo);
    $templateId = saveTemplate($pdo, $id, $name, $description, $status, $blocks, $screenStyle);
    jsonResponse(['ok' => true, 'data' => ['template_id' => $templateId]]);
} catch (RuntimeException $e) {
    if ($e->getMessage() === 'template_not_found') {
        jsonResponse(['ok' => false, 'error' => 'Шаблон не найден'], 404);
    }
    jsonResponse(['ok' => false, 'error' => 'Не удалось сохранить шаблон'], 500);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось сохранить шаблон'], 500);
}
