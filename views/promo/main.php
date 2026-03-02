<?php
declare(strict_types=1);
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'VRT Kiosk') ?></title>
    <style>
        :root {
            --bg1: #f5f7fb;
            --bg2: #e8eef6;
            --text: #142033;
            --muted: #5c6b80;
            --line: #d5dde8;
            --accent: #0f6cbd;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px;
            background:
                radial-gradient(circle at top left, rgba(15, 108, 189, 0.10), transparent 32%),
                radial-gradient(circle at bottom right, rgba(15, 108, 189, 0.12), transparent 30%),
                linear-gradient(180deg, var(--bg1) 0%, var(--bg2) 100%);
            color: var(--text);
            font-family: Tahoma, sans-serif;
        }
        .hero {
            width: min(100%, 1080px);
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(280px, 0.9fr);
            gap: 20px;
        }
        .panel {
            background: rgba(255,255,255,0.88);
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 28px;
            box-shadow: 0 18px 42px rgba(20, 32, 51, 0.10);
        }
        .title {
            margin: 0 0 12px;
            font-size: clamp(36px, 6vw, 72px);
            line-height: 0.96;
            letter-spacing: -0.04em;
        }
        .lead {
            margin: 0;
            max-width: 640px;
            color: var(--muted);
            font-size: 18px;
            line-height: 1.5;
        }
        .grid {
            display: grid;
            gap: 12px;
            margin-top: 24px;
        }
        .feature {
            padding: 14px 16px;
            border: 1px solid #e3e9f2;
            border-radius: 14px;
            background: #fff;
        }
        .feature strong {
            display: block;
            margin-bottom: 4px;
            font-size: 14px;
        }
        .feature span {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }
        .sideTitle {
            margin: 0 0 10px;
            font-size: 24px;
        }
        .sideText {
            margin: 0 0 18px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }
        .actions {
            display: grid;
            gap: 10px;
        }
        .actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            border-radius: 12px;
            text-decoration: none;
            font: inherit;
        }
        .primary {
            background: var(--accent);
            border: 1px solid var(--accent);
            color: #fff;
        }
        .ghost {
            background: #fff;
            border: 1px solid #b9c6d6;
            color: var(--text);
        }
        .foot {
            margin-top: 18px;
            color: var(--muted);
            font-size: 12px;
        }
        @media (max-width: 860px) {
            .hero {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="hero">
    <section class="panel">
        <h1 class="title">VRT Kiosk</h1>
        <p class="lead">Система управления показом контента для киосков: шаблоны, контент, очередь показа и ручной режим управления экраном.</p>
        <div class="grid">
            <div class="feature">
                <strong>Шаблонизатор</strong>
                <span>Настройка блоков, фона и компоновки экранов.</span>
            </div>
            <div class="feature">
                <strong>Редактор контента</strong>
                <span>Поддержка изображений, HTML, видео и презентаций.</span>
            </div>
            <div class="feature">
                <strong>Панель управления</strong>
                <span>Авторизация, очередь показа, ручной режим и мониторинг киоска.</span>
            </div>
        </div>
    </section>

    <aside class="panel">
        <h2 class="sideTitle">Навигация</h2>
        <p class="sideText">Основная работа с системой выполняется через административную панель. Киоск доступен по отдельному маршруту.</p>
        <div class="actions">
            <a class="primary" href="/admin/">Панель администратора</a>
            <a class="ghost" href="/kiosk/">Экран киоска</a>
        </div>
        <p class="foot">Версия проекта: <strong><?= h($projectVersion ?? '0.0.0-dev') ?></strong></p>
    </aside>
</div>
</body>
</html>
