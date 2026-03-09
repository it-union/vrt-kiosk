<?php
declare(strict_types=1);
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'Управление аккаунтами') ?></title>
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
            --ok-bg: #e8f4ea;
            --ok-text: #216e39;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: linear-gradient(180deg, #334155 0%, #475569 100%);
            color: var(--text);
            font-family: Tahoma, sans-serif;
        }
        .page {
            max-width: 1600px;
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
        }
        .linkbar {
            display: flex;
            gap: 10px;
        }
        .linkbar a,
        .actions button,
        .actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 10px;
            border: 1px solid #b8c3cf;
            background: #fff;
            color: var(--text);
            text-decoration: none;
            font: inherit;
            cursor: pointer;
        }
        .linkbar a {
            border-color: var(--accent);
            background: var(--accent);
            color: #fff;
        }
        .layout {
            display: grid;
            grid-template-columns: minmax(420px, 1.3fr) minmax(360px, 0.9fr);
            gap: 20px;
            flex: 1;
            min-height: 0;
        }
        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: hidden;
        }
        .panelHead {
            padding: 16px 18px;
            border-bottom: 1px solid var(--line);
        }
        .panelHead h2 {
            margin: 0 0 4px;
            font-size: 20px;
        }
        .panelHead p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }
        .notice {
            margin: 16px 18px 0;
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
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .tableWrap {
            flex: 1;
            min-height: 0;
            overflow: auto;
        }
        th, td {
            padding: 12px 18px;
            border-bottom: 1px solid #e7edf4;
            font-size: 14px;
            text-align: left;
            vertical-align: top;
        }
        th {
            color: var(--muted);
            font-weight: 400;
            background: #fafbfd;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 12px;
            border: 1px solid #cdd8e5;
            background: #f6f8fb;
        }
        .badge.active {
            color: #216e39;
            background: #edf8f0;
            border-color: #bde0c4;
        }
        .badge.inactive {
            color: #7a3d1d;
            background: #fff2e9;
            border-color: #f0cfb7;
        }
        .formBody {
            padding: 18px;
        }
        .field {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 12px;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e7edf4;
        }
        .field label {
            color: var(--muted);
            font-size: 13px;
        }
        .field input,
        .field select {
            width: 100%;
            min-height: 38px;
            padding: 8px 10px;
            border: 1px solid #b8c3cf;
            border-radius: 10px;
            font: inherit;
            background: #fff;
        }
        .checkboxWrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .checkboxWrap input {
            width: auto;
            min-height: 0;
            padding: 0;
        }
        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 16px;
        }
        @media (min-width: 761px) {
            html, body {
                overflow: hidden;
            }
            .page {
                height: 100vh;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <div>
            <h1>Аккаунты</h1>
            <p>Управление доступом к административной панели и редакторам.</p>
        </div>
        <div class="linkbar">
            <a href="/admin/">Панель</a>
            <a href="/logout/">Выйти</a>
        </div>
    </div>

    <div class="layout">
        <section class="panel">
            <div class="panelHead">
                <h2>Список пользователей</h2>
                <p>Текущие аккаунты и роли доступа.</p>
            </div>
            <?php if ($errorMessage !== ''): ?>
                <div class="notice error"><?= h($errorMessage) ?></div>
            <?php endif; ?>
            <?php if ($infoMessage !== ''): ?>
                <div class="notice info"><?= h($infoMessage) ?></div>
            <?php endif; ?>
            <div class="tableWrap">
            <table>
                <thead>
                <tr>
                    <th>Имя</th>
                    <th>Логин</th>
                    <th>Роль</th>
                    <th>Статус</th>
                    <th>Активность</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $userRow): ?>
                    <tr>
                        <td><?= h((string)$userRow['full_name']) ?></td>
                        <td><?= h((string)$userRow['login']) ?></td>
                        <td><?= h((string)($userRow['role_name'] ?? '')) ?></td>
                        <td>
                            <?php $isActive = (int)($userRow['is_active'] ?? 0) === 1; ?>
                            <span class="badge <?= $isActive ? 'active' : 'inactive' ?>">
                                <?= $isActive ? 'Активен' : 'Отключён' ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $lastActivity = $userRow['last_activity_at'] ?? null;
                            if ($lastActivity):
                                $dt = new DateTime($lastActivity);
                                echo h($dt->format('d.m.Y H:i'));
                            else:
                                echo '<span style="color:#99a3af;">—</span>';
                            endif;
                            ?>
                        </td>
                        <td><a href="/users/?edit=<?= (int)$userRow['id'] ?>">Изменить</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </section>

        <section class="panel">
            <div class="panelHead">
                <h2><?= $editUser ? 'Редактирование аккаунта' : 'Новый аккаунт' ?></h2>
                <p><?= $editUser ? 'Измените роль, статус и данные входа.' : 'Создание пользователя с ролью доступа.' ?></p>
            </div>
            <form method="post" class="formBody">
                <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">
                <?php if ($editUser): ?>
                    <input type="hidden" name="user_id" value="<?= (int)$editUser['id'] ?>">
                <?php endif; ?>

                <div class="field">
                    <label for="full_name">Имя</label>
                    <input id="full_name" name="full_name" type="text" required value="<?= h((string)($editUser['full_name'] ?? '')) ?>">
                </div>
                <div class="field">
                    <label for="login">Логин</label>
                    <input id="login" name="login" type="text" required value="<?= h((string)($editUser['login'] ?? '')) ?>">
                </div>
                <div class="field">
                    <label for="password">Пароль</label>
                    <input id="password" name="password" type="password" <?= $editUser ? '' : 'required' ?>>
                </div>
                <div class="field">
                    <label for="role_code">Роль</label>
                    <select id="role_code" name="role_code">
                        <?php foreach ($roleOptions as $roleCode => $roleLabel): ?>
                            <option value="<?= h($roleCode) ?>" <?= (($editUser['role_code'] ?? AUTH_ROLE_USER) === $roleCode) ? 'selected' : '' ?>>
                                <?= h($roleLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Активность</label>
                    <div class="checkboxWrap">
                        <input id="is_active" name="is_active" type="checkbox" value="1" <?= ((int)($editUser['is_active'] ?? 1) === 1) ? 'checked' : '' ?>>
                        <label for="is_active">Аккаунт активен</label>
                    </div>
                </div>
                <div class="actions">
                    <?php if ($editUser): ?>
                        <a href="/users/">Новый аккаунт</a>
                    <?php endif; ?>
                    <button type="submit"><?= $editUser ? 'Сохранить' : 'Создать' ?></button>
                </div>
            </form>
        </section>
    </div>

    <p style="margin:18px 0 0;color:#667085;font-size:12px;">Версия проекта: <strong><?= h($projectVersion ?? '0.0.0-dev') ?></strong></p>
</div>
</body>
</html>
