<?php
declare(strict_types=1);

function scheduleThemeDirectory(): string
{
    return __DIR__ . '/../config/schedule_themes';
}

function scheduleThemeNormalize(array $raw): ?array
{
    $id = trim((string)($raw['id'] ?? ''));
    if ($id === '' || preg_match('/^[a-z0-9_-]+$/', $id) !== 1) {
        return null;
    }

    $name = trim((string)($raw['name'] ?? ''));
    if ($name === '') {
        $name = $id;
    }

    $colors = is_array($raw['colors'] ?? null) ? $raw['colors'] : [];
    $defaults = [
        'text' => '#0f172a',
        'header_bg' => '#e2e8f0',
        'header_text' => '#0f172a',
        'grid_line' => '#cbd5e1',
        'busy_bg' => '#fee2e2',
        'busy_text' => '#991b1b',
        'free_bg' => '#dcfce7',
        'free_text' => '#166534',
    ];
    $normalizedColors = [];
    foreach ($defaults as $key => $fallback) {
        $value = trim((string)($colors[$key] ?? ''));
        $normalizedColors[$key] = preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? $value : $fallback;
    }

    return [
        'id' => $id,
        'name' => $name,
        'colors' => $normalizedColors,
    ];
}

function scheduleThemeLoadAll(): array
{
    $dir = scheduleThemeDirectory();
    $themes = [];

    if (is_dir($dir)) {
        $files = glob($dir . '/*.json') ?: [];
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($files as $file) {
            $raw = @file_get_contents($file);
            if (!is_string($raw) || trim($raw) === '') {
                continue;
            }
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                continue;
            }
            $theme = scheduleThemeNormalize($decoded);
            if ($theme !== null) {
                $themes[] = $theme;
            }
        }
    }

    if (count($themes) === 0) {
        $themes[] = scheduleThemeNormalize([
            'id' => 'light_blue',
            'name' => 'Светлая синяя',
            'colors' => [
                'text' => '#0f172a',
                'header_bg' => '#dbeafe',
                'header_text' => '#1e3a8a',
                'grid_line' => '#bfdbfe',
                'busy_bg' => '#fee2e2',
                'busy_text' => '#991b1b',
                'free_bg' => '#dcfce7',
                'free_text' => '#166534',
            ],
        ]);
    }

    return array_values(array_filter($themes));
}

function scheduleThemeFindById(array $themes, string $themeId): ?array
{
    foreach ($themes as $theme) {
        if (is_array($theme) && (string)($theme['id'] ?? '') === $themeId) {
            return $theme;
        }
    }
    return null;
}
