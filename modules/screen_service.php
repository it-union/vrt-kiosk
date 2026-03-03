<?php
declare(strict_types=1);

require_once __DIR__ . '/screen_repository.php';
require_once __DIR__ . '/template_repository.php';
require_once __DIR__ . '/content_repository.php';
require_once __DIR__ . '/queue_repository.php';

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

function resolveQueueTemplateForScreen(PDO $pdo, ?array $stateRow): ?array
{
    $queue = queueGetActive($pdo);
    if ($queue === null) {
        return null;
    }

    $items = queueGetItems($pdo, (int)$queue['id']);
    if (count($items) === 0) {
        return [
            'id' => 0,
            'name' => 'Пустая очередь',
            'status' => 'work',
            'version' => 1,
            'layout_json' => '',
            'blocks' => [[
                'id' => 0,
                'block_key' => 'queue_empty',
                'x_pct' => 0,
                'y_pct' => 0,
                'w_pct' => 100,
                'h_pct' => 100,
                'z_index' => 1,
                'content_mode' => 'dynamic_current',
                'content_id' => null,
                'content_type' => 'html',
                'style_json' => null,
                'sort_order' => 1,
            ]],
            '_queue_empty' => true,
        ];
    }

    $startedAt = time();
    if (is_array($stateRow) && !empty($stateRow['applied_at'])) {
        $parsed = strtotime((string)$stateRow['applied_at']);
        if ($parsed !== false) {
            $startedAt = $parsed;
        }
    }

    $totalDuration = 0;
    foreach ($items as $item) {
        $totalDuration += max(1, (int)($item['duration_sec'] ?? 1));
    }
    if ($totalDuration <= 0) {
        $totalDuration = count($items);
    }

    $elapsed = max(0, time() - $startedAt);
    $cursor = $elapsed % $totalDuration;
    $selected = $items[0];
    foreach ($items as $item) {
        $duration = max(1, (int)($item['duration_sec'] ?? 1));
        if ($cursor < $duration) {
            $selected = $item;
            break;
        }
        $cursor -= $duration;
    }

    $templateId = (int)($selected['template_id'] ?? 0);
    if ($templateId <= 0) {
        return null;
    }

    $tpl = templateGet($pdo, $templateId);
    if ($tpl === null) {
        return null;
    }
    $tpl['blocks'] = templateGetBlocks($pdo, $templateId);
    $tpl['blocks'] = hydrateTemplateBlocks($tpl);
    return $tpl;
}

function resolveQueueStateForScreen(PDO $pdo, ?array $stateRow): ?array
{
    $queue = queueGetActive($pdo);
    if ($queue === null) {
        return null;
    }

    $items = queueGetItems($pdo, (int)$queue['id']);
    if (count($items) === 0) {
        return [
            'queue_id' => (int)$queue['id'],
            'queue_name' => (string)($queue['name'] ?? ''),
            'total_items' => 0,
            'current_index' => 0,
            'current_template_id' => 0,
            'duration_sec' => 0,
            'elapsed_sec' => 0,
            'progress_pct' => 0,
            'server_now_ts' => time(),
        ];
    }

    $startedAt = time();
    if (is_array($stateRow) && !empty($stateRow['applied_at'])) {
        $parsed = strtotime((string)$stateRow['applied_at']);
        if ($parsed !== false) {
            $startedAt = $parsed;
        }
    }

    $totalDuration = 0;
    foreach ($items as $item) {
        $totalDuration += max(1, (int)($item['duration_sec'] ?? 1));
    }
    if ($totalDuration <= 0) {
        $totalDuration = count($items);
    }

    $elapsed = max(0, time() - $startedAt);
    $cursor = $elapsed % $totalDuration;
    $selected = $items[0];
    $selectedIndex = 0;
    $selectedElapsed = 0;
    foreach ($items as $index => $item) {
        $duration = max(1, (int)($item['duration_sec'] ?? 1));
        if ($cursor < $duration) {
            $selected = $item;
            $selectedIndex = (int)$index;
            $selectedElapsed = (int)$cursor;
            break;
        }
        $cursor -= $duration;
    }

    $durationSec = max(1, (int)($selected['duration_sec'] ?? 1));
    $progressPct = (int)max(0, min(100, round(($selectedElapsed / $durationSec) * 100)));

    return [
        'queue_id' => (int)$queue['id'],
        'queue_name' => (string)($queue['name'] ?? ''),
        'total_items' => count($items),
        'current_index' => $selectedIndex,
        'current_template_id' => (int)($selected['template_id'] ?? 0),
        'duration_sec' => $durationSec,
        'elapsed_sec' => $selectedElapsed,
        'progress_pct' => $progressPct,
        'server_now_ts' => time(),
    ];
}

