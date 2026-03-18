<?php
declare(strict_types=1);

$userName = (string)($currentUser['full_name'] ?? $currentUser['login'] ?? '');
$roleName = authRoleLabel((string)($currentUser['role_code'] ?? ''));
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'Настройка проекта') ?></title>
    <style>
        :root {
            --bg: #eef3f8;
            --panel: #ffffff;
            --line: #d6dee8;
            --text: #1f2937;
            --muted: #667085;
            --accent: #0f6cbd;
            --danger: #b42318;
        }
        * { box-sizing: border-box; }
        html, body {
            height: 100%;
            overflow: hidden;
        }
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
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
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
        .actionLink,
        .btn,
        .btnGhost,
        .tabBtn {
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
        .actionLink,
        .btn {
            border: 1px solid #0f6cbd;
            background: #0f6cbd;
            color: #fff;
        }
        .btnGhost,
        .tabBtn {
            border: 1px solid #b8c3cf;
            background: #fff;
            color: var(--text);
        }
        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
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
            max-height: calc(100vh - 170px);
            display: flex;
            flex-direction: column;
        }
        .panelHead {
            display: flex;
            align-items: center;
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
        .tabs {
            display: flex;
            gap: 8px;
            padding: 14px 18px 0;
            border-bottom: 1px solid var(--line);
            background: rgba(255,255,255,0.92);
        }
        .tabBtn {
            min-height: 34px;
            padding: 0 12px;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }
        .tabBtn.active {
            border-color: #0f6cbd;
            color: #0f6cbd;
            background: #eef5ff;
        }
        .tabPane {
            padding: 18px;
            display: none;
            overflow: hidden;
        }
        .tabPane.active {
            display: block;
        }
        .settingsGrid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }
        .settingsGrid label,
        .doctorFormGrid label {
            display: grid;
            gap: 6px;
            color: var(--muted);
            font-size: 12px;
        }
        input,
        select {
            width: 100%;
            box-sizing: border-box;
            padding: 8px 10px;
            border: 1px solid #c8ced6;
            border-radius: 10px;
            font: inherit;
        }
        .settingsActions,
        .doctorActions {
            margin-top: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .statusText {
            min-height: 24px;
            color: var(--muted);
            font-size: 13px;
        }
        .doctorFormGrid {
            display: grid;
            grid-template-columns: 220px 1fr 160px;
            gap: 12px;
            align-items: end;
        }
        .doctorListWrap {
            margin-top: 16px;
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            max-height: calc(100vh - 430px);
            overflow-y: auto;
        }
        .doctorTable {
            width: 100%;
            border-collapse: collapse;
        }
        .doctorTable th,
        .doctorTable td {
            padding: 10px;
            border-bottom: 1px solid var(--line);
            font-size: 13px;
            text-align: left;
            vertical-align: top;
        }
        .doctorTable th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
        }
        .doctorTable tr:last-child td {
            border-bottom: 0;
        }
        .doctorTable .inactiveRow {
            opacity: 0.72;
            background: #fafafa;
        }
        .doctorCellActions {
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
        }
        .miniBtn {
            width: 30px;
            min-width: 30px;
            height: 30px;
            min-height: 30px;
            padding: 0;
            border-radius: 8px;
            border: 1px solid #c8ced6;
            background: #fff;
            cursor: pointer;
            font: inherit;
            font-size: 15px;
            line-height: 1;
        }
        .miniBtn.edit {
            border-color: #0f6cbd;
            color: #0f6cbd;
        }
        .miniBtn.delete {
            border-color: #f1a5a0;
            color: var(--danger);
            background: #fff5f5;
        }
        .modalBack {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 70;
        }
        .modalCard {
            width: min(420px, calc(100vw - 24px));
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.22);
        }
        .modalCard h3 {
            margin: 0 0 10px;
            font-size: 18px;
        }
        .modalCard p {
            margin: 0 0 14px;
            color: #475569;
            font-size: 14px;
        }
        .modalActions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .pageFooter {
            margin-top: 18px;
            color: #fff;
            font-size: 12px;
            border-top: 1px solid rgba(255,255,255,0.16);
            padding-top: 10px;
        }
        @media (max-width: 920px) {
            .settingsGrid {
                grid-template-columns: 1fr;
            }
            .doctorFormGrid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 760px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <div>
            <h1>Настройка проекта</h1>
            <p>Общие параметры экрана и справочники проекта.</p>
        </div>
        <div class="topbarActions">
            <div class="topbarUser">
                <strong><?= h($userName) ?></strong>
                <span><?= h($roleName) ?></span>
            </div>
            <a class="actionLink" href="/admin/">Панель</a>
            <a class="actionLink" href="/logout/">Выйти</a>
        </div>
    </div>

    <section class="layout">
        <div class="panel">
            <div class="panelHead">
                <div>
                    <h2>Настройка проекта</h2>
                    <p>Выберите раздел и сохраните изменения.</p>
                </div>
            </div>

            <div class="tabs">
                <button id="tabKioskBtn" class="tabBtn active" type="button">Экран киоска</button>
                <button id="tabDoctorsBtn" class="tabBtn" type="button">Список докторов</button>
            </div>

            <div id="tabKiosk" class="tabPane active">
                <div class="settingsGrid">
                    <label>
                        Ширина, px
                        <input id="kioskWidthPx" type="number" min="640" max="7680" step="1" value="<?= (int)($kioskDisplaySettings['kiosk_width_px'] ?? 1920) ?>">
                    </label>
                    <label>
                        Высота, px
                        <input id="kioskHeightPx" type="number" min="360" max="4320" step="1" value="<?= (int)($kioskDisplaySettings['kiosk_height_px'] ?? 1080) ?>">
                    </label>
                    <label>
                        HTML tune, %
                        <input id="htmlTemplatePreviewTunePct" type="number" min="25" max="400" step="1" value="<?= (int)($kioskDisplaySettings['html_template_preview_tune_pct'] ?? 100) ?>">
                    </label>
                    <label>
                        Кеширование на стороне клиента
                        <select id="clientMediaCacheEnabled">
                            <option value="1" <?= ((int)($kioskDisplaySettings['client_media_cache_enabled'] ?? 1) === 1) ? 'selected' : '' ?>>Вкл</option>
                            <option value="0" <?= ((int)($kioskDisplaySettings['client_media_cache_enabled'] ?? 1) === 0) ? 'selected' : '' ?>>Выкл</option>
                        </select>
                    </label>
                </div>
                <div class="settingsActions">
                    <button id="saveKioskSettingsBtn" class="btnGhost" type="button">Сохранить</button>
                    <div id="kioskSettingsStatus" class="statusText"></div>
                </div>
            </div>

            <div id="tabDoctors" class="tabPane">
                <input id="doctorRowId" type="hidden" value="0">
                <div class="doctorFormGrid">
                    <label>
                        ID доктора
                        <input id="doctorExternalId" type="number" min="1" step="1" placeholder="1001">
                    </label>
                    <label>
                        ФИО доктора
                        <input id="doctorFullName" type="text" maxlength="255" placeholder="Иванов Иван Иванович">
                    </label>
                    <label>
                        Статус
                        <select id="doctorIsActive">
                            <option value="1">Активный</option>
                            <option value="0">Неактивный</option>
                        </select>
                    </label>
                </div>
                <div class="doctorActions">
                    <button id="doctorSaveBtn" class="btnGhost" type="button">Сохранить</button>
                    <button id="doctorResetBtn" class="btnGhost" type="button">Новый</button>
                    <div id="doctorStatus" class="statusText"></div>
                </div>

                <div class="doctorListWrap">
                    <table class="doctorTable">
                        <thead>
                        <tr>
                            <th style="width:70px;">ID</th>
                            <th>ФИО</th>
                            <th style="width:140px;">Статус</th>
                            <th style="width:110px;">Действия</th>
                        </tr>
                        </thead>
                        <tbody id="doctorTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <div id="doctorDeleteModal" class="modalBack">
        <div class="modalCard">
            <h3>Удаление доктора</h3>
            <p id="doctorDeleteText">Удалить этого доктора?</p>
            <div class="modalActions">
                <button id="doctorDeleteCancelBtn" class="btnGhost" type="button">Отмена</button>
                <button id="doctorDeleteConfirmBtn" class="btnGhost" type="button">Удалить</button>
            </div>
        </div>
    </div>

    <div class="pageFooter">Версия проекта: <strong><?= h($projectVersion ?? '0.0.0-dev') ?></strong></div>
</div>

<script>
const tabKioskBtn = document.getElementById('tabKioskBtn');
const tabDoctorsBtn = document.getElementById('tabDoctorsBtn');
const tabKiosk = document.getElementById('tabKiosk');
const tabDoctors = document.getElementById('tabDoctors');

const kioskWidthPxInput = document.getElementById('kioskWidthPx');
const kioskHeightPxInput = document.getElementById('kioskHeightPx');
const htmlTemplatePreviewTunePctInput = document.getElementById('htmlTemplatePreviewTunePct');
const clientMediaCacheEnabledInput = document.getElementById('clientMediaCacheEnabled');
const saveKioskSettingsBtn = document.getElementById('saveKioskSettingsBtn');
const kioskSettingsStatus = document.getElementById('kioskSettingsStatus');

const doctorRowIdInput = document.getElementById('doctorRowId');
const doctorExternalIdInput = document.getElementById('doctorExternalId');
const doctorFullNameInput = document.getElementById('doctorFullName');
const doctorIsActiveInput = document.getElementById('doctorIsActive');
const doctorSaveBtn = document.getElementById('doctorSaveBtn');
const doctorResetBtn = document.getElementById('doctorResetBtn');
const doctorStatus = document.getElementById('doctorStatus');
const doctorTableBody = document.getElementById('doctorTableBody');
const doctorDeleteModal = document.getElementById('doctorDeleteModal');
const doctorDeleteText = document.getElementById('doctorDeleteText');
const doctorDeleteCancelBtn = document.getElementById('doctorDeleteCancelBtn');
const doctorDeleteConfirmBtn = document.getElementById('doctorDeleteConfirmBtn');
let pendingDeleteDoctorRowId = 0;

function switchTab(tabId) {
    const isKiosk = tabId === 'kiosk';
    tabKioskBtn.classList.toggle('active', isKiosk);
    tabDoctorsBtn.classList.toggle('active', !isKiosk);
    tabKiosk.classList.toggle('active', isKiosk);
    tabDoctors.classList.toggle('active', !isKiosk);
}

function setKioskSettingsStatus(message, isError) {
    if (!kioskSettingsStatus) return;
    kioskSettingsStatus.textContent = String(message || '');
    kioskSettingsStatus.style.color = isError ? '#b42318' : '#667085';
}

function setDoctorStatus(message, isError) {
    if (!doctorStatus) return;
    doctorStatus.textContent = String(message || '');
    doctorStatus.style.color = isError ? '#b42318' : '#667085';
}

function applyKioskSettingsInputs(settings) {
    if (!settings || typeof settings !== 'object') return;
    if (kioskWidthPxInput) kioskWidthPxInput.value = String(settings.kiosk_width_px || 1920);
    if (kioskHeightPxInput) kioskHeightPxInput.value = String(settings.kiosk_height_px || 1080);
    if (htmlTemplatePreviewTunePctInput) htmlTemplatePreviewTunePctInput.value = String(settings.html_template_preview_tune_pct || 100);
    if (clientMediaCacheEnabledInput) clientMediaCacheEnabledInput.value = String(Number(settings.client_media_cache_enabled || 0) === 1 ? 1 : 0);
}

function doctorResetForm() {
    if (doctorRowIdInput) doctorRowIdInput.value = '0';
    if (doctorExternalIdInput) doctorExternalIdInput.value = '';
    if (doctorFullNameInput) doctorFullNameInput.value = '';
    if (doctorIsActiveInput) doctorIsActiveInput.value = '1';
}

function escapeHtml(text) {
    return String(text || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderDoctorRows(items) {
    if (!doctorTableBody) return;
    const rows = Array.isArray(items) ? items : [];
    if (rows.length === 0) {
        doctorTableBody.innerHTML = '<tr><td colspan="4">Список докторов пуст.</td></tr>';
        return;
    }

    doctorTableBody.innerHTML = rows.map((row) => {
        const rowId = Number(row.id || 0);
        const doctorId = Number(row.doctor_id || 0);
        const fullName = escapeHtml(row.full_name || '');
        const isActive = Number(row.is_active || 0) === 1;
        const statusText = isActive ? 'Активный' : 'Неактивный';
        return '<tr class="' + (isActive ? '' : 'inactiveRow') + '">' +
            '<td>' + doctorId + '</td>' +
            '<td>' + fullName + '</td>' +
            '<td>' + statusText + '</td>' +
            '<td><div class="doctorCellActions">' +
            '<button type="button" class="miniBtn edit" title="Изменить" data-action="edit" data-row-id="' + rowId + '" data-doctor-id="' + doctorId + '" data-full-name="' + fullName + '" data-active="' + (isActive ? '1' : '0') + '">✎</button>' +
            '<button type="button" class="miniBtn delete" title="Удалить" data-action="delete" data-row-id="' + rowId + '" data-doctor-id="' + doctorId + '">✕</button>' +
            '</div></td>' +
            '</tr>';
    }).join('');
}

async function fetchJson(url, options) {
    const res = await fetch(url, options || {});
    const payload = await res.json();
    if (!res.ok || !payload || payload.ok !== true) {
        throw new Error((payload && payload.error) ? String(payload.error) : 'Ошибка запроса');
    }
    return payload.data || {};
}

async function saveKioskSettings() {
    if (!kioskWidthPxInput || !kioskHeightPxInput || !htmlTemplatePreviewTunePctInput || !clientMediaCacheEnabledInput || !saveKioskSettingsBtn) return;
    saveKioskSettingsBtn.disabled = true;
    setKioskSettingsStatus('Сохранение...', false);
    try {
        const body = new URLSearchParams();
        body.set('kiosk_width_px', String(kioskWidthPxInput.value || '1920'));
        body.set('kiosk_height_px', String(kioskHeightPxInput.value || '1080'));
        body.set('html_template_preview_tune_pct', String(htmlTemplatePreviewTunePctInput.value || '100'));
        body.set('client_media_cache_enabled', String(clientMediaCacheEnabledInput.value === '0' ? '0' : '1'));
        const data = await fetchJson('/api/app_settings_save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body
        });
        applyKioskSettingsInputs(data);
        setKioskSettingsStatus('Настройки экрана сохранены.', false);
    } catch (error) {
        setKioskSettingsStatus(error instanceof Error ? error.message : 'Не удалось сохранить настройки экрана', true);
    } finally {
        saveKioskSettingsBtn.disabled = false;
    }
}

async function loadDoctors() {
    setDoctorStatus('Загрузка списка докторов...', false);
    try {
        const data = await fetchJson('/api/doctor_manage_list.php');
        const items = Array.isArray(data.items) ? data.items : [];
        renderDoctorRows(items);
        setDoctorStatus('Список докторов загружен.', false);
    } catch (error) {
        setDoctorStatus(error instanceof Error ? error.message : 'Не удалось загрузить список докторов', true);
    }
}

async function saveDoctor() {
    if (!doctorSaveBtn || !doctorExternalIdInput || !doctorFullNameInput || !doctorIsActiveInput || !doctorRowIdInput) return;

    const doctorExternalId = Number(doctorExternalIdInput.value || 0);
    const fullName = String(doctorFullNameInput.value || '').trim();
    if (doctorExternalId <= 0) {
        setDoctorStatus('Введите корректный ID доктора.', true);
        return;
    }
    if (fullName === '') {
        setDoctorStatus('Введите ФИО врача.', true);
        return;
    }

    doctorSaveBtn.disabled = true;
    setDoctorStatus('Сохранение врача...', false);
    try {
        const body = new URLSearchParams();
        body.set('row_id', String(doctorRowIdInput.value || '0'));
        body.set('doctor_id', String(doctorExternalId));
        body.set('full_name', fullName);
        body.set('is_active', String(doctorIsActiveInput.value || '1'));
        await fetchJson('/api/doctor_manage_save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body
        });
        doctorResetForm();
        await loadDoctors();
        setDoctorStatus('Доктор сохранен.', false);
    } catch (error) {
        setDoctorStatus(error instanceof Error ? error.message : 'Не удалось сохранить доктора', true);
    } finally {
        doctorSaveBtn.disabled = false;
    }
}

function fillDoctorFormFromButton(button) {
    if (!button || !doctorRowIdInput || !doctorExternalIdInput || !doctorFullNameInput || !doctorIsActiveInput) return;
    doctorRowIdInput.value = String(button.dataset.rowId || '0');
    doctorExternalIdInput.value = String(button.dataset.doctorId || '');
    doctorFullNameInput.value = String(button.dataset.fullName || '');
    doctorIsActiveInput.value = String(button.dataset.active || '1') === '1' ? '1' : '0';
}

function openDoctorDeleteModal(rowId, doctorId) {
    if (!doctorDeleteModal) return;
    pendingDeleteDoctorRowId = Number(rowId || 0);
    if (doctorDeleteText) {
        doctorDeleteText.textContent = 'Удалить доктора с ID ' + String(Number(doctorId || 0)) + '?';
    }
    doctorDeleteModal.style.display = 'flex';
}

function closeDoctorDeleteModal() {
    pendingDeleteDoctorRowId = 0;
    if (!doctorDeleteModal) return;
    doctorDeleteModal.style.display = 'none';
}

async function deactivateDoctor(rowId) {
    if (!rowId || rowId <= 0) return;
    setDoctorStatus('Удаление доктора...', false);
    try {
        const body = new URLSearchParams();
        body.set('row_id', String(rowId));
        await fetchJson('/api/doctor_manage_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body
        });
        await loadDoctors();
        setDoctorStatus('Доктор удален.', false);
    } catch (error) {
        setDoctorStatus(error instanceof Error ? error.message : 'Не удалось удалить доктора', true);
    }
}

