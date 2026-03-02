<?php
declare(strict_types=1);

$userName = (string)($currentUser['full_name'] ?? $currentUser['login'] ?? '');
$roleName = authRoleLabel((string)($currentUser['role_code'] ?? ''));
$activeQueueName = (string)($activeQueue['name'] ?? 'Активная очередь');
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'Очередь показа') ?></title>
    <style>
        html, body { height: 100%; }
        body { margin: 0; min-height: 100vh; font-family: Tahoma, sans-serif; background: linear-gradient(180deg, #334155 0%, #475569 100%); color: #1a1a1a; }
        .page { max-width: 1600px; margin: 0 auto; padding: 22px; min-height: 100vh; display: flex; flex-direction: column; box-sizing: border-box; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
        .topbar h1 { margin: 0 0 6px; font-size: 32px; color: #fff; }
        .topbar p { margin: 0; color: #cbd5e1; font-size: 13px; }
        .topbarActions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .wrap { flex: 1; min-height: 0; display: grid; grid-template-columns: 300px minmax(0, 1fr) 360px; gap: 12px; }
        .panel { background: #fff; border: 1px solid #d7dbe0; border-radius: 16px; padding: 10px; display: flex; flex-direction: column; min-height: 0; }
        .panel h2 { margin: 0 0 10px; font-size: 16px; }
        .panelSub { margin: -2px 0 10px; color: #5a6472; font-size: 12px; }
        .toolbar { display: flex; gap: 8px; margin-bottom: 8px; flex-wrap: wrap; }
        .toolbar .status { margin-left: auto; }
        .queueHeaderBar { display: grid; grid-template-columns: 132px minmax(0, 1fr); gap: 10px; margin-bottom: 8px; align-items: start; }
        .queueHeaderBar .toolbar { margin: 0; align-items: center; }
        .queueHeaderBarRight { display: flex; align-items: center; }
        .list { flex: 1; min-height: 0; overflow: auto; border: 1px solid #e0e3e8; border-radius: 10px; background: #fff; }
        .item { padding: 8px 9px; border-bottom: 1px solid #eceff3; cursor: pointer; font-size: 12px; user-select: none; background: #fff; }
        .item:last-child { border-bottom: 0; }
        .item:hover { background: #f8fafc; }
        .item.active { background: #e8f2ff; }
        .item.dragging { opacity: 0.45; }
        .item.dropBefore { box-shadow: inset 0 2px 0 #1d5fbf; }
        .item.dropAfter { box-shadow: inset 0 -2px 0 #1d5fbf; }
        .itemTitle { margin: 0; font-size: 13px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .itemMeta { margin: 4px 0 0; color: #5a6472; font-size: 11px; line-height: 1.35; }
        .emptyState { padding: 12px; color: #5a6472; font-size: 13px; line-height: 1.45; }
        .queueDropZone { min-height: 180px; }
        .queueDropZone.isOver { background: #eef5ff; }
        .inspectorEmpty { color: #5a6472; font-size: 13px; line-height: 1.45; }
        .fold { border: 1px solid #e0e3e8; border-radius: 10px; background: #fff; margin-bottom: 8px; }
        .fold > summary { cursor: pointer; list-style: none; padding: 8px 10px; font-size: 13px; font-weight: bold; border-bottom: 1px solid transparent; position: relative; padding-right: 26px; background: #f0f0f0; }
        .fold > summary::-webkit-details-marker { display: none; }
        .fold > summary::after { content: '\25B8'; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #5a6472; font-size: 12px; }
        .fold[open] > summary::after { content: '\25BE'; }
        .fold[open] > summary { border-bottom-color: #e0e3e8; }
        .foldBody { padding: 6px 10px 10px; }
        .foldBody label { margin: 8px 0 0; display: grid; grid-template-columns: 140px minmax(0, 1fr); gap: 10px; align-items: center; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; }
        .foldBody label input, .foldBody label select { grid-column: 2; }
        .status { align-self: center; display: none; padding: 6px 10px; border-radius: 10px; border: 1px solid transparent; font-size: 12px; line-height: 1.2; }
        .status.show { display: inline-flex; }
        .status.success { color: #0f5132; background: #d1e7dd; border-color: #badbcc; }
        .status.error { color: #842029; background: #f8d7da; border-color: #f5c2c7; }
        button, a.actionLink, input, select { font: inherit; }
        button, a.actionLink { display: inline-flex; align-items: center; justify-content: center; min-height: 34px; padding: 0 12px; border-radius: 10px; border: 1px solid #1d5fbf; background: transparent; color: #1d5fbf; text-decoration: none; cursor: pointer; }
        a.actionLink { background: #1d5fbf; color: #fff; }
        button.primary { background: transparent; color: #1d5fbf; }
        button.danger { border-color: #b91c1c; color: #b91c1c; }
        button.iconBtn { width: 34px; height: 34px; min-width: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1; }
        input, select { width: 100%; box-sizing: border-box; padding: 6px; border: 1px solid #c8ced6; border-radius: 10px; }
        .pageFooter { margin-top: 18px; color: #fff; font-size: 12px; border-top: 1px solid rgba(255,255,255,0.16); padding-top: 10px; flex: 0 0 auto; }
        @media (min-width: 761px) { html, body { overflow: hidden; } .page { height: 100vh; } }
        @media (max-width: 1180px) { .wrap { grid-template-columns: 280px 1fr; } .panel.inspectorPanel { grid-column: 1 / -1; } }
        @media (max-width: 920px) { .queueHeaderBar { grid-template-columns: 1fr; } }
        @media (max-width: 760px) { .topbar { flex-direction: column; align-items: flex-start; } .topbarActions { width: 100%; } .wrap { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <div>
            <h1>Очередь показа</h1>
            <p>Перетаскивайте рабочие шаблоны в центр и задавайте время показа.</p>
        </div>
        <div class="topbarActions">
            <a class="actionLink" href="/admin/">Панель</a>
            <a class="actionLink" href="/template/">Шаблонизатор</a>
            <a class="actionLink" href="/logout/">Выйти</a>
        </div>
    </div>

    <div class="wrap">
    <section class="panel">
        <h2>Рабочие шаблоны</h2>
        <p class="panelSub">Перетащите шаблон в очередь или дважды кликните по нему.</p>
        <div id="templatePool" class="list">
            <?php if (count($workTemplates) > 0): ?>
                <?php foreach ($workTemplates as $templateRow): ?>
                    <div class="item" draggable="true"
                         data-pool-item="1"
                         data-template-id="<?= (int)($templateRow['id'] ?? 0) ?>"
                         data-template-name="<?= h((string)($templateRow['name'] ?? 'Шаблон')) ?>">
                        <p class="itemTitle"><?= h((string)($templateRow['name'] ?? 'Шаблон')) ?></p>
                        <p class="itemMeta">
                            ID: <?= (int)($templateRow['id'] ?? 0) ?>
                            <?php if (trim((string)($templateRow['updated_at'] ?? '')) !== ''): ?>
                                • Обновлён: <?= h((string)$templateRow['updated_at']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="emptyState">Нет рабочих шаблонов. Сначала переведите нужные шаблоны в статус «рабочий».</div>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <h2>Очередь</h2>
        <div class="queueHeaderBar">
            <div class="toolbar">
                <button id="createQueueBtn" type="button" class="iconBtn" title="Создать очередь" aria-label="Создать очередь">&#x2795;</button>
                <button id="saveQueueBtn" type="button" class="primary iconBtn" title="Сохранить очередь" aria-label="Сохранить очередь">&#x1F4BE;</button>
                <button id="clearQueueBtn" type="button" class="danger iconBtn" title="Очистить очередь" aria-label="Очистить очередь">&#x1F5D1;</button>
            </div>
            <div class="queueHeaderBarRight">
                <select id="queueSelect">
                    <?php foreach ($queues as $queueRow): ?>
                        <option value="<?= (int)$queueRow['id'] ?>" <?= ((int)($queueRow['id'] ?? 0) === (int)($activeQueue['id'] ?? 0)) ? 'selected' : '' ?>>
                            <?= h((string)($queueRow['name'] ?? 'Очередь')) ?><?= ((int)($queueRow['is_active'] ?? 0) === 1) ? ' [активная]' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="toolbar">
            <span id="queueStatus" class="status"></span>
        </div>
        <div id="queueList" class="list queueDropZone"></div>
    </section>

    <section class="panel inspectorPanel">
        <h2>Инспектор</h2>
        <details class="fold" open>
            <summary>Параметры очереди</summary>
            <div class="foldBody">
                <label>Название <input id="queueName" type="text" maxlength="150"></label>
                <label>Активная
                    <select id="queueIsActive">
                        <option value="0">нет</option>
                        <option value="1">да</option>
                    </select>
                </label>
            </div>
        </details>
        <p id="inspectorEmpty" class="inspectorEmpty">Выберите элемент очереди, чтобы настроить время показа.</p>
        <div id="inspectorControls" style="display:none;">
            <details class="fold" open>
                <summary>Параметры элемента очереди</summary>
                <div class="foldBody">
                    <label>Время показа, сек <input id="qDurationSec" type="number" min="1" step="1"></label>
                </div>
            </details>
            <div class="toolbar">
                <button id="removeQueueItemBtn" type="button" class="danger">Удалить из очереди</button>
            </div>
        </div>
    </section>
    </div>

    <div class="pageFooter">Версия проекта: <strong><?= h($projectVersion ?? '0.0.0-dev') ?></strong></div>
</div>

<script>
const DEFAULT_DURATION_SEC = 15;

const state = {
    queueId: <?= (int)($activeQueue['id'] ?? 0) ?>,
    queueName: <?= json_encode((string)($activeQueue['name'] ?? 'Активная очередь'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    queueIsActive: <?= ((int)($activeQueue['is_active'] ?? 0) === 1) ? 'true' : 'false' ?>,
    queue: [],
    selectedId: null,
    drag: null
};

const el = {
    templatePool: document.getElementById('templatePool'),
    queueList: document.getElementById('queueList'),
    queueSelect: document.getElementById('queueSelect'),
    createQueueBtn: document.getElementById('createQueueBtn'),
    queueStatus: document.getElementById('queueStatus'),
    clearQueueBtn: document.getElementById('clearQueueBtn'),
    saveQueueBtn: document.getElementById('saveQueueBtn'),
    queueName: document.getElementById('queueName'),
    queueIsActive: document.getElementById('queueIsActive'),
    inspectorEmpty: document.getElementById('inspectorEmpty'),
    inspectorControls: document.getElementById('inspectorControls'),
    qDurationSec: document.getElementById('qDurationSec'),
    removeQueueItemBtn: document.getElementById('removeQueueItemBtn')
};

function setStatus(message, isError = false) {
    const text = String(message || '').trim();
    if (!text) {
        el.queueStatus.textContent = '';
        el.queueStatus.className = 'status';
        return;
    }
    el.queueStatus.textContent = text;
    el.queueStatus.className = 'status show ' + (isError ? 'error' : 'success');
}

function uniqueQueueId() {
    return 'q_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 8);
}

function queueItemById(id) {
    return state.queue.find((item) => item.id === id) || null;
}

function syncQueueInspector() {
    el.queueName.value = String(state.queueName || '');
    el.queueIsActive.value = state.queueIsActive === true ? '1' : '0';
}

async function refreshQueueList(selectedQueueId = 0) {
    const res = await fetch('/api/queue_list.php', { cache: 'no-store' });
    const payload = await res.json();
    if (!res.ok || !payload || payload.ok !== true) {
        throw new Error((payload && payload.error) ? String(payload.error) : 'Не удалось загрузить список очередей');
    }

    const queues = Array.isArray(payload.data) ? payload.data : [];
    const currentValue = selectedQueueId > 0 ? selectedQueueId : state.queueId;
    el.queueSelect.innerHTML = '';
    queues.forEach((queue) => {
        const option = document.createElement('option');
        option.value = String(queue.id || 0);
        option.textContent = String(queue.name || 'Очередь') + ((Number(queue.is_active || 0) === 1) ? ' [активная]' : '');
        if (Number(queue.id || 0) === Number(currentValue || 0)) {
            option.selected = true;
        }
        el.queueSelect.appendChild(option);
    });
}

async function loadQueue(queueId = 0) {
    try {
        const url = queueId > 0 ? ('/api/queue_get.php?queue_id=' + encodeURIComponent(queueId)) : '/api/queue_get.php';
        const res = await fetch(url, { cache: 'no-store' });
        const payload = await res.json();
        if (!res.ok || !payload || payload.ok !== true) {
            throw new Error((payload && payload.error) ? String(payload.error) : 'Не удалось загрузить очередь');
        }

        const queue = payload.data?.queue || null;
        const items = Array.isArray(payload.data?.items) ? payload.data.items : [];
        state.queueId = Number(queue?.id || 0);
        state.queueName = String(queue?.name || 'Очередь');
        state.queueIsActive = Number(queue?.is_active || 0) === 1;
        state.queue = items.map((item) => ({
            id: uniqueQueueId(),
            template_id: Number(item.template_id || 0),
            template_name: String(item.template_name || 'Шаблон'),
            duration_sec: Math.max(1, Number(item.duration_sec || DEFAULT_DURATION_SEC))
        })).filter((item) => item.template_id > 0);
        state.selectedId = state.queue[0]?.id || null;
        await refreshQueueList(state.queueId);
        syncQueueInspector();
        renderQueue();
        setStatus('');
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Не удалось загрузить очередь';
        setStatus(message, true);
    }
}

function showInspector(item) {
    if (!item) {
        el.inspectorEmpty.style.display = '';
        el.inspectorControls.style.display = 'none';
        el.qDurationSec.value = '';
        return;
    }
    el.inspectorEmpty.style.display = 'none';
    el.inspectorControls.style.display = '';
    el.qDurationSec.value = String(item.duration_sec);
}

function renderQueue() {
    el.queueList.innerHTML = '';

    if (state.queue.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'emptyState';
        empty.textContent = 'Очередь пока пустая. Перетащите сюда рабочие шаблоны из левого списка.';
        el.queueList.appendChild(empty);
        showInspector(null);
        return;
    }

    state.queue.forEach((item, index) => {
        const node = document.createElement('div');
        node.className = 'item' + (state.selectedId === item.id ? ' active' : '');
        node.draggable = true;
        node.dataset.queueItem = '1';
        node.dataset.queueId = item.id;
        node.innerHTML = `
            <p class="itemTitle">${escapeHtml(item.template_name)}</p>
            <p class="itemMeta">Позиция: ${index + 1} • Время показа: ${item.duration_sec} сек</p>
        `;
        node.addEventListener('click', () => {
            state.selectedId = item.id;
            renderQueue();
        });
        node.addEventListener('dragstart', (event) => {
            state.drag = { kind: 'queue', queueId: item.id };
            node.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', item.id);
        });
        node.addEventListener('dragend', () => {
            node.classList.remove('dragging');
            clearDropMarks();
            state.drag = null;
        });
        node.addEventListener('dragover', (event) => {
            event.preventDefault();
            if (!state.drag) return;
            const rect = node.getBoundingClientRect();
            const before = (event.clientY - rect.top) < rect.height / 2;
            clearDropMarks();
            node.classList.add(before ? 'dropBefore' : 'dropAfter');
            node.dataset.dropMode = before ? 'before' : 'after';
            event.dataTransfer.dropEffect = 'move';
        });
        node.addEventListener('dragleave', () => {
            node.classList.remove('dropBefore', 'dropAfter');
            delete node.dataset.dropMode;
        });
        node.addEventListener('drop', (event) => {
            event.preventDefault();
            const mode = node.dataset.dropMode === 'after' ? 'after' : 'before';
            moveDraggedItemTo(item.id, mode);
            clearDropMarks();
        });
        el.queueList.appendChild(node);
    });

    showInspector(queueItemById(state.selectedId));
}

function clearDropMarks() {
    el.queueList.querySelectorAll('.dropBefore, .dropAfter').forEach((node) => {
        node.classList.remove('dropBefore', 'dropAfter');
        delete node.dataset.dropMode;
    });
    el.queueList.classList.remove('isOver');
}

function insertTemplateToQueue(templateId, templateName, atIndex = null) {
    const item = {
        id: uniqueQueueId(),
        template_id: Number(templateId),
        template_name: String(templateName || 'Шаблон'),
        duration_sec: DEFAULT_DURATION_SEC
    };
    if (atIndex === null || atIndex < 0 || atIndex > state.queue.length) {
        state.queue.push(item);
    } else {
        state.queue.splice(atIndex, 0, item);
    }
    state.selectedId = item.id;
    renderQueue();
}

function moveQueueItem(queueId, targetId, mode) {
    const fromIndex = state.queue.findIndex((item) => item.id === queueId);
    const targetIndex = state.queue.findIndex((item) => item.id === targetId);
    if (fromIndex < 0 || targetIndex < 0) return;

    const [moved] = state.queue.splice(fromIndex, 1);
    let insertIndex = targetIndex;
    if (mode === 'after') {
        insertIndex = targetIndex + 1;
    }
    if (fromIndex < targetIndex && mode === 'before') {
        insertIndex -= 1;
    }
    if (fromIndex < targetIndex && mode === 'after') {
        insertIndex -= 1;
    }
    state.queue.splice(insertIndex, 0, moved);
    state.selectedId = moved.id;
    renderQueue();
}

function moveDraggedItemTo(targetId, mode) {
    if (!state.drag) return;
    if (state.drag.kind === 'pool') {
        const targetIndex = state.queue.findIndex((item) => item.id === targetId);
        const insertIndex = mode === 'after' ? targetIndex + 1 : targetIndex;
        insertTemplateToQueue(state.drag.templateId, state.drag.templateName, insertIndex);
    } else if (state.drag.kind === 'queue') {
        moveQueueItem(state.drag.queueId, targetId, mode);
    }
}

async function saveQueueDraft() {
    try {
        const body = new URLSearchParams();
        body.set('queue_id', String(state.queueId || 0));
        body.set('queue_name', String(state.queueName || '').trim());
        body.set('queue_is_active', state.queueIsActive ? '1' : '0');
        body.set('items_json', JSON.stringify(state.queue.map((item) => ({
            template_id: item.template_id,
            duration_sec: item.duration_sec
        }))));

        const res = await fetch('/api/queue_save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body
        });
        const payload = await res.json();
        if (!res.ok || !payload || payload.ok !== true) {
            throw new Error((payload && payload.error) ? String(payload.error) : 'Не удалось сохранить очередь');
        }

        state.queueName = String(payload.data?.queue_name || state.queueName || '');
        state.queueIsActive = Number(payload.data?.queue_is_active || 0) === 1;
        await refreshQueueList(state.queueId);
        syncQueueInspector();
        setStatus('Очередь сохранена в базе данных.', false);
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Не удалось сохранить очередь';
        setStatus(message, true);
    }
}

async function createQueue() {
    try {
        const res = await fetch('/api/queue_create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams()
        });
        const payload = await res.json();
        if (!res.ok || !payload || payload.ok !== true) {
            throw new Error((payload && payload.error) ? String(payload.error) : 'Не удалось создать очередь');
        }

        const queueId = Number(payload.data?.queue_id || 0);
        if (queueId <= 0) {
            throw new Error('Некорректный идентификатор очереди');
        }

        state.queueId = queueId;
        state.queueName = String(payload.data?.name || 'Очередь');
        state.queueIsActive = false;
        state.queue = [];
        state.selectedId = null;
        await refreshQueueList(queueId);
        syncQueueInspector();
        renderQueue();
        setStatus('Новая очередь создана.', false);
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Не удалось создать очередь';
        setStatus(message, true);
    }
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

el.templatePool.querySelectorAll('[data-pool-item="1"]').forEach((node) => {
    const templateId = Number(node.dataset.templateId || 0);
    const templateName = String(node.dataset.templateName || '');

    node.addEventListener('dblclick', () => {
        insertTemplateToQueue(templateId, templateName);
    });
    node.addEventListener('dragstart', (event) => {
        state.drag = { kind: 'pool', templateId, templateName };
        node.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'copyMove';
        event.dataTransfer.setData('text/plain', String(templateId));
    });
    node.addEventListener('dragend', () => {
        node.classList.remove('dragging');
        state.drag = null;
        clearDropMarks();
    });
});

el.queueSelect.addEventListener('change', () => {
    const queueId = Number(el.queueSelect.value || 0);
    loadQueue(queueId);
});

el.createQueueBtn.addEventListener('click', createQueue);
el.queueName.addEventListener('input', () => {
    state.queueName = String(el.queueName.value || '');
});
el.queueIsActive.addEventListener('input', () => {
    state.queueIsActive = String(el.queueIsActive.value || '0') === '1';
});
el.queueIsActive.addEventListener('change', () => {
    state.queueIsActive = String(el.queueIsActive.value || '0') === '1';
});

el.queueList.addEventListener('dragover', (event) => {
    event.preventDefault();
    el.queueList.classList.add('isOver');
    event.dataTransfer.dropEffect = 'move';
});

el.queueList.addEventListener('dragleave', (event) => {
    if (event.target === el.queueList) {
        el.queueList.classList.remove('isOver');
    }
});

el.queueList.addEventListener('drop', (event) => {
    event.preventDefault();
    clearDropMarks();

    if (!state.drag) return;
    if (state.drag.kind === 'pool') {
        insertTemplateToQueue(state.drag.templateId, state.drag.templateName);
    } else if (state.drag.kind === 'queue') {
        const movedIndex = state.queue.findIndex((item) => item.id === state.drag.queueId);
        if (movedIndex >= 0) {
            const [moved] = state.queue.splice(movedIndex, 1);
            state.queue.push(moved);
            state.selectedId = moved.id;
            renderQueue();
        }
    }
});

el.qDurationSec.addEventListener('input', () => {
    const item = queueItemById(state.selectedId);
    if (!item) return;
    item.duration_sec = Math.max(1, Number(el.qDurationSec.value || DEFAULT_DURATION_SEC));
    renderQueue();
});

el.removeQueueItemBtn.addEventListener('click', () => {
    const index = state.queue.findIndex((item) => item.id === state.selectedId);
    if (index < 0) return;
    state.queue.splice(index, 1);
    state.selectedId = state.queue[index]?.id || state.queue[index - 1]?.id || null;
    renderQueue();
    setStatus('Элемент удалён из очереди.', false);
});

el.clearQueueBtn.addEventListener('click', () => {
    state.queue = [];
    state.selectedId = null;
    renderQueue();
    setStatus('Очередь очищена. Для фиксации сохраните изменения.', false);
});

el.saveQueueBtn.addEventListener('click', saveQueueDraft);

syncQueueInspector();
loadQueue();
</script>
</body>
</html>
