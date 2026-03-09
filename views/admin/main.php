<?php
declare(strict_types=1);

$userName = (string)($currentUser['full_name'] ?? $currentUser['login'] ?? '');
$roleName = authRoleLabel((string)($currentUser['role_code'] ?? ''));
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'Панель администратора') ?></title>
    <style>
        :root {
            --bg: #eef3f8;
            --panel: #ffffff;
            --line: #d6dee8;
            --text: #1f2937;
            --muted: #667085;
            --accent: #0f6cbd;
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
        .topbarActions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .topbarUser {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            color: #cbd5e1;
            font-size: 12px;
        }
        .topbarUser strong {
            color: #fff;
            font-size: 14px;
        }
        .actionLink {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 0 12px;
            border-radius: 10px;
            border: 1px solid #1d5fbf;
            background: #1d5fbf;
            color: #fff;
            text-decoration: none;
        }
        .navRow {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .navRowRight {
            margin-left: auto;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .navRow .btn,
        .navRow .btnGhost {
            min-width: 160px;
        }
        .navIconBtn {
            min-width: 42px;
            width: 42px;
            min-height: 42px;
            height: 42px;
            flex: 0 0 42px;
            padding: 0;
            font-size: 18px;
            line-height: 1;
        }
        .navRowRight .navIconBtn {
            min-width: 42px;
            width: 42px;
            min-height: 42px;
            height: 42px;
            flex: 0 0 42px;
            padding: 0;
        }
        .navRow .btnGhost.disabled {
            opacity: 0.7;
            cursor: default;
            pointer-events: none;
        }
        .navNote {
            display: flex;
            align-items: center;
            color: var(--muted);
            font-size: 13px;
            padding-left: 6px;
        }
        .queueActions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn,
        .btnGhost {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 0 14px;
            border-radius: 10px;
            font: inherit;
            text-decoration: none;
            cursor: pointer;
        }
        .btn {
            border: 1px solid #0f6cbd;
            background: #0f6cbd;
            color: #fff;
        }
        .btn.isActive,
        .btnGhost.isActive {
            border-color: #86efac;
            background: #dcfce7;
            color: #166534;
        }
        .btn:disabled,
        .btnGhost:disabled {
            opacity: 0.6;
            cursor: default;
        }
        .btnGhost {
            border: 1px solid #b8c3cf;
            background: #fff;
            color: var(--text);
        }
        .layout {
            display: grid;
            grid-template-columns: minmax(320px, 460px) minmax(0, 1fr);
            gap: 18px;
            flex: 1;
            min-height: 0;
        }
        .panel {
            border: 1px solid var(--line);
            border-radius: 16px;
            background: rgba(255,255,255,0.94);
            overflow: hidden;
            box-shadow: 0 12px 26px rgba(31, 41, 55, 0.06);
        }
        .panelHead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid var(--line);
        }
        .panelHead.screenPanelHead {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .panelHead.screenPanelHead > :first-child {
            justify-self: start;
        }
        .panelHead.screenPanelHead > :nth-child(2) {
            justify-self: center;
        }
        .panelHead.screenPanelHead > :nth-child(3) {
            justify-self: end;
        }
        .panelHead h2 {
            margin: 0 0 4px;
            font-size: 21px;
        }
        .panelHead p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }
        .panelBody {
            padding: 18px;
        }
        .queueActions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }
        .queueSelectorRow {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            margin-bottom: 14px;
        }
        .queueSelectorRow select {
            width: 100%;
            min-width: 0;
            padding: 8px 10px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            color: var(--text);
            font: inherit;
        }
        .queueStatus {
            margin-top: 14px;
            min-height: 24px;
            color: var(--muted);
            font-size: 13px;
        }
        .queueListBox {
            margin-top: 14px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: #fff;
            overflow: hidden;
        }
        .queueListHead {
            padding: 12px 14px;
            border-bottom: 1px solid var(--line);
            background: #f8fafc;
        }
        .queueListHead strong {
            display: block;
            font-size: 14px;
        }
        .queueListHead span {
            display: block;
            margin-top: 3px;
            color: var(--muted);
            font-size: 12px;
        }
        .queueList {
            display: grid;
            max-height: 320px;
            overflow: auto;
        }
        .queueListItem {
            padding: 12px 14px;
            border-bottom: 1px solid #e7edf4;
            background: #fff;
        }
        .queueListItem.inactive {
            background: linear-gradient(90deg, rgba(254, 226, 226, 0.85) 0%, rgba(255, 255, 255, 1) 34%);
        }
        .queueListItem:last-child {
            border-bottom: 0;
        }
        .queueListItem.current {
            background: #ecfdf3;
            box-shadow: inset 4px 0 0 #16a34a;
        }
        .queueListItem.inactive.current {
            background: linear-gradient(90deg, rgba(254, 202, 202, 0.95) 0%, rgba(236, 253, 243, 1) 34%);
        }
        .queueListName {
            margin: 0 0 4px;
            font-size: 13px;
            font-weight: 700;
        }
        .queueListMeta {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.35;
        }
        .queueProgress {
            margin-top: 8px;
            height: 6px;
            border-radius: 999px;
            background: #e5e7eb;
            overflow: hidden;
        }
        .queueProgressBar {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #22c55e, #16a34a);
            transition: width 160ms linear;
        }
        .queueEmpty {
            padding: 14px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.45;
        }
        .status {
            margin: 0;
            display: none;
            padding: 6px 10px;
            border-radius: 10px;
            border: 1px solid transparent;
            font-size: 12px;
            line-height: 1.2;
            white-space: nowrap;
        }
        .status.show {
            display: inline-flex;
        }
        .status.success {
            color: #0f5132;
            background: #d1e7dd;
            border-color: #badbcc;
        }
        .status.error {
            color: #842029;
            background: #f8d7da;
            border-color: #f5c2c7;
        }
        .mirrorPanelBody {
            display: flex;
            flex-direction: column;
            gap: 12px;
            justify-content: center;
            align-items: flex-start;
            padding: 18px;
            min-height: 0;
        }
        .mirrorToolbar {
            width: min(960px, 100%);
            display: flex;
            justify-content: flex-end;
        }
        .mirrorToolbar select {
            width: auto;
            min-width: 220px;
            padding: 8px 10px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            color: var(--text);
            font: inherit;
        }
        .mirrorFrame {
            position: relative;
            width: min(960px, 100%);
            margin: 0 auto;
            aspect-ratio: 16/9;
            border: 2px dashed #b7c1cf;
            border-radius: 16px;
            background: #fff;
            overflow: hidden;
        }
        .mirror {
            width: 100%;
            height: 100%;
            border: 0;
            background: #000;
            display: block;
        }
        .modalBack {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.42);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            z-index: 100;
        }
        .modalBack.open {
            display: flex;
        }
        .modal {
            width: min(680px, 100%);
            max-height: min(82vh, 860px);
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
        }
        .modalHead {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid var(--line);
        }
        .modalHead h3 {
            margin: 0 0 4px;
            font-size: 20px;
        }
        .modalHead p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }
        .modalClose {
            width: 38px;
            min-width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 1px solid #b8c3cf;
            background: #fff;
            color: var(--text);
            font: inherit;
            cursor: pointer;
        }
        .modalBody {
            padding: 12px 18px 18px;
            max-height: calc(82vh - 90px);
            overflow: auto;
        }
        .alertModal {
            width: min(460px, 100%);
            border-color: #ef4444;
            background: #fff5f5;
        }
        .alertModal .modalHead {
            background: #fee2e2;
            border-bottom-color: #fecaca;
        }
        .alertModal .modalHead h3 {
            color: #991b1b;
        }
        .alertModal .modalBody {
            color: #7f1d1d;
            font-size: 15px;
            line-height: 1.45;
        }
        .alertModalActions {
            display: flex;
            justify-content: flex-end;
            margin-top: 14px;
        }
        .alertOkBtn {
            min-width: 96px;
            border-color: #b91c1c;
            background: #dc2626;
            color: #fff;
        }
        .templateList {
            display: grid;
            gap: 8px;
        }
        .templateItem {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e7edf4;
        }
        .templateItem:first-child {
            padding-top: 0;
        }
        .templateItem:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }
        .templateName {
            margin: 0 0 4px;
            font-size: 14px;
            font-weight: 700;
        }
        .templateMeta {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.4;
        }
        .templateEmpty {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
        }
        .pageFooter {
            margin-top: 18px;
            color: #fff;
            font-size: 12px;
            border-top: 1px solid #dfe5ee;
            padding-top: 10px;
        }
        @media (max-width: 1250px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }
        @media (min-width: 761px) {
            html, body {
                overflow: hidden;
            }
            .page {
                height: 100vh;
            }
        }
        @media (max-width: 760px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
            .navRow {
                flex-direction: column;
                align-items: stretch;
            }
            .navRowRight {
                margin-left: 0;
                width: 100%;
            }
            .navRow .btn,
            .navRow .btnGhost {
                width: 100%;
                min-width: 0;
            }
            .navIconBtn {
                width: 42px;
                min-width: 42px;
                min-height: 42px;
                height: 42px;
                flex: 0 0 42px;
            }
            .queueActions {
                grid-template-columns: 1fr;
            }
            .templateItem {
                grid-template-columns: 1fr;
            }
            .mirrorFrame {
                width: 100%;
            }
            .panelHead {
                flex-direction: column;
            }
            .panelHead.screenPanelHead {
                grid-template-columns: 1fr;
            }
            .status {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <div>
            <h1>Панель администратора</h1>
            <p>Единая точка входа для управления киоском, шаблонами и пользователями.</p>
        </div>
        <div class="topbarActions">
            <div class="topbarUser">
                <strong><?= h($userName) ?></strong>
                <span><?= h($roleName) ?></span>
            </div>
            <a class="actionLink" href="/logout/">Выйти</a>
        </div>
    </div>

    <div class="navRow">
        <?php if ($canUseTemplateEditor): ?>
            <a class="btn" href="/template/">Шаблонизатор</a>
        <?php else: ?>
            <span class="btnGhost disabled">Шаблонизатор</span>
        <?php endif; ?>

        <?php if ($canManageAccounts): ?>
            <a class="btn" href="/users/">Аккаунты</a>
        <?php else: ?>
            <span class="btnGhost disabled">Аккаунты</span>
        <?php endif; ?>

        <?php if ($canUseQueueEditor): ?>
            <a class="btn" href="/queue/">Настройка очереди</a>
        <?php else: ?>
            <span class="btnGhost disabled">Настройка очереди</span>
        <?php endif; ?>
        <div class="navRowRight">
            <?php if ($canManageAccounts): ?>
                <a class="btn navIconBtn" href="/log/" title="Журнал активности" aria-label="Журнал активности">&#128203;</a>
                <a class="btn navIconBtn" href="/settings/" title="Настройки проекта" aria-label="Настройки проекта">&#9881;</a>
            <?php endif; ?>
            <a class="btn navIconBtn" href="/kiosk/" target="_blank" rel="noopener" title="Киоск" aria-label="Киоск">&#128187;</a>
            <a class="btn navIconBtn" href="/kiosk/test/" target="_blank" rel="noopener" title="Тестовый киоск" aria-label="Тестовый киоск">&#9879;</a>
        </div>
    </div>

    <section class="layout">
        <div class="panel" id="queue-panel">
            <div class="panelHead">
                <div>
                    <h2>Очередь показа</h2>
                    <p>Запуск и остановка очереди, а также переход в ручной режим показа.</p>
                </div>
            </div>
            <div class="panelBody">
                <div class="queueSelectorRow">
                    <select id="queueSelect">
                        <?php foreach ($mainQueues as $queueRow): ?>
                            <option value="<?= (int)($queueRow['id'] ?? 0) ?>" <?= (int)($queueRow['id'] ?? 0) === (int)($activeQueue['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= h((string)($queueRow['name'] ?? 'Очередь')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button id="queueActivateBtn" class="btn" type="button">Активировать</button>
                </div>
                <div class="queueActions">
                    <button id="queueStartBtn" class="btnGhost" type="button">Старт</button>
                    <button id="queueStopBtn" class="btnGhost" type="button">Стоп</button>
                    <button id="queueManualBtn" class="btnGhost" type="button">Ручной</button>
                </div>
                <div id="queueStatus" class="queueStatus"></div>
                <div class="queueListBox">
                    <div class="queueListHead">
                        <strong id="queuePreviewName"><?= h((string)($activeQueue['name'] ?? 'Активная очередь')) ?></strong>
                        <span id="queuePreviewCaption">Шаблоны активной очереди</span>
                    </div>
                    <div id="queuePreviewList"></div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panelHead screenPanelHead">
                <div>
                    <h2>Экран</h2>
                    <p>Дублирование текущего состояния экрана в реальном времени.</p>
                </div>
                <div id="screenStatus" class="status"></div>
                <?php if ($canSelectPreviewScreen): ?>
                    <div class="mirrorToolbar">
                        <select id="previewScreenSelect">
                            <option value="main-kiosk">Киоск</option>
                            <option value="test-kiosk">Тестовый киоск</option>
                        </select>
                    </div>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>
            </div>
            <div class="mirrorPanelBody">
                <div class="mirrorFrame">
                    <iframe id="mirrorFrame" class="mirror" src="/kiosk/?preview=1" title="Экран"></iframe>
                </div>
            </div>
        </div>
    </section>

    <div class="pageFooter">Версия проекта: <strong><?= h($projectVersion ?? '0.0.0-dev') ?></strong></div>
</div>

<div id="manualModal" class="modalBack" aria-hidden="true">
    <div class="modal">
        <div class="modalHead">
            <div>
                <h3>Ручной режим</h3>
                <p>Выберите рабочий шаблон для постоянного показа.</p>
            </div>
            <button id="manualModalClose" class="modalClose" type="button">×</button>
        </div>
        <div class="modalBody">
            <?php if (count($workTemplates) > 0): ?>
                <div class="templateList">
                    <?php foreach ($workTemplates as $templateRow): ?>
                        <div class="templateItem">
                            <div>
                                <p class="templateName"><?= h((string)($templateRow['name'] ?? 'Шаблон')) ?></p>
                                <p class="templateMeta">
                                    ID: <?= (int)($templateRow['id'] ?? 0) ?>
                                    <?php if (trim((string)($templateRow['updated_at'] ?? '')) !== ''): ?>
                                        • Обновлён: <?= h((string)$templateRow['updated_at']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <button class="btn js-show-template" type="button" data-template-id="<?= (int)($templateRow['id'] ?? 0) ?>">Показать</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="templateEmpty">Сейчас нет рабочих шаблонов. Переведите нужный шаблон в статус «рабочий» в шаблонизаторе.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="heartbeatAlertModal" class="modalBack" aria-hidden="true">
    <div class="modal alertModal">
        <div class="modalHead">
            <div>
                <h3>Внимание</h3>
                <p>Статус киоска</p>
            </div>
        </div>
        <div class="modalBody">
            <div>Нет связи с киоском!</div>
            <div class="alertModalActions">
                <button id="heartbeatAlertOkBtn" class="btn alertOkBtn" type="button">Ок</button>
            </div>
        </div>
    </div>
</div>

<script>
const queueStatus = document.getElementById('queueStatus');
const screenStatus = document.getElementById('screenStatus');
const queueSelect = document.getElementById('queueSelect');
const queueActivateBtn = document.getElementById('queueActivateBtn');
const queueStartBtn = document.getElementById('queueStartBtn');
const queueStopBtn = document.getElementById('queueStopBtn');
const queueManualBtn = document.getElementById('queueManualBtn');
const previewScreenSelect = document.getElementById('previewScreenSelect');
const mirrorFrame = document.getElementById('mirrorFrame');
const queuePreviewName = document.getElementById('queuePreviewName');
const queuePreviewCaption = document.getElementById('queuePreviewCaption');
const queuePreviewList = document.getElementById('queuePreviewList');
const manualModal = document.getElementById('manualModal');
const manualModalClose = document.getElementById('manualModalClose');
const heartbeatAlertModal = document.getElementById('heartbeatAlertModal');
const heartbeatAlertOkBtn = document.getElementById('heartbeatAlertOkBtn');
const showTemplateButtons = Array.from(document.querySelectorAll('.js-show-template'));
let screenStatusTimer = null;
let heartbeatWasOnline = true;
let heartbeatOfflineAcknowledged = false;
const queueOptions = <?= json_encode(array_values($mainQueues), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const queuePreviewData = {
    'main-kiosk': {
        name: <?= json_encode((string)($activeQueue['name'] ?? 'Активная очередь'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        caption: 'Шаблоны активной очереди',
        emptyText: 'Активная очередь пока пуста.',
        items: <?= json_encode(array_values($activeQueueItems), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    },
    'test-kiosk': {
        name: <?= json_encode((string)($testQueue['name'] ?? 'Тестовая очередь'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        caption: 'Шаблоны тестовой очереди',
        emptyText: 'Тестовая очередь пока пуста.',
        items: <?= json_encode(array_values($testQueueItems), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    }
};

function getPreviewDeviceKey() {
    return previewScreenSelect ? String(previewScreenSelect.value || 'main-kiosk') : 'main-kiosk';
}

function syncMirrorFrame() {
    if (!mirrorFrame) return;
    mirrorFrame.src = getPreviewDeviceKey() === 'test-kiosk' ? '/kiosk/test/?preview=1' : '/kiosk/?preview=1';
}

function getPreviewQueueData() {
    const deviceKey = getPreviewDeviceKey();
    return queuePreviewData[deviceKey] || queuePreviewData['main-kiosk'];
}

function getQueuePreviewItems() {
    if (!queuePreviewList) {
        return [];
    }
    return Array.from(queuePreviewList.querySelectorAll('.queueListItem'));
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function renderQueuePreview() {
    if (!queuePreviewList || !queuePreviewName || !queuePreviewCaption) {
        return;
    }
    const preview = getPreviewQueueData();
    const items = Array.isArray(preview.items) ? preview.items : [];
    queuePreviewName.textContent = String(preview.name || '');
    queuePreviewCaption.textContent = String(preview.caption || '');
    if (items.length <= 0) {
        queuePreviewList.innerHTML = '<div class="queueEmpty">' + escapeHtml(String(preview.emptyText || 'Очередь пока пуста.')) + '</div>';
        return;
    }

    queuePreviewList.innerHTML = '<div class="queueList">' + items.map((item, index) => `
        <div class="queueListItem${Number(item.is_active ?? 1) === 1 ? '' : ' inactive'}" data-template-id="${Number(item.template_id || 0)}">
            <p class="queueListName">${escapeHtml(String(item.template_name || 'Шаблон'))}</p>
            <p class="queueListMeta">Позиция: ${index + 1} • Время показа: ${Math.max(1, Number(item.duration_sec || 1))} сек</p>
            <div class="queueProgress"><div class="queueProgressBar"></div></div>
        </div>
    `).join('') + '</div>';
}

function setQueueStatus(message, isError) {
    if (!queueStatus) return;
    queueStatus.textContent = message || '';
    queueStatus.style.color = isError ? '#b42318' : '#667085';
}

function setScreenStatus(message, isError) {
    if (!screenStatus) return;
    const text = String(message || '').trim();
    if (!text) {
        screenStatus.textContent = '';
        screenStatus.className = 'status';
        return;
    }
    screenStatus.textContent = text;
    screenStatus.className = 'status show ' + (isError ? 'error' : 'success');
}

function setModeButtons(source) {
    if (queueStartBtn) queueStartBtn.classList.toggle('isActive', source === 'schedule');
    if (queueStopBtn) queueStopBtn.classList.toggle('isActive', source === 'fallback');
    if (queueManualBtn) queueManualBtn.classList.toggle('isActive', source === 'manual');
}

function paintQueueProgress() {
    const state = arguments.length > 0 ? arguments[0] : null;
    getQueuePreviewItems().forEach((node) => {
        const bar = node.querySelector('.queueProgressBar');
        if (!bar) return;
        const nodeTemplateId = Number(node.dataset.templateId || 0);
        if (!state || nodeTemplateId !== Number(state.current_template_id || 0)) {
            bar.style.width = '0%';
            return;
        }
        const progress = Math.max(0, Math.min(100, Number(state.progress_pct || 0)));
        bar.style.width = progress + '%';
    });
}

function setQueueProgress(queueState, source) {
    if (source !== 'schedule' || !queueState || Number(queueState.current_template_id || 0) <= 0) {
        paintQueueProgress(null);
        return;
    }
    paintQueueProgress(queueState);
}

function markCurrentQueueTemplate(templateId, source) {
    getQueuePreviewItems().forEach((node) => {
        const nodeTemplateId = Number(node.dataset.templateId || 0);
        const isCurrent = source === 'schedule' && templateId > 0 && nodeTemplateId === templateId;
        node.classList.toggle('current', isCurrent);
    });
}

function setButtonsDisabled(disabled) {
    [queueActivateBtn, queueStartBtn, queueStopBtn, queueManualBtn].forEach((button) => {
        if (button) button.disabled = disabled;
    });
}

function updateQueueSelectValue(queueId) {
    if (!queueSelect) return;
    queueSelect.value = String(Number(queueId || 0));
}

async function loadQueuePreview(queueId) {
    const res = await fetch('/api/queue_get.php?queue_id=' + encodeURIComponent(String(queueId || 0)), { cache: 'no-store' });
    const payload = await res.json();
    if (!res.ok || !payload || payload.ok !== true) {
        throw new Error((payload && payload.error) ? String(payload.error) : 'Не удалось загрузить очередь');
    }
    const queue = payload.data && payload.data.queue ? payload.data.queue : null;
    const items = payload.data && Array.isArray(payload.data.items) ? payload.data.items : [];
    if (!queue) {
        throw new Error('Некорректный ответ API');
    }
    queuePreviewData['main-kiosk'] = {
        name: String(queue.name || ''),
        caption: 'Шаблоны активной очереди',
        emptyText: 'Активная очередь пока пуста.',
        items
    };
    updateQueueSelectValue(queue.id || 0);
}

async function activateSelectedQueue() {
    if (!queueSelect || !queueActivateBtn) return;
    const queueId = Number(queueSelect.value || 0);
    if (queueId <= 0) {
        setQueueStatus('Выберите очередь', true);
        return;
    }

    setButtonsDisabled(true);
    setQueueStatus('Активация очереди...', false);
    try {
        const body = new URLSearchParams();
        body.set('queue_id', String(queueId));
        const res = await fetch('/api/queue_activate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body
        });
        const payload = await res.json();
        if (!res.ok || !payload || payload.ok !== true) {
            throw new Error((payload && payload.error) ? String(payload.error) : 'Не удалось активировать очередь');
        }
        await loadQueuePreview(queueId);
        if (getPreviewDeviceKey() === 'main-kiosk') {
            renderQueuePreview();
        }
        setQueueStatus('Очередь активирована.', false);
        await refreshScreenStatus();
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Не удалось активировать очередь';
        setQueueStatus(message, true);
    } finally {
        setButtonsDisabled(false);
    }
}

function openManualModal() {
    if (!manualModal) return;
    manualModal.classList.add('open');
    manualModal.setAttribute('aria-hidden', 'false');
}

function closeManualModal() {
    if (!manualModal) return;
    manualModal.classList.remove('open');
    manualModal.setAttribute('aria-hidden', 'true');
}
function openHeartbeatAlertModal() {
    if (!heartbeatAlertModal) return;
    if (heartbeatOfflineAcknowledged) return;
    heartbeatAlertModal.classList.add('open');
    heartbeatAlertModal.setAttribute('aria-hidden', 'false');
}
function closeHeartbeatAlertModal(markAcknowledged = false) {
    if (!heartbeatAlertModal) return;
    heartbeatAlertModal.classList.remove('open');
    heartbeatAlertModal.setAttribute('aria-hidden', 'true');
    if (markAcknowledged) {
        heartbeatOfflineAcknowledged = true;
    }
}

async function refreshScreenStatus() {
    try {
        const previewDeviceKey = getPreviewDeviceKey();
        const res = await fetch('/api/screen.php?device_key=' + encodeURIComponent(previewDeviceKey), { cache: 'no-store' });
        const payload = await res.json();
        const source = String(payload?.data?.source || '');
        const templateId = Number(payload?.data?.template?.id || 0);
        const queueState = payload?.data?.queue_state || null;
        const kioskStatus = payload?.data?.kiosk_status || null;
        const isOnline = Boolean(kioskStatus && kioskStatus.is_online === true);
        const ageSec = Number(kioskStatus && kioskStatus.age_sec !== null ? kioskStatus.age_sec : -1);
        markCurrentQueueTemplate(templateId, source);
        setQueueProgress(queueState, source);
        setModeButtons(source);
        if (!isOnline) {
            if (heartbeatWasOnline) {
                heartbeatOfflineAcknowledged = false;
            }
            heartbeatWasOnline = false;
            openHeartbeatAlertModal();
            setScreenStatus('Киоск офлайн (нет связи)', true);
            return;
        }
        heartbeatWasOnline = true;
        heartbeatOfflineAcknowledged = false;
        closeHeartbeatAlertModal(false);
        const ageText = ageSec >= 0 ? (' • ' + ageSec + ' сек назад') : '';
        if (source === 'schedule') {
            setScreenStatus((previewDeviceKey === 'test-kiosk' ? 'Тестовый киоск онлайн' : 'Киоск онлайн') + ageText, false);
        } else if (source === 'fallback') {
            setScreenStatus((previewDeviceKey === 'test-kiosk' ? 'Тестовый киоск онлайн, очередь остановлена' : 'Киоск онлайн, очередь остановлена') + ageText, true);
        } else if (source === 'manual') {
            setScreenStatus((previewDeviceKey === 'test-kiosk' ? 'Тестовый киоск онлайн, ручной режим' : 'Киоск онлайн, ручной режим') + ageText, false);
        } else {
            setScreenStatus('Киоск онлайн' + ageText, false);
        }
    } catch (error) {
        markCurrentQueueTemplate(0, '');
        setQueueProgress(null, '');
        setModeButtons('');
        heartbeatWasOnline = true;
        heartbeatOfflineAcknowledged = false;
        closeHeartbeatAlertModal(false);
        setScreenStatus('Нет связи с экраном', true);
    }
}

async function postQueueAction(url, successMessage) {
    setButtonsDisabled(true);
    setQueueStatus('Отправка команды...', false);

    try {
        const body = new URLSearchParams();
        body.set('device_key', getPreviewDeviceKey());

        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body
        });
        const data = await res.json();
        if (!res.ok || !data || data.ok !== true) {
            throw new Error((data && data.error) ? String(data.error) : 'Не удалось выполнить команду');
        }

        setQueueStatus(successMessage, false);
        await refreshScreenStatus();
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Не удалось выполнить команду';
        setQueueStatus(message, true);
    } finally {
        setButtonsDisabled(false);
    }
}

if (queueStartBtn) {
    queueStartBtn.addEventListener('click', () => {
        postQueueAction('/api/admin_queue_start.php', 'Очередь показа запущена.');
    });
}


if (queueActivateBtn) {
    queueActivateBtn.addEventListener('click', activateSelectedQueue);
}

if (queueStopBtn) {
    queueStopBtn.addEventListener('click', () => {
        postQueueAction('/api/admin_queue_stop.php', 'Очередь показа остановлена.');
    });
}

if (queueManualBtn) {
    queueManualBtn.addEventListener('click', openManualModal);
}

if (manualModalClose) {
    manualModalClose.addEventListener('click', closeManualModal);
}

if (manualModal) {
    manualModal.addEventListener('click', (event) => {
        if (event.target === manualModal) {
            closeManualModal();
        }
    });
}
if (heartbeatAlertOkBtn) {
    heartbeatAlertOkBtn.addEventListener('click', () => {
        closeHeartbeatAlertModal(true);
    });
}
if (heartbeatAlertModal) {
    heartbeatAlertModal.addEventListener('click', (event) => {
        if (event.target === heartbeatAlertModal) {
            closeHeartbeatAlertModal(true);
        }
    });
}

if (previewScreenSelect) {
    previewScreenSelect.addEventListener('change', () => {
        renderQueuePreview();
        syncMirrorFrame();
        refreshScreenStatus();
    });
}

showTemplateButtons.forEach((button) => {
    button.addEventListener('click', async () => {
        const templateId = Number(button.dataset.templateId || 0);
        if (templateId <= 0) {
            setQueueStatus('Некорректный шаблон для показа.', true);
            return;
        }

        button.disabled = true;
        setButtonsDisabled(true);
        setQueueStatus('Включение ручного режима...', false);

        try {
            const body = new URLSearchParams();
            body.set('device_key', getPreviewDeviceKey());
            body.set('template_id', String(templateId));

            const res = await fetch('/api/admin_show_template.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body
            });
            const data = await res.json();
            if (!res.ok || !data || data.ok !== true) {
                throw new Error((data && data.error) ? String(data.error) : 'Не удалось включить ручной режим');
            }

            closeManualModal();
            setQueueStatus('Ручной режим включён. Шаблон показан постоянно.', false);
            await refreshScreenStatus();
        } catch (error) {
            const message = error instanceof Error ? error.message : 'Не удалось включить ручной режим';
            setQueueStatus(message, true);
        } finally {
            button.disabled = false;
            setButtonsDisabled(false);
        }
    });
});

renderQueuePreview();
updateQueueSelectValue(<?= (int)($activeQueue['id'] ?? 0) ?>);
syncMirrorFrame();
refreshScreenStatus();
screenStatusTimer = window.setInterval(refreshScreenStatus, 5000);
  window.addEventListener('beforeunload', () => {
      if (screenStatusTimer !== null) {
          window.clearInterval(screenStatusTimer);
      }
  });
</script>
</body>
</html>
