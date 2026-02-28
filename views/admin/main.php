<?php
declare(strict_types=1);
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <style>
        body { margin: 0; padding: 20px; font-family: Tahoma, sans-serif; }
        .footer { margin-top: 16px; color: #5a6472; font-size: 12px; border-top: 1px solid #e3e7ed; padding-top: 10px; }
    </style>
</head>
<body>
<h1>Панель администратора</h1>
<p><code>/admin/</code></p>

<form id="showNowForm">
    <label>Screen ID: <input type="number" name="screen_id" value="1" min="1" required></label><br>
    <label>Content ID: <input type="number" name="content_id" value="1" min="1" required></label><br>
    <label>Duration (minutes): <input type="number" name="duration_minutes" value="10" min="1" max="1440" required></label><br>
    <button type="submit">Показать сейчас</button>
    <button type="button" id="clearNowBtn">Снять ручной режим</button>
    <button type="button" id="refreshBtn">Обновить состояние</button>
</form>

<pre id="out" style="background:#f3f3f3;padding:12px;border:1px solid #ddd;max-width:860px;overflow:auto;"></pre>

<div class="footer">Версия проекта: <strong><?= h($projectVersion ?? '0.0.0-dev') ?></strong></div>

<script>
const out = document.getElementById('out');
const form = document.getElementById('showNowForm');

function print(obj) {
    out.textContent = JSON.stringify(obj, null, 2);
}

async function refreshState() {
    const screenId = Number(form.screen_id.value || 1);
    const res = await fetch('/api/screen.php?screen_id=' + encodeURIComponent(screenId), { cache: 'no-store' });
    print(await res.json());
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const body = new URLSearchParams(new FormData(form));
    const res = await fetch('/api/admin_show_now.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body
    });

    print(await res.json());
    await refreshState();
});

document.getElementById('clearNowBtn').addEventListener('click', async () => {
    const body = new URLSearchParams();
    body.set('screen_id', form.screen_id.value || '1');

    const res = await fetch('/api/admin_clear_now.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body
    });

    print(await res.json());
    await refreshState();
});

document.getElementById('refreshBtn').addEventListener('click', refreshState);
refreshState();
</script>
</body>
</html>
