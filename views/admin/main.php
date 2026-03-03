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
        .navRow .btn,
        .navRow .btnGhost {
            min-width: 160px;
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
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid var(--line);
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
        .queueListItem:last-child {
            border-bottom: 0;
        }
        .queueListItem.current {
            background: #ecfdf3;
            box-shadow: inset 4px 0 0 #16a34a;
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
            margin: 0 0 0 auto;
            align-self: center;
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
            justify-content: center;
            align-items: flex-start;
            padding: 18px;
            min-height: 0;
        }
        .mirrorFrame {
            position: relative;
            width: min(960px, 100%);
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
            .navRow .btn,
            .navRow .btnGhost {
                width: 100%;
                min-width: 0;
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

        <a class="btn" href="/queue/">Настройка очереди</a>
        <div class="navNote">Навигация по основным разделам панели.</div>
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
                <div class="queueActions">
                    <button id="queueStartBtn" class="btnGhost" type="button">Старт</button>
                    <button id="queueStopBtn" class="btnGhost" type="button">Стоп</button>
                    <button id="queueManualBtn" class="btnGhost" type="button">Ручной</button>
                </div>
                <div id="queueStatus" class="queueStatus"></div>
                <div class="queueListBox">
                    <div class="queueListHead">
                        <strong><?= h((string)($activeQueue['name'] ?? 'Активная очередь')) ?></strong>
                        <span>Шаблоны активной очереди</span>
                    </div>
                    <?php if (count($activeQueueItems) > 0): ?>
                        <div class="queueList" id="activeQueueList">
                            <?php foreach ($activeQueueItems as $index => $queueItem): ?>
                                <?php
                                $templateId = (int)($queueItem['template_id'] ?? 0);
                                $isCurrentQueueItem = $currentScreenSource === 'schedule' && $currentScreenTemplateId > 0 && $currentScreenTemplateId === $templateId;
                                ?>
                                <div class="queueListItem<?= $isCurrentQueueItem ? ' current' : '' ?>" data-template-id="<?= $templateId ?>">
                                    <p class="queueListName"><?= h((string)($queueItem['template_name'] ?? 'Шаблон')) ?></p>
                                    <p class="queueListMeta">
                                        Позиция: <?= $index + 1 ?> • Время показа: <?= max(1, (int)($queueItem['duration_sec'] ?? 1)) ?> сек
                                    </p>
                                    <div class="queueProgress"><div class="queueProgressBar"></div></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="queueEmpty">Активная очередь пока пуста.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panelHead">
                <div>
                    <h2>Экран</h2>
                    <p>Дублирование текущего состояния экрана в реальном времени.</p>
                </div>
                <p id="screenStatus" class="status"></p>
            </div>
            <div class="mirrorPanelBody">
                <div class="mirrorFrame">
                    <iframe class="mirror" src="/kiosk/" title="Экран"></iframe>
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

<script>
const queueStatus = document.getElementById('queueStatus');
const screenStatus = document.getElementById('screenStatus');
const queueStartBtn = document.getElementById('queueStartBtn');
const queueStopBtn = document.getElementById('queueStopBtn');
const queueManualBtn = document.getElementById('queueManualBtn');
const manualModal = document.getElementById('manualModal');
const manualModalClose = document.getElementById('manualModalClose');
const showTemplateButtons = Array.from(document.querySelectorAll('.js-show-template'));
const activeQueueListItems = Array.from(document.querySelectorAll('#activeQueueList .queueListItem'));
let screenStatusTimer = null;
let queueProgressTimer = null;
let currentQueueProgressState = null;
let currentQueueProgressStartedAtMs = 0;

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

function stopQueueProgressTimer() {
    if (queueProgressTimer !== null) {
        window.clearInterval(queueProgressTimer);
        queueProgressTimer = null;
    }
}

function paintQueueProgress() {
    const state = currentQueueProgressState;
    activeQueueListItems.forEach((node) => {
        const bar = node.querySelector('.queueProgressBar');
        if (!bar) return;
        const nodeTemplateId = Number(node.dataset.templateId || 0);
        if (!state || nodeTemplateId !== Number(state.current_template_id || 0)) {
            bar.style.width = '0%';
            return;
        }
        const durationSec = Math.max(1, Number(state.duration_sec || 1));
        const elapsedBase = Math.max(0, Number(state.elapsed_sec || 0));
        const extraElapsed = Math.max(0, (Date.now() - currentQueueProgressStartedAtMs) / 1000);
        const progress = Math.max(0, Math.min(100, ((elapsedBase + extraElapsed) / durationSec) * 100));
        bar.style.width = progress + '%';
    });
}

function setQueueProgress(queueState, source) {
    stopQueueProgressTimer();
    currentQueueProgressState = null;
    currentQueueProgressStartedAtMs = 0;
    if (source !== 'schedule' || !queueState || Number(queueState.current_template_id || 0) <= 0) {
        paintQueueProgress();
        return;
    }
    currentQueueProgressState = queueState;
    currentQueueProgressStartedAtMs = Date.now();
    paintQueueProgress();
    queueProgressTimer = window.setInterval(paintQueueProgress, 200);
}

function markCurrentQueueTemplate(templateId, source) {
    activeQueueListItems.forEach((node) => {
        const nodeTemplateId = Number(node.dataset.templateId || 0);
        const isCurrent = source === 'schedule' && templateId > 0 && nodeTemplateId === templateId;
        node.classList.toggle('current', isCurrent);
    });
}

function setButtonsDisabled(disabled) {
    [queueStartBtn, queueStopBtn, queueManualBtn].forEach((button) => {
        if (button) button.disabled = disabled;
    });
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

async function refreshScreenStatus() {
    try {
        const res = await fetch('/api/screen.php?screen_id=1', { cache: 'no-store' });
        const payload = await res.json();
        const source = String(payload?.data?.source || '');
        const templateId = Number(payload?.data?.template?.id || 0);
        const queueState = payload?.data?.queue_state || null;
        markCurrentQueueTemplate(templateId, source);
        setQueueProgress(queueState, source);
        setModeButtons(source);
        if (source === 'schedule') {
            setScreenStatus('Очередь работает', false);
        } else if (source === 'fallback') {
            setScreenStatus('Очередь остановлена', true);
        } else if (source === 'manual') {
            setScreenStatus('Ручной режим', false);
        } else {
            setScreenStatus('', false);
        }
    } catch (error) {
        markCurrentQueueTemplate(0, '');
        setQueueProgress(null, '');
        setModeButtons('');
        setScreenStatus('Нет связи с экраном', true);
    }
}

async function postQueueAction(url, successMessage) {
    setButtonsDisabled(true);
    setQueueStatus('Отправка команды...', false);

    try {
        const body = new URLSearchParams();
        body.set('screen_id', '1');

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
            body.set('screen_id', '1');
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

refreshScreenStatus();
screenStatusTimer = window.setInterval(refreshScreenStatus, 5000);
  window.addEventListener('beforeunload', () => {
      if (screenStatusTimer !== null) {
          window.clearInterval(screenStatusTimer);
      }
      stopQueueProgressTimer();
  });
</script>
</body>
</html>
