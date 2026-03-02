<?php
declare(strict_types=1);
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <style>
        html, body { height: 100%; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; font-family: Tahoma, sans-serif; background: #f5f6f8; color: #1a1a1a; }
        .wrap { flex: 1; display: grid; grid-template-columns: 280px 1fr 380px; gap: 12px; padding: 12px; min-height: 0; }
        .panel { background: #fff; border: 1px solid #d7dbe0; border-radius: 8px; padding: 10px; }
        .wrap .panel { display: flex; flex-direction: column; min-height: 0; }
        .editor { overflow: hidden; }
        #inspectorControls { flex: 1; min-height: 0; overflow: auto; }
        .fold { border: 1px solid #e0e3e8; border-radius: 6px; background: #fff; margin-bottom: 8px; }
        .fold > summary { cursor: pointer; list-style: none; padding: 8px 10px; font-size: 13px; font-weight: bold; border-bottom: 1px solid transparent; position: relative; padding-right: 26px; background: #f0f0f0; }
        .fold > summary::-webkit-details-marker { display: none; }
        .fold > summary::after { content: '\25B8'; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #5a6472; font-size: 12px; }
        .fold[open] > summary::after { content: '\25BE'; }
        .fold[open] > summary { border-bottom-color: #e0e3e8; }
        .foldBody { padding: 6px 10px 10px; }
        .foldBody label { margin: 8px 0 0; }
        .foldBody .row { margin: 8px 0 0; }
        .foldBody .row > label { margin: 0; }
        .urlRow { display: grid; grid-template-columns: 1fr 120px; gap: 8px; align-items: end; }
        .urlRow > label { margin: 0; }
        .urlCol { min-width: 0; }
        .urlLabel { margin: 0 0 4px 0; font-size: 13px; line-height: 1.2; }
        .urlRow input { height: 36px; }
        .urlPickBtn { width: 100%; height: 36px; min-height: 36px; max-height: 36px; box-sizing: border-box; margin: 0; padding: 0 12px; font-size: 13px; font-weight: 400; line-height: 1; display: inline-flex; align-items: center; justify-content: center; align-self: end; white-space: nowrap; }
        .libraryHead { display: flex; align-items: center; gap: 8px; margin-top: 0; margin-bottom: 8px; }
        .libraryBtn { min-width: 38px; width: 38px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
        .libraryUploadBtn { width: 140px; height: 30px; min-height: 30px; max-height: 30px; margin: 0; padding: 0 10px; }
        .libraryDeleteBtn { width: 120px; height: 30px; min-height: 30px; max-height: 30px; margin: 0; padding: 0 10px; border-color: #b91c1c; color: #b91c1c; }
        .librarySelectBtn { width: 120px; height: 30px; min-height: 30px; max-height: 30px; margin: 0; padding: 0 10px; }
        .libraryCloseBtn { margin-left: auto; min-width: 34px; width: 34px; height: 30px; padding: 0; font-size: 20px; line-height: 1; display: inline-flex; align-items: center; justify-content: center; }
        .uploadProgressWrap { display: none; align-items: center; gap: 8px; margin: 0 0 8px; }
        .uploadProgressTrack { flex: 1; height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
        .uploadProgressBar { width: 0%; height: 100%; background: #1d5fbf; transition: width .15s linear; }
        .uploadProgressText { min-width: 44px; text-align: right; font-size: 12px; color: #334155; }
        .libraryGrid { border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; padding: 8px; max-height: 58vh; overflow: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 8px; align-content: start; align-items: start; }
        .libraryItem { border: 1px solid #d8dee8; border-radius: 6px; padding: 6px; cursor: pointer; background: #f8fafc; align-self: start; }
        .libraryItem.active { border-color: #1d5fbf; box-shadow: 0 0 0 1px #1d5fbf inset; background: #eef5ff; }
        .libraryItem img { width: 100%; height: 70px; object-fit: cover; border-radius: 4px; display: block; background: #e8edf5; }
        .libraryName { margin-top: 4px; font-size: 11px; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #imageLibraryModal .modal { width: 80vw; height: 80vh; max-width: 80vw; max-height: 80vh; display: flex; flex-direction: column; min-height: 0; }
        #imageLibraryModal .libraryGrid { flex: 1; min-height: 0; max-height: none; overflow: auto; }
        .panel h2 { margin: 0 0 10px 0; font-size: 16px; }
        .list { flex: 1; min-height: 0; overflow: auto; border: 1px solid #e0e3e8; border-radius: 6px; }
        .item { padding: 7px 8px; border-bottom: 1px solid #eceff3; cursor: pointer; font-size: 12px; }
        .item:hover { background: #f8fafc; }
        .item.active { background: #e8f2ff; }
        .hiddenFile { display: none !important; }
        .meta label, .editor label { display: block; margin: 6px 0; font-size: 13px; }
        input, select, textarea, button { font: inherit; }
        input, select, textarea { width: 100%; box-sizing: border-box; padding: 6px; border: 1px solid #c8ced6; border-radius: 6px; }
        button { padding: 7px 10px; border: 1px solid #1d5fbf; background: transparent; color: #1d5fbf; border-radius: 6px; cursor: pointer; }
        button.secondary { border: 1px solid #1d5fbf; background: transparent; color: #1d5fbf; }
        .toolbar { display: flex; gap: 8px; margin-bottom: 8px; }
        .stagePanel .stageToolbar { width: min(960px, 100%); margin-left: auto; margin-right: auto; justify-content: center; align-items: center; }
        .stagePanel .canvasWrap { width: min(960px, 100%); margin-left: auto; margin-right: auto; }
        .stagePanel .stageOptions { width: min(960px, 100%); margin: 8px auto 0; font-size: 13px; color: #334155; display: flex; align-items: center; gap: 8px; }
        .stagePanel .stageOptions input { width: auto; }
        .iconBtn { width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1; }
        .row { display: flex; gap: 8px; }
        .row > * { flex: 1; }
        .canvasWrap { position: relative; width: 100%; max-width: 960px; aspect-ratio: 16/9; border: 2px dashed #b7c1cf; border-radius: 8px; background: #fff; overflow: hidden; user-select: none; }
        .block { position: absolute; border: 1px solid #2672d6; box-sizing: border-box; padding: 4px; cursor: move; user-select: none; }
        .block.selected { border-color: #1d5fbf; box-shadow: 0 0 0 2px rgba(29,95,191,0.35) inset; }
        .block .title { font-size: 11px; font-weight: bold; background: rgba(255,255,255,0.75); display: inline-block; padding: 2px 4px; border-radius: 4px; position: relative; z-index: 2; }
        .block .metaWrap { position: absolute; left: 4px; right: 4px; bottom: 4px; display: flex; flex-direction: column; gap: 3px; align-items: flex-start; pointer-events: none; }
        .block .metaLine { font-size: 10px; line-height: 1.2; color: #0f356a; background: rgba(255,255,255,0.75); display: inline-block; padding: 2px 4px; border-radius: 4px; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .block .metaWrap { z-index: 2; }
        .blockPreview { position: absolute; left: 4px; right: 4px; top: 4px; bottom: 4px; overflow: hidden; pointer-events: none; z-index: 1; display: flex; align-items: center; justify-content: center; }
        .blockPreview.html { display: block; padding: 6px; overflow: auto; background: rgba(255,255,255,0.45); }
        .blockPreviewMedia { max-width: 100%; max-height: 100%; display: block; object-fit: contain; }
        .handle { position: absolute; z-index: 3; }
        .handle-n, .handle-s { left: 10px; right: 10px; height: 8px; cursor: ns-resize; }
        .handle-n { top: -4px; }
        .handle-s { bottom: -4px; }
        .handle-e, .handle-w { top: 10px; bottom: 10px; width: 8px; cursor: ew-resize; }
        .handle-e { right: -4px; }
        .handle-w { left: -4px; }
        .handle-ne, .handle-nw, .handle-se, .handle-sw { width: 10px; height: 10px; background: #ffffff; border: 1px solid #1d5fbf; border-radius: 2px; }
        .handle-ne { right: -5px; top: -5px; cursor: nesw-resize; }
        .handle-nw { left: -5px; top: -5px; cursor: nwse-resize; }
        .handle-se { right: -5px; bottom: -5px; cursor: nwse-resize; }
        .handle-sw { left: -5px; bottom: -5px; cursor: nesw-resize; }
        .status { margin: 0 0 0 auto; align-self: center; display: none; padding: 6px 10px; border-radius: 6px; border: 1px solid transparent; font-size: 12px; line-height: 1.2; }
        .status.show { display: inline-flex; }
        .status.success { color: #0f5132; background: #d1e7dd; border-color: #badbcc; }
        .status.error { color: #842029; background: #f8d7da; border-color: #f5c2c7; }
        .pageFooter { margin: 0 12px 12px; color: #5a6472; font-size: 12px; border-top: 1px solid #e3e7ed; padding-top: 10px; flex: 0 0 auto; }
        .modalBack { position: fixed; inset: 0; background: rgba(0,0,0,.35); display: none; align-items: center; justify-content: center; z-index: 50; }
        .modalBack.open { display: flex; }
        .modal { width: min(420px, calc(100vw - 24px)); background: #fff; border: 1px solid #d7dbe0; border-radius: 8px; padding: 12px; }
        .modal h3 { margin: 0 0 8px; font-size: 16px; }
        .modal p { margin: 0 0 12px; font-size: 14px; color: #334155; }
    </style>
</head>
<body>
<div class="wrap">
    <section class="panel">
        <h2>Список шаблонов</h2>
        <div class="toolbar">
            <button class="iconBtn secondary" id="reloadListBtn" type="button" title="Обновить список" aria-label="Обновить список">&#x21bb;</button>
            <button class="iconBtn" id="newTemplateBtn" type="button" title="Новый шаблон" aria-label="Новый шаблон">&#x2795;</button>
            <button class="iconBtn secondary" id="duplicateTemplateBtn" type="button" title="Дублировать шаблон" aria-label="Дублировать шаблон">&#x29C9;</button>
            <button class="iconBtn secondary" id="previewTemplateBtn" type="button" title="Предпросмотр шаблона" aria-label="Предпросмотр шаблона">&#x1F441;</button>
            <button class="iconBtn secondary" id="deleteTemplateBtn" type="button" title="Удалить шаблон" aria-label="Удалить шаблон">&#x1F5D1;</button>
            <button class="iconBtn secondary" id="openContentBtn" type="button" title="Редактор контента" aria-label="Редактор контента">&#x1F4DD;</button>
        </div>
        <div id="templateList" class="list"></div>
    </section>

    <section class="panel">
        <h2>Шаблон</h2>
        <div class="toolbar" id="stageToolbar" style="display:none;">
            <button class="iconBtn" id="addBlockBtn" type="button" title="Добавить блок" aria-label="Добавить блок">&#x2795;</button>
            <button class="iconBtn secondary" id="removeBlockBtn" type="button" title="Удалить блок" aria-label="Удалить блок">&#x2796;</button>
            <button class="iconBtn" id="saveTemplateBtn" type="button" title="Сохранить шаблон" aria-label="Сохранить шаблон">&#x1F4BE;</button>
        </div>
        <div id="canvas" class="canvasWrap"></div>
        <label class="stageOptions" for="globalShowContentPreview">
            <input id="globalShowContentPreview" type="checkbox">
            Показывать выбранный контент внутри всех блоков
        </label>
    </section>

    <section class="panel editor">
        <h2>Параметры шаблона</h2>
        <p id="inspectorEmpty" style="font-size:12px;color:#5e6e82;">Выберите шаблон из списка или создайте новый.</p>
        <div id="inspectorControls" style="display:none;">
        <details class="fold" open>
            <summary>Параметры шаблона</summary>
            <div class="foldBody meta">
                <label>Название <input id="tplName" type="text" value="Новый шаблон"></label>
                <label>Описание <input id="tplDesc" type="text" value=""></label>
                <label>Статус
                    <select id="tplStatus">
                        <option value="draft">черновик</option>
                        <option value="work">рабочий</option>
                        <option value="archive">архив</option>
                    </select>
                </label>
                <label>Фон экрана
                    <select id="screenBgMode">
                        <option value="none">без фона</option>
                        <option value="color">цвет</option>
                        <option value="image">изображение</option>
                    </select>
                </label>
                <label>Цвет экрана <input id="screenBgColor" type="color" value="#ffffff"></label>
                <div class="urlRow">
                    <div class="urlCol">
                        <label for="screenBgImage" class="urlLabel">URL фона экрана</label>
                        <input id="screenBgImage" type="text" value="">
                    </div>
                    <button type="button" id="screenBgPickBtn" class="urlPickBtn">Библиотека</button>
                </div>
                <div class="row">
                    <label>Размер
                        <select id="screenBgSize">
                            <option value="cover">cover</option>
                            <option value="contain">contain</option>
                            <option value="auto">auto</option>
                        </select>
                    </label>
                    <label>Повтор
                        <select id="screenBgRepeat">
                            <option value="no-repeat">no-repeat</option>
                            <option value="repeat">repeat</option>
                            <option value="repeat-x">repeat-x</option>
                            <option value="repeat-y">repeat-y</option>
                        </select>
                    </label>
                </div>
                <label>Позиция
                    <select id="screenBgPosition">
                        <option value="center center">center center</option>
                        <option value="left top">left top</option>
                        <option value="center top">center top</option>
                        <option value="right top">right top</option>
                        <option value="left center">left center</option>
                        <option value="right center">right center</option>
                        <option value="left bottom">left bottom</option>
                        <option value="center bottom">center bottom</option>
                        <option value="right bottom">right bottom</option>
                    </select>
                </label>
            </div>
        </details>
        <details class="fold" open>
            <summary>Параметры блока</summary>
            <div class="foldBody">
                <div class="row">
                    <label>X % <input id="bX" type="number" min="0" max="100" step="0.1"></label>
                    <label>Y % <input id="bY" type="number" min="0" max="100" step="0.1"></label>
                </div>
                <div class="row">
                    <label>Ширина % <input id="bW" type="number" min="1" max="100" step="0.1"></label>
                    <label>Высота % <input id="bH" type="number" min="1" max="100" step="0.1"></label>
                </div>
                <label>Слой Z <input id="bZ" type="number" min="1" step="1"></label>
                <label>Режим контента
                    <select id="bMode">
                        <option value="dynamic_current">текущий динамический</option>
                        <option value="fixed">фиксированный</option>
                        <option value="empty">пустой</option>
                    </select>
                </label>
                <label>Тип контента
                    <select id="bType">
                        <option value="image">изображение</option>
                        <option value="html">html</option>
                        <option value="video">видео</option>
                        <option value="ppt">ppt</option>
                    </select>
                </label>
                <label>Фиксированный контент
                    <select id="bContentId"></select>
                </label>
                <label>Фон блока
                    <select id="bBgMode">
                        <option value="none">без фона</option>
                        <option value="color">цвет</option>
                        <option value="image">изображение</option>
                    </select>
                </label>
                <label>Цвет блока <input id="bBgColor" type="color" value="#ffffff"></label>
                <div class="urlRow">
                    <div class="urlCol">
                        <label for="bBgImage" class="urlLabel">URL фона блока</label>
                        <input id="bBgImage" type="text" value="">
                    </div>
                    <button type="button" id="bBgPickBtn" class="urlPickBtn">Библиотека</button>
                </div>
                <div class="row">
                    <label>Размер
                        <select id="bBgSize">
                            <option value="cover">cover</option>
                            <option value="contain">contain</option>
                            <option value="auto">auto</option>
                        </select>
                    </label>
                    <label>Повтор
                        <select id="bBgRepeat">
                            <option value="no-repeat">no-repeat</option>
                            <option value="repeat">repeat</option>
                            <option value="repeat-x">repeat-x</option>
                            <option value="repeat-y">repeat-y</option>
                        </select>
                    </label>
                </div>
                <label>Позиция
                    <select id="bBgPosition">
                        <option value="center center">center center</option>
                        <option value="left top">left top</option>
                        <option value="center top">center top</option>
                        <option value="right top">right top</option>
                        <option value="left center">left center</option>
                        <option value="right center">right center</option>
                        <option value="left bottom">left bottom</option>
                        <option value="center bottom">center bottom</option>
                        <option value="right bottom">right bottom</option>
                    </select>
                </label>
            </div>
        </details>
        <p id="status" class="status"></p>
        </div>
    </section>
</div>
<div class="pageFooter">Версия проекта: <strong><?= h($projectVersion ?? '0.0.0-dev') ?></strong></div>

<div class="modalBack" id="deleteModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="deleteTitle">
        <h3 id="deleteTitle">Удаление шаблона</h3>
        <p id="deleteText">Удалить выбранный шаблон?</p>
        <div class="row">
            <button class="secondary" type="button" id="deleteCancelBtn">Отмена</button>
            <button type="button" id="deleteConfirmBtn">Удалить</button>
        </div>
    </div>
</div>
<div class="modalBack" id="duplicateTemplateModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="duplicateTemplateTitle">
        <h3 id="duplicateTemplateTitle">Дублирование шаблона</h3>
        <p id="duplicateTemplateText">Дублировать выбранный шаблон?</p>
        <div class="row">
            <button class="secondary" type="button" id="duplicateTemplateCancelBtn">Отмена</button>
            <button type="button" id="duplicateTemplateConfirmBtn">Дублировать</button>
        </div>
    </div>
</div>
<div class="modalBack" id="imageLibraryModal">
    <div class="modal" role="dialog" aria-modal="true">
        <input class="hiddenFile" id="libraryUploadFile" type="file" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml">
        <div class="libraryHead">
            <button type="button" id="reloadLibraryBtn" class="libraryBtn" title="Обновить библиотеку">&#x21bb;</button>
            <button type="button" id="libraryUploadBtn" class="libraryUploadBtn" title="Загрузить файл">Загрузка</button>
            <button type="button" id="libraryDeleteBtn" class="libraryDeleteBtn" title="Удалить файл">Удалить</button>
            <button type="button" id="librarySelectBtn" class="librarySelectBtn" title="Выбрать изображение">Выбрать</button>
            <button type="button" id="closeLibraryBtn" class="libraryCloseBtn" title="Закрыть" aria-label="Закрыть">&times;</button>
        </div>
        <div id="libraryUploadProgressWrap" class="uploadProgressWrap">
            <div class="uploadProgressTrack"><div id="libraryUploadProgressBar" class="uploadProgressBar"></div></div>
            <div id="libraryUploadProgressText" class="uploadProgressText">0%</div>
        </div>
        <div id="imageLibrary" class="libraryGrid"></div>
    </div>
</div>
<div class="modalBack" id="deleteImageModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="deleteImageTitle">
        <h3 id="deleteImageTitle">Удаление изображения</h3>
        <p id="deleteImageText" style="margin:0 0 12px;color:#334155;">Удалить выбранное изображение?</p>
        <div class="row">
            <button type="button" id="deleteImageCancelBtn">Отмена</button>
            <button type="button" id="deleteImageConfirmBtn" class="libraryDeleteBtn">Удалить</button>
        </div>
    </div>
</div>

<script>
const state = {
  templates: [],
  contents: [],
  currentTemplateId: null,
  blocks: [],
  selectedBlockIndex: -1,
  screen_style: { mode: 'color', color: '#ffffff', image: '', size: 'cover', position: 'center center', repeat: 'no-repeat' },
  saveInProgress: false,
  globalShowContentPreview: false,
  bgGallery: [],
  bgGallerySelectedUrl: '',
  bgGallerySelectedName: '',
  bgGalleryTarget: ''
};
const pointer = { mode: null, index: -1, dir: '', startClientX: 0, startClientY: 0, startX: 0, startY: 0, startW: 0, startH: 0 };
const SNAP_PX = 8;
let bgLibraryUploadInProgress = false;
let bgLibraryUploadProgressTimer = null;
let bgLibraryUploadFinalizing = false;

const el = {
  templateList: document.getElementById('templateList'),
  canvas: document.getElementById('canvas'),
  status: document.getElementById('status'),
  tplName: document.getElementById('tplName'),
  tplDesc: document.getElementById('tplDesc'),
  tplStatus: document.getElementById('tplStatus'),
  screenBgMode: document.getElementById('screenBgMode'),
  screenBgColor: document.getElementById('screenBgColor'),
  screenBgImage: document.getElementById('screenBgImage'),
  screenBgPickBtn: document.getElementById('screenBgPickBtn'),
  screenBgSize: document.getElementById('screenBgSize'),
  screenBgPosition: document.getElementById('screenBgPosition'),
  screenBgRepeat: document.getElementById('screenBgRepeat'),
  bX: document.getElementById('bX'),
  bY: document.getElementById('bY'),
  bW: document.getElementById('bW'),
  bH: document.getElementById('bH'),
  bZ: document.getElementById('bZ'),
  bMode: document.getElementById('bMode'),
  bType: document.getElementById('bType'),
  bContentId: document.getElementById('bContentId'),
  globalShowContentPreview: document.getElementById('globalShowContentPreview'),
  bBgMode: document.getElementById('bBgMode'),
  bBgColor: document.getElementById('bBgColor'),
  bBgImage: document.getElementById('bBgImage'),
  bBgPickBtn: document.getElementById('bBgPickBtn'),
  bBgSize: document.getElementById('bBgSize'),
  bBgPosition: document.getElementById('bBgPosition'),
  bBgRepeat: document.getElementById('bBgRepeat'),
  libraryUploadBtn: document.getElementById('libraryUploadBtn'),
  libraryDeleteBtn: document.getElementById('libraryDeleteBtn'),
  libraryUploadFile: document.getElementById('libraryUploadFile'),
  imageLibraryModal: document.getElementById('imageLibraryModal'),
  imageLibrary: document.getElementById('imageLibrary')
};

function setStatus(msg, isErr = false) {
  const text = String(msg || '').trim();
  if (!text) {
    el.status.textContent = '';
    el.status.className = 'status';
    return;
  }
  el.status.textContent = text;
  el.status.className = 'status show ' + (isErr ? 'error' : 'success');
}
function setSaveButtonDisabled(disabled) {
  const btn = document.getElementById('saveTemplateBtn');
  if (!btn) return;
  btn.disabled = !!disabled;
  btn.style.opacity = disabled ? '0.6' : '';
  btn.style.cursor = disabled ? 'not-allowed' : '';
}
function clamp(v, min, max) { return Math.max(min, Math.min(max, v)); }
function parseJsonObject(value) {
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
  const color = String(src.color || '#ffffff').trim() || '#ffffff';
  return { mode, color, image: String(src.image || '').trim(), size, position, repeat };
}
function normalizeBlockBackground(raw) {
  const src = raw && typeof raw === 'object' ? raw : {};
  const mode = ['none', 'color', 'image'].includes(String(src.background_mode || '')) ? String(src.background_mode) : 'color';
  const size = ['cover', 'contain', 'auto'].includes(String(src.background_size || '')) ? String(src.background_size) : 'cover';
  const repeat = ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'].includes(String(src.background_repeat || '')) ? String(src.background_repeat) : 'no-repeat';
  const positions = ['left top', 'center top', 'right top', 'left center', 'center center', 'right center', 'left bottom', 'center bottom', 'right bottom'];
  const position = positions.includes(String(src.background_position || '')) ? String(src.background_position) : 'center center';
  const color = String(src.background_color || '#ffffff').trim() || '#ffffff';
  return {
    background_mode: mode,
    background_color: color,
    background_image: String(src.background_image || '').trim(),
    background_size: size,
    background_position: position,
    background_repeat: repeat
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
function setStageEditorVisible(visible) {
  const stageToolbar = document.getElementById('stageToolbar');
  if (!stageToolbar) return;
  stageToolbar.style.display = visible ? 'flex' : 'none';
}
function setInspectorVisible(visible) {
  const inspectorControls = document.getElementById('inspectorControls');
  const inspectorEmpty = document.getElementById('inspectorEmpty');
  if (inspectorControls) inspectorControls.style.display = visible ? 'block' : 'none';
  if (inspectorEmpty) inspectorEmpty.style.display = visible ? 'none' : 'block';
}
function ensureBlockKey(block, index) {
  if (!block.block_key || String(block.block_key).trim() === '') {
    block.block_key = `block_${index + 1}`;
  }
}
function labelContentType(v) {
  const map = { image: 'Изображение', html: 'HTML', video: 'Видео', ppt: 'Презентация' };
  return map[v] || v;
}
function labelContentMode(v) {
  const map = { dynamic_current: 'Динамический', fixed: 'Фиксированный', empty: 'Пустой' };
  return map[v] || v;
}
function findContentTitle(contentId) {
  const id = Number(contentId || 0);
  if (id <= 0) return '';
  const item = state.contents.find((c) => Number(c.id) === id);
  return item ? String(item.title || '') : '';
}
function resolveMediaPosition(position) {
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
  return map[String(position || 'center')] || map.center;
}
function getPptPreviewUrl(mediaUrl, page = 1) {
  const src = String(mediaUrl || '').trim();
  if (!src) return '';
  const m = src.match(/\/uploads\/content_ppt\/([^?#]+)/i);
  if (!m) return '';
  const raw = m[1];
  const file = decodeURIComponent(raw);
  const lower = file.toLowerCase();
  if (!lower.endsWith('.pdf')) return '';
  const base = file.slice(0, -4);
  const p = Math.max(1, Math.floor(Number(page || 1)));
  const name = p <= 1 ? `${base}.png` : `${base}_${p}.png`;
  return '/uploads/content_ppt_preview/' + encodeURIComponent(name);
}
function renderBlockContentPreview(blockEl, block) {
  if (!state.globalShowContentPreview) return;
  if (String(block.content_mode || '') !== 'fixed') return;
  const contentId = Number(block.content_id || 0);
  if (contentId <= 0) return;
  const item = state.contents.find((c) => Number(c.id) === contentId);
  if (!item) return;

  const type = String(item.type || block.content_type || 'image');
  const data = parseJsonObject(item.data_json) || {};
  const wrap = document.createElement('div');
  wrap.className = 'blockPreview' + (type === 'html' ? ' html' : '');

  if (type === 'image') {
    const src = String(item.media_url || '');
    if (!src) return;
    const p = data && typeof data.image === 'object' ? data.image : {};
    const [justify, align] = resolveMediaPosition(p.position);
    const widthPx = Math.max(0, Number(p.width_px || 0));
    const heightPx = Math.max(0, Number(p.height_px || 0));
    const rotateDeg = Math.max(-360, Math.min(360, Number(p.rotate_deg || 0)));
    const fluid = p.fluid === true;
    wrap.style.justifyContent = justify;
    wrap.style.alignItems = align;
    const img = document.createElement('img');
    img.className = 'blockPreviewMedia';
    img.src = src;
    img.alt = String(item.title || '');
    if (fluid) {
      img.style.width = '100%';
      img.style.height = 'auto';
    } else {
      img.style.width = widthPx > 0 ? (widthPx + 'px') : 'auto';
      img.style.height = heightPx > 0 ? (heightPx + 'px') : 'auto';
    }
    img.style.transform = rotateDeg !== 0 ? ('rotate(' + rotateDeg + 'deg)') : 'none';
    img.style.transformOrigin = 'center center';
    wrap.appendChild(img);
    blockEl.appendChild(wrap);
    return;
  }

  if (type === 'video') {
    const src = String(item.media_url || '');
    if (!src) return;
    const p = data && typeof data.video === 'object' ? data.video : {};
    const [justify, align] = resolveMediaPosition(p.position);
    const widthPx = Math.max(0, Number(p.width_px || 0));
    const heightPx = Math.max(0, Number(p.height_px || 0));
    const fluid = p.fluid === true;
    wrap.style.justifyContent = justify;
    wrap.style.alignItems = align;
    const video = document.createElement('video');
    video.className = 'blockPreviewMedia';
    video.src = src;
    video.autoplay = true;
    video.loop = p.loop !== false;
    video.muted = true;
    video.playsInline = true;
    video.controls = false;
    if (fluid) {
      video.style.width = '100%';
      video.style.height = 'auto';
    } else {
      video.style.width = widthPx > 0 ? (widthPx + 'px') : 'auto';
      video.style.height = heightPx > 0 ? (heightPx + 'px') : 'auto';
    }
    wrap.appendChild(video);
    blockEl.appendChild(wrap);
    return;
  }

  if (type === 'ppt') {
    const src = String(item.media_url || '');
    if (!src) return;
    const p = data && typeof data.ppt === 'object' ? data.ppt : {};
    const [justify, align] = resolveMediaPosition(p.position);
    const widthPx = Math.max(0, Number(p.width_px || 0));
    const heightPx = Math.max(0, Number(p.height_px || 0));
    const fluid = p.fluid === true;
    wrap.style.justifyContent = justify;
    wrap.style.alignItems = align;
    const img = document.createElement('img');
    img.className = 'blockPreviewMedia';
    img.alt = String(item.title || '');
    const first = getPptPreviewUrl(src, 1);
    img.src = first || '';
    img.onerror = () => {
      const fallback = getPptPreviewUrl(src, 1);
      if (img.src !== fallback) img.src = fallback;
    };
    if (fluid) {
      img.style.width = '100%';
      img.style.height = 'auto';
    } else {
      img.style.width = widthPx > 0 ? (widthPx + 'px') : '100%';
      img.style.height = heightPx > 0 ? (heightPx + 'px') : '100%';
    }
    wrap.appendChild(img);
    blockEl.appendChild(wrap);
    return;
  }

  if (type === 'html') {
    wrap.innerHTML = String(item.body || '');
    blockEl.appendChild(wrap);
  }
}
function renderContentOptionsForBlock(block) {
  const current = block && block.content_id ? Number(block.content_id) : 0;
  const type = block ? String(block.content_type || '') : '';
  const options = ['<option value="">Не выбрано</option>'];
  for (const c of state.contents) {
    if (type && String(c.type) !== type) continue;
    const selected = Number(c.id) === current ? ' selected' : '';
    options.push(`<option value="${Number(c.id)}"${selected}>[${labelContentType(String(c.type))}] ${String(c.title || '')} (ID ${Number(c.id)})</option>`);
  }
  el.bContentId.innerHTML = options.join('');
}

function renderTemplateList() {
  el.templateList.innerHTML = '';
  for (const tpl of state.templates) {
    const rawStatus = String(tpl.status || '');
    const normalized = rawStatus === 'active' ? 'work' : (rawStatus === 'archived' ? 'archive' : rawStatus);
    const statusLabel = normalized === 'draft' ? 'черновик' : (normalized === 'work' ? 'рабочий' : (normalized === 'archive' ? 'архив' : normalized));
    const d = document.createElement('div');
    d.className = 'item' + (Number(tpl.id) === Number(state.currentTemplateId) ? ' active' : '');
    d.textContent = `${tpl.name} [${statusLabel}] v${tpl.version}`;
    d.onclick = () => loadTemplate(tpl.id);
    el.templateList.appendChild(d);
  }
}

function renderCanvas() {
  applyBackgroundStyle(el.canvas, state.screen_style, '#ffffff');
  el.canvas.innerHTML = '';
  state.blocks.forEach((b, i) => {
    const div = document.createElement('div');
    div.className = 'block' + (i === state.selectedBlockIndex ? ' selected' : '');
    div.style.left = `${b.x_pct}%`;
    div.style.top = `${b.y_pct}%`;
    div.style.width = `${b.w_pct}%`;
    div.style.height = `${b.h_pct}%`;
    div.style.zIndex = String(b.z_index || 1);
    applyBackgroundStyle(div, b, '#ffffff');
    renderBlockContentPreview(div, b);

    const t = document.createElement('div');
    t.className = 'title';
    t.textContent = `${b.block_key}`;
    div.appendChild(t);

    const metaWrap = document.createElement('div');
    metaWrap.className = 'metaWrap';

    const m1 = document.createElement('div');
    m1.className = 'metaLine';
    m1.textContent = `Режим: ${labelContentMode(String(b.content_mode || 'dynamic_current'))}`;
    metaWrap.appendChild(m1);

    const m2 = document.createElement('div');
    m2.className = 'metaLine';
    m2.textContent = `Тип: ${labelContentType(String(b.content_type || 'image'))}`;
    metaWrap.appendChild(m2);

    if (String(b.content_mode || '') === 'fixed') {
      const title = findContentTitle(b.content_id);
      const m3 = document.createElement('div');
      m3.className = 'metaLine';
      m3.textContent = title ? `Контент: ${title} (ID ${Number(b.content_id || 0)})` : `Контент: ID ${Number(b.content_id || 0) || 'не выбран'}`;
      metaWrap.appendChild(m3);
    }

    if (!state.globalShowContentPreview) {
      div.appendChild(metaWrap);
    }

    div.onclick = (event) => { event.stopPropagation(); state.selectedBlockIndex = i; fillBlockEditor(); renderCanvas(); };
    div.onmousedown = (event) => {
      if (event.target !== div && event.target !== t) return;
      state.selectedBlockIndex = i;
      fillBlockEditor();
      renderCanvas();
      startPointerDrag(event, i);
    };

    if (i === state.selectedBlockIndex) {
      ['n', 's', 'e', 'w', 'ne', 'nw', 'se', 'sw'].forEach((dir) => {
        const h = document.createElement('div');
        h.className = 'handle handle-' + dir;
        h.onmousedown = (event) => {
          state.selectedBlockIndex = i;
          fillBlockEditor();
          renderCanvas();
          startPointerResize(event, i, dir);
        };
        div.appendChild(h);
      });
    }

    el.canvas.appendChild(div);
  });
}

function currentBlock() { return state.selectedBlockIndex < 0 || state.selectedBlockIndex >= state.blocks.length ? null : state.blocks[state.selectedBlockIndex]; }
function setFieldVisibility(node, visible) {
  if (!node) return;
  node.style.display = visible ? '' : 'none';
}
function syncScreenBackgroundFieldVisibility() {
  const mode = String(el.screenBgMode?.value || 'color');
  const showColor = mode === 'color';
  const showImage = mode === 'image';
  setFieldVisibility(el.screenBgColor ? el.screenBgColor.closest('label') : null, showColor);
  setFieldVisibility(el.screenBgImage ? el.screenBgImage.closest('.urlRow') : null, showImage);
  setFieldVisibility(el.screenBgSize ? el.screenBgSize.closest('.row') : null, showImage);
  setFieldVisibility(el.screenBgPosition ? el.screenBgPosition.closest('label') : null, showImage);
}
function syncBlockBackgroundFieldVisibility() {
  const mode = String(el.bBgMode?.value || 'color');
  const showColor = mode === 'color';
  const showImage = mode === 'image';
  setFieldVisibility(el.bBgColor ? el.bBgColor.closest('label') : null, showColor);
  setFieldVisibility(el.bBgImage ? el.bBgImage.closest('.urlRow') : null, showImage);
  setFieldVisibility(el.bBgSize ? el.bBgSize.closest('.row') : null, showImage);
  setFieldVisibility(el.bBgPosition ? el.bBgPosition.closest('label') : null, showImage);
}
function fillTemplateMeta(tpl) {
  const rawStatus = String(tpl?.status || 'draft');
  const normalized = rawStatus === 'active' ? 'work' : (rawStatus === 'archived' ? 'archive' : rawStatus);
  el.tplName.value = tpl?.name || 'Новый шаблон';
  el.tplDesc.value = tpl?.description || '';
  el.tplStatus.value = normalized;
  const layout = parseJsonObject(tpl?.layout_json);
  state.screen_style = normalizeScreenStyle(layout?.screen_style || {});
  el.screenBgMode.value = state.screen_style.mode;
  el.screenBgColor.value = state.screen_style.color;
  el.screenBgImage.value = state.screen_style.image;
  el.screenBgSize.value = state.screen_style.size;
  el.screenBgPosition.value = state.screen_style.position;
  el.screenBgRepeat.value = state.screen_style.repeat;
  syncScreenBackgroundFieldVisibility();
}

function fillBlockEditor() {
  const b = currentBlock();
  if (!b) {
    el.bX.value = ''; el.bY.value = ''; el.bW.value = ''; el.bH.value = ''; el.bZ.value = '';
    el.bMode.value = 'dynamic_current'; el.bType.value = 'image';
    const emptyBg = normalizeBlockBackground({});
    el.bBgMode.value = emptyBg.background_mode;
    el.bBgColor.value = emptyBg.background_color;
    el.bBgImage.value = emptyBg.background_image;
    el.bBgSize.value = emptyBg.background_size;
    el.bBgPosition.value = emptyBg.background_position;
    el.bBgRepeat.value = emptyBg.background_repeat;
    syncBlockBackgroundFieldVisibility();
    renderContentOptionsForBlock(null);
    return;
  }
  ensureBlockKey(b, state.selectedBlockIndex);
  renderContentOptionsForBlock(b);
  const bg = normalizeBlockBackground(b);
  el.bX.value = b.x_pct; el.bY.value = b.y_pct; el.bW.value = b.w_pct; el.bH.value = b.h_pct;
  el.bZ.value = b.z_index; el.bMode.value = b.content_mode; el.bType.value = b.content_type; el.bContentId.value = b.content_id || '';
  el.bBgMode.value = bg.background_mode;
  el.bBgColor.value = bg.background_color;
  el.bBgImage.value = bg.background_image;
  el.bBgSize.value = bg.background_size;
  el.bBgPosition.value = bg.background_position;
  el.bBgRepeat.value = bg.background_repeat;
  syncBlockBackgroundFieldVisibility();
}

function updateBlockFromEditor() {
  const b = currentBlock(); if (!b) return;
  ensureBlockKey(b, state.selectedBlockIndex);
  b.x_pct = clamp(Number(el.bX.value || 0), 0, 100);
  b.y_pct = clamp(Number(el.bY.value || 0), 0, 100);
  b.w_pct = clamp(Number(el.bW.value || 1), 1, 100);
  b.h_pct = clamp(Number(el.bH.value || 1), 1, 100);
  b.z_index = Math.max(1, Number(el.bZ.value || 1));
  b.content_mode = el.bMode.value;
  b.content_type = el.bType.value;
  b.content_id = el.bContentId.value ? Number(el.bContentId.value) : null;
  b.background_mode = el.bBgMode.value || 'color';
  b.background_color = (el.bBgColor.value || '#ffffff').trim() || '#ffffff';
  b.background_image = (el.bBgImage.value || '').trim();
  b.background_size = el.bBgSize.value || 'cover';
  b.background_position = el.bBgPosition.value || 'center center';
  b.background_repeat = el.bBgRepeat.value || 'no-repeat';
  if (b.x_pct + b.w_pct > 100) b.w_pct = 100 - b.x_pct;
  if (b.y_pct + b.h_pct > 100) b.h_pct = 100 - b.y_pct;
  renderCanvas();
}

function collectSnapLines(index) {
  const linesX = [0, 100]; const linesY = [0, 100];
  state.blocks.forEach((b, i) => { if (i === index) return; const x = Number(b.x_pct), y = Number(b.y_pct); linesX.push(x, x + Number(b.w_pct)); linesY.push(y, y + Number(b.h_pct)); });
  return { linesX, linesY };
}

function snapMove(x, y, w, h, index, tX, tY) {
  const { linesX, linesY } = collectSnapLines(index);
  let bestX = x, bestY = y, dxBest = Infinity, dyBest = Infinity;
  for (const line of linesX) {
    const dLeft = Math.abs(x - line); if (dLeft <= tX && dLeft < dxBest) { dxBest = dLeft; bestX = line; }
    const dRight = Math.abs((x + w) - line); if (dRight <= tX && dRight < dxBest) { dxBest = dRight; bestX = line - w; }
  }
  for (const line of linesY) {
    const dTop = Math.abs(y - line); if (dTop <= tY && dTop < dyBest) { dyBest = dTop; bestY = line; }
    const dBottom = Math.abs((y + h) - line); if (dBottom <= tY && dBottom < dyBest) { dyBest = dBottom; bestY = line - h; }
  }
  return { x: clamp(bestX, 0, 100 - w), y: clamp(bestY, 0, 100 - h) };
}

function snapResize(rect, index, dir, tX, tY) {
  const { linesX, linesY } = collectSnapLines(index);
  let { x, y, w, h } = rect;
  if (dir.includes('e') || dir.includes('w')) {
    const edge = dir.includes('e') ? (x + w) : x; let best = edge, bestD = Infinity;
    for (const line of linesX) { const d = Math.abs(edge - line); if (d <= tX && d < bestD) { bestD = d; best = line; } }
    if (dir.includes('e')) w = clamp(best - x, 1, 100 - x); else { x = clamp(best, 0, x + w - 1); w = clamp((rect.x + rect.w) - x, 1, 100 - x); }
  }
  if (dir.includes('n') || dir.includes('s')) {
    const edge = dir.includes('s') ? (y + h) : y; let best = edge, bestD = Infinity;
    for (const line of linesY) { const d = Math.abs(edge - line); if (d <= tY && d < bestD) { bestD = d; best = line; } }
    if (dir.includes('s')) h = clamp(best - y, 1, 100 - y); else { y = clamp(best, 0, y + h - 1); h = clamp((rect.y + rect.h) - y, 1, 100 - y); }
  }
  return { x, y, w, h };
}

function startPointerDrag(event, index) {
  if (event.button !== 0) return; const b = state.blocks[index]; if (!b) return;
  pointer.mode = 'drag'; pointer.index = index; pointer.dir = '';
  pointer.startClientX = event.clientX; pointer.startClientY = event.clientY;
  pointer.startX = Number(b.x_pct); pointer.startY = Number(b.y_pct); pointer.startW = Number(b.w_pct); pointer.startH = Number(b.h_pct);
  window.addEventListener('mousemove', onPointerMove); window.addEventListener('mouseup', onPointerUp); event.preventDefault();
}

function startPointerResize(event, index, dir) {
  if (event.button !== 0) return; const b = state.blocks[index]; if (!b) return;
  pointer.mode = 'resize'; pointer.index = index; pointer.dir = dir;
  pointer.startClientX = event.clientX; pointer.startClientY = event.clientY;
  pointer.startX = Number(b.x_pct); pointer.startY = Number(b.y_pct); pointer.startW = Number(b.w_pct); pointer.startH = Number(b.h_pct);
  window.addEventListener('mousemove', onPointerMove); window.addEventListener('mouseup', onPointerUp); event.preventDefault(); event.stopPropagation();
}

function onPointerMove(event) {
  if (!pointer.mode) return; const b = state.blocks[pointer.index]; if (!b) return;
  const rect = el.canvas.getBoundingClientRect(); if (rect.width <= 0 || rect.height <= 0) return;
  const dxPct = ((event.clientX - pointer.startClientX) / rect.width) * 100;
  const dyPct = ((event.clientY - pointer.startClientY) / rect.height) * 100;
  const tX = (SNAP_PX / rect.width) * 100; const tY = (SNAP_PX / rect.height) * 100;

  if (pointer.mode === 'drag') {
    const x = clamp(pointer.startX + dxPct, 0, 100 - pointer.startW);
    const y = clamp(pointer.startY + dyPct, 0, 100 - pointer.startH);
    const snapped = snapMove(x, y, pointer.startW, pointer.startH, pointer.index, tX, tY);
    b.x_pct = snapped.x; b.y_pct = snapped.y;
  }

  if (pointer.mode === 'resize') {
    let x = pointer.startX, y = pointer.startY, w = pointer.startW, h = pointer.startH; const dir = pointer.dir;
    if (dir.includes('e')) w = clamp(pointer.startW + dxPct, 1, 100 - x);
    if (dir.includes('s')) h = clamp(pointer.startH + dyPct, 1, 100 - y);
    if (dir.includes('w')) { const right = pointer.startX + pointer.startW; x = clamp(pointer.startX + dxPct, 0, right - 1); w = clamp(right - x, 1, 100 - x); }
    if (dir.includes('n')) { const bottom = pointer.startY + pointer.startH; y = clamp(pointer.startY + dyPct, 0, bottom - 1); h = clamp(bottom - y, 1, 100 - y); }
    const snapped = snapResize({ x, y, w, h }, pointer.index, dir, tX, tY);
    b.x_pct = snapped.x; b.y_pct = snapped.y; b.w_pct = snapped.w; b.h_pct = snapped.h;
  }

  fillBlockEditor(); renderCanvas();
}

function onPointerUp() {
  if (!pointer.mode) return; pointer.mode = null; pointer.index = -1; pointer.dir = '';
  window.removeEventListener('mousemove', onPointerMove); window.removeEventListener('mouseup', onPointerUp);
}

async function apiGet(url) {
  const res = await fetch(url, { cache: 'no-store' });
  const p = await res.json();
  if (!p.ok) throw new Error(p.error || 'Ошибка API');
  return p.data;
}

async function apiPost(url, params) {
  const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(params) });
  const p = await res.json();
  if (!p.ok) throw new Error(p.error || 'Ошибка API');
  return p.data;
}
function uploadWithProgress(url, formData, onProgress) {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.responseType = 'text';
    xhr.upload.onloadstart = () => {
      if (typeof onProgress === 'function') onProgress(1);
    };
    xhr.upload.onprogress = (event) => {
      if (!event.lengthComputable) return;
      const rawPct = Math.round((event.loaded / event.total) * 100);
      const pct = Math.max(1, Math.min(95, rawPct));
      if (typeof onProgress === 'function') onProgress(pct);
    };
    xhr.upload.onload = () => {
      if (typeof onProgress === 'function') onProgress(96);
    };
    xhr.onerror = () => reject(new Error('Ошибка сети при загрузке'));
    xhr.onload = () => {
      const raw = String(xhr.responseText || '').trim();
      if (raw === '') {
        reject(new Error('Пустой ответ сервера'));
        return;
      }
      let payload = null;
      try {
        payload = JSON.parse(raw);
      } catch (_) {
        const first = raw.indexOf('{');
        const last = raw.lastIndexOf('}');
        if (first !== -1 && last > first) {
          try {
            payload = JSON.parse(raw.slice(first, last + 1));
          } catch (_) {
            payload = null;
          }
        }
      }
      if (!payload) {
        const preview = raw.replace(/\s+/g, ' ').slice(0, 180);
        reject(new Error(preview ? ('Сервер вернул невалидный JSON: ' + preview) : 'Сервер вернул невалидный JSON'));
        return;
      }
      if (payload.ok !== true) {
        reject(new Error(payload.error ? String(payload.error) : 'Ошибка загрузки'));
        return;
      }
      resolve(payload);
    };
    xhr.send(formData);
  });
}
function setBgLibraryUploadState(inProgress, text = '') {
  bgLibraryUploadInProgress = !!inProgress;
  const uploadBtn = document.getElementById('libraryUploadBtn');
  const reloadBtn = document.getElementById('reloadLibraryBtn');
  const delBtn = document.getElementById('libraryDeleteBtn');
  const selectBtn = document.getElementById('librarySelectBtn');
  const closeBtn = document.getElementById('closeLibraryBtn');
  const progressWrap = document.getElementById('libraryUploadProgressWrap');
  const progressBar = document.getElementById('libraryUploadProgressBar');
  const progressText = document.getElementById('libraryUploadProgressText');

  if (uploadBtn) {
    if (!uploadBtn.dataset.defaultLabel) uploadBtn.dataset.defaultLabel = uploadBtn.textContent || 'Загрузка';
    uploadBtn.disabled = bgLibraryUploadInProgress;
    uploadBtn.textContent = uploadBtn.dataset.defaultLabel;
  }
  if (reloadBtn) reloadBtn.disabled = bgLibraryUploadInProgress;
  if (delBtn) delBtn.disabled = bgLibraryUploadInProgress;
  if (selectBtn) selectBtn.disabled = bgLibraryUploadInProgress;
  if (closeBtn) closeBtn.disabled = bgLibraryUploadInProgress;

  if (progressWrap) progressWrap.style.display = bgLibraryUploadInProgress ? 'flex' : 'none';
  if (!bgLibraryUploadInProgress) {
    if (bgLibraryUploadProgressTimer) {
      clearInterval(bgLibraryUploadProgressTimer);
      bgLibraryUploadProgressTimer = null;
    }
    bgLibraryUploadFinalizing = false;
    if (progressBar) progressBar.style.width = '0%';
    if (progressText) progressText.textContent = '0%';
  }
}
function openBgGalleryModal(targetInputId) {
  state.bgGalleryTarget = targetInputId;
  state.bgGallerySelectedUrl = '';
  state.bgGallerySelectedName = '';
  if (el.imageLibrary) el.imageLibrary.innerHTML = '';
  if (el.imageLibraryModal) el.imageLibraryModal.classList.add('open');
}
function closeBgGalleryModal() {
  if (el.imageLibraryModal) el.imageLibraryModal.classList.remove('open');
}
function renderBgGallery() {
  if (!el.imageLibrary) return;
  el.imageLibrary.innerHTML = '';
  state.bgGallery.forEach((item) => {
    const url = String(item.url || '');
    const name = String(item.name || '');
    const card = document.createElement('div');
    card.className = 'libraryItem' + (url === state.bgGallerySelectedUrl ? ' active' : '');
    card.onclick = () => {
      state.bgGallerySelectedUrl = url;
      state.bgGallerySelectedName = name;
      renderBgGallery();
    };
    const img = document.createElement('img');
    img.src = url;
    img.alt = name;
    card.appendChild(img);
    const meta = document.createElement('div');
    meta.className = 'libraryName';
    meta.textContent = name;
    card.appendChild(meta);
    el.imageLibrary.appendChild(card);
  });
}
async function loadBgGalleryAndOpen(targetInputId) {
  try {
    state.bgGallery = await apiGet('/api/template_image_library.php');
    openBgGalleryModal(targetInputId);
    renderBgGallery();
  } catch (e) {
    setStatus(String(e.message || e), true);
  }
}
async function reloadBgGalleryOnly() {
  try {
    state.bgGallery = await apiGet('/api/template_image_library.php');
    renderBgGallery();
  } catch (e) {
    setStatus(String(e.message || e), true);
  }
}
async function uploadBgGalleryImage() {
  try {
    const file = el.libraryUploadFile && el.libraryUploadFile.files && el.libraryUploadFile.files[0] ? el.libraryUploadFile.files[0] : null;
    if (!file) {
      setStatus('Сначала выберите файл', true);
      return;
    }
    const fd = new FormData();
    fd.append('image', file);

    setBgLibraryUploadState(true);
    const payload = await uploadWithProgress('/api/template_upload_image.php', fd, (pct) => {
      const progressBar = document.getElementById('libraryUploadProgressBar');
      const progressText = document.getElementById('libraryUploadProgressText');
      if (progressBar) progressBar.style.width = String(pct) + '%';
      if (progressText) progressText.textContent = String(pct) + '%';
      if (pct >= 96 && !bgLibraryUploadFinalizing) {
        bgLibraryUploadFinalizing = true;
        if (bgLibraryUploadProgressTimer) clearInterval(bgLibraryUploadProgressTimer);
        bgLibraryUploadProgressTimer = setInterval(() => {
          const current = progressBar ? parseInt(progressBar.style.width || '96', 10) : 96;
          if (current >= 99) return;
          const next = current + 1;
          if (progressBar) progressBar.style.width = String(next) + '%';
          if (progressText) progressText.textContent = String(next) + '%';
        }, 500);
      }
    });
    if (bgLibraryUploadProgressTimer) {
      clearInterval(bgLibraryUploadProgressTimer);
      bgLibraryUploadProgressTimer = null;
    }
    const progressBar = document.getElementById('libraryUploadProgressBar');
    const progressText = document.getElementById('libraryUploadProgressText');
    if (progressBar) progressBar.style.width = '100%';
    if (progressText) progressText.textContent = '100%';

    state.bgGallery = await apiGet('/api/template_image_library.php');
    state.bgGallerySelectedUrl = String(payload.data && payload.data.url ? payload.data.url : '');
    state.bgGallerySelectedName = String(payload.data && payload.data.name ? payload.data.name : '');
    renderBgGallery();
    setStatus('Изображение загружено. Нажмите "Выбрать"');
  } catch (e) {
    setStatus(String(e.message || e), true);
  } finally {
    setBgLibraryUploadState(false);
    if (el.libraryUploadFile) el.libraryUploadFile.value = '';
  }
}
function openDeleteImageModal() {
  const modal = document.getElementById('deleteImageModal');
  const text = document.getElementById('deleteImageText');
  if (text) {
    text.textContent = state.bgGallerySelectedName
      ? `Удалить изображение "${state.bgGallerySelectedName}"?`
      : 'Удалить выбранное изображение?';
  }
  if (modal) modal.classList.add('open');
}
function closeDeleteImageModal() {
  const modal = document.getElementById('deleteImageModal');
  if (modal) modal.classList.remove('open');
}
async function deleteLibraryImage() {
  if (!state.bgGallerySelectedUrl) {
    setStatus('Сначала выберите изображение', true);
    closeDeleteImageModal();
    return;
  }
  try {
    await apiPost('/api/template_image_delete.php', { url: state.bgGallerySelectedUrl });
    const target = state.bgGalleryTarget ? document.getElementById(state.bgGalleryTarget) : null;
    if (target && String(target.value || '').trim() === state.bgGallerySelectedUrl) {
      target.value = '';
      target.dispatchEvent(new Event('input', { bubbles: true }));
      target.dispatchEvent(new Event('change', { bubbles: true }));
    }
    state.bgGallerySelectedUrl = '';
    state.bgGallerySelectedName = '';
    state.bgGallery = await apiGet('/api/template_image_library.php');
    renderBgGallery();
    closeDeleteImageModal();
    setStatus('Изображение удалено из библиотеки');
  } catch (e) {
    closeDeleteImageModal();
    setStatus(String(e.message || e), true);
  }
}
function applyBgGallerySelection() {
  if (!state.bgGalleryTarget || !state.bgGallerySelectedUrl) {
    setStatus('Сначала выберите изображение', true);
    return;
  }
  const target = document.getElementById(state.bgGalleryTarget);
  if (!target) {
    closeBgGalleryModal();
    return;
  }
  target.value = state.bgGallerySelectedUrl;
  target.dispatchEvent(new Event('input', { bubbles: true }));
  target.dispatchEvent(new Event('change', { bubbles: true }));
  closeBgGalleryModal();
}

async function reloadTemplateList() {
  try { state.templates = await apiGet('/api/template_list.php'); renderTemplateList(); }
  catch (e) { setStatus(String(e.message || e), true); }
}
async function reloadContentList() {
  try {
    state.contents = await apiGet('/api/content_list.php?active=1');
    renderContentOptionsForBlock(currentBlock());
  } catch (e) {
    setStatus(String(e.message || e), true);
  }
}

async function loadTemplate(templateId) {
  try {
    const tpl = await apiGet('/api/template_get.php?template_id=' + encodeURIComponent(templateId));
    const layout = parseJsonObject(tpl.layout_json);
    state.screen_style = normalizeScreenStyle(layout?.screen_style || {});
    state.currentTemplateId = Number(tpl.id);
    state.blocks = (tpl.blocks || []).map((b) => {
      const style = normalizeBlockBackground(parseJsonObject(b.style_json) || {});
      return {
        block_key: b.block_key,
        x_pct: Number(b.x_pct),
        y_pct: Number(b.y_pct),
        w_pct: Number(b.w_pct),
        h_pct: Number(b.h_pct),
        z_index: Number(b.z_index),
        content_mode: b.content_mode,
        content_type: String(b.content_type || 'image'),
        content_id: b.content_id ? Number(b.content_id) : null,
        background_mode: style.background_mode,
        background_color: style.background_color,
        background_image: style.background_image,
        background_size: style.background_size,
        background_position: style.background_position,
        background_repeat: style.background_repeat
      };
    });
    setStageEditorVisible(true);
    setInspectorVisible(true);
    fillTemplateMeta(tpl); state.selectedBlockIndex = state.blocks.length ? 0 : -1; fillBlockEditor(); renderTemplateList(); renderCanvas(); setStatus('Шаблон загружен');
  } catch (e) { setStatus(String(e.message || e), true); }
}

function newTemplate() {
  state.currentTemplateId = null;
  state.screen_style = normalizeScreenStyle({});
  state.blocks = [{
    block_key: 'main',
    x_pct: 0,
    y_pct: 0,
    w_pct: 100,
    h_pct: 100,
    z_index: 1,
    content_mode: 'dynamic_current',
    content_type: 'image',
    content_id: null,
    background_mode: 'none',
    background_color: '#ffffff',
    background_image: '',
    background_size: 'cover',
    background_position: 'center center',
    background_repeat: 'no-repeat'
  }];
  setStageEditorVisible(true);
  setInspectorVisible(true);
  state.selectedBlockIndex = 0; fillTemplateMeta(null); fillBlockEditor(); renderTemplateList(); renderCanvas(); setStatus('Черновик нового шаблона');
}

function resetTemplateEditor() {
  state.currentTemplateId = null;
  state.blocks = [];
  state.globalShowContentPreview = false;
  setStageEditorVisible(false);
  setInspectorVisible(false);
  state.selectedBlockIndex = -1;
  el.tplName.value = '';
  el.tplDesc.value = '';
  el.tplStatus.value = 'draft';
  state.screen_style = normalizeScreenStyle({});
  el.screenBgMode.value = state.screen_style.mode;
  el.screenBgColor.value = state.screen_style.color;
  el.screenBgImage.value = state.screen_style.image;
  el.screenBgSize.value = state.screen_style.size;
  el.screenBgPosition.value = state.screen_style.position;
  el.screenBgRepeat.value = state.screen_style.repeat;
  syncScreenBackgroundFieldVisibility();
  if (el.globalShowContentPreview) el.globalShowContentPreview.checked = false;
  fillBlockEditor();
  renderTemplateList();
  renderCanvas();
  setStatus('');
}

async function saveTemplate() {
  if (state.saveInProgress) return;
  state.saveInProgress = true;
  setSaveButtonDisabled(true);
  try {
    state.screen_style = normalizeScreenStyle({
      mode: el.screenBgMode.value,
      color: el.screenBgColor.value,
      image: el.screenBgImage.value,
      size: el.screenBgSize.value,
      position: el.screenBgPosition.value,
      repeat: el.screenBgRepeat.value
    });
    const d = await apiPost('/api/template_save.php', {
      template_id: state.currentTemplateId || 0,
      name: el.tplName.value || '',
      description: el.tplDesc.value || '',
      status: el.tplStatus.value || 'draft',
      blocks_json: JSON.stringify(state.blocks),
      screen_style_json: JSON.stringify(state.screen_style)
    });
    state.currentTemplateId = Number(d.template_id); await reloadTemplateList(); setStatus('Шаблон сохранен');
  } catch (e) { setStatus(String(e.message || e), true); }
  finally {
    state.saveInProgress = false;
    setSaveButtonDisabled(false);
  }
}
function openDuplicateTemplateModal() {
  const modal = document.getElementById('duplicateTemplateModal');
  const text = document.getElementById('duplicateTemplateText');
  const name = String(el.tplName.value || '').trim();
  if (text) text.textContent = name ? `Дублировать шаблон "${name}"?` : 'Дублировать выбранный шаблон?';
  if (modal) modal.classList.add('open');
}
function closeDuplicateTemplateModal() {
  const modal = document.getElementById('duplicateTemplateModal');
  if (modal) modal.classList.remove('open');
}
async function confirmDuplicateTemplate() {
  const id = Number(state.currentTemplateId || 0);
  if (id <= 0) { closeDuplicateTemplateModal(); setStatus('Сначала выберите шаблон', true); return; }
  try {
    const d = await apiPost('/api/template_duplicate.php', { template_id: id });
    const newId = Number(d.template_id || 0);
    if (newId <= 0) throw new Error('Некорректный ответ API');
    closeDuplicateTemplateModal();
    await reloadTemplateList();
    await loadTemplate(newId);
    setStatus('Шаблон дублирован');
  } catch (e) {
    closeDuplicateTemplateModal();
    setStatus(String(e.message || e), true);
  }
}

document.getElementById('reloadListBtn').onclick = reloadTemplateList;
document.getElementById('newTemplateBtn').onclick = newTemplate;
document.getElementById('duplicateTemplateBtn').onclick = () => {
  const id = Number(state.currentTemplateId || 0);
  if (id <= 0) { setStatus('Сначала выберите шаблон', true); return; }
  openDuplicateTemplateModal();
};
document.getElementById('openContentBtn').onclick = () => { window.location.href = '/content/'; };
document.getElementById('previewTemplateBtn').onclick = () => {
  const id = Number(state.currentTemplateId || 0);
  if (id <= 0) { setStatus('Сначала выберите или сохраните шаблон', true); return; }
  window.open('/preview/?template_id=' + encodeURIComponent(id), '_blank');
};
document.getElementById('deleteTemplateBtn').onclick = () => {
  const id = Number(state.currentTemplateId || 0);
  if (id <= 0) { setStatus('Сначала выберите шаблон', true); return; }
  document.getElementById('deleteText').textContent = `Удалить шаблон ID ${id}?`;
  document.getElementById('deleteModal').classList.add('open');
};
document.getElementById('addBlockBtn').onclick = () => {
  const i = state.blocks.length + 1;
  state.blocks.push({
    block_key: 'block_' + i,
    x_pct: 10,
    y_pct: 10,
    w_pct: 30,
    h_pct: 30,
    z_index: i,
    content_mode: 'dynamic_current',
    content_type: 'image',
    content_id: null,
    background_mode: 'none',
    background_color: '#ffffff',
    background_image: '',
    background_size: 'cover',
    background_position: 'center center',
    background_repeat: 'no-repeat'
  });
  state.selectedBlockIndex = state.blocks.length - 1; fillBlockEditor(); renderCanvas();
};
document.getElementById('removeBlockBtn').onclick = () => {
  if (state.selectedBlockIndex < 0) return;
  state.blocks.splice(state.selectedBlockIndex, 1);
  state.selectedBlockIndex = Math.min(state.selectedBlockIndex, state.blocks.length - 1);
  fillBlockEditor(); renderCanvas();
};
document.getElementById('saveTemplateBtn').onclick = saveTemplate;
document.getElementById('deleteCancelBtn').onclick = () => { document.getElementById('deleteModal').classList.remove('open'); };
document.getElementById('duplicateTemplateCancelBtn').onclick = closeDuplicateTemplateModal;
document.getElementById('duplicateTemplateConfirmBtn').onclick = confirmDuplicateTemplate;
document.getElementById('deleteConfirmBtn').onclick = async () => {
  const id = Number(state.currentTemplateId || 0);
  if (id <= 0) return;
  try {
    await apiPost('/api/template_delete.php', { template_id: id });
    document.getElementById('deleteModal').classList.remove('open');
    setStatus('Шаблон удален');
    state.currentTemplateId = null;
    await reloadTemplateList();
    resetTemplateEditor();
  } catch (e) {
    setStatus(String(e.message || e), true);
  }
};

['bX','bY','bW','bH','bZ','bMode','bContentId','bBgMode','bBgColor','bBgImage','bBgSize','bBgPosition','bBgRepeat'].forEach((id) => {
  const n = document.getElementById(id);
  n.addEventListener('input', updateBlockFromEditor);
  n.addEventListener('change', updateBlockFromEditor);
});
if (el.bBgMode) {
  el.bBgMode.addEventListener('input', syncBlockBackgroundFieldVisibility);
  el.bBgMode.addEventListener('change', syncBlockBackgroundFieldVisibility);
}
if (el.globalShowContentPreview) {
  el.globalShowContentPreview.addEventListener('change', () => {
    state.globalShowContentPreview = !!el.globalShowContentPreview.checked;
    renderCanvas();
  });
}
['screenBgMode','screenBgColor','screenBgImage','screenBgSize','screenBgPosition','screenBgRepeat'].forEach((id) => {
  const n = document.getElementById(id);
  const onChange = () => {
    state.screen_style = normalizeScreenStyle({
      mode: el.screenBgMode.value,
      color: el.screenBgColor.value,
      image: el.screenBgImage.value,
      size: el.screenBgSize.value,
      position: el.screenBgPosition.value,
      repeat: el.screenBgRepeat.value
    });
    renderCanvas();
  };
  n.addEventListener('input', onChange);
  n.addEventListener('change', onChange);
});
if (el.screenBgMode) {
  el.screenBgMode.addEventListener('input', syncScreenBackgroundFieldVisibility);
  el.screenBgMode.addEventListener('change', syncScreenBackgroundFieldVisibility);
}
if (el.screenBgPickBtn) el.screenBgPickBtn.onclick = () => { loadBgGalleryAndOpen('screenBgImage'); };
if (el.bBgPickBtn) el.bBgPickBtn.onclick = () => { loadBgGalleryAndOpen('bBgImage'); };
document.getElementById('closeLibraryBtn').onclick = closeBgGalleryModal;
document.getElementById('reloadLibraryBtn').onclick = reloadBgGalleryOnly;
document.getElementById('librarySelectBtn').onclick = applyBgGallerySelection;
if (el.libraryUploadBtn) el.libraryUploadBtn.onclick = () => { if (bgLibraryUploadInProgress) return; if (el.libraryUploadFile) el.libraryUploadFile.click(); };
if (el.libraryDeleteBtn) {
  el.libraryDeleteBtn.onclick = () => {
    if (!state.bgGallerySelectedUrl) {
      setStatus('Сначала выберите изображение', true);
      return;
    }
    openDeleteImageModal();
  };
}
if (el.libraryUploadFile) {
  el.libraryUploadFile.addEventListener('change', async () => {
    if (el.libraryUploadFile.files && el.libraryUploadFile.files.length > 0) {
      await uploadBgGalleryImage();
    }
  });
}
const deleteImageCancelBtn = document.getElementById('deleteImageCancelBtn');
if (deleteImageCancelBtn) deleteImageCancelBtn.onclick = closeDeleteImageModal;
const deleteImageConfirmBtn = document.getElementById('deleteImageConfirmBtn');
if (deleteImageConfirmBtn) deleteImageConfirmBtn.onclick = deleteLibraryImage;
if (el.imageLibraryModal) {
  el.imageLibraryModal.onclick = (event) => {
    if (event.target && event.target.id === 'imageLibraryModal') closeBgGalleryModal();
  };
}
const duplicateTemplateModal = document.getElementById('duplicateTemplateModal');
if (duplicateTemplateModal) {
  duplicateTemplateModal.onclick = (event) => {
    if (event.target && event.target.id === 'duplicateTemplateModal') closeDuplicateTemplateModal();
  };
}
const deleteImageModal = document.getElementById('deleteImageModal');
if (deleteImageModal) {
  deleteImageModal.onclick = (event) => {
    if (event.target && event.target.id === 'deleteImageModal') closeDeleteImageModal();
  };
}
el.bType.addEventListener('change', () => {
  const b = currentBlock();
  if (!b) return;
  b.content_type = el.bType.value;
  b.content_id = null;
  renderContentOptionsForBlock(b);
  updateBlockFromEditor();
});

(async function boot() {
  const listToolbar = document.getElementById('reloadListBtn')?.closest('.toolbar');
  const openContentBtn = document.getElementById('openContentBtn');
  if (listToolbar && openContentBtn) {
    listToolbar.insertBefore(openContentBtn, listToolbar.firstChild);
  }
  const stageToolbar = document.getElementById('saveTemplateBtn')?.closest('.toolbar');
  const stagePanel = document.getElementById('canvas')?.closest('.panel');
  if (stageToolbar) stageToolbar.classList.add('stageToolbar');
  if (stagePanel) stagePanel.classList.add('stagePanel');
  if (stageToolbar && el.status) stageToolbar.appendChild(el.status);
  await reloadContentList();
  await reloadTemplateList();
  resetTemplateEditor();
})();
</script>
</body>
</html>
