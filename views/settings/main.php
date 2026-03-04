<?php
declare(strict_types=1);

$userName = (string)($currentUser['full_name'] ?? $currentUser['login'] ?? '');
$roleName = authRoleLabel((string)($currentUser['role_code'] ?? ''));
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'Настройки проекта') ?></title>
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
        .actionLink,
        .btn {
            border: 1px solid #0f6cbd;
            background: #0f6cbd;
            color: #fff;
        }
        .btnGhost {
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
        .panelBody {
            padding: 18px;
        }
        .settingsGrid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }
        .settingsGrid label {
            display: grid;
            gap: 6px;
            color: var(--muted);
            font-size: 12px;
        }
        input {
            width: 100%;
            box-sizing: border-box;
            padding: 8px 10px;
            border: 1px solid #c8ced6;
            border-radius: 10px;
            font: inherit;
        }
        .settingsActions {
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
        .pageFooter {
            margin-top: 18px;
            color: #fff;
            font-size: 12px;
            border-top: 1px solid rgba(255,255,255,0.16);
            padding-top: 10px;
        }
        @media (max-width: 760px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
            .settingsGrid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <div>
            <h1>Настройки проекта</h1>
            <p>Общие параметры, которые влияют на preview и поведение проекта.</p>
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
                    <h2>Экран киоска</h2>
                    <p>Параметры для расчёта HTML-preview в шаблонизаторе и последующей подстройки под реальный экран.</p>
                </div>
            </div>
            <div class="panelBody">
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
                </div>
                <div class="settingsActions">
                    <button id="saveKioskSettingsBtn" class="btnGhost" type="button">Сохранить</button>
                    <div id="kioskSettingsStatus" class="statusText"></div>
                </div>
            </div>
        </div>
    </section>

    <div class="pageFooter">Версия проекта: <strong><?= h($projectVersion ?? '0.0.0-dev') ?></strong></div>
</div>

<script>
const kioskWidthPxInput = document.getElementById('kioskWidthPx');
const kioskHeightPxInput = document.getElementById('kioskHeightPx');
const htmlTemplatePreviewTunePctInput = document.getElementById('htmlTemplatePreviewTunePct');
const saveKioskSettingsBtn = document.getElementById('saveKioskSettingsBtn');
const kioskSettingsStatus = document.getElementById('kioskSettingsStatus');

function setKioskSettingsStatus(message, isError) {
    if (!kioskSettingsStatus) return;
    kioskSettingsStatus.textContent = String(message || '');
    kioskSettingsStatus.style.color = isError ? '#b42318' : '#667085';
}

function applyKioskSettingsInputs(settings) {
    if (!settings || typeof settings !== 'object') return;
    if (kioskWidthPxInput) kioskWidthPxInput.value = String(settings.kiosk_width_px || 1920);
    if (kioskHeightPxInput) kioskHeightPxInput.value = String(settings.kiosk_height_px || 1080);
    if (htmlTemplatePreviewTunePctInput) htmlTemplatePreviewTunePctInput.value = String(settings.html_template_preview_tune_pct || 100);
}

async function saveKioskSettings() {
    if (!kioskWidthPxInput || !kioskHeightPxInput || !htmlTemplatePreviewTunePctInput || !saveKioskSettingsBtn) {
        return;
    }

    saveKioskSettingsBtn.disabled = true;
    setKioskSettingsStatus('Сохранение...', false);

    try {
        const body = new URLSearchParams();
        body.set('kiosk_width_px', String(kioskWidthPxInput.value || '1920'));
        body.set('kiosk_height_px', String(kioskHeightPxInput.value || '1080'));
        body.set('html_template_preview_tune_pct', String(htmlTemplatePreviewTunePctInput.value || '100'));

        const res = await fetch('/api/app_settings_save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body
        });
        const payload = await res.json();
        if (!res.ok || !payload || payload.ok !== true) {
            throw new Error((payload && payload.error) ? String(payload.error) : 'Не удалось сохранить настройки экрана');
        }

        applyKioskSettingsInputs(payload.data || {});
        setKioskSettingsStatus('Настройки экрана сохранены.', false);
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Не удалось сохранить настройки экрана';
        setKioskSettingsStatus(message, true);
    } finally {
        saveKioskSettingsBtn.disabled = false;
    }
}

if (saveKioskSettingsBtn) {
    saveKioskSettingsBtn.addEventListener('click', saveKioskSettings);
}
</script>
</body>
</html>
