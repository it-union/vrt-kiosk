<?php
declare(strict_types=1);
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'Вход в систему') ?></title>
    <style>
        :root {
            --bg: #eef2f6;
            --panel: #ffffff;
            --line: #d7dde6;
            --text: #1f2937;
            --muted: #667085;
            --accent: #0f6cbd;
            --danger-bg: #fdecec;
            --danger-text: #9d2020;
            --ok-bg: #e8f4ea;
            --ok-text: #216e39;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: linear-gradient(180deg, #f5f7fa 0%, #e7edf4 100%);
            color: var(--text);
            font-family: Tahoma, sans-serif;
        }
        .card {
            width: min(100%, 460px);
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: 0 18px 45px rgba(31, 41, 55, 0.10);
            overflow: hidden;
        }
        .head {
            padding: 24px 24px 16px;
            border-bottom: 1px solid var(--line);
        }
        .head h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }
        .head p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }
        form {
            padding: 20px 24px 24px;
        }
        .field {
            display: grid;
            gap: 6px;
            margin-bottom: 14px;
        }
        .field label {
            font-size: 13px;
            color: var(--muted);
        }
        .field input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #b9c3cf;
            border-radius: 10px;
            font: inherit;
        }
        .notice {
            margin-bottom: 16px;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 14px;
        }
        .notice.error {
            background: var(--danger-bg);
            color: var(--danger-text);
            border: 1px solid #f3b3b3;
        }
        .notice.info {
            background: var(--ok-bg);
            color: var(--ok-text);
            border: 1px solid #b8d9be;
        }
        .actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 18px;
        }
        button {
            border: 0;
            border-radius: 10px;
            padding: 10px 16px;
            background: var(--accent);
            color: #fff;
            font: inherit;
            cursor: pointer;
        }
        .muted {
            font-size: 12px;
            color: var(--muted);
        }
        .footer {
            padding: 0 24px 20px;
            color: var(--muted);
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="head">
        <h1><?= $hasUsers ? 'Вход в систему' : 'Первый запуск' ?></h1>
        <p><?= $hasUsers ? 'Авторизуйтесь для входа в панель администратора.' : 'Создайте первого администратора системы.' ?></p>
    </div>
    <form method="post">
        <?php if ($errorMessage !== ''): ?>
            <div class="notice error"><?= h($errorMessage) ?></div>
        <?php endif; ?>
        <?php if ($infoMessage !== ''): ?>
            <div class="notice info"><?= h($infoMessage) ?></div>
        <?php endif; ?>

        <input type="hidden" name="next" value="<?= h($nextUrl) ?>">

        <?php if ($hasUsers): ?>
            <input type="hidden" name="action" value="login">
            <div class="field">
                <label for="login">Логин</label>
                <input id="login" name="login" type="text" autocomplete="username" required>
            </div>
            <div class="field">
                <label for="password">Пароль</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <div class="actions">
                <span class="muted">Доступ к панели обязателен через авторизацию.</span>
                <button type="submit">Войти</button>
            </div>
        <?php else: ?>
            <input type="hidden" name="action" value="setup">
            <div class="field">
                <label for="full_name">Имя пользователя</label>
                <input id="full_name" name="full_name" type="text" autocomplete="name" required>
            </div>
            <div class="field">
                <label for="login">Логин</label>
                <input id="login" name="login" type="text" autocomplete="username" required>
            </div>
            <div class="field">
                <label for="password">Пароль</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required>
            </div>
            <div class="field">
                <label for="password_confirm">Повтор пароля</label>
                <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required>
            </div>
            <div class="actions">
                <span class="muted">Первый пользователь автоматически получит роль администратора.</span>
                <button type="submit">Создать администратора</button>
            </div>
        <?php endif; ?>
    </form>
    <div class="footer">Версия проекта: <strong><?= h($projectVersion ?? '0.0.0-dev') ?></strong></div>
</div>
</body>
</html>
