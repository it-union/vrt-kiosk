<?php
declare(strict_types=1);

require_once __DIR__ . '/screen_repository.php';
require_once __DIR__ . '/template_repository.php';
require_once __DIR__ . '/content_repository.php';

function defaultScreenStyle(): array
{
    return [
        'mode' => 'color',
        'color' => '#ffffff',
        'image' => '',
        'size' => 'cover',
        'position' => 'center center',
        'repeat' => 'no-repeat',
    ];
}

function extractScreenStyleFromLayout(?string $layoutJson): array
{
    $style = defaultScreenStyle();
    if (!is_string($layoutJson) || trim($layoutJson) === '') {
        return $style;
    }

    $layout = json_decode($layoutJson, true);
    if (!is_array($layout)) {
        return $style;
    }
    $raw = $layout['screen_style'] ?? null;
    if (!is_array($raw)) {
        return $style;
    }

    $mode = (string)($raw['mode'] ?? $style['mode']);
    if (in_array($mode, ['none', 'color', 'image'], true)) {
        $style['mode'] = $mode;
    }

    $size = (string)($raw['size'] ?? $style['size']);
    if (in_array($size, ['cover', 'contain', 'auto'], true)) {
        $style['size'] = $size;
    }

    $repeat = (string)($raw['repeat'] ?? $style['repeat']);
    if (in_array($repeat, ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'], true)) {
        $style['repeat'] = $repeat;
    }

    $positions = ['left top', 'center top', 'right top', 'left center', 'center center', 'right center', 'left bottom', 'center bottom', 'right bottom'];
    $position = (string)($raw['position'] ?? $style['position']);
    if (in_array($position, $positions, true)) {
        $style['position'] = $position;
    }

    $color = trim((string)($raw['color'] ?? $style['color']));
    $style['color'] = $color !== '' ? $color : '#ffffff';
    $style['image'] = trim((string)($raw['image'] ?? ''));

    return $style;
}

function hydrateTemplateBlocks(array $template): array
{
    $blocks = $template['blocks'] ?? [];
    if (is_array($blocks) && count($blocks) > 0) {
        return $blocks;
    }

    $layout = isset($template['layout_json']) ? json_decode((string)$template['layout_json'], true) : null;
    if (is_array($layout) && isset($layout['zones']) && is_array($layout['zones'])) {
        $fromZones = [];
        foreach ($layout['zones'] as $i => $zone) {
            if (!is_array($zone)) {
                continue;
            }
            $fromZones[] = [
                'id' => 0,
                'block_key' => (string)($zone['id'] ?? ('zone_' . ($i + 1))),
                'x_pct' => (float)($zone['x'] ?? 0),
                'y_pct' => (float)($zone['y'] ?? 0),
                'w_pct' => (float)($zone['w'] ?? 100),
                'h_pct' => (float)($zone['h'] ?? 100),
                'z_index' => (int)($zone['z'] ?? 1),
                'content_mode' => (string)($zone['content_mode'] ?? 'dynamic_current'),
                'content_id' => isset($zone['content_id']) ? (int)$zone['content_id'] : null,
                'content_type' => (string)($zone['content_type'] ?? 'image'),
                'style_json' => null,
                'sort_order' => $i + 1,
            ];
        }
        if (count($fromZones) > 0) {
            return $fromZones;
        }
    }

    return [[
        'id' => 0,
        'block_key' => 'main',
        'x_pct' => 0,
        'y_pct' => 0,
        'w_pct' => 100,
        'h_pct' => 100,
        'z_index' => 1,
        'content_mode' => 'dynamic_current',
        'content_id' => null,
        'content_type' => 'image',
        'style_json' => null,
        'sort_order' => 1,
    ]];
}

function resolveTemplateForScreen(PDO $pdo, array $sourceRow): ?array
{
    $templateId = isset($sourceRow['template_id']) ? (int)$sourceRow['template_id'] : 0;
    if ($templateId > 0) {
        $tpl = templateGet($pdo, $templateId);
        if ($tpl !== null) {
            $tpl['blocks'] = templateGetBlocks($pdo, $templateId);
            $tpl['blocks'] = hydrateTemplateBlocks($tpl);
            return $tpl;
        }
    }

    $work = templateGetByStatus($pdo, 'work');
    if ($work !== null) {
        $work['blocks'] = templateGetBlocks($pdo, (int)$work['id']);
        $work['blocks'] = hydrateTemplateBlocks($work);
        return $work;
    }

    // Backward compatibility for old records
    $legacyActive = templateGetByStatus($pdo, 'active');
    if ($legacyActive !== null) {
        $legacyActive['blocks'] = templateGetBlocks($pdo, (int)$legacyActive['id']);
        $legacyActive['blocks'] = hydrateTemplateBlocks($legacyActive);
        return $legacyActive;
    }

    return null;
}

function buildRenderedBlocks(PDO $pdo, array $templateBlocks, ?array $currentContent): array
{
    $ids = [];
    foreach ($templateBlocks as $block) {
        if (($block['content_mode'] ?? '') === 'fixed' && !empty($block['content_id'])) {
            $ids[] = (int)$block['content_id'];
        }
    }
    $contentMap = contentFindManyByIds($pdo, $ids);

    $rendered = [];
    foreach ($templateBlocks as $block) {
        $mode = (string)$block['content_mode'];
        $content = null;

        if ($mode === 'fixed' && !empty($block['content_id'])) {
            $content = $contentMap[(int)$block['content_id']] ?? null;
        } elseif ($mode === 'dynamic_current') {
            $content = $currentContent;
        }

        $style = null;
        if (!empty($block['style_json'])) {
            $decoded = json_decode((string)$block['style_json'], true);
            if (is_array($decoded)) {
                $style = $decoded;
            }
        }

        $rendered[] = [
            'id' => (int)$block['id'],
            'key' => (string)$block['block_key'],
            'x_pct' => (float)$block['x_pct'],
            'y_pct' => (float)$block['y_pct'],
            'w_pct' => (float)$block['w_pct'],
            'h_pct' => (float)$block['h_pct'],
            'z_index' => (int)$block['z_index'],
            'content_mode' => $mode,
            'content_type' => (string)$block['content_type'],
            'content' => [
                'id' => $content ? (int)$content['id'] : null,
                'type' => $content['type'] ?? null,
                'title' => $content['title'] ?? '',
                'body' => $content['body'] ?? '',
                'media_url' => $content['media_url'] ?? null,
                'data_json' => $content['data_json'] ?? null,
            ],
            'style' => $style,
        ];
    }

    return $rendered;
}

function getScreenPayload(PDO $pdo, int $screenId): array
{
    $source = 'fallback';
    $sourceRow = [];
    $currentContent = null;

    $manual = screenFindActiveManual($pdo, $screenId);
    if ($manual !== null) {
        $source = 'manual';
        $sourceRow = $manual;
        if (!empty($manual['content_id'])) {
            $currentContent = [
                'id' => (int)$manual['content_id'],
                'title' => (string)($manual['title'] ?? ''),
                'body' => (string)($manual['body'] ?? ''),
                'media_url' => $manual['media_url'] ?? null,
                'type' => $manual['type'] ?? null,
                'data_json' => $manual['data_json'] ?? null,
            ];
        }
    } else {
        $rule = screenFindActiveSchedule($pdo, $screenId);
        if ($rule !== null) {
            $source = 'schedule';
            $sourceRow = $rule;
            if (!empty($rule['content_id'])) {
                $currentContent = [
                    'id' => (int)$rule['content_id'],
                    'title' => (string)($rule['title'] ?? ''),
                    'body' => (string)($rule['body'] ?? ''),
                    'media_url' => $rule['media_url'] ?? null,
                    'type' => $rule['type'] ?? null,
                    'data_json' => $rule['data_json'] ?? null,
                ];
            }
        }
    }

    $template = resolveTemplateForScreen($pdo, $sourceRow);
    if ($template === null) {
        return [
            'screen_id' => $screenId,
            'source' => $source,
            'template' => null,
            'screen_style' => defaultScreenStyle(),
            'blocks' => [[
                'id' => 0,
                'key' => 'fallback',
                'x_pct' => 0,
                'y_pct' => 0,
                'w_pct' => 100,
                'h_pct' => 100,
                'z_index' => 1,
                'content_mode' => 'dynamic_current',
                'content_type' => 'image',
                'content' => [
                    'id' => $currentContent['id'] ?? null,
                    'type' => $currentContent['type'] ?? null,
                    'title' => $currentContent['title'] ?? 'Нет активного шаблона',
                    'body' => $currentContent['body'] ?? 'Создайте и активируйте шаблон в /template/.',
                    'media_url' => $currentContent['media_url'] ?? null,
                    'data_json' => null,
                ],
                'style' => null,
            ]],
        ];
    }

    return [
        'screen_id' => $screenId,
        'source' => $source,
        'screen_style' => extractScreenStyleFromLayout((string)($template['layout_json'] ?? '')),
        'template' => [
            'id' => (int)$template['id'],
            'name' => (string)$template['name'],
            'status' => (string)$template['status'],
            'version' => (int)$template['version'],
        ],
        'blocks' => buildRenderedBlocks($pdo, $template['blocks'], $currentContent),
    ];
}

function showNow(PDO $pdo, int $screenId, int $contentId, int $durationMinutes): int
{
    if (!screenContentExists($pdo, $contentId)) {
        throw new RuntimeException('content_not_found');
    }

    screenDeactivateManualCommands($pdo, $screenId);
    $commandId = screenCreateManualCommand($pdo, $screenId, $contentId, $durationMinutes);
    screenUpsertManualState($pdo, $screenId, $commandId, $contentId, $durationMinutes);
    return $commandId;
}

function clearNow(PDO $pdo, int $screenId): void
{
    screenDeactivateManualCommands($pdo, $screenId);
    screenClearManualState($pdo, $screenId);
}
