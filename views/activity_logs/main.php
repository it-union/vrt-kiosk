<?php
declare(strict_types=1);
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <style>
        :root {
            --bg: #111827;
            --panel: #ffffff;
            --line: #d6dde6;
            --text: #1f2937;
            --muted: #667085;
            --accent: #0f6cbd;
            --accent-soft: #ebf4fd;
            --danger-bg: #fdecec;
            --danger-text: #9d2020;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: linear-gradient(180deg, #334155 0%, #475569 100%);
            color: var(--text);
            font-family: Tahoma, sans-serif;
        }
        .page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 22px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }
        .topbar h1 {
            margin: 0 0 6px;
            font-size: 32px;
            color: #fff;
        }
        .topbar p {
            margin: 0;
            color: #cbd5e1;
            font-size: 13px;
        }
        .topbarActions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .linkbar {
            display: flex;
            gap: 10px;
        }
        .linkbar a {
            padding: 0 12px;
            min-height: 34px;
            border-radius: 10px;
            background: #1d5fbf;
            color: #fff;
            text-decoration: none;
            border: 1px solid #1d5fbf;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .layout {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            min-height: 0;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        .panelHead {
            margin-bottom: 16px;
        }
        .panelHead h2 {
            margin: 0 0 4px;
            font-size: 18px;
        }
        .panelHead p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }
        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 8px;
            margin-bottom: 12px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 12px;
        }
        .filters label {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 11px;
            color: var(--muted);
        }
        .filters select,
        .filters input {
            padding: 8px 10px;
            border: 1px solid #b8c3cf;
            border-radius: 10px;
            font: inherit;
            background: #fff;
        }
        .filterActions {
            display: flex;
            align-items: flex-end;
            gap: 8px;
        }
        .filterActions button {
            width: 38px;
            height: 38px;
            min-width: 38px;
            padding: 0;
            border: 1px solid #1d5fbf;
            background: #1d5fbf;
            color: #fff;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        .filterActions .clearBtn {
            border-color: #64748b;
            background: #fff;
            color: #64748b;
        }
        .filterActions .dangerBtn {
            border-color: #b91c1c;
            background: #fff;
            color: #b91c1c;
            margin-left: auto;
        }
        .tableWrap {
            flex: 1;
            min-height: 0;
            overflow: auto;
            border: 1px solid #e7edf4;
            border-radius: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 6px 10px;
            border-bottom: 1px solid #e7edf4;
            font-size: 12px;
            text-align: left;
            vertical-align: top;
            white-space: nowrap;
        }
        th:nth-child(5), td:nth-child(5) {
            white-space: normal;
            width: auto;
        }
        th:nth-child(1), td:nth-child(1) { width: 1%; }
        th:nth-child(2), td:nth-child(2) { width: 1%; }
        th:nth-child(3), td:nth-child(3) { width: 1%; }
        th:nth-child(4), td:nth-child(4) { width: 1%; }
        th {
            color: var(--muted);
            font-weight: 400;
            background: #fafbfd;
            position: sticky;
            top: 0;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 11px;
            border: 1px solid #cdd8e5;
            background: #f6f8fb;
        }
        .badge.navigation { color: #0369a1; background: #e0f2fe; border-color: #bae6fd; }
        .badge.content_create { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .badge.content_save { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .badge.template_create { color: #7c3aed; background: #ede9fe; border-color: #ddd6fe; }
        .badge.template_save { color: #7c3aed; background: #ede9fe; border-color: #ddd6fe; }
        .badge.queue_create { color: #9d174d; background: #fce7f3; border-color: #fbcfe8; }
        .badge.queue_save { color: #9d174d; background: #fce7f3; border-color: #fbcfe8; }
        .badge.queue_start { color: #ea580c; background: #ffedd5; border-color: #fed7aa; }
        .badge.queue_stop { color: #dc2626; background: #fee2e2; border-color: #fecaca; }
        .badge.queue_manual { color: #2563eb; background: #dbeafe; border-color: #bfdbfe; }
        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid #e7edf4;
        }
        .pagination button {
            padding: 8px 16px;
            border: 1px solid #c8ced6;
            background: #fff;
            border-radius: 10px;
            cursor: pointer;
        }
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .notice {
            margin: 16px 0 0;
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
            background: #eff6ff;
            color: #1d5fbf;
            border: 1px solid #bfdbfe;
        }
        .pageFooter {
            margin-top: 18px;
            color: #fff;
            font-size: 12px;
            border-top: 1px solid rgba(255,255,255,0.16);
            padding-top: 10px;
        }
        @media (min-width: 761px) {
            html, body { overflow: hidden; }
            .page { height: 100vh; }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <div>
            <h1>Журнал активности</h1>
            <p>Просмотр действий пользователей системы.</p>
        </div>
        <div class="topbarActions">
            <div class="linkbar">
                <a href="/admin/">Панель</a>
                <a href="/logout/">Выйти</a>
            </div>
        </div>
    </div>

    <div class="layout">
        <section class="panel">
            <div class="panelHead">
                <h2>Логи действий</h2>
                <p>Фильтрация и просмотр записей журнала активности.</p>
            </div>

            <form method="get" class="filters" id="logFilters">
                <label>
                    Пользователь
                    <select name="user_id" id="filterUserId">
                        <option value="">Все пользователи</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= (isset($_GET['user_id']) && (int)$_GET['user_id'] === (int)$u['id']) ? 'selected' : '' ?>>
                                <?= h($u['full_name'] . ' (' . $u['login'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Тип действия
                    <select name="action_type" id="filterActionType">
                        <option value="">Все действия</option>
                        <option value="navigation" <?= (isset($_GET['action_type']) && $_GET['action_type'] === 'navigation') ? 'selected' : '' ?>>Переход</option>
                        <option value="content_create" <?= (isset($_GET['action_type']) && $_GET['action_type'] === 'content_create') ? 'selected' : '' ?>>Создание контента</option>
                        <option value="content_save" <?= (isset($_GET['action_type']) && $_GET['action_type'] === 'content_save') ? 'selected' : '' ?>>Сохранение контента</option>
                        <option value="template_create" <?= (isset($_GET['action_type']) && $_GET['action_type'] === 'template_create') ? 'selected' : '' ?>>Создание шаблона</option>
                        <option value="template_save" <?= (isset($_GET['action_type']) && $_GET['action_type'] === 'template_save') ? 'selected' : '' ?>>Сохранение шаблона</option>
                        <option value="queue_create" <?= (isset($_GET['action_type']) && $_GET['action_type'] === 'queue_create') ? 'selected' : '' ?>>Создание очереди</option>
                        <option value="queue_save" <?= (isset($_GET['action_type']) && $_GET['action_type'] === 'queue_save') ? 'selected' : '' ?>>Сохранение очереди</option>
                        <option value="queue_start" <?= (isset($_GET['action_type']) && $_GET['action_type'] === 'queue_start') ? 'selected' : '' ?>>Старт очереди</option>
                        <option value="queue_stop" <?= (isset($_GET['action_type']) && $_GET['action_type'] === 'queue_stop') ? 'selected' : '' ?>>Стоп очереди</option>
                        <option value="queue_manual" <?= (isset($_GET['action_type']) && $_GET['action_type'] === 'queue_manual') ? 'selected' : '' ?>>Ручной режим</option>
                    </select>
                </label>
                <label>
                    Сущность
                    <select name="entity_type" id="filterEntityType">
                        <option value="">Все сущности</option>
                        <option value="content" <?= (isset($_GET['entity_type']) && $_GET['entity_type'] === 'content') ? 'selected' : '' ?>>Контент</option>
                        <option value="template" <?= (isset($_GET['entity_type']) && $_GET['entity_type'] === 'template') ? 'selected' : '' ?>>Шаблон</option>
                        <option value="queue" <?= (isset($_GET['entity_type']) && $_GET['entity_type'] === 'queue') ? 'selected' : '' ?>>Очередь</option>
                    </select>
                </label>
                <label>
                    Дата с
                    <input type="date" name="date_from" id="filterDateFrom" value="<?= h($_GET['date_from'] ?? '') ?>">
                </label>
                <label>
                    Дата по
                    <input type="date" name="date_to" id="filterDateTo" value="<?= h($_GET['date_to'] ?? '') ?>">
                </label>
                <div class="filterActions">
                    <button type="submit" title="Применить">&#128269;</button>
                    <button type="button" class="clearBtn" title="Сбросить" onclick="window.location.href='/log/'">&#10227;</button>
                    <button type="button" class="dangerBtn" id="clearLogsBtn" title="Очистить логи">&#128465;</button>
                </div>
            </form>

            <?php if (isset($_GET['cleared'])): ?>
                <div class="notice info">Логи очищены</div>
            <?php endif; ?>

            <div class="tableWrap">
                <table>
                    <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Пользователь</th>
                        <th>Действие</th>
                        <th>Сущность</th>
                        <th>Описание</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $pdo = dbMysql();
                    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
                    $actionType = isset($_GET['action_type']) ? trim($_GET['action_type']) : null;
                    $entityType = isset($_GET['entity_type']) ? trim($_GET['entity_type']) : null;
                    $dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : null;
                    $dateTo = isset($_GET['date_to']) ? trim($_GET['date_to']) : null;
                    $page = max(1, (int)($_GET['page'] ?? 1));
                    $limit = 50;
                    $offset = ($page - 1) * $limit;
                    $logs = activityLogList($pdo, $userId, $actionType, $entityType, $dateFrom, $dateTo, $limit, $offset);
                    $totalCount = activityLogCount($pdo, $userId, $actionType, $entityType, $dateFrom, $dateTo);
                    $totalPages = max(1, (int)ceil($totalCount / $limit));
                    ?>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center;color:#99a3af;">Записей не найдено</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= h((new DateTime($log['created_at']))->format('d.m.Y H:i')) ?></td>
                                <td><?= h($log['full_name'] ?: $log['login']) ?></td>
                                <td><span class="badge <?= h($log['action_type']) ?>"><?= h($log['action_type']) ?></span></td>
                                <td>
                                    <?php if ($log['entity_type']): ?>
                                        <?= h($log['entity_type']) ?>
                                    <?php else: ?>
                                        <span style="color:#99a3af;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= h($log['action_description'] ?: '—') ?>
                                    <?php if ($log['entity_name']): ?>
                                        <br><small style="color:#64748b;">
                                            <?php if ($log['content_type']): ?>
                                                <?= h('[' . $log['content_type'] . '] ' . $log['entity_name']) ?>
                                            <?php else: ?>
                                                <?= h($log['entity_name']) ?>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <form method="get" style="display:inline;">
                        <?php foreach ($_GET as $key => $val): ?>
                            <?php if ($key !== 'page'): ?>
                                <input type="hidden" name="<?= h($key) ?>" value="<?= h($val) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <input type="hidden" name="page" value="<?= max(1, $page - 1) ?>">
                        <button type="submit" <?= $page <= 1 ? 'disabled' : '' ?>>← Назад</button>
                    </form>
                    <span style="color:#64748b;font-size:13px;">Страница <?= $page ?> из <?= $totalPages ?></span>
                    <form method="get" style="display:inline;">
                        <?php foreach ($_GET as $key => $val): ?>
                            <?php if ($key !== 'page'): ?>
                                <input type="hidden" name="<?= h($key) ?>" value="<?= h($val) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <input type="hidden" name="page" value="<?= min($totalPages, $page + 1) ?>">
                        <button type="submit" <?= $page >= $totalPages ? 'disabled' : '' ?>>Вперёд →</button>
                    </form>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="pageFooter">Версия проекта: <strong><?= h($projectVersion ?? '0.0.0-dev') ?></strong></div>
</div>

<div class="modalBack" id="clearLogsModal">
    <div class="modal" role="dialog" aria-modal="true">
        <h3 id="clearLogsTitle">Очистка журнала активности</h3>
        <p style="margin:0 0 12px;color:#334155;">Удалить все записи журнала активности?</p>
        <div class="row">
            <button type="button" id="clearLogsCancelBtn">Отмена</button>
            <button type="button" id="clearLogsConfirmBtn" class="danger">Удалить</button>
        </div>
    </div>
</div>

<style>
    .modalBack { position: fixed; inset: 0; background: rgba(0,0,0,.35); display: none; align-items: center; justify-content: center; z-index: 100; }
    .modalBack.open { display: flex; }
    .modal { width: min(400px, calc(100vw - 24px)); background: #fff; border: 1px solid #d7dbe0; border-radius: 16px; padding: 16px; }
    .modal h3 { margin: 0 0 10px; font-size: 16px; }
    .modal .row { display: flex; gap: 10px; justify-content: flex-end; }
    .modal button { padding: 8px 16px; border: 1px solid #c8ced6; background: #fff; border-radius: 10px; cursor: pointer; }
    .modal button.danger { border-color: #b91c1c; color: #b91c1c; }
</style>

<script>
const clearLogsModal = document.getElementById('clearLogsModal');
const clearLogsCancelBtn = document.getElementById('clearLogsCancelBtn');
const clearLogsConfirmBtn = document.getElementById('clearLogsConfirmBtn');

if (clearLogsCancelBtn) {
    clearLogsCancelBtn.onclick = () => clearLogsModal.classList.remove('open');
}

if (clearLogsModal) {
    clearLogsModal.onclick = (event) => {
        if (event.target === clearLogsModal) clearLogsModal.classList.remove('open');
    };
}

document.getElementById('clearLogsBtn').onclick = function() {
    clearLogsModal.classList.add('open');
};

if (clearLogsConfirmBtn) {
    clearLogsConfirmBtn.onclick = function() {
        fetch('/api/activity_log_clear.php', { method: 'POST' })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    clearLogsModal.classList.remove('open');
                    window.location.href = '/log/?cleared=1';
                } else {
                    alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
                }
            })
            .catch(e => alert('Ошибка: ' + e.message));
    };
}
</script>
</body>
</html>