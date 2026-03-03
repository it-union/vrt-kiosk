<?php
declare(strict_types=1);

require_once __DIR__ . '/template_repository.php';

function normalizeScreenStyle(?array $raw): array
{
    $raw = is_array($raw) ? $raw : [];

    $mode = (string)($raw['mode'] ?? 'color');
    if (!in_array($mode, ['none', 'color', 'image'], true)) {
        $mode = 'color';
    }

    $size = (string)($raw['size'] ?? 'cover');
    if (!in_array($size, ['cover', 'contain', 'auto'], true)) {
        $size = 'cover';
    }

    $position = (string)($raw['position'] ?? 'center center');
    $allowedPositions = ['left top', 'center top', 'right top', 'left center', 'center center', 'right center', 'left bottom', 'center bottom', 'right bottom'];
    if (!in_array($position, $allowedPositions, true)) {
        $position = 'center center';
    }

    $repeat = (string)($raw['repeat'] ?? 'no-repeat');
    if (!in_array($repeat, ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'], true)) {
        $repeat = 'no-repeat';
    }

    $color = trim((string)($raw['color'] ?? '#ffffff'));
    if ($color === '') {
        $color = '#ffffff';
    }

    return [
        'mode' => $mode,
        'color' => $color,
        'image' => trim((string)($raw['image'] ?? '')),
        'size' => $size,
        'position' => $position,
        'repeat' => $repeat,
    ];
}

function normalizeBlockBackgroundStyle(array $raw): array
{
    $mode = (string)($raw['background_mode'] ?? 'color');
    if (!in_array($mode, ['none', 'color', 'image'], true)) {
        $mode = 'color';
    }

    $size = (string)($raw['background_size'] ?? 'cover');
    if (!in_array($size, ['cover', 'contain', 'auto'], true)) {
        $size = 'cover';
    }

    $position = (string)($raw['background_position'] ?? 'center center');
    $allowedPositions = ['left top', 'center top', 'right top', 'left center', 'center center', 'right center', 'left bottom', 'center bottom', 'right bottom'];
    if (!in_array($position, $allowedPositions, true)) {
        $position = 'center center';
    }

    $repeat = (string)($raw['background_repeat'] ?? 'no-repeat');
    if (!in_array($repeat, ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'], true)) {
        $repeat = 'no-repeat';
    }

    $color = trim((string)($raw['background_color'] ?? '#ffffff'));
    if ($color === '') {
        $color = '#ffffff';
    }
    return [
        'background_mode' => $mode,
        'background_color' => $color,
        'background_image' => trim((string)($raw['background_image'] ?? '')),
        'background_size' => $size,
        'background_position' => $position,
        'background_repeat' => $repeat,
        'animation' => in_array((string)($raw['animation'] ?? 'none'), ['none', 'fade_in', 'slide_up', 'slide_left', 'zoom_in'], true)
            ? (string)($raw['animation'] ?? 'none')
            : 'none',
        'animation_ms' => max(100, min(5000, (int)($raw['animation_ms'] ?? 700))),
        'delay_on_ms' => max(0, (int)($raw['delay_on_ms'] ?? 0)),
        'delay_off_ms' => max(0, (int)($raw['delay_off_ms'] ?? 0)),
    ];
}

function normalizeTemplateBlock(array $raw, int $index): array
{
    $blockKey = trim((string)($raw['block_key'] ?? ''));
    if ($blockKey === '') {
        $blockKey = 'block_' . ($index + 1);
    }

    $x = max(0.0, min(100.0, (float)($raw['x_pct'] ?? 0)));
    $y = max(0.0, min(100.0, (float)($raw['y_pct'] ?? 0)));
    $w = max(1.0, min(100.0, (float)($raw['w_pct'] ?? 100)));
    $h = max(1.0, min(100.0, (float)($raw['h_pct'] ?? 100)));

    if ($x + $w > 100.0) {
        $w = max(1.0, 100.0 - $x);
    }
    if ($y + $h > 100.0) {
        $h = max(1.0, 100.0 - $y);
    }

    $contentMode = (string)($raw['content_mode'] ?? 'dynamic_current');
    if (!in_array($contentMode, ['fixed', 'dynamic_current', 'empty'], true)) {
        $contentMode = 'dynamic_current';
    }

    $contentType = (string)($raw['content_type'] ?? 'image');
    if (!in_array($contentType, ['image', 'html', 'video', 'ppt'], true)) {
        $contentType = 'image';
    }

    $contentId = isset($raw['content_id']) ? (int)$raw['content_id'] : null;
    if ($contentId !== null && $contentId <= 0) {
        $contentId = null;
    }

    $styleRaw = $raw['style_json'] ?? null;
    if (is_string($styleRaw) && $styleRaw !== '') {
        $decoded = json_decode($styleRaw, true);
        if (is_array($decoded)) {
            $styleRaw = $decoded;
        }
    }
    if (!is_array($styleRaw)) {
        $styleRaw = [];
    }
    $stylePayload = normalizeBlockBackgroundStyle(array_merge($styleRaw, $raw));
    $style = json_encode($stylePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return [
        'block_key' => $blockKey,
        'x_pct' => round($x, 2),
        'y_pct' => round($y, 2),
        'w_pct' => round($w, 2),
        'h_pct' => round($h, 2),
        'z_index' => max(1, (int)($raw['z_index'] ?? 1)),
        'content_mode' => $contentMode,
        'content_id' => $contentId,
        'content_type' => $contentType,
        'style_json' => $style,
    ];
}

function normalizeTemplateBlocks(array $blocks): array
{
    $normalized = [];
    foreach ($blocks as $i => $raw) {
        if (is_array($raw)) {
            $normalized[] = normalizeTemplateBlock($raw, $i);
        }
    }

    if (count($normalized) === 0) {
        $normalized[] = normalizeTemplateBlock([
            'block_key' => 'main',
            'x_pct' => 0,
            'y_pct' => 0,
            'w_pct' => 100,
            'h_pct' => 100,
            'content_mode' => 'dynamic_current',
            'content_type' => 'image',
        ], 0);
    }

    return $normalized;
}

function buildLayoutJsonFromBlocks(array $blocks): string
{
    $zones = [];
    foreach ($blocks as $block) {
        $zones[] = [
            'id' => $block['block_key'],
            'x' => $block['x_pct'],
            'y' => $block['y_pct'],
            'w' => $block['w_pct'],
            'h' => $block['h_pct'],
            'z' => $block['z_index'],
            'content_mode' => $block['content_mode'],
            'content_type' => $block['content_type'],
            'content_id' => $block['content_id'],
        ];
    }

    return json_encode([
        'zones' => $zones,
        'screen_style' => normalizeScreenStyle(null),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function buildLayoutJson(array $blocks, ?array $screenStyle): string
{
    $zones = [];
    foreach ($blocks as $block) {
        $zones[] = [
            'id' => $block['block_key'],
            'x' => $block['x_pct'],
            'y' => $block['y_pct'],
            'w' => $block['w_pct'],
            'h' => $block['h_pct'],
            'z' => $block['z_index'],
            'content_mode' => $block['content_mode'],
            'content_type' => $block['content_type'],
            'content_id' => $block['content_id'],
        ];
    }

    return json_encode([
        'zones' => $zones,
        'screen_style' => normalizeScreenStyle($screenStyle),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function saveTemplate(PDO $pdo, int $id, string $name, string $description, string $status, array $blocks, ?array $screenStyle = null): int
{
    $normalizedBlocks = normalizeTemplateBlocks($blocks);
    $layoutJson = buildLayoutJson($normalizedBlocks, $screenStyle);

    $pdo->beginTransaction();
    try {
        if ($id > 0) {
            $ok = templateUpdate($pdo, $id, $name, $description, $layoutJson, $status);
            if (!$ok) {
                throw new RuntimeException('template_not_found');
            }
            $templateId = $id;
        } else {
            $templateId = templateCreate($pdo, $name, $description, $layoutJson, $status);
        }

        templateDeleteBlocks($pdo, $templateId);
        foreach ($normalizedBlocks as $index => $block) {
            templateInsertBlock($pdo, $templateId, $block, $index + 1);
        }

        $pdo->commit();
        return $templateId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function getTemplateWithBlocks(PDO $pdo, int $id): ?array
{
    $tpl = templateGet($pdo, $id);
    if ($tpl === null) {
        return null;
    }

    $tpl['blocks'] = templateGetBlocks($pdo, $id);
    return $tpl;
}

function listTemplatesWithActiveMark(PDO $pdo): array
{
    return templateList($pdo);
}

function activateTemplate(PDO $pdo, int $templateId): void
{
    $pdo->beginTransaction();
    try {
        $ok = templateActivate($pdo, $templateId);
        if (!$ok) {
            throw new RuntimeException('template_not_found');
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function deleteTemplate(PDO $pdo, int $templateId): void
{
    $pdo->beginTransaction();
    try {
        templateDeleteScheduleRules($pdo, $templateId);

        $ok = templateDelete($pdo, $templateId);
        if (!$ok) {
            throw new RuntimeException('template_not_found');
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function duplicateTemplate(PDO $pdo, int $templateId): int
{
    $source = getTemplateWithBlocks($pdo, $templateId);
    if ($source === null) {
        throw new RuntimeException('template_not_found');
    }

    $sourceName = trim((string)($source['name'] ?? 'Шаблон'));
    $newName = $sourceName === '' ? 'Шаблон (копия)' : ($sourceName . ' (копия)');
    $description = (string)($source['description'] ?? '');
    $blocks = is_array($source['blocks'] ?? null) ? $source['blocks'] : [];

    return saveTemplate($pdo, 0, $newName, $description, 'draft', $blocks);
}
