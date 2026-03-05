<?php
declare(strict_types=1);
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <style>
        html, body { margin: 0; width: 100%; height: 100%; overflow: hidden; font-family: Tahoma, sans-serif; background: #334155; color: #1a1a1a; }
        #stage { position: relative; width: 100vw; height: calc(100vh - 32px); }
        .block { position: absolute; box-sizing: border-box; border: 1px solid rgba(26,26,26,0.12); overflow: hidden; padding: 0; }
        @keyframes fadeInBlock { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUpBlock { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideLeftBlock { from { opacity: 0; transform: translateX(18px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes zoomInBlock { from { opacity: 0; transform: scale(0.94); } to { opacity: 1; transform: scale(1); } }
        .title { font-size: 2.2vmin; font-weight: 700; margin: 0 0 0.8vmin 0; line-height: 1.2; }
        .body { font-size: 1.6vmin; margin: 0; line-height: 1.35; opacity: 0.94; }
        .media { width: auto; height: auto; max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 4px; }
        .textRenderContent { width: 100%; height: 100%; box-sizing: border-box; overflow: hidden; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word; font-family: Tahoma, sans-serif; }
        .htmlRenderContent { width: 100%; height: 100%; box-sizing: border-box; overflow: hidden; }
        .htmlRenderContent p,
        .htmlRenderContent h1,
        .htmlRenderContent h2,
        .htmlRenderContent h3,
        .htmlRenderContent h4,
        .htmlRenderContent h5,
        .htmlRenderContent h6,
        .htmlRenderContent ul,
        .htmlRenderContent ol,
        .htmlRenderContent blockquote { margin: 0; }
        .htmlRenderContent ul,
        .htmlRenderContent ol { padding-left: 1.2em; }
        .meta { height: 32px; display: flex; align-items: center; gap: 10px; padding: 0 10px; background: #f3f4f6; font-size: 12px; border-top: 1px solid #d7dbe0; }
        .ok { color: #95d17b; }
        .warn { color: #f1c27d; }
    </style>
</head>
<body>
<div id="stage"></div>
<div class="meta" id="meta">Предпросмотр шаблона</div>
<script>
const stage = document.getElementById('stage');
const meta = document.getElementById('meta');
const url = new URL(window.location.href);
const templateId = Number(url.searchParams.get('template_id') || 0);
const DEFAULT_SCREEN_STYLE = { mode: 'color', color: '#ffffff', image: '', size: 'cover', position: 'center center', repeat: 'no-repeat' };
const SCHEDULE_THEMES = <?= json_encode(array_values($scheduleThemes ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const activeMediaTimers = [];
const pptPreviewCache = new Map();

function clamp(v, min, max) {
    const n = Number(v);
    if (!Number.isFinite(n)) return min;
    return Math.max(min, Math.min(max, n));
}

function parsePdfPageCount(url) {
    const m = String(url || '').match(/[?#&]pages=(\d+)/i);
    if (!m) return 0;
    const n = Number(m[1] || 0);
    return Number.isFinite(n) && n > 0 ? Math.floor(n) : 0;
}
function getPptPreviewUrl(mediaUrl, pageNum) {
    const src = String(mediaUrl || '').trim();
    const m = src.match(/\/uploads\/content_ppt\/([^?#]+)/i);
    if (!m) return '';
    const raw = decodeURIComponent(m[1] || '');
    if (!raw.toLowerCase().endsWith('.pdf')) return '';
    const base = raw.slice(0, -4);
    const safePage = Math.max(1, Math.floor(Number(pageNum || 1)));
    const file = safePage <= 1 ? `${base}.png` : `${base}_${safePage}.png`;
    return '/uploads/content_ppt_preview/' + encodeURIComponent(file);
}
async function getPptPreviewPages(mediaUrl) {
    const src = String(mediaUrl || '').trim();
    if (!src) return [];
    if (pptPreviewCache.has(src)) return pptPreviewCache.get(src);
    try {
        const res = await fetch('/api/content_ppt_probe.php?url=' + encodeURIComponent(src), { cache: 'no-store' });
        const payload = await res.json();
        const pages = payload && payload.ok && payload.data && Array.isArray(payload.data.preview_pages)
            ? payload.data.preview_pages.filter((item) => typeof item === 'string' && item.trim() !== '')
            : [];
        pptPreviewCache.set(src, pages);
        return pages;
    } catch (_) {
        pptPreviewCache.set(src, []);
        return [];
    }
}
function clearMediaTimers() {
    while (activeMediaTimers.length > 0) {
        const id = activeMediaTimers.pop();
        clearTimeout(id);
    }
}
function parseDataJson(value) {
    if (value && typeof value === 'object') return value;
    if (typeof value !== 'string' || value.trim() === '') return null;
    try {
        const parsed = JSON.parse(value);
        return parsed && typeof parsed === 'object' ? parsed : null;
    } catch (_) {
        return null;
    }
}
function normalizeScreenStyle(raw) {
    const src = raw && typeof raw === 'object' ? raw : {};
    const mode = ['none', 'color', 'image'].includes(String(src.mode || '')) ? String(src.mode) : 'color';
    const size = ['cover', 'contain', 'auto'].includes(String(src.size || '')) ? String(src.size) : 'cover';
    const repeat = ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'].includes(String(src.repeat || '')) ? String(src.repeat) : 'no-repeat';
    const positions = ['left top', 'center top', 'right top', 'left center', 'center center', 'right center', 'left bottom', 'center bottom', 'right bottom'];
    const position = positions.includes(String(src.position || '')) ? String(src.position) : 'center center';
    return {
        mode,
        color: String(src.color || '#ffffff').trim() || '#ffffff',
        image: String(src.image || '').trim(),
        size,
        position,
        repeat
    };
}
function normalizeBlockBackground(raw) {
    const src = raw && typeof raw === 'object' ? raw : {};
    const mode = ['none', 'color', 'image'].includes(String(src.background_mode || '')) ? String(src.background_mode) : 'color';
    const size = ['cover', 'contain', 'auto'].includes(String(src.background_size || '')) ? String(src.background_size) : 'cover';
    const repeat = ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'].includes(String(src.background_repeat || '')) ? String(src.background_repeat) : 'no-repeat';
    const positions = ['left top', 'center top', 'right top', 'left center', 'center center', 'right center', 'left bottom', 'center bottom', 'right bottom'];
    const position = positions.includes(String(src.background_position || '')) ? String(src.background_position) : 'center center';
    return {
        background_mode: mode,
        background_color: String(src.background_color || '#ffffff').trim() || '#ffffff',
        background_image: String(src.background_image || '').trim(),
        background_size: size,
        background_position: position,
        background_repeat: repeat,
        animation: ['none', 'fade_in', 'slide_up', 'slide_left', 'zoom_in'].includes(String(src.animation || '')) ? String(src.animation || 'none') : 'none',
        animation_ms: Math.max(100, Math.min(5000, Number(src.animation_ms || 700))),
        delay_on_ms: Math.max(0, Number(src.delay_on_ms || 0)),
        delay_off_ms: Math.max(0, Number(src.delay_off_ms || 0))
    };
}
function applyBackgroundStyle(target, cfg, fallbackColor = '#ffffff') {
    const mode = String(cfg?.mode || cfg?.background_mode || 'color');
    const color = String(cfg?.color || cfg?.background_color || '').trim();
    const image = String(cfg?.image || cfg?.background_image || '').trim();
    const size = String(cfg?.size || cfg?.background_size || 'cover');
    const position = String(cfg?.position || cfg?.background_position || 'center center');
    const repeat = String(cfg?.repeat || cfg?.background_repeat || 'no-repeat');

    target.style.backgroundColor = '';
    target.style.backgroundImage = '';
    target.style.backgroundSize = '';
    target.style.backgroundPosition = '';
    target.style.backgroundRepeat = '';

    if (mode === 'none') {
        target.style.backgroundColor = 'transparent';
        return;
    }
    if (mode === 'image' && image !== '') {
        target.style.backgroundColor = color || fallbackColor;
        target.style.backgroundImage = `url(${image})`;
        target.style.backgroundSize = size;
        target.style.backgroundPosition = position;
        target.style.backgroundRepeat = repeat;
        return;
    }
    target.style.backgroundColor = color || fallbackColor;
}
function normalizeBlock(raw) {
    const x = clamp(raw.x_pct, 0, 100);
    const y = clamp(raw.y_pct, 0, 100);
    let w = clamp(raw.w_pct, 1, 100);
    let h = clamp(raw.h_pct, 1, 100);
    if (x + w > 100) w = Math.max(1, 100 - x);
    if (y + h > 100) h = Math.max(1, 100 - y);
    const styleFromJson = parseDataJson(raw.style_json);
    const style = (raw.style && typeof raw.style === 'object') ? raw.style : (styleFromJson && typeof styleFromJson === 'object' ? styleFromJson : null);
    return {
        id: Number(raw.id || 0),
        key: String(raw.key || raw.block_key || 'block'),
        x_pct: x,
        y_pct: y,
        w_pct: w,
        h_pct: h,
        z_index: Math.max(1, Number(raw.z_index || 1)),
        content_mode: String(raw.content_mode || 'dynamic_current'),
        content_type: String(raw.content_type || 'image'),
        content_id: raw.content_id ? Number(raw.content_id) : null,
        style,
        content: raw.content && typeof raw.content === 'object' ? raw.content : null
    };
}
function normalizeTextData(raw) {
    const src = raw && typeof raw === 'object' ? raw : {};
    return {
        font_size_px: Math.max(8, Math.min(400, Number(src.font_size_px || 64))),
        color: String(src.color || '#ffffff').trim() || '#ffffff',
        align: ['left', 'center', 'right'].includes(String(src.align || '')) ? String(src.align || 'left') : 'left',
        font_weight: ['400', '500', '600', '700', '800'].includes(String(src.font_weight || '')) ? String(src.font_weight || '700') : '700',
        line_height: Math.max(0.8, Math.min(3, Number(src.line_height || 1.1))),
        padding_px: Math.max(0, Math.min(300, Number(src.padding_px || 0)))
    };
}
function createTextRenderNode(text, rawData) {
    const p = normalizeTextData(rawData);
    const node = document.createElement('div');
    node.className = 'textRenderContent';
    node.textContent = String(text || '');
    node.style.fontSize = p.font_size_px + 'px';
    node.style.color = p.color;
    node.style.textAlign = p.align;
    node.style.fontWeight = p.font_weight;
    node.style.lineHeight = String(p.line_height);
    node.style.padding = p.padding_px + 'px';
    return node;
}
function getScheduleThemeById(themeId) {
    const themes = Array.isArray(SCHEDULE_THEMES) ? SCHEDULE_THEMES : [];
    const fallback = themes[0] || null;
    const found = themes.find((theme) => String(theme && theme.id ? theme.id : '') === String(themeId || ''));
    return found || fallback || {
        id: 'light_blue',
        name: 'Светлая синяя',
        colors: {
            text: '#0f172a',
            header_bg: '#dbeafe',
            header_text: '#1e3a8a',
            grid_line: '#bfdbfe',
            busy_bg: '#fee2e2',
            busy_text: '#991b1b',
            free_bg: '#dcfce7',
            free_text: '#166534'
        }
    };
}
function normalizeScheduleData(raw) {
    const src = raw && typeof raw === 'object' ? raw : {};
    return {
        doctor_id: Math.max(1, Number(src.doctor_id || 1)),
        theme_id: String(src.theme_id || ''),
        cached_payload: src.cached_payload && typeof src.cached_payload === 'object' ? src.cached_payload : null
    };
}
function extractScheduleRows(payload) {
    if (!payload || typeof payload !== 'object') return [];
    const days = Array.isArray(payload.days) ? payload.days : [];
    return days
        .filter((day) => day && typeof day === 'object')
        .map((day) => ({
            label: String(day.day || day.label || ''),
            slots: Array.isArray(day.slots) ? day.slots.filter((slot) => slot && typeof slot === 'object') : []
        }));
}
function createScheduleRenderNode(rawData) {
    const schedule = normalizeScheduleData(rawData);
    const theme = getScheduleThemeById(schedule.theme_id);
    const colors = theme && typeof theme.colors === 'object' ? theme.colors : {};

    const wrap = document.createElement('div');
    wrap.style.width = '100%';
    wrap.style.height = '100%';
    wrap.style.boxSizing = 'border-box';
    wrap.style.padding = '10px';
    wrap.style.overflow = 'auto';
    wrap.style.color = String(colors.text || '#0f172a');

    const title = document.createElement('div');
    title.style.fontSize = '16px';
    title.style.fontWeight = '700';
    title.style.marginBottom = '8px';
    title.textContent = 'Расписание врача #' + String(schedule.doctor_id);
    wrap.appendChild(title);

    const rows = extractScheduleRows(schedule.cached_payload);
    if (rows.length <= 0) {
        const empty = document.createElement('div');
        empty.style.fontSize = '14px';
        empty.style.opacity = '0.8';
        empty.textContent = 'Нет кэшированных данных расписания';
        wrap.appendChild(empty);
        return wrap;
    }

    const table = document.createElement('table');
    table.style.width = '100%';
    table.style.borderCollapse = 'collapse';
    table.style.tableLayout = 'fixed';
    table.style.fontSize = '14px';

    const tbody = document.createElement('tbody');
    for (const row of rows) {
        const tr = document.createElement('tr');

        const th = document.createElement('th');
        th.textContent = String(row.label || 'День');
        th.style.border = '1px solid ' + String(colors.grid_line || '#cbd5e1');
        th.style.background = String(colors.header_bg || '#dbeafe');
        th.style.color = String(colors.header_text || '#1e3a8a');
        th.style.padding = '6px';
        th.style.textAlign = 'left';
        th.style.width = '22%';
        tr.appendChild(th);

        const td = document.createElement('td');
        td.style.border = '1px solid ' + String(colors.grid_line || '#cbd5e1');
        td.style.padding = '6px';
        const slotsWrap = document.createElement('div');
        slotsWrap.style.display = 'flex';
        slotsWrap.style.flexWrap = 'wrap';
        slotsWrap.style.gap = '6px';

        for (const slot of row.slots) {
            const badge = document.createElement('span');
            const statusRaw = String(slot.status || slot.state || '').toLowerCase();
            const isBusy = slot.busy === true || statusRaw === 'busy' || statusRaw === 'occupied';
            const from = String(slot.from || '').trim();
            const to = String(slot.to || '').trim();
            const label = String(slot.time || slot.label || (from && to ? (from + '-' + to) : 'Слот'));
            badge.textContent = label;
            badge.style.display = 'inline-flex';
            badge.style.alignItems = 'center';
            badge.style.padding = '4px 8px';
            badge.style.borderRadius = '999px';
            badge.style.border = '1px solid ' + String(colors.grid_line || '#cbd5e1');
            badge.style.fontSize = '13px';
            badge.style.background = isBusy ? String(colors.busy_bg || '#fee2e2') : String(colors.free_bg || '#dcfce7');
            badge.style.color = isBusy ? String(colors.busy_text || '#991b1b') : String(colors.free_text || '#166534');
            slotsWrap.appendChild(badge);
        }

        if (slotsWrap.childElementCount === 0) {
            const emptySlot = document.createElement('span');
            emptySlot.textContent = 'Нет окон';
            emptySlot.style.opacity = '0.75';
            emptySlot.style.fontSize = '13px';
            slotsWrap.appendChild(emptySlot);
        }

        td.appendChild(slotsWrap);
        tr.appendChild(td);
        tbody.appendChild(tr);
    }

    table.appendChild(tbody);
    wrap.appendChild(table);
    return wrap;
}
function applyBlockAnimation(target, animationName) {
    const map = {
        none: '',
        fade_in: 'fadeInBlock .7s ease both',
        slide_up: 'slideUpBlock .7s ease both',
        slide_left: 'slideLeftBlock .7s ease both',
        zoom_in: 'zoomInBlock .7s ease both'
    };
    const key = ['none', 'fade_in', 'slide_up', 'slide_left', 'zoom_in'].includes(String(animationName || ''))
        ? String(animationName || '')
        : 'none';
    target.style.animation = map[key] || '';
}
function applyTimedAppearance(target, animationName, animationMs, delayMs) {
    const name = ['none', 'fade_in', 'slide_up', 'slide_left', 'zoom_in'].includes(String(animationName || ''))
        ? String(animationName || 'none')
        : 'none';
    const ms = Math.max(100, Math.min(5000, Number(animationMs || 700)));
    const delay = Math.max(0, Number(delayMs || 0));
    const map = {
        none: '',
        fade_in: `fadeInBlock ${ms}ms ease ${delay}ms both`,
        slide_up: `slideUpBlock ${ms}ms ease ${delay}ms both`,
        slide_left: `slideLeftBlock ${ms}ms ease ${delay}ms both`,
        zoom_in: `zoomInBlock ${ms}ms ease ${delay}ms both`
    };
    target.style.animation = map[name] || '';
}

function appendTitleBody(root, title, body) {
    const h = document.createElement('h3');
    h.className = 'title';
    h.textContent = title || '';
    root.appendChild(h);

    const p = document.createElement('p');
    p.className = 'body';
    p.textContent = body || '';
    root.appendChild(p);
}
function resolvePosition(pos) {
    const map = {
        center: ['center', 'center'],
        top_left: ['flex-start', 'flex-start'],
        top_center: ['center', 'flex-start'],
        top_right: ['flex-end', 'flex-start'],
        center_left: ['flex-start', 'center'],
        center_right: ['flex-end', 'center'],
        bottom_left: ['flex-start', 'flex-end'],
        bottom_center: ['center', 'flex-end'],
        bottom_right: ['flex-end', 'flex-end']
    };
    return map[String(pos || 'center')] || map.center;
}
function applyImageEffects(target, imageData) {
    const p = imageData && typeof imageData === 'object' ? imageData : {};
    const opacity = Math.max(0, Math.min(100, Number(p.opacity_pct ?? 100)));
    const radius = Math.max(0, Math.min(500, Number(p.radius_px ?? 0)));
    const brightness = Math.max(0, Math.min(300, Number(p.brightness_pct ?? 100)));
    const contrast = Math.max(0, Math.min(300, Number(p.contrast_pct ?? 100)));
    const saturation = Math.max(0, Math.min(300, Number(p.saturation_pct ?? 100)));
    const fade = Math.max(0, Math.min(100, Number(p.fade_pct ?? 0)));
    const fadeMode = ['all', 'horizontal', 'vertical'].includes(String(p.fade_mode || ''))
        ? String(p.fade_mode || 'all')
        : 'all';
    const shadow = ['none', 'soft', 'medium', 'strong'].includes(String(p.shadow || ''))
        ? String(p.shadow || 'none')
        : 'none';
    const shadowMap = {
        none: 'none',
        soft: '0 6px 18px rgba(15, 23, 42, 0.18)',
        medium: '0 10px 26px rgba(15, 23, 42, 0.24)',
        strong: '0 16px 36px rgba(15, 23, 42, 0.34)'
    };
    target.style.opacity = String(opacity / 100);
    target.style.borderRadius = radius > 0 ? (radius + 'px') : '0';
    target.style.boxShadow = shadowMap[shadow] || 'none';
    target.style.filter = `brightness(${brightness}%) contrast(${contrast}%) saturate(${saturation}%)`;
}
function setImageMask(target, mode, fade) {
    if (!target) return;
    if (fade <= 0) {
        target.style.webkitMaskImage = 'none';
        target.style.maskImage = 'none';
        target.style.webkitMaskRepeat = 'no-repeat';
        target.style.maskRepeat = 'no-repeat';
        target.style.webkitMaskSize = '100% 100%';
        target.style.maskSize = '100% 100%';
        return;
    }
    const sideFade = Math.min(49.5, fade / 2);
    let maskImage = '';
    if (mode === 'horizontal') {
        maskImage = `linear-gradient(to right, transparent 0%, black ${sideFade}%, black ${100 - sideFade}%, transparent 100%)`;
    } else if (mode === 'vertical') {
        maskImage = `linear-gradient(to bottom, transparent 0%, black ${sideFade}%, black ${100 - sideFade}%, transparent 100%)`;
    } else {
        maskImage = 'none';
    }
    target.style.webkitMaskImage = maskImage;
    target.style.maskImage = maskImage;
    target.style.webkitMaskRepeat = 'no-repeat';
    target.style.maskRepeat = 'no-repeat';
    target.style.webkitMaskSize = '100% 100%';
    target.style.maskSize = '100% 100%';
}
function buildImageElement(src, title, imageData, className) {
    const p = imageData && typeof imageData === 'object' ? imageData : {};
    const wrap = document.createElement('div');
    wrap.className = className;
    wrap.style.position = 'relative';
    wrap.style.lineHeight = '0';
    const img = document.createElement('img');
    img.className = className;
    img.src = src;
    img.alt = title || '';
    img.style.width = '100%';
    img.style.height = '100%';
    img.style.display = 'block';
    img.style.objectFit = 'contain';
    wrap.appendChild(img);
    applyImageEffects(img, p);
    const fade = Math.max(0, Math.min(100, Number(p.fade_pct ?? 0)));
    const fadeMode = ['all', 'horizontal', 'vertical'].includes(String(p.fade_mode || ''))
        ? String(p.fade_mode || 'all')
        : 'all';
    setImageMask(wrap, 'none', 0);
    setImageMask(img, 'none', 0);
    if (fadeMode === 'all') {
        setImageMask(wrap, 'horizontal', fade);
        setImageMask(img, 'vertical', fade);
    } else {
        setImageMask(img, fadeMode, fade);
    }
    applyImageAnimation(wrap, p);
    return wrap;
}
function applyImageAnimation(target, imageData) {
    const p = imageData && typeof imageData === 'object' ? imageData : {};
    const name = ['none', 'fade_in', 'slide_up', 'slide_left', 'zoom_in'].includes(String(p.animation || ''))
        ? String(p.animation || 'none')
        : 'none';
    const ms = Math.max(100, Math.min(5000, Number(p.animation_ms || 700)));
    const delayMs = Math.max(0, Number((p.delay_on_ms ?? p.delay_ms) || 0));
    const map = {
        none: '',
        fade_in: `fadeInBlock ${ms}ms ease ${delayMs}ms both`,
        slide_up: `slideUpBlock ${ms}ms ease ${delayMs}ms both`,
        slide_left: `slideLeftBlock ${ms}ms ease ${delayMs}ms both`,
        zoom_in: `zoomInBlock ${ms}ms ease ${delayMs}ms both`
    };
    target.style.animation = map[name] || '';
}
let pdfjsLibPromise = null;
const pdfDocCache = new Map();
function getPdfJsLib() {
    if (!pdfjsLibPromise) {
        pdfjsLibPromise = import('/vendor/pdfjs/pdf.min.mjs').then((mod) => {
            mod.GlobalWorkerOptions.workerSrc = '/vendor/pdfjs/pdf.worker.min.mjs';
            return mod;
        });
    }
    return pdfjsLibPromise;
}
async function getPdfDoc(url) {
    const src = String(url || '').trim();
    if (!src) return null;
    if (pdfDocCache.has(src)) return pdfDocCache.get(src);
    const lib = await getPdfJsLib();
    const task = lib.getDocument(src);
    const doc = await task.promise;
    pdfDocCache.set(src, doc);
    return doc;
}
async function renderPdfPageToCanvas(canvas, url, pageNum) {
    const doc = await getPdfDoc(url);
    if (!doc) return;
    const safeNum = Math.max(1, Math.min(doc.numPages, Math.floor(Number(pageNum || 1))));
    const page = await doc.getPage(safeNum);
    const viewport = page.getViewport({ scale: 1 });
    const maxW = Math.max(1, canvas.clientWidth || viewport.width);
    const maxH = Math.max(1, canvas.clientHeight || viewport.height);
    const scale = Math.min(maxW / viewport.width, maxH / viewport.height);
    const drawViewport = page.getViewport({ scale: Math.max(0.1, scale) });
    canvas.width = Math.max(1, Math.floor(drawViewport.width));
    canvas.height = Math.max(1, Math.floor(drawViewport.height));
    const ctx = canvas.getContext('2d', { alpha: false });
    if (!ctx) return;
    await page.render({ canvasContext: ctx, viewport: drawViewport }).promise;
}
async function renderPdfPageToImageUrl(url, pageNum, maxWidth, maxHeight) {
    const doc = await getPdfDoc(url);
    if (!doc) return '';
    const safeNum = Math.max(1, Math.min(doc.numPages, Math.floor(Number(pageNum || 1))));
    const page = await doc.getPage(safeNum);
    const viewport = page.getViewport({ scale: 1 });
    const safeW = Math.max(1, Math.floor(Number(maxWidth || viewport.width)));
    const safeH = Math.max(1, Math.floor(Number(maxHeight || viewport.height)));
    const scale = Math.min(safeW / viewport.width, safeH / viewport.height);
    const drawViewport = page.getViewport({ scale: Math.max(0.1, scale) });
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.floor(drawViewport.width));
    canvas.height = Math.max(1, Math.floor(drawViewport.height));
    const ctx = canvas.getContext('2d', { alpha: false });
    if (!ctx) return '';
    await page.render({ canvasContext: ctx, viewport: drawViewport }).promise;
    return canvas.toDataURL('image/png');
}
function animatePptCanvas(canvas, animName, durationMs) {
    const name = ['none', 'fade', 'slide_left', 'slide_up', 'zoom', 'flip'].includes(String(animName || '')) ? String(animName || 'fade') : 'fade';
    const ms = Math.max(100, Math.min(5000, Number(durationMs || 700)));
    if (name === 'none') {
        canvas.style.transition = '';
        canvas.style.opacity = '1';
        canvas.style.transform = 'none';
        return;
    }
    canvas.style.transition = 'none';
    canvas.style.transformOrigin = 'center center';
    if (name === 'fade') {
        canvas.style.opacity = '0';
        canvas.style.transform = 'none';
    } else if (name === 'slide_left') {
        canvas.style.opacity = '0.96';
        canvas.style.transform = 'translateX(24px)';
    } else if (name === 'slide_up') {
        canvas.style.opacity = '0.96';
        canvas.style.transform = 'translateY(24px)';
    } else if (name === 'zoom') {
        canvas.style.opacity = '0.96';
        canvas.style.transform = 'scale(0.92)';
    } else if (name === 'flip') {
        canvas.style.opacity = '0.96';
        canvas.style.transform = 'rotateY(12deg)';
    }
    canvas.getBoundingClientRect();
    requestAnimationFrame(() => {
        canvas.style.transition = `transform ${ms}ms ease, opacity ${ms}ms ease`;
        canvas.style.opacity = '1';
        canvas.style.transform = 'none';
    });
}
function resetPptCanvas(canvas) {
    canvas.style.transition = '';
    canvas.style.opacity = '1';
    canvas.style.transform = 'none';
    canvas.style.zIndex = '1';
}
function animatePptSlide(node, animName, durationMs) {
    const name = ['none', 'fade', 'slide_left', 'slide_up', 'zoom', 'flip'].includes(String(animName || '')) ? String(animName || 'fade') : 'fade';
    const ms = Math.max(100, Math.min(5000, Number(durationMs || 700)));
    node.style.transition = 'none';
    node.style.transformOrigin = 'center center';
    if (name === 'none') {
        node.style.opacity = '1';
        node.style.transform = 'none';
        return;
    }
    if (name === 'fade') {
        node.style.opacity = '0';
        node.style.transform = 'scale(0.85)';
    } else if (name === 'slide_left') {
        node.style.opacity = '1';
        node.style.transform = 'translate3d(140%, 0, 0)';
    } else if (name === 'slide_up') {
        node.style.opacity = '1';
        node.style.transform = 'translate3d(0, 140%, 0)';
    } else if (name === 'zoom') {
        node.style.opacity = '1';
        node.style.transform = 'scale(0.65)';
    } else if (name === 'flip') {
        node.style.opacity = '1';
        node.style.transform = 'rotateY(88deg)';
    }
    node.getBoundingClientRect();
    requestAnimationFrame(() => {
        node.style.transition = `transform ${ms}ms cubic-bezier(0.22, 1, 0.36, 1), opacity ${ms}ms ease`;
        node.style.opacity = '1';
        node.style.transform = 'none';
    });
}
function resetPptSlide(node) {
    node.style.transition = '';
    node.style.opacity = '1';
    node.style.transform = 'none';
    node.style.zIndex = '1';
}

async function renderBlock(blockRaw, contentMap) {
    const block = normalizeBlock(blockRaw);
    const el = document.createElement('div');
    el.className = 'block';
    el.style.left = block.x_pct + '%';
    el.style.top = block.y_pct + '%';
    el.style.width = block.w_pct + '%';
    el.style.height = block.h_pct + '%';
    el.style.zIndex = String(block.z_index || 1);

    applyBackgroundStyle(el, normalizeBlockBackground(block.style), '#ffffff');

    if (block.content_mode === 'empty') {
        return el;
    }

    let content = block.content;
    if (!content && block.content_mode === 'fixed' && block.content_id && contentMap.has(block.content_id)) {
        content = contentMap.get(block.content_id);
    }
    content = content && typeof content === 'object' ? content : {};

    const type = String(content.type || block.content_type || 'image');
    const data = parseDataJson(content.data_json);
    const mediaUrl = content.media_url || null;
    const title = content.title || block.key;

    if (block.content_mode === 'fixed' && !content.id) {
        appendTitleBody(el, title, 'Контент не выбран');
        return el;
    }
    if (block.content_mode === 'dynamic_current' && !content.id) {
        appendTitleBody(el, title, 'Динамический контент в предпросмотре недоступен');
        return el;
    }

    if (type === 'image') {
        if (mediaUrl) {
            const p = data && typeof data.image === 'object' ? data.image : {};
            const motion = normalizeBlockBackground(block.style);
            const [justify, align] = resolvePosition(p.position);
            const widthPx = Math.max(1, Number(p.width_px || 0));
            const heightPx = Math.max(1, Number(p.height_px || 0));
            const rotateDeg = Math.max(-360, Math.min(360, Number(p.rotate_deg || 0)));
            const fluid = p.fluid === true;
            el.style.display = 'flex';
            el.style.justifyContent = justify;
            el.style.alignItems = align;
            const img = buildImageElement(mediaUrl, title, { ...p, animation: motion.animation, animation_ms: motion.animation_ms, delay_on_ms: motion.delay_on_ms, delay_off_ms: motion.delay_off_ms }, 'media', el);
            if (fluid) {
                img.style.width = '100%';
                img.style.height = '100%';
            } else {
                img.style.width = widthPx > 0 ? (widthPx + 'px') : 'auto';
                img.style.height = heightPx > 0 ? (heightPx + 'px') : 'auto';
            }
            img.style.transform = rotateDeg !== 0 ? ('rotate(' + rotateDeg + 'deg)') : 'none';
            img.style.transformOrigin = 'center center';
            el.appendChild(img);
        } else {
            appendTitleBody(el, title, 'Для изображения не задан media_url');
        }
        return el;
    }

    if (type === 'html') {
        const html = String(content.body || '');
        const p = data && typeof data.html === 'object' ? data.html : {};
        const motion = normalizeBlockBackground(block.style);
        if (html.trim() === '') {
            appendTitleBody(el, title, 'Для HTML не задан body');
        } else {
            const scalePct = Math.max(1, Math.min(500, Number(p.scale_pct || 100)));
            const htmlInner = document.createElement('div');
            htmlInner.className = 'htmlRenderContent';
            htmlInner.innerHTML = html;
            htmlInner.style.zoom = scalePct + '%';
            el.appendChild(htmlInner);
            applyTimedAppearance(el, motion.animation || 'none', motion.animation_ms || 700, motion.delay_on_ms || 0);
        }
        return el;
    }

    if (type === 'text') {
        const text = String(content.body || '');
        const p = data && typeof data.text === 'object' ? data.text : {};
        const motion = normalizeBlockBackground(block.style);
        if (text.trim() === '') {
            appendTitleBody(el, title, 'Для текста не задан body');
        } else {
            el.appendChild(createTextRenderNode(text, p));
            applyTimedAppearance(el, motion.animation || 'none', motion.animation_ms || 700, motion.delay_on_ms || 0);
        }
        return el;
    }

    if (type === 'schedule') {
        const p = data && typeof data.schedule === 'object' ? data.schedule : {};
        const motion = normalizeBlockBackground(block.style);
        el.appendChild(createScheduleRenderNode(p));
        applyTimedAppearance(el, motion.animation || 'none', motion.animation_ms || 700, motion.delay_on_ms || 0);
        return el;
    }

    if (type === 'video') {
        if (mediaUrl) {
            const p = data && typeof data.video === 'object' ? data.video : {};
            const map = {
                center: ['center', 'center'],
                top_left: ['flex-start', 'flex-start'],
                top_center: ['center', 'flex-start'],
                top_right: ['flex-end', 'flex-start'],
                center_left: ['flex-start', 'center'],
                center_right: ['flex-end', 'center'],
                bottom_left: ['flex-start', 'flex-end'],
                bottom_center: ['center', 'flex-end'],
                bottom_right: ['flex-end', 'flex-end']
            };
            const [justify, align] = map[String(p.position || 'center')] || map.center;
            const widthPx = Math.max(1, Number(p.width_px || 0));
            const heightPx = Math.max(1, Number(p.height_px || 0));
            const fluid = p.fluid === true;
            const loop = p.loop !== false;
            const sound = p.sound === true;
            el.style.display = 'flex';
            el.style.justifyContent = justify;
            el.style.alignItems = align;
            const video = document.createElement('video');
            video.className = 'media';
            video.src = mediaUrl;
            video.autoplay = true;
            video.muted = !sound;
            video.loop = loop;
            video.playsInline = true;
            video.controls = false;
            if (fluid) {
                video.style.width = '100%';
                video.style.height = 'auto';
            } else {
                video.style.width = widthPx > 0 ? (widthPx + 'px') : 'auto';
                video.style.height = heightPx > 0 ? (heightPx + 'px') : 'auto';
            }
            el.appendChild(video);
        } else {
            appendTitleBody(el, title, 'Для видео не задан media_url');
        }
        return el;
    }
    if (type === 'ppt') {
        if (mediaUrl) {
            const p = data && typeof data.ppt === 'object' ? data.ppt : {};
            const [justify, align] = resolvePosition(p.position);
            const widthPx = Math.max(0, Number(p.width_px || 0));
            const heightPx = Math.max(0, Number(p.height_px || 0));
            const fluid = p.fluid === true;
            const startPage = Math.max(1, Number(p.start_page || 1));
            const totalPages = Math.max(1, Number(p.total_pages || 1));
            const intervalSec = Math.max(1, Number(p.interval_sec || 5));
            const loop = p.loop !== false;
            const pageAnim = String(p.page_animation || 'fade');
            const animMs = Math.max(100, Math.min(5000, Number(p.animation_ms || 700)));

            el.style.display = 'flex';
            el.style.justifyContent = justify;
            el.style.alignItems = align;

            const frame = document.createElement('div');
            frame.style.position = 'relative';
            frame.style.overflow = 'hidden';
            frame.style.perspective = '1200px';
            if (fluid) {
                frame.style.width = '100%';
                frame.style.height = '100%';
            } else {
                frame.style.width = widthPx > 0 ? (widthPx + 'px') : '100%';
                frame.style.height = heightPx > 0 ? (heightPx + 'px') : '100%';
            }

            const slideA = document.createElement('img');
            const slideB = document.createElement('img');
            [slideA, slideB].forEach((img) => {
                img.className = 'media';
                img.alt = title;
                img.style.position = 'absolute';
                img.style.inset = '0';
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.maxWidth = '100%';
                img.style.maxHeight = '100%';
                img.style.objectFit = 'contain';
                frame.appendChild(img);
            });
            resetPptSlide(slideA);
            resetPptSlide(slideB);
            slideA.style.zIndex = '1';
            slideB.style.zIndex = '0';

            let currentPage = startPage;
            let activeSlide = slideA;
            let standbySlide = slideB;
            const doc = await getPdfDoc(mediaUrl);
            const effectiveTotalPages = doc ? Math.max(1, Number(doc.numPages || totalPages || 1)) : totalPages;
            el.appendChild(frame);
            const getFrameSize = () => {
                const rect = frame.getBoundingClientRect();
                return {
                    width: Math.max(1, Math.floor(rect.width || widthPx || 1280)),
                    height: Math.max(1, Math.floor(rect.height || heightPx || 720))
                };
            };
            const applyPage = async (page, animate = true) => {
                currentPage = Math.max(1, Math.floor(Number(page || 1)));
                const size = getFrameSize();
                const nextUrl = await renderPdfPageToImageUrl(mediaUrl, currentPage, size.width, size.height);
                if (!nextUrl) return;
                return new Promise((resolve) => {
                    standbySlide.onload = () => {
                        resetPptSlide(standbySlide);
                        resetPptSlide(activeSlide);
                        standbySlide.style.zIndex = '2';
                        activeSlide.style.zIndex = '1';
                        if (animate) animatePptSlide(standbySlide, pageAnim, animMs);
                        const prev = activeSlide;
                        activeSlide = standbySlide;
                        standbySlide = prev;
                        standbySlide.onload = null;
                        standbySlide.onerror = null;
                        resolve();
                    };
                    standbySlide.onerror = () => {
                        standbySlide.onload = null;
                        standbySlide.onerror = null;
                        resolve();
                    };
                    standbySlide.src = nextUrl;
                    if (standbySlide.complete) standbySlide.onload();
                });
            };
            const initialSize = getFrameSize();
            activeSlide.src = await renderPdfPageToImageUrl(mediaUrl, startPage, initialSize.width, initialSize.height);

            if (effectiveTotalPages > 1) {
                let stopped = false;
                let busy = false;
                const tick = async () => {
                    if (stopped || busy) return;
                    busy = true;
                    try {
                        let next = currentPage + 1;
                        if (next > effectiveTotalPages) {
                            if (!loop) {
                                stopped = true;
                                return;
                            }
                            next = 1;
                        }
                        await applyPage(next, true);
                    } finally {
                        busy = false;
                        if (!stopped) {
                            const timerId = setTimeout(tick, intervalSec * 1000);
                            activeMediaTimers.push(timerId);
                        }
                    }
                };
                const firstTimerId = setTimeout(tick, intervalSec * 1000);
                activeMediaTimers.push(firstTimerId);
            }
        } else {
            appendTitleBody(el, title, 'PPT requires media_url');
        }
        return el;
    }


    appendTitleBody(el, title, 'Поддерживаются типы "изображение", "html" и "видео"');
    return el;
}

async function loadAllContentMap() {
    const res = await fetch('/api/content_list.php', { cache: 'no-store' });
    const payload = await res.json();
    if (!payload.ok) throw new Error(payload.error || 'Ошибка API контента');
    const rows = Array.isArray(payload.data) ? payload.data : [];
    const map = new Map();
    for (const row of rows) {
        map.set(Number(row.id), row);
    }
    return map;
}

async function loadTemplatePreview() {
    if (templateId <= 0) {
        meta.innerHTML = '<span class="warn">Укажите template_id в адресе.</span> Пример: /preview/?template_id=1';
        return;
    }

    try {
        const res = await fetch('/api/template_get.php?template_id=' + encodeURIComponent(templateId), { cache: 'no-store' });
        const payload = await res.json();
        if (!payload.ok) throw new Error(payload.error || 'Ошибка API');

        const tpl = payload.data || {};
        const layout = parseDataJson(tpl.layout_json);
        const screenStyle = normalizeScreenStyle(layout && layout.screen_style ? layout.screen_style : DEFAULT_SCREEN_STYLE);
        const blocks = Array.isArray(tpl.blocks) ? tpl.blocks : [];
        const contentMap = await loadAllContentMap();
        clearMediaTimers();
        applyBackgroundStyle(document.body, screenStyle, '#ffffff');
        applyBackgroundStyle(stage, screenStyle, '#ffffff');
        clearMediaTimers();
        stage.innerHTML = '';
        for (const block of blocks) {
            stage.appendChild(await renderBlock(block, contentMap));
        }

        meta.innerHTML = `<span class="ok">Предпросмотр</span> Шаблон: ${tpl.name || '-'} | ID: ${tpl.id || '-'} | Блоков: ${blocks.length} | Версия проекта: <?= h($projectVersion ?? '0.0.0-dev') ?>`;
    } catch (e) {
        stage.innerHTML = '';
        meta.innerHTML = '<span class="warn">Ошибка предпросмотра:</span> ' + String(e.message || e);
    }
}

loadTemplatePreview();
</script>
</body>
</html>
