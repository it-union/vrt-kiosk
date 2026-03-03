<?php
declare(strict_types=1);
?><!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <style>
        html, body { margin: 0; width: 100%; height: 100%; overflow: hidden; font-family: Tahoma, sans-serif; background: #ffffff; color: #1a1a1a; }
        #stage { position: relative; width: 100vw; height: 100vh; overflow: hidden; }
        .screenLayer { position: absolute; inset: 0; }
        .block { position: absolute; box-sizing: border-box; overflow: hidden; padding: 0; }
        @keyframes fadeInBlock { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUpBlock { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideLeftBlock { from { opacity: 0; transform: translateX(18px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes zoomInBlock { from { opacity: 0; transform: scale(0.94); } to { opacity: 1; transform: scale(1); } }
        @keyframes screenFadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes screenSlideLeftIn { from { opacity: 0.98; transform: translateX(-5%); } to { opacity: 1; transform: translateX(0); } }
        @keyframes screenSlideRightIn { from { opacity: 0.98; transform: translateX(5%); } to { opacity: 1; transform: translateX(0); } }
        @keyframes screenSlideUpIn { from { opacity: 0.98; transform: translateY(5%); } to { opacity: 1; transform: translateY(0); } }
        @keyframes screenZoomIn { from { opacity: 0; transform: scale(0.97); } to { opacity: 1; transform: scale(1); } }
        .title { font-size: 2.2vmin; font-weight: 700; margin: 0 0 0.8vmin 0; line-height: 1.2; }
        .body { font-size: 1.6vmin; margin: 0; line-height: 1.35; opacity: 0.94; }
        .media { width: auto; height: auto; max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 4px; }
        .muted { opacity: 0.75; }
    </style>
</head>
<body>
<div id="stage"></div>

<script>
const stage = document.getElementById('stage');
const DEFAULT_SCREEN_STYLE = { mode: 'color', color: '#ffffff', image: '', size: 'cover', position: 'center center', repeat: 'no-repeat' };
const activeMediaTimers = [];
let lastScreenSignature = '';
const pptPreviewCache = new Map();
let screenTransitionClipCounter = 0;

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
    stage.querySelectorAll('svg[data-screen-transition="squares"]').forEach((node) => node.remove());
    stage.querySelectorAll('.screenLayer').forEach((node) => {
        node.style.clipPath = '';
        node.style.webkitClipPath = '';
    });
}
function registerMediaTimer(id) {
    activeMediaTimers.push(id);
    return id;
}
function shuffleList(items) {
    const list = Array.isArray(items) ? items.slice() : [];
    for (let i = list.length - 1; i > 0; i -= 1) {
        const j = Math.floor(Math.random() * (i + 1));
        const tmp = list[i];
        list[i] = list[j];
        list[j] = tmp;
    }
    return list;
}
function createSvgNode(tagName) {
    return document.createElementNS('http://www.w3.org/2000/svg', tagName);
}
function getSquaresGridMetrics(node, squareSizePx) {
    const width = Math.max(1, Math.round(node?.clientWidth || stage.clientWidth || window.innerWidth || 1));
    const height = Math.max(1, Math.round(node?.clientHeight || stage.clientHeight || window.innerHeight || 1));
    const targetCell = Math.max(40, Math.min(400, Number(squareSizePx || 160)));
    const cols = Math.max(4, Math.min(12, Math.round(width / targetCell) || 1));
    const rows = Math.max(3, Math.min(8, Math.round(height / targetCell) || 1));
    return {
        width,
        height,
        cols,
        rows,
        cellWidth: width / cols,
        cellHeight: height / rows
    };
}
function runSquaresTransition(node, animationMs, squareSizePx, onDone) {
    if (!node) {
        if (typeof onDone === 'function') onDone();
        return;
    }
    const ms = Math.max(100, Math.min(5000, Number(animationMs || 700)));
    const metrics = getSquaresGridMetrics(node, squareSizePx);
    const clipId = `screenSquaresClip${++screenTransitionClipCounter}`;
    const svg = createSvgNode('svg');
    svg.setAttribute('width', '0');
    svg.setAttribute('height', '0');
    svg.setAttribute('aria-hidden', 'true');
    svg.dataset.screenTransition = 'squares';
    svg.style.position = 'absolute';
    svg.style.width = '0';
    svg.style.height = '0';
    svg.style.pointerEvents = 'none';
    const defs = createSvgNode('defs');
    const clipPath = createSvgNode('clipPath');
    clipPath.setAttribute('id', clipId);
    clipPath.setAttribute('clipPathUnits', 'userSpaceOnUse');
    clipPath.setAttribute('shape-rendering', 'crispEdges');
    defs.appendChild(clipPath);
    svg.appendChild(defs);
    stage.appendChild(svg);

    const cleanup = () => {
        node.style.clipPath = '';
        node.style.webkitClipPath = '';
        if (svg.parentNode) {
            svg.remove();
        }
    };

    node.style.clipPath = `url(#${clipId})`;
    node.style.webkitClipPath = `url(#${clipId})`;

    const cells = [];
    const overlapPx = 1;
    for (let row = 0; row < metrics.rows; row += 1) {
        for (let col = 0; col < metrics.cols; col += 1) {
            const x = Math.round(col * metrics.cellWidth);
            const y = Math.round(row * metrics.cellHeight);
            const nextX = Math.round((col + 1) * metrics.cellWidth);
            const nextY = Math.round((row + 1) * metrics.cellHeight);
            const drawX = Math.max(0, x - overlapPx);
            const drawY = Math.max(0, y - overlapPx);
            const drawWidth = Math.min(metrics.width - drawX, Math.max(1, nextX - x) + overlapPx * 2);
            const drawHeight = Math.min(metrics.height - drawY, Math.max(1, nextY - y) + overlapPx * 2);
            cells.push({
                x: drawX,
                y: drawY,
                width: Math.max(1, drawWidth),
                height: Math.max(1, drawHeight)
            });
        }
    }

    const shuffled = shuffleList(cells);
    const total = Math.max(1, shuffled.length);
    shuffled.forEach((cell, index) => {
        const delay = Math.round((index * ms) / total);
        registerMediaTimer(setTimeout(() => {
            if (!node.isConnected) return;
            const rect = createSvgNode('rect');
            rect.setAttribute('x', String(cell.x));
            rect.setAttribute('y', String(cell.y));
            rect.setAttribute('width', String(cell.width));
            rect.setAttribute('height', String(cell.height));
            clipPath.appendChild(rect);
        }, delay));
    });

    registerMediaTimer(setTimeout(() => {
        cleanup();
        if (typeof onDone === 'function') onDone();
    }, ms + 40));
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
    const transitionName = ['none', 'fade', 'slide_left', 'slide_right', 'slide_up', 'zoom', 'squares'].includes(String(src.transition_name || ''))
        ? String(src.transition_name || 'none')
        : 'none';
    const transitionMs = Math.max(100, Math.min(5000, Number(src.transition_ms || 700)));
    const transitionSquaresPx = Math.max(40, Math.min(400, Number(src.transition_squares_px || 160)));
    return {
        mode,
        color: String(src.color || '#ffffff').trim() || '#ffffff',
        image: String(src.image || '').trim(),
        size,
        position,
        repeat,
        transition_name: transitionName,
        transition_ms: transitionMs,
        transition_squares_px: transitionSquaresPx
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
    const style = raw.style && typeof raw.style === 'object' ? raw.style : null;
    return {
        id: Number(raw.id || 0),
        key: String(raw.key || 'block'),
        x_pct: x,
        y_pct: y,
        w_pct: w,
        h_pct: h,
        z_index: Math.max(1, Number(raw.z_index || 1)),
        content_mode: String(raw.content_mode || 'dynamic_current'),
        content_type: String(raw.content_type || 'image'),
        content: raw.content && typeof raw.content === 'object' ? raw.content : {},
        style
    };
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
function getTemplateTransitionDelay(runtime) {
    if (!runtime || typeof runtime !== 'object') return 0;
    return Math.max(0, Number(runtime.templateDelayMs || 0));
}
function buildShowAnimation(animationName, animationMs) {
    const name = ['none', 'fade_in', 'slide_up', 'slide_left', 'zoom_in'].includes(String(animationName || ''))
        ? String(animationName || 'none')
        : 'none';
    const ms = Math.max(100, Math.min(5000, Number(animationMs || 700)));
    if (name === 'none') {
        return { value: '', duration: ms, name: 'none' };
    }
    const map = {
        fade_in: `fadeInBlock ${ms}ms ease 0ms both`,
        slide_up: `slideUpBlock ${ms}ms ease 0ms both`,
        slide_left: `slideLeftBlock ${ms}ms ease 0ms both`,
        zoom_in: `zoomInBlock ${ms}ms ease 0ms both`
    };
    return { value: map[name] || '', duration: ms, name };
}
function buildHideAnimation(animationName, animationMs) {
    const name = ['none', 'fade_in', 'slide_up', 'slide_left', 'zoom_in'].includes(String(animationName || ''))
        ? String(animationName || 'none')
        : 'none';
    const ms = Math.max(100, Math.min(5000, Number(animationMs || 700)));
    if (name === 'none') {
        return { value: '', duration: ms, name: 'none' };
    }
    const map = {
        fade_in: `fadeInBlock ${ms}ms ease 0ms reverse both`,
        slide_up: `slideUpBlock ${ms}ms ease 0ms reverse both`,
        slide_left: `slideLeftBlock ${ms}ms ease 0ms reverse both`,
        zoom_in: `zoomInBlock ${ms}ms ease 0ms reverse both`
      };
      return { value: map[name] || '', duration: ms, name };
  }
function startContentCycle(target, animationName, animationMs, delayOnMs, delayOffMs, runtime) {
    if (!target || !runtime || runtime.source !== 'schedule') return false;
    const queueState = runtime.queueState && typeof runtime.queueState === 'object' ? runtime.queueState : null;
    if (!queueState) return false;
    const slideDurationMs = Math.max(0, Math.round(Math.max(0, Number(queueState.duration_sec || 0)) * 1000));
    if (slideDurationMs <= 0) return false;

    const totalItems = Math.max(0, Number(queueState.total_items || 0));
    const show = buildShowAnimation(animationName, animationMs);
    const hide = buildHideAnimation(animationName, animationMs);
    const onMs = Math.max(0, Number(delayOnMs || 0)) + getTemplateTransitionDelay(runtime);
    const offMs = Math.max(0, Number(delayOffMs || 0));
    const elapsedMsRaw = Math.max(0, Math.round(Math.max(0, Number(queueState.elapsed_sec || 0)) * 1000));
    const cycleOffsetMs = slideDurationMs > 0 ? (elapsedMsRaw % slideDurationMs) : 0;
    const showStartMs = onMs;
    const showEndMs = showStartMs + (show.name === 'none' ? 0 : show.duration);
    const hideStartMs = showEndMs + offMs;
    const hideEndMs = hideStartMs + (hide.name === 'none' ? 0 : hide.duration);

    const resetForCycle = () => {
        target.style.animation = '';
        target.style.opacity = '';
        target.style.visibility = '';
        target.style.pointerEvents = '';
        if (onMs > 0 || show.name !== 'none') {
            target.style.opacity = '0';
            target.style.visibility = 'hidden';
        }
    };
    const runShow = () => {
        if (!target.isConnected) return;
        target.style.visibility = 'visible';
        target.style.pointerEvents = '';
        if (show.name === 'none') {
            target.style.opacity = '1';
            target.style.animation = '';
            return;
        }
        target.style.animation = 'none';
        void target.offsetWidth;
        target.style.animation = show.value;
    };
    const runHide = () => {
        if (!target.isConnected) return;
        if (hide.name === 'none') {
            target.style.opacity = '0';
            target.style.visibility = 'hidden';
            target.style.pointerEvents = 'none';
            return;
        }
        target.style.animation = 'none';
        void target.offsetWidth;
        target.style.animation = hide.value;
        registerMediaTimer(setTimeout(() => {
            if (!target.isConnected) return;
            target.style.visibility = 'hidden';
            target.style.pointerEvents = 'none';
        }, hide.duration));
    };
    const showFinalState = () => {
        target.style.animation = '';
        target.style.visibility = 'visible';
        target.style.opacity = '1';
        target.style.pointerEvents = '';
    };
    const hideFinalState = () => {
        target.style.animation = '';
        target.style.opacity = '0';
        target.style.visibility = 'hidden';
        target.style.pointerEvents = 'none';
    };
    const scheduleCycle = (offsetMs) => {
        const safeOffset = Math.max(0, Math.min(slideDurationMs, Number(offsetMs || 0)));
        resetForCycle();
        if (safeOffset <= 0) {
            if (showStartMs > 0) {
                registerMediaTimer(setTimeout(runShow, showStartMs));
            } else {
                runShow();
            }
            if (offMs > 0 && hideStartMs < slideDurationMs) {
                registerMediaTimer(setTimeout(runHide, hideStartMs));
            }
            return;
        }

        if (safeOffset < showStartMs) {
            registerMediaTimer(setTimeout(runShow, Math.max(0, showStartMs - safeOffset)));
            if (offMs > 0 && hideStartMs < slideDurationMs) {
                registerMediaTimer(setTimeout(runHide, Math.max(0, hideStartMs - safeOffset)));
            }
            return;
        }

        if (offMs <= 0 || safeOffset < hideStartMs) {
            showFinalState();
            if (offMs > 0 && hideStartMs < slideDurationMs) {
                registerMediaTimer(setTimeout(runHide, Math.max(0, hideStartMs - safeOffset)));
            }
            return;
        }

        if (safeOffset < hideEndMs) {
            runHide();
            return;
        }

        hideFinalState();
    };

    scheduleCycle(cycleOffsetMs);
    if (totalItems <= 1) {
        registerMediaTimer(setTimeout(function repeatCycle() {
            if (!target.isConnected) return;
            scheduleCycle(0);
            registerMediaTimer(setTimeout(repeatCycle, slideDurationMs));
        }, Math.max(0, slideDurationMs - cycleOffsetMs)));
    }
    return true;
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

async function renderBlock(blockRaw, runtime = null) {
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

    const content = block.content || {};
    const type = String(content.type || block.content_type || 'image');
    const data = parseDataJson(content.data_json);
    const mediaUrl = content.media_url || null;
    const title = content.title || block.key;

    if (block.content_mode === 'fixed' && !content.id) {
        appendTitleBody(el, title, 'Контент не выбран');
        return el;
    }
    if (block.content_mode === 'dynamic_current' && !content.id) {
        appendTitleBody(el, title, 'Нет текущего контента');
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
            const templateDelayMs = getTemplateTransitionDelay(runtime);
            const hasOwnAnimation = String(motion.animation || 'none') !== 'none';
            const img = buildImageElement(
                mediaUrl,
                title,
                {
                    ...p,
                    animation: motion.animation,
                    animation_ms: motion.animation_ms,
                    delay_on_ms: Math.max(0, Number(motion.delay_on_ms || 0)) + (hasOwnAnimation ? templateDelayMs : 0),
                    delay_off_ms: motion.delay_off_ms
                },
                'media',
                el
            );
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
            if (!startContentCycle(img, motion.animation || 'none', motion.animation_ms || 700, motion.delay_on_ms || 0, motion.delay_off_ms || 0, runtime)) {
                applyImageAnimation(img, {
                    ...motion,
                    delay_on_ms: Math.max(0, Number(motion.delay_on_ms || 0)) + (hasOwnAnimation ? templateDelayMs : 0)
                });
            }
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
            const templateDelayMs = getTemplateTransitionDelay(runtime);
            const hasOwnAnimation = String(motion.animation || 'none') !== 'none';
            const htmlInner = document.createElement('div');
            htmlInner.innerHTML = html;
            htmlInner.style.zoom = scalePct + '%';
            htmlInner.style.width = '100%';
            htmlInner.style.height = '100%';
            el.appendChild(htmlInner);
            if (!startContentCycle(el, motion.animation || 'none', motion.animation_ms || 700, motion.delay_on_ms || 0, motion.delay_off_ms || 0, runtime)) {
                applyTimedAppearance(
                    el,
                    motion.animation || 'none',
                    motion.animation_ms || 700,
                    Math.max(0, Number(motion.delay_on_ms || 0)) + (hasOwnAnimation ? templateDelayMs : 0)
                );
            }
        }
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

function buildScreenTransition(style) {
    const name = ['none', 'fade', 'slide_left', 'slide_right', 'slide_up', 'zoom', 'squares'].includes(String(style?.transition_name || ''))
        ? String(style.transition_name || 'none')
        : 'none';
    const ms = Math.max(100, Math.min(5000, Number(style?.transition_ms || 700)));
    const map = {
        none: '',
        fade: `screenFadeIn ${ms}ms ease both`,
        slide_left: `screenSlideLeftIn ${ms}ms cubic-bezier(0.22, 1, 0.36, 1) both`,
        slide_right: `screenSlideRightIn ${ms}ms cubic-bezier(0.22, 1, 0.36, 1) both`,
        slide_up: `screenSlideUpIn ${ms}ms cubic-bezier(0.22, 1, 0.36, 1) both`,
        zoom: `screenZoomIn ${ms}ms ease both`,
        squares: ''
    };
    return { name, ms, value: map[name] || '' };
}
function getScreenTransitionContentDelay(style) {
    const transition = buildScreenTransition(style);
    if (transition.name === 'none') {
        return 0;
    }
    return transition.ms + (transition.name === 'squares' ? 40 : 30);
}

async function buildScreenLayer(blocks, runtime, screenStyle) {
    const layer = document.createElement('div');
    layer.className = 'screenLayer';
    applyBackgroundStyle(layer, screenStyle, '#ffffff');
    for (const block of blocks) {
        layer.appendChild(await renderBlock(block, runtime));
    }
    return layer;
}

async function loadScreen() {
    try {
        const res = await fetch('/api/screen.php?screen_id=1', { cache: 'no-store' });
        if (!res.ok) throw new Error('Код HTTP: ' + res.status);
        const payload = await res.json();
        if (!payload.ok) throw new Error(payload.error || 'Ошибка API');

        const data = payload.data || {};
        const blocks = Array.isArray(data.blocks) ? data.blocks : [];
        const screenStyle = normalizeScreenStyle(data.screen_style || DEFAULT_SCREEN_STYLE);
        const transition = buildScreenTransition(screenStyle);
        const runtime = {
            source: String(data.source || ''),
            queueState: data.queue_state && typeof data.queue_state === 'object' ? data.queue_state : null,
            templateDelayMs: getScreenTransitionContentDelay(screenStyle)
        };
        const signature = JSON.stringify({
            source: data.source || '',
            template: data.template || null,
            screen_style: screenStyle,
            blocks: blocks
        });

        if (signature === lastScreenSignature) {
            return;
        }
        lastScreenSignature = signature;

        clearMediaTimers();
        applyBackgroundStyle(document.body, screenStyle, '#ffffff');
        applyBackgroundStyle(stage, { mode: 'none' }, '#ffffff');
        const existingLayers = Array.from(stage.querySelectorAll('.screenLayer'));
        const previousLayer = existingLayers.length > 0 ? existingLayers[existingLayers.length - 1] : null;
        existingLayers.slice(0, -1).forEach((node) => node.remove());
        const nextLayer = await buildScreenLayer(blocks, runtime, screenStyle);

        if (!previousLayer || transition.name === 'none') {
            stage.innerHTML = '';
            stage.appendChild(nextLayer);
        } else if (transition.name === 'squares') {
            nextLayer.style.zIndex = '2';
            stage.appendChild(nextLayer);
            runSquaresTransition(nextLayer, transition.ms, screenStyle.transition_squares_px, () => {
                if (previousLayer.parentNode === stage) {
                    previousLayer.remove();
                }
            });
        } else {
            nextLayer.style.zIndex = '2';
            nextLayer.style.animation = transition.value;
            stage.appendChild(nextLayer);
            registerMediaTimer(setTimeout(() => {
                if (previousLayer.parentNode === stage) {
                    previousLayer.remove();
                }
                nextLayer.style.animation = '';
            }, transition.ms + 30));
        }

    } catch (e) {
        lastScreenSignature = '';
        stage.innerHTML = '';
        const block = document.createElement('div');
        block.className = 'block';
        block.style.left = '0';
        block.style.top = '0';
        block.style.width = '100%';
        block.style.height = '100%';

        const h = document.createElement('h3');
        h.className = 'title';
        h.textContent = 'Ошибка киоска';
        block.appendChild(h);

        const p = document.createElement('p');
        p.className = 'body';
        p.textContent = String(e.message || e);
        block.appendChild(p);

        stage.appendChild(block);
    }
}

loadScreen();
setInterval(loadScreen, 5000);
</script>
</body>
</html>