if (tabKioskBtn) tabKioskBtn.addEventListener('click', () => switchTab('kiosk'));
if (tabDoctorsBtn) tabDoctorsBtn.addEventListener('click', () => switchTab('doctors'));
if (saveKioskSettingsBtn) saveKioskSettingsBtn.addEventListener('click', saveKioskSettings);
if (doctorSaveBtn) doctorSaveBtn.addEventListener('click', saveDoctor);
if (doctorResetBtn) doctorResetBtn.addEventListener('click', () => {
    doctorResetForm();
    setDoctorStatus('Форма очищена.', false);
});
if (doctorTableBody) {
    doctorTableBody.addEventListener('click', async (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        const action = String(target.dataset.action || '');
        const rowId = Number(target.dataset.rowId || 0);
        if (action === 'edit') {
            fillDoctorFormFromButton(target);
            setDoctorStatus('Запись доктора загружена в форму.', false);
            return;
        }
        if (action === 'delete') {
            openDoctorDeleteModal(rowId, Number(target.dataset.doctorId || 0));
        }
    });
}
if (doctorDeleteCancelBtn) {
    doctorDeleteCancelBtn.addEventListener('click', closeDoctorDeleteModal);
}
if (doctorDeleteConfirmBtn) {
    doctorDeleteConfirmBtn.addEventListener('click', async () => {
        const rowId = pendingDeleteDoctorRowId;
        closeDoctorDeleteModal();
        if (rowId > 0) {
            await deactivateDoctor(rowId);
        }
    });
}
if (doctorDeleteModal) {
    doctorDeleteModal.addEventListener('click', (event) => {
        if (event.target === doctorDeleteModal) {
            closeDoctorDeleteModal();
        }
    });
}

(async function boot() {
    switchTab('kiosk');
    await loadDoctors();
})();
</script>
</body>
</html>