function getScreenPayload(PDO $pdo, int $screenId): array
{
    $source = 'fallback';
    $sourceRow = [];
    $currentContent = null;
    $queueState = null;
    $stateRow = screenGetState($pdo, $screenId);

    if (is_array($stateRow) && (string)($stateRow['source'] ?? '') === 'fallback') {
        return [
            'screen_id' => $screenId,
            'source' => 'fallback',
            'template' => null,
            'screen_style' => defaultScreenStyle(),
            'blocks' => [[
                'id' => 0,
                'key' => 'stopped',
                'x_pct' => 0,
                'y_pct' => 0,
                'w_pct' => 100,
                'h_pct' => 100,
                'z_index' => 1,
                'content_mode' => 'dynamic_current',
                'content_type' => 'html',
                'content' => [
                    'id' => null,
                    'type' => 'html',
                    'title' => 'Очередь показа остановлена',
                    'body' => '<div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;font-family:Tahoma,sans-serif;font-size:36px;color:#475569;">Очередь показа остановлена</div>',
                    'media_url' => null,
                    'data_json' => null,
                ],
                'style' => null,
            ]],
        ];
    }

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
        } elseif (is_array($stateRow) && (string)($stateRow['source'] ?? '') === 'schedule') {
            $source = 'schedule';
            $sourceRow = $stateRow;
        }
    }

    $template = resolveTemplateForScreen($pdo, $sourceRow);
    if ($template === null && $source === 'schedule') {
        $template = resolveQueueTemplateForScreen($pdo, $stateRow);
        $queueState = resolveQueueStateForScreen($pdo, $stateRow);
    }

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
                    'body' => $currentContent['body'] ?? 'Создайте и активируйте шаблон или заполните активную очередь показа.',
                    'media_url' => $currentContent['media_url'] ?? null,
                    'data_json' => null,
                ],
                'style' => null,
            ]],
        ];
    }

    $blocks = $template['_queue_empty'] ?? false
        ? [[
            'id' => 0,
            'key' => 'queue_empty',
            'x_pct' => 0,
            'y_pct' => 0,
            'w_pct' => 100,
            'h_pct' => 100,
            'z_index' => 1,
            'content_mode' => 'dynamic_current',
            'content_type' => 'html',
            'content' => [
                'id' => null,
                'type' => 'html',
                'title' => 'Активная очередь пуста',
                'body' => '<div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;font-family:Tahoma,sans-serif;font-size:36px;color:#475569;">Активная очередь пуста</div>',
                'media_url' => null,
                'data_json' => null,
            ],
            'style' => null,
        ]]
        : buildRenderedBlocks($pdo, $template['blocks'], $currentContent);

    return [
        'screen_id' => $screenId,
        'source' => $source,
        'screen_style' => extractScreenStyleFromLayout((string)($template['layout_json'] ?? '')),
        'template' => [
            'id' => (int)($template['id'] ?? 0),
            'name' => (string)($template['name'] ?? ''),
            'status' => (string)($template['status'] ?? 'work'),
            'version' => (int)($template['version'] ?? 1),
        ],
        'queue_state' => $queueState,
        'blocks' => $blocks,
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

function showTemplateNow(PDO $pdo, int $screenId, int $templateId): int
{
    if (!screenTemplateExists($pdo, $templateId)) {
        throw new RuntimeException('template_not_found');
    }

    screenDeactivateManualCommands($pdo, $screenId);
    $commandId = screenCreatePersistentManualTemplateCommand($pdo, $screenId, $templateId);
    screenUpsertPersistentManualTemplateState($pdo, $screenId, $commandId, $templateId);
    return $commandId;
}

function startQueue(PDO $pdo, int $screenId): void
{
    screenDeactivateManualCommands($pdo, $screenId);
    screenStartQueueState($pdo, $screenId);
}

function stopQueue(PDO $pdo, int $screenId): void
{
    screenDeactivateManualCommands($pdo, $screenId);
    screenStopQueueState($pdo, $screenId);
}

function clearNow(PDO $pdo, int $screenId): void
{
    screenDeactivateManualCommands($pdo, $screenId);
    screenClearManualState($pdo, $screenId);
}
