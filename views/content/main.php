<?php
declare(strict_types=1);
?><!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <link rel="stylesheet" href="/vendor/ckeditor5/ckeditor5.css">
    <style>
        html, body { height: 100%; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; font-family: Tahoma, sans-serif; background: #f5f6f8; color: #1a1a1a; }
        .wrap { flex: 1; display: grid; grid-template-columns: 320px 1fr 360px; gap: 12px; padding: 12px; min-height: 0; }
        .panel { background: #fff; border: 1px solid #d7dbe0; border-radius: 8px; padding: 10px; }
        .wrap .panel { display: flex; flex-direction: column; min-height: 0; }
        .panel h2 { margin: 0 0 10px; font-size: 16px; }
        .toolbar { display: flex; gap: 8px; margin-bottom: 8px; }
        .iconBtn { width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1; }
        .listFilter { margin-bottom: 8px; }
        .list { flex: 1; min-height: 0; overflow: auto; border: 1px solid #e0e3e8; border-radius: 6px; }
        .item { padding: 8px; border-bottom: 1px solid #eceff3; cursor: pointer; font-size: 12px; }
        .item:hover { background: #f8fafc; }
        .item.active { background: #e8f2ff; }
        .status { margin-left: auto; align-self: center; display: none; padding: 6px 10px; border-radius: 6px; border: 1px solid transparent; font-size: 12px; line-height: 1.2; }
        .status.show { display: inline-flex; }
        .status.success { color: #0f5132; background: #d1e7dd; border-color: #badbcc; }
        .status.error { color: #842029; background: #f8d7da; border-color: #f5c2c7; }
        .row { display: flex; gap: 8px; }
        .row > * { flex: 1; }
        .hiddenFile { display: none !important; }
        .uploadTwoCols { display: grid; grid-template-columns: 1fr 180px; gap: 8px; align-items: end; }
        .uploadTwoCols .urlCol { min-width: 0; }
        .uploadTwoCols .urlLabel { margin: 0 0 4px 0; font-size: 13px; line-height: 1.2; }
        .uploadActions { display: grid; gap: 6px; }
        .uploadTwoCols input { height: 36px; }
        #cMediaUrl { font-size: 12px; }
        #uploadBtn { width: 100%; height: 36px; min-height: 36px; max-height: 36px; box-sizing: border-box; margin: 0; padding: 0 12px; font-size: 13px; font-weight: 400; line-height: 1; display: inline-flex; align-items: center; justify-content: center; align-self: end; white-space: nowrap; }
        #openLibraryBtn { width: 100%; height: 36px; min-height: 36px; max-height: 36px; box-sizing: border-box; margin: 0; padding: 0 12px; font-size: 13px; font-weight: 400; line-height: 1; display: inline-flex; align-items: center; justify-content: center; align-self: end; white-space: nowrap; }
        #openVideoLibraryBtn { width: 100%; height: 36px; min-height: 36px; max-height: 36px; box-sizing: border-box; margin: 0; padding: 0 12px; font-size: 13px; font-weight: 400; line-height: 1; display: inline-flex; align-items: center; justify-content: center; align-self: end; white-space: nowrap; }
        #openPptLibraryBtn { width: 100%; height: 36px; min-height: 36px; max-height: 36px; box-sizing: border-box; margin: 0; padding: 0 12px; font-size: 13px; font-weight: 400; line-height: 1; display: inline-flex; align-items: center; justify-content: center; align-self: end; white-space: nowrap; }
        label { display: block; margin: 6px 0; font-size: 13px; }
        input, select, textarea, button { font: inherit; }
        input, select, textarea { width: 100%; box-sizing: border-box; padding: 6px; border: 1px solid #c8ced6; border-radius: 6px; }
        button { padding: 7px 10px; border: 1px solid #1d5fbf; background: transparent; color: #1d5fbf; border-radius: 6px; cursor: pointer; }
        .preview { margin-top: 8px; border: 1px solid #e2e8f0; border-radius: 6px; height: 220px; display: flex; align-items: center; justify-content: center; background: #f8fafc; overflow: hidden; }
        .preview img, .preview video, .preview iframe { max-width: 100%; max-height: 100%; display: block; }
        .previewHtml { width: 100%; height: 100%; overflow: auto; padding: 12px; box-sizing: border-box; color: #1a1a1a; }
        .previewPanel { overflow: hidden; }
        .htmlEditorWrap { margin-top: 8px; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; min-height: 0; flex: 1; display: none; flex-direction: column; overflow: hidden; }
        #htmlEditorContainer { flex: 1; min-height: 0; display: flex; flex-direction: column; overflow: hidden; }
        .htmlEditorToolbar { display: flex; gap: 6px; flex-wrap: wrap; padding: 8px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
        .htmlToolBtn { min-width: 34px; height: 30px; padding: 0 8px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #c8ced6; background: #fff; color: #1d5fbf; border-radius: 6px; cursor: pointer; font-size: 12px; line-height: 1; }
        .htmlToolBtn:hover { background: #eef5ff; }
        .htmlToolBtn.labelBtn { padding: 0 10px; min-width: auto; }
        .htmlEditorSurface { flex: 1; min-height: 0; overflow: hidden; }
        .htmlEditorWrap .ck.ck-editor { flex: 1; min-height: 0; display: flex; flex-direction: column; border: 0; }
        .htmlEditorWrap .ck-editor__main { flex: 1; min-height: 0; display: flex; flex-direction: column; }
        .htmlEditorWrap .ck-editor__editable { flex: 1; min-height: 240px; max-height: none; }
        .htmlEditorWrap .ck-content img { max-width: 100%; height: auto; }
        .previewPanel #previewControls { display: flex; flex-direction: column; min-height: 0; flex: 1; overflow: hidden; }
        .previewPanel .preview { margin-top: 0; height: auto; min-height: 0; flex: 1; }
        .libraryHead { display: flex; align-items: center; gap: 8px; margin-top: 0; margin-bottom: 8px; }
        .libraryBtn { min-width: 38px; width: 38px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
        .libraryGrid { border: 1px solid #e2e8f0; border-radius: 6px; background: #fff; padding: 8px; max-height: 58vh; overflow: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 8px; align-content: start; align-items: start; }
        .libraryUploadBtn { width: 140px; height: 30px; min-height: 30px; max-height: 30px; margin: 0; padding: 0 10px; }
        .libraryDeleteBtn { width: 120px; height: 30px; min-height: 30px; max-height: 30px; margin: 0; padding: 0 10px; border-color: #b91c1c; color: #b91c1c; }
        .librarySelectBtn { width: 120px; height: 30px; min-height: 30px; max-height: 30px; margin: 0; padding: 0 10px; }
        .libraryCloseBtn { margin-left: auto; min-width: 34px; width: 34px; height: 30px; padding: 0; font-size: 20px; line-height: 1; display: inline-flex; align-items: center; justify-content: center; }
        .uploadProgressWrap { display: none; align-items: center; gap: 8px; margin: 0 0 8px; }
        .uploadProgressTrack { flex: 1; height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
        .uploadProgressBar { width: 0%; height: 100%; background: #1d5fbf; transition: width .15s linear; }
        .uploadProgressText { min-width: 44px; text-align: right; font-size: 12px; color: #334155; }
        .libraryItem { border: 1px solid #d8dee8; border-radius: 6px; padding: 6px; cursor: pointer; background: #f8fafc; align-self: start; }
        .libraryItem.active { border-color: #1d5fbf; box-shadow: 0 0 0 1px #1d5fbf inset; background: #eef5ff; }
        .libraryItem img { width: 100%; height: 70px; object-fit: cover; border-radius: 4px; display: block; background: #e8edf5; }
        .libraryName { margin-top: 4px; font-size: 11px; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .footer { margin: 0 12px 12px; color: #5a6472; font-size: 12px; border-top: 1px solid #e3e7ed; padding-top: 10px; flex: 0 0 auto; }
        .modalBack { position: fixed; inset: 0; background: rgba(0,0,0,.35); display: none; align-items: center; justify-content: center; z-index: 50; }
        .modalBack.open { display: flex; }
        .modal { width: min(440px, calc(100vw - 24px)); background: #fff; border: 1px solid #d7dbe0; border-radius: 8px; padding: 12px; }
        #imageLibraryModal .modal { width: 80vw; height: 80vh; max-width: 80vw; max-height: 80vh; display: flex; flex-direction: column; min-height: 0; }
        #imageLibraryModal .libraryGrid { flex: 1; min-height: 0; max-height: none; overflow: auto; }
        .modal h3 { margin: 0 0 10px; font-size: 16px; }
        .typeGrid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px; }
        .typeBtn { padding: 10px; border: 1px solid #c9d2de; border-radius: 6px; background: #fff; cursor: pointer; text-align: left; }
        .typeBtn.active { border-color: #1d5fbf; box-shadow: 0 0 0 1px #1d5fbf inset; }
        .typeBtn.disabled { opacity: .5; cursor: not-allowed; }
    </style>
</head>
<body>
<div class="wrap">
    <section class="panel">
        <h2>Контент</h2>
        <div class="toolbar">
            <button class="iconBtn secondary" id="toTemplateBtn" type="button" title="Шаблонизатор" aria-label="Шаблонизатор">&#x1F4D0;</button>
            <button class="iconBtn" id="newBtn" type="button" title="Новое изображение" aria-label="Новое изображение">&#x2795;</button>
            <button class="iconBtn secondary" id="duplicateBtn" type="button" title="Дублировать" aria-label="Дублировать">&#x29C9;</button>
            <button class="iconBtn secondary" id="reloadBtn" type="button" title="Обновить список" aria-label="Обновить список">&#x21bb;</button>
            <button class="iconBtn secondary" id="deleteBtn" type="button" title="Удалить" aria-label="Удалить">&#x1F5D1;</button>
        </div>
        <div class="listFilter">
            <label for="contentTypeFilter">Фильтр типа контента</label>
            <select id="contentTypeFilter">
                <option value="">Все</option>
                <option value="image">Изображение</option>
                <option value="html">HTML</option>
                <option value="video">Видео</option>
                <option value="ppt">Презентация</option>
            </select>
        </div>
        <div id="list" class="list"></div>
    </section>

    <section class="panel previewPanel">
        <h2 id="previewTitle">Тип контента</h2>
        <p id="previewEmpty" style="font-size:12px;color:#5e6e82;">Выберите контент из списка или создайте новый.</p>
        <div id="previewControls" style="display:none;">
            <div class="toolbar">
                <button class="iconBtn" id="saveBtn" type="button" title="Сохранить" aria-label="Сохранить">&#x1F4BE;</button>
                <span id="status" class="status"></span>
            </div>
            <div class="preview">
                <img id="previewImg" alt="">
                <video id="previewVideo" muted loop playsinline style="display:none;"></video>
                <iframe id="previewPpt" style="display:none;border:0;background:#fff;"></iframe>
            </div>
            <div id="previewHtml" class="previewHtml" style="display:none;"></div>
            <div id="htmlEditorWrap" class="htmlEditorWrap">
                <div id="htmlEditorContainer">
                    <div class="htmlEditorToolbar" id="htmlEditorToolbar">
                        <button type="button" class="htmlToolBtn labelBtn" id="openHtmlLibraryBtn" title="Вставить изображение из галереи">Изображение</button>
                    </div>
                    <div id="cHtmlEditor" class="htmlEditorSurface"></div>
                    <textarea id="cHtmlBody" style="display:none;"></textarea>
                </div>
            </div></div>
    </section>

    <section class="panel inspectorPanel">
        <h2>нспектор свойств</h2>
        <p id="editorEmpty" style="font-size:12px;color:#5e6e82;">Выберите контент из списка или создайте новый.</p>
        <div id="editorControls" style="display:none;">
            <div class="row">
                <label>Название <input id="cTitle" type="text"></label>
                <label>Статус
                    <select id="cActive">
                        <option value="1">Активный</option>
                        <option value="0">Неактивный</option>
                    </select>
                </label>
            </div>

            <label>Анимация
                <select id="cAnimation">
                    <option value="none">без анимации</option>
                    <option value="fade_in">появление</option>
                    <option value="slide_up">снизу вверх</option>
                    <option value="slide_left">справа налево</option>
                    <option value="zoom_in">масштаб</option>
                </select>
            </label>

            <input id="cMediaUrl" type="hidden" value="">
            <div id="imageControls">
                <button type="button" id="openLibraryBtn">иблиотека изображений</button>
                <div class="row">
                    <label>Ширина, px <input id="pImageWidth" type="number" min="1" step="1"></label>
                    <label>Высота, px <input id="pImageHeight" type="number" min="1" step="1"></label>
                </div>
                <label>Масштаб, % <input id="pImageScale" type="number" min="1" max="500" step="1" value="100"></label>
                <label>Режи изображения
                    <select id="pImageFluidMode">
                        <option value="fixed">фиксированный разер</option>
                        <option value="fluid">адаптивный (img-fluid)</option>
                    </select>
                </label>
                <label>Позиционирование
                    <select id="pImagePosition">
                        <option value="center">центр</option>
                        <option value="top_left">сверху слева</option>
                        <option value="top_center">сверху по центру</option>
                        <option value="top_right">сверху справа</option>
                        <option value="center_left">по центру слева</option>
                        <option value="center_right">по центру справа</option>
                        <option value="bottom_left">снизу слева</option>
                        <option value="bottom_center">снизу по центру</option>
                        <option value="bottom_right">снизу справа</option>
                    </select>
                </label>
            </div>
            <div id="videoControls" style="display:none;">
                <button type="button" id="openVideoLibraryBtn">иблиотека видео</button>
                <div class="row">
                    <label>Ширина, px <input id="pVideoWidth" type="number" min="1" step="1"></label>
                    <label>Высота, px <input id="pVideoHeight" type="number" min="1" step="1"></label>
                </div>
                <label>Масштаб, % <input id="pVideoScale" type="number" min="1" max="500" step="1" value="100"></label>
                <label>Режи видео
                    <select id="pVideoFluidMode">
                        <option value="fixed">фиксированный разер</option>
                        <option value="fluid">адаптивный</option>
                    </select>
                </label>
                <label>Позиционирование
                    <select id="pVideoPosition">
                        <option value="center">центр</option>
                        <option value="top_left">сверху слева</option>
                        <option value="top_center">сверху по центру</option>
                        <option value="top_right">сверху справа</option>
                        <option value="center_left">по центру слева</option>
                        <option value="center_right">по центру справа</option>
                        <option value="bottom_left">снизу слева</option>
                        <option value="bottom_center">снизу по центру</option>
                        <option value="bottom_right">снизу справа</option>
                    </select>
                </label>
                <div class="row">
                    <label>Зацикливание
                        <select id="pVideoLoop">
                            <option value="1">вкл</option>
                            <option value="0">выкл</option>
                        </select>
                    </label>
                    <label>Звук
                        <select id="pVideoSound">
                            <option value="0">выкл</option>
                            <option value="1">вкл</option>
                        </select>
                    </label>
                </div>
            </div>
            <div id="pptControls" style="display:none;">
                            <button type="button" id="openPptLibraryBtn">иблиотека презентаций</button>
                            <div class="row">
                                <label>Ширина, px <input id="pPptWidth" type="number" min="1" step="1"></label>
                                <label>Высота, px <input id="pPptHeight" type="number" min="1" step="1"></label>
                            </div>
                            <label>Масштаб, % <input id="pPptScale" type="number" min="1" max="500" step="1" value="100"></label>
                            <label>Режи
                                <select id="pPptFluidMode">
                                    <option value="fixed">фиксированный разер</option>
                                    <option value="fluid">адаптивный</option>
                                </select>
                            </label>
                            <label>Позиционирование
                                <select id="pPptPosition">
                                    <option value="center">центр</option>
                                    <option value="top_left">сверху слева</option>
                                    <option value="top_center">сверху по центру</option>
                                    <option value="top_right">сверху справа</option>
                                    <option value="center_left">по центру слева</option>
                                    <option value="center_right">по центру справа</option>
                                    <option value="bottom_left">снизу слева</option>
                                    <option value="bottom_center">снизу по центру</option>
                                    <option value="bottom_right">снизу справа</option>
                                </select>
                            </label>
                            <div class="row">
                                <label>Стартовая страница <input id="pPptStartPage" type="number" min="1" step="1" value="1"></label>
                                <label>Страниц в файле <input id="pPptTotalPages" type="number" min="1" step="1" value="1"></label>
                            </div>
                            <div class="row">
                                <label>нтервал, сек <input id="pPptInterval" type="number" min="1" max="600" step="1" value="5"></label>
                                <label>Зацикливание
                                    <select id="pPptLoop">
                                        <option value="1">вкл</option>
                                        <option value="0">выкл</option>
                                    </select>
                                </label>
                            </div>
                        </div>

        </div>
    </section>
</div>
<div class="footer">Версия проекта: <strong><?= h($projectVersion ?? '0.0.0-dev') ?></strong></div>
<div class="modalBack" id="newTypeModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="newTypeTitle">
        <h3 id="newTypeTitle">Выбор типа контента</h3>
        <div class="typeGrid" id="typeGrid">
            <button type="button" class="typeBtn active" data-type="image">Изображение</button>
            <button type="button" class="typeBtn disabled" data-type="text" disabled>Текст</button>
            <button type="button" class="typeBtn" data-type="html">HTML</button>
            <button type="button" class="typeBtn" data-type="video">Видео</button>
            <button type="button" class="typeBtn" data-type="ppt">Презентация</button>
        </div>
        <div class="row">
            <button type="button" id="newTypeCancelBtn">Отена</button>
            <button type="button" id="newTypeCreateBtn">Создать</button>
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
            <button type="button" id="deleteImageCancelBtn">Отена</button>
            <button type="button" id="deleteImageConfirmBtn" class="libraryDeleteBtn">Удалить</button>
        </div>
    </div>
</div>
<div class="modalBack" id="deleteContentModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="deleteContentTitle">
        <h3 id="deleteContentTitle">Удаление контента</h3>
        <p id="deleteContentText" style="margin:0 0 12px;color:#334155;">Удалить выбранный контент?</p>
        <div class="row">
            <button type="button" id="deleteContentCancelBtn">Отена</button>
            <button type="button" id="deleteContentConfirmBtn" class="libraryDeleteBtn">Удалить</button>
        </div>
    </div>
</div>
<div class="modalBack" id="duplicateContentModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="duplicateContentTitle">
        <h3 id="duplicateContentTitle">Дублирование контента</h3>
        <p id="duplicateContentText" style="margin:0 0 12px;color:#334155;">Дублировать выбранный контент?</p>
        <div class="row">
            <button type="button" id="duplicateContentCancelBtn">Отена</button>
            <button type="button" id="duplicateContentConfirmBtn">Дублировать</button>
        </div>
    </div>
</div>

<script type="module">
import * as CKEDITOR from '/vendor/ckeditor5/ckeditor5.js';
import ru from '/vendor/ckeditor5/translations/ru.js';
window.CKEDITOR_LOCAL = CKEDITOR;
window.CKEDITOR_LOCAL_TRANSLATIONS = [ru];
window.dispatchEvent(new Event('ckeditor-local-ready'));
</script>
<script>
const state = { list: [], library: [], currentId: 0, currentType: 'image', listFilterType: '', saveInProgress: false, libraryMode: 'image' };
let selectedLibraryUrl = '';
let selectedLibraryName = '';
let imageBaseWidth = 0;
let imageBaseHeight = 0;
let videoBaseWidth = 0;
let videoBaseHeight = 0;
let pptBaseWidth = 0;
let pptBaseHeight = 0;
let pptPageTimer = null;
let pptCurrentPage = 1;
let pptProbeCache = Object.create(null);
let htmlEditorReady = false;
let htmlEditorInstance = null;
let htmlEditorReadyPromise = null;
let libraryUploadInProgress = false;
let libraryUploadProgressTimer = null;
let libraryUploadFinalizing = false;
const el = {
  list: document.getElementById('list'),
  status: document.getElementById('status'),
  previewTitle: document.getElementById('previewTitle'),
  previewEmpty: document.getElementById('previewEmpty'),
  previewControls: document.getElementById('previewControls'),
  editorEmpty: document.getElementById('editorEmpty'),
  editorControls: document.getElementById('editorControls'),
  cActive: document.getElementById('cActive'),
  cTitle: document.getElementById('cTitle'),
  cAnimation: document.getElementById('cAnimation'),
  cMediaUrl: document.getElementById('cMediaUrl'),
  cHtmlEditor: document.getElementById('cHtmlEditor'),
  cHtmlBody: document.getElementById('cHtmlBody'),
  pImageWidth: document.getElementById('pImageWidth'),
  pImageHeight: document.getElementById('pImageHeight'),
  pImageScale: document.getElementById('pImageScale'),
  pImageFluidMode: document.getElementById('pImageFluidMode'),
  pImagePosition: document.getElementById('pImagePosition'),
  pVideoWidth: document.getElementById('pVideoWidth'),
  pVideoHeight: document.getElementById('pVideoHeight'),
  pVideoScale: document.getElementById('pVideoScale'),
  pVideoFluidMode: document.getElementById('pVideoFluidMode'),
  pVideoPosition: document.getElementById('pVideoPosition'),
  pVideoLoop: document.getElementById('pVideoLoop'),
  pVideoSound: document.getElementById('pVideoSound'),
  pPptWidth: document.getElementById('pPptWidth'),
  pPptHeight: document.getElementById('pPptHeight'),
  pPptScale: document.getElementById('pPptScale'),
  pPptFluidMode: document.getElementById('pPptFluidMode'),
  pPptPosition: document.getElementById('pPptPosition'),
  pPptStartPage: document.getElementById('pPptStartPage'),
  pPptTotalPages: document.getElementById('pPptTotalPages'),
  pPptInterval: document.getElementById('pPptInterval'),
  pPptLoop: document.getElementById('pPptLoop'),
  uploadFile: document.getElementById('libraryUploadFile'),
  previewImg: document.getElementById('previewImg'),
  previewVideo: document.getElementById('previewVideo'),
  previewPpt: document.getElementById('previewPpt'),
  previewHtml: document.getElementById('previewHtml'),
  imageLibrary: document.getElementById('imageLibrary'),
  imageControls: document.getElementById('imageControls'),
  videoControls: document.getElementById('videoControls'),
  pptControls: document.getElementById('pptControls'),
  htmlEditorWrap: document.getElementById('htmlEditorWrap')
};
function setLabelText(labelEl, text) {
  if (!labelEl) return;
  for (const node of labelEl.childNodes) {
    if (node.nodeType === Node.TEXT_NODE) {
      node.nodeValue = text;
      return;
    }
  }
  labelEl.prepend(document.createTextNode(text));
}
function normalizeInspectorTexts() {
  const inspectorTitle = document.querySelector('.inspectorPanel h2');
  if (inspectorTitle) inspectorTitle.textContent = 'Инспектор свойств';

  if (el.cTitle) setLabelText(el.cTitle.parentElement, 'Название ');
  if (el.cActive) setLabelText(el.cActive.parentElement, 'Статус');
  if (el.cAnimation && el.cAnimation.parentElement) {
    el.cAnimation.parentElement.style.display = 'none';
  }

  const activeOpts = el.cActive ? el.cActive.options : null;
  if (activeOpts && activeOpts.length >= 2) {
    activeOpts[0].text = 'Активный';
    activeOpts[1].text = 'Неактивный';
  }

  const imageBtn = document.getElementById('openLibraryBtn');
  const videoBtn = document.getElementById('openVideoLibraryBtn');
  const pptBtn = document.getElementById('openPptLibraryBtn');
  if (imageBtn) imageBtn.textContent = 'Библиотека изображений';
  if (videoBtn) videoBtn.textContent = 'Библиотека видео';
  if (pptBtn) pptBtn.textContent = 'Библиотека презентаций';

  if (el.pPptWidth) setLabelText(el.pPptWidth.parentElement, 'Ширина, px ');
  if (el.pPptHeight) setLabelText(el.pPptHeight.parentElement, 'Высота, px ');
  if (el.pPptScale) setLabelText(el.pPptScale.parentElement, 'Масштаб, % ');
  if (el.pPptFluidMode) {
    setLabelText(el.pPptFluidMode.parentElement, 'Режим');
    const modeOpts = el.pPptFluidMode.options;
    if (modeOpts && modeOpts.length >= 2) {
      modeOpts[0].text = 'фиксированный размер';
      modeOpts[1].text = 'адаптивный';
    }
  }
  if (el.pPptPosition) setLabelText(el.pPptPosition.parentElement, 'Позиционирование');
  if (el.pPptStartPage) setLabelText(el.pPptStartPage.parentElement, 'Стартовая страница ');
  if (el.pPptTotalPages) setLabelText(el.pPptTotalPages.parentElement, 'Страниц в файле ');
  if (el.pPptInterval) setLabelText(el.pPptInterval.parentElement, 'Интервал, сек ');
  if (el.pPptLoop) setLabelText(el.pPptLoop.parentElement, 'Зацикливание');
  ensurePptAnimationControls();
  const pptAnim = document.getElementById('pPptPageAnim');
  const pptAnimMs = document.getElementById('pPptAnimDuration');
  if (pptAnim) setLabelText(pptAnim.parentElement, 'Анимация смены');
  if (pptAnimMs) setLabelText(pptAnimMs.parentElement, 'Длительность, мс ');
}
function ensurePptAnimationControls() {
  const loopSelect = document.getElementById('pPptLoop');
  if (!loopSelect) return;
  if (document.getElementById('pPptPageAnim') && document.getElementById('pPptAnimDuration')) return;
  const row = loopSelect.closest('.row');
  if (!row || !row.parentElement) return;
  const wrap = document.createElement('div');
  wrap.className = 'row';
  wrap.innerHTML = '' +
    '<label>Анимация смены' +
      '<select id="pPptPageAnim">' +
        '<option value="fade">появление</option>' +
        '<option value="slide_left">сдвиг влево</option>' +
        '<option value="slide_up">сдвиг вверх</option>' +
        '<option value="zoom">масштаб</option>' +
        '<option value="flip">переворот</option>' +
        '<option value="none">без анимации</option>' +
      '</select>' +
    '</label>' +
    '<label>Длительность, мс <input id="pPptAnimDuration" type="number" min="100" max="5000" step="50" value="700"></label>';
  row.parentElement.insertBefore(wrap, row.nextSibling);
}

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
  const btn = document.getElementById('saveBtn');
  if (!btn) return;
  btn.disabled = !!disabled;
  btn.style.opacity = disabled ? '0.6' : '';
  btn.style.cursor = disabled ? 'not-allowed' : '';
}
function setEditorVisible(visible, type = 'image') {
  const viewType = String(type || 'image');
  const previewBox = el.previewImg ? el.previewImg.parentElement : null;
  if (el.previewTitle) {
    const label = viewType === 'html' ? 'HTML' : (viewType === 'video' ? 'Видео' : (viewType === 'ppt' ? 'Презентация' : 'Изображение'));
    el.previewTitle.textContent = visible ? label : 'Тип контента';
  }
  if (el.previewControls) el.previewControls.style.display = visible ? 'flex' : 'none';
  if (el.previewEmpty) el.previewEmpty.style.display = visible ? 'none' : 'block';
  if (el.editorControls) el.editorControls.style.display = visible ? 'block' : 'none';
  if (el.editorEmpty) el.editorEmpty.style.display = visible ? 'none' : 'block';
  if (el.imageControls) el.imageControls.style.display = visible && viewType === 'image' ? 'block' : 'none';
  if (el.videoControls) el.videoControls.style.display = visible && viewType === 'video' ? 'block' : 'none';
  if (el.pptControls) el.pptControls.style.display = visible && viewType === 'ppt' ? 'block' : 'none';
  if (el.htmlEditorWrap) el.htmlEditorWrap.style.display = visible && viewType === 'html' ? 'flex' : 'none';
  if (previewBox) previewBox.style.display = visible && viewType !== 'html' ? 'flex' : 'none';
  if (el.previewHtml) el.previewHtml.style.display = 'none';
}
function parseJsonSafe(v) {
  if (typeof v !== 'string' || v.trim() === '') return {};
  try {
    const parsed = JSON.parse(v);
    return parsed && typeof parsed === 'object' ? parsed : {};
  } catch (_) {
    return {};
  }
}
function stopPptPreviewTimer() {
  if (pptPageTimer) {
    clearInterval(pptPageTimer);
    pptPageTimer = null;
  }
}
function parsePdfPageCount(url) {
  const m = String(url || '').match(/[#&?]pages=(\d+)/i);
  if (!m) return 0;
  const v = Number(m[1] || 0);
  return Number.isFinite(v) && v > 0 ? Math.floor(v) : 0;
}
function getContentAnimationValue() {
  const raw = String(el.cAnimation && el.cAnimation.value ? el.cAnimation.value : 'none');
  return ['none', 'fade_in', 'slide_up', 'slide_left', 'zoom_in'].includes(raw) ? raw : 'none';
}
function buildImageDataJson() {
  const widthPx = Number(el.pImageWidth.value || 0);
  const heightPx = Number(el.pImageHeight.value || 0);
  const scalePct = Number(el.pImageScale.value || 100);
  const fluid = el.pImageFluidMode && el.pImageFluidMode.value === 'fluid';
  return {
    image: {
      width_px: widthPx > 0 ? widthPx : null,
      height_px: heightPx > 0 ? heightPx : null,
      scale_pct: scalePct > 0 ? scalePct : 100,
      fluid: fluid,
      mode: fluid ? 'fluid' : 'fixed',
      position: el.pImagePosition.value || 'center'
    }
  };
}
function buildPptDataJson() {
  const widthPx = Number(el.pPptWidth.value || 0);
  const heightPx = Number(el.pPptHeight.value || 0);
  const scalePct = Number(el.pPptScale.value || 100);
  const fluid = el.pPptFluidMode && el.pPptFluidMode.value === 'fluid';
  const startPage = Math.max(1, Number(el.pPptStartPage.value || 1));
  const totalPages = Math.max(1, Number(el.pPptTotalPages.value || 1));
  const intervalSec = Math.max(1, Number(el.pPptInterval.value || 5));
  const loop = String(el.pPptLoop?.value || '1') === '1';
  const pptAnim = document.getElementById('pPptPageAnim');
  const pptAnimMs = document.getElementById('pPptAnimDuration');
  const pageAnimRaw = String(pptAnim && pptAnim.value ? pptAnim.value : 'fade');
  const pageAnim = ['none', 'fade', 'slide_left', 'slide_up', 'zoom', 'flip'].includes(pageAnimRaw) ? pageAnimRaw : 'fade';
  const animMs = Math.max(100, Math.min(5000, Number(pptAnimMs && pptAnimMs.value ? pptAnimMs.value : 700)));
  return {
    ppt: {
      width_px: widthPx > 0 ? widthPx : null,
      height_px: heightPx > 0 ? heightPx : null,
      scale_pct: scalePct > 0 ? scalePct : 100,
      fluid: fluid,
      mode: fluid ? 'fluid' : 'fixed',
      position: el.pPptPosition.value || 'center',
      start_page: startPage,
      total_pages: totalPages,
      interval_sec: intervalSec,
      loop: loop,
      page_animation: pageAnim,
      animation_ms: animMs
    }
  };
}
function buildVideoDataJson() {
  const widthPx = Number(el.pVideoWidth.value || 0);
  const heightPx = Number(el.pVideoHeight.value || 0);
  const scalePct = Number(el.pVideoScale.value || 100);
  const fluid = el.pVideoFluidMode && el.pVideoFluidMode.value === 'fluid';
  const loop = String(el.pVideoLoop?.value || '1') === '1';
  const sound = String(el.pVideoSound?.value || '0') === '1';
  return {
    video: {
      width_px: widthPx > 0 ? widthPx : null,
      height_px: heightPx > 0 ? heightPx : null,
      scale_pct: scalePct > 0 ? scalePct : 100,
      fluid: fluid,
      mode: fluid ? 'fluid' : 'fixed',
      position: el.pVideoPosition.value || 'center',
      loop: loop,
      sound: sound
    }
  };
}
function buildHtmlDataJson() {
  return {};
}
function syncDataJson() {
  if (state.currentType === 'html') return JSON.stringify(buildHtmlDataJson());
  if (state.currentType === 'video') return JSON.stringify(buildVideoDataJson());
  if (state.currentType === 'ppt') return JSON.stringify(buildPptDataJson());
  return JSON.stringify(buildImageDataJson());
}
function escapeHtmlAttr(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}
function getCkeditorApi() {
  return new Promise((resolve, reject) => {
    const readyApi = window.CKEDITOR_LOCAL || null;
    if (readyApi && readyApi.ClassicEditor) {
      resolve(readyApi);
      return;
    }

    const timeoutId = window.setTimeout(() => {
      window.removeEventListener('ckeditor-local-ready', onReady);
      reject(new Error('CKEditor не загружен'));
    }, 5000);

    function onReady() {
      const api = window.CKEDITOR_LOCAL || null;
      if (api && api.ClassicEditor) {
        window.clearTimeout(timeoutId);
        window.removeEventListener('ckeditor-local-ready', onReady);
        resolve(api);
      }
    }

    window.addEventListener('ckeditor-local-ready', onReady);
  });
}
async function ensureHtmlEditor() {
  if (htmlEditorInstance) return htmlEditorInstance;
  if (htmlEditorReadyPromise) return htmlEditorReadyPromise;
  if (!el.cHtmlEditor) return null;

  htmlEditorReadyPromise = (async () => {
    const CKEDITOR = await getCkeditorApi();
    const editor = await CKEDITOR.ClassicEditor.create(el.cHtmlEditor, {
      licenseKey: 'GPL',
      language: 'ru',
      translations: Array.isArray(window.CKEDITOR_LOCAL_TRANSLATIONS) ? window.CKEDITOR_LOCAL_TRANSLATIONS : [],
      plugins: [
        CKEDITOR.Essentials,
        CKEDITOR.Paragraph,
        CKEDITOR.Heading,
        CKEDITOR.AutoLink,
        CKEDITOR.Bold,
        CKEDITOR.Italic,
        CKEDITOR.Underline,
        CKEDITOR.Strikethrough,
        CKEDITOR.Link,
        CKEDITOR.List,
        CKEDITOR.TodoList,
        CKEDITOR.Indent,
        CKEDITOR.IndentBlock,
        CKEDITOR.Alignment,
        CKEDITOR.BlockQuote,
        CKEDITOR.CodeBlock,
        CKEDITOR.HorizontalLine,
        CKEDITOR.Font,
        CKEDITOR.FontSize,
        CKEDITOR.FontFamily,
        CKEDITOR.FontColor,
        CKEDITOR.FontBackgroundColor,
        CKEDITOR.Highlight,
        CKEDITOR.RemoveFormat,
        CKEDITOR.Table,
        CKEDITOR.TableToolbar,
        CKEDITOR.TableProperties,
        CKEDITOR.TableCellProperties,
        CKEDITOR.SourceEditing,
        CKEDITOR.Image,
        CKEDITOR.ImageToolbar,
        CKEDITOR.ImageCaption,
        CKEDITOR.ImageStyle,
        CKEDITOR.ImageResize,
        CKEDITOR.ImageInsert,
        CKEDITOR.Base64UploadAdapter,
        CKEDITOR.GeneralHtmlSupport
      ],
      toolbar: [
        'undo', 'redo', '|',
        'heading', '|',
        'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|',
        'bold', 'italic', 'underline', 'strikethrough', 'removeFormat', '|',
        'link', 'alignment', '|',
        'bulletedList', 'numberedList', 'todoList', 'outdent', 'indent', '|',
        'blockQuote', 'codeBlock', 'horizontalLine', 'insertTable', '|',
        'sourceEditing'
      ],
      image: {
        toolbar: ['imageTextAlternative', 'toggleImageCaption', 'imageStyle:inline', 'imageStyle:block', 'resizeImage']
      },
      table: {
        contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties']
      },
      htmlSupport: {
        allow: [
          {
            name: /.*/,
            attributes: true,
            classes: true,
            styles: true
          }
        ]
      }
    });

    editor.model.document.on('change:data', () => {
      syncHtmlShadow();
      if (state.currentType === 'html') syncPreview();
    });

    const initialValue = String(el.cHtmlBody?.value || '');
    if (initialValue) {
      editor.setData(initialValue);
    }

    htmlEditorInstance = editor;
    htmlEditorReady = true;
    return editor;
  })().catch((error) => {
    htmlEditorReadyPromise = null;
    throw error;
  });

  return htmlEditorReadyPromise;
}
function bindHtmlEditorEvents() {
  return;
}
function saveHtmlSelection() {
  return;
}
function restoreHtmlSelection() {
  return false;
}
function syncHtmlShadow() {
  if (!el.cHtmlBody) return;
  if (htmlEditorInstance) {
    el.cHtmlBody.value = htmlEditorInstance.getData();
    return;
  }
  el.cHtmlBody.value = String(el.cHtmlBody.value || '');
}
function getHtmlValue() {
  if (htmlEditorInstance) return String(htmlEditorInstance.getData() || '');
  return String(el.cHtmlBody?.value || '');
}
function setHtmlValue(value) {
  const v = String(value || '');
  if (el.cHtmlBody) el.cHtmlBody.value = v;
  if (htmlEditorInstance) {
    if (htmlEditorInstance.getData() !== v) {
      htmlEditorInstance.setData(v);
    }
    return;
  }
  if (el.cHtmlEditor) {
    el.cHtmlEditor.innerHTML = v;
  }
}
function syncPreview() {
  if (state.currentType === 'html') {
    if (el.previewImg) el.previewImg.style.display = 'none';
    if (el.previewVideo) {
      el.previewVideo.style.display = 'none';
      el.previewVideo.removeAttribute('src');
      el.previewVideo.load();
    }
    if (el.previewPpt) {
      el.previewPpt.style.display = 'none';
      el.previewPpt.removeAttribute('src');
    }
    stopPptPreviewTimer();
    if (el.previewHtml) el.previewHtml.style.display = 'none';
    syncHtmlShadow();
    return;
  }

  if (state.currentType === 'video') {
    const url = (el.cMediaUrl.value || '').trim();
    if (el.previewImg) el.previewImg.style.display = 'none';
    if (el.previewPpt) {
      el.previewPpt.style.display = 'none';
      el.previewPpt.removeAttribute('src');
    }
    stopPptPreviewTimer();
    if (el.previewVideo) {
      const data = buildVideoDataJson();
      const p = data.video || {};
      const widthPx = Math.max(0, Number(p.width_px || 0));
      const heightPx = Math.max(0, Number(p.height_px || 0));
      const fluid = p.fluid === true;
      const loop = p.loop !== false;
      const sound = p.sound === true;
      const position = String(p.position || 'center');
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
      const [justify, align] = map[position] || map.center;
      const box = el.previewVideo.parentElement;
      if (box) {
        box.style.justifyContent = justify;
        box.style.alignItems = align;
      }
      el.previewVideo.style.display = 'block';
      el.previewVideo.src = url;
      el.previewVideo.controls = true;
      el.previewVideo.loop = loop;
      el.previewVideo.muted = !sound;
      if (fluid) {
        el.previewVideo.style.width = '100%';
        el.previewVideo.style.height = 'auto';
      } else {
        el.previewVideo.style.width = widthPx > 0 ? (widthPx + 'px') : 'auto';
        el.previewVideo.style.height = heightPx > 0 ? (heightPx + 'px') : 'auto';
      }
    }
    if (el.previewHtml) {
      el.previewHtml.style.display = 'none';
      el.previewHtml.innerHTML = '';
    }
    return;
  }

  if (state.currentType === 'ppt') {
    const url = (el.cMediaUrl.value || '').trim();
    if (el.previewImg) {
      el.previewImg.style.display = 'none';
      el.previewImg.removeAttribute('src');
    }
    if (el.previewVideo) {
      el.previewVideo.style.display = 'none';
      el.previewVideo.removeAttribute('src');
      el.previewVideo.load();
    }
    if (el.previewHtml) {
      el.previewHtml.style.display = 'none';
      el.previewHtml.innerHTML = '';
    }
    stopPptPreviewTimer();
    if (el.previewPpt) {
      const data = buildPptDataJson();
      const p = data.ppt || {};
      const fluid = p.fluid === true;
      const widthPx = Math.max(0, Number(p.width_px || 0));
      const heightPx = Math.max(0, Number(p.height_px || 0));
      const startPage = Math.max(1, Number(p.start_page || 1));
      const probe = pptProbeCache[url] && typeof pptProbeCache[url] === 'object' ? pptProbeCache[url] : {};
      const previewPages = Array.isArray(probe.preview_pages) ? probe.preview_pages.filter((u) => typeof u === 'string' && u.trim() !== '') : [];
      const totalPages = Math.max(1, Number(p.total_pages || 1));
      const position = String(p.position || 'center');
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
      const [justify, align] = map[position] || map.center;
      const box = el.previewPpt.parentElement;
      if (box) {
        box.style.justifyContent = justify;
        box.style.alignItems = align;
      }
      const useOwnPreview = previewPages.length > 0 && !!el.previewImg;
      if (useOwnPreview) {
        el.previewPpt.style.display = 'none';
        el.previewPpt.removeAttribute('src');
        el.previewImg.style.display = 'block';
        if (fluid) {
          el.previewImg.style.width = '100%';
          el.previewImg.style.height = 'auto';
          el.previewImg.style.maxWidth = '100%';
          el.previewImg.style.maxHeight = '100%';
        } else {
          el.previewImg.style.width = widthPx > 0 ? (widthPx + 'px') : '100%';
          el.previewImg.style.height = heightPx > 0 ? (heightPx + 'px') : '100%';
          el.previewImg.style.maxWidth = '100%';
          el.previewImg.style.maxHeight = '100%';
        }
      } else {
        el.previewPpt.style.display = 'block';
        if (fluid) {
          el.previewPpt.style.width = '100%';
          el.previewPpt.style.height = '100%';
        } else {
          el.previewPpt.style.width = widthPx > 0 ? (widthPx + 'px') : '100%';
          el.previewPpt.style.height = heightPx > 0 ? (heightPx + 'px') : '100%';
        }
      }
      const setPage = (page) => {
        const pageNum = Math.max(1, Math.min(totalPages, Math.floor(Number(page || 1))));
        pptCurrentPage = pageNum;
        if (useOwnPreview) {
          const idx = Math.max(0, Math.min(previewPages.length - 1, pageNum - 1));
          el.previewImg.src = previewPages[idx] || '';
        } else {
          el.previewPpt.src = url ? `${url}#page=${pageNum}` : '';
        }
      };
      setPage(startPage);
    }
    return;
  }

  if (el.previewHtml) {
    el.previewHtml.style.display = 'none';
    el.previewHtml.innerHTML = '';
  }
  if (el.previewImg) el.previewImg.style.display = 'block';
  if (el.previewVideo) {
    el.previewVideo.style.display = 'none';
    el.previewVideo.removeAttribute('src');
    el.previewVideo.load();
  }
  if (el.previewPpt) {
    el.previewPpt.style.display = 'none';
    el.previewPpt.removeAttribute('src');
  }
  stopPptPreviewTimer();
  const url = (el.cMediaUrl.value || '').trim();
  el.previewImg.src = url;
  const data = buildImageDataJson();
  const widthPx = Number(data.image.width_px || 0);
  const heightPx = Number(data.image.height_px || 0);
  const fluid = !!data.image.fluid;
  const position = String(data.image.position || 'center');
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
  const [justify, align] = map[position] || map.center;
  const box = el.previewImg.parentElement;
  box.style.justifyContent = justify;
  box.style.alignItems = align;
  if (fluid) {
    el.previewImg.style.width = '100%';
    el.previewImg.style.height = 'auto';
    el.previewImg.style.maxWidth = '100%';
    el.previewImg.style.maxHeight = '100%';
  } else {
    el.previewImg.style.width = widthPx > 0 ? (widthPx + 'px') : 'auto';
    el.previewImg.style.height = heightPx > 0 ? (heightPx + 'px') : 'auto';
    el.previewImg.style.maxWidth = '100%';
    el.previewImg.style.maxHeight = '100%';
  }
}
function loadImageNaturalSize(url) {
  return new Promise((resolve) => {
    const src = String(url || '').trim();
    if (!src) { resolve(null); return; }
    const img = new Image();
    img.onload = () => resolve({ width: Number(img.naturalWidth || 0), height: Number(img.naturalHeight || 0) });
    img.onerror = () => resolve(null);
    img.src = src;
  });
}
function loadVideoNaturalSize(url) {
  return new Promise((resolve) => {
    const src = String(url || '').trim();
    if (!src) { resolve(null); return; }
    const video = document.createElement('video');
    video.preload = 'metadata';
    video.onloadedmetadata = () => {
      resolve({ width: Number(video.videoWidth || 0), height: Number(video.videoHeight || 0) });
    };
    video.onerror = () => resolve(null);
    video.src = src;
  });
}
function syncScaleFromDimensions() {
  if (imageBaseWidth <= 0 || imageBaseHeight <= 0) return;
  const widthPx = Number(el.pImageWidth.value || 0);
  const heightPx = Number(el.pImageHeight.value || 0);
  if (widthPx <= 0 || heightPx <= 0) return;
  const ratioW = widthPx / imageBaseWidth;
  const ratioH = heightPx / imageBaseHeight;
  const ratio = (ratioW + ratioH) / 2;
  const scale = Math.max(1, Math.round(ratio * 100));
  el.pImageScale.value = String(scale);
}
function applyScaleToDimensions() {
  if (imageBaseWidth <= 0 || imageBaseHeight <= 0) return;
  const scale = Math.max(1, Number(el.pImageScale.value || 100));
  el.pImageWidth.value = String(Math.max(1, Math.round(imageBaseWidth * scale / 100)));
  el.pImageHeight.value = String(Math.max(1, Math.round(imageBaseHeight * scale / 100)));
}
function syncVideoScaleFromDimensions() {
  if (videoBaseWidth <= 0 || videoBaseHeight <= 0) return;
  const widthPx = Number(el.pVideoWidth.value || 0);
  const heightPx = Number(el.pVideoHeight.value || 0);
  if (widthPx <= 0 || heightPx <= 0) return;
  const ratioW = widthPx / videoBaseWidth;
  const ratioH = heightPx / videoBaseHeight;
  const ratio = (ratioW + ratioH) / 2;
  const scale = Math.max(1, Math.round(ratio * 100));
  el.pVideoScale.value = String(scale);
}
function applyVideoScaleToDimensions() {
  if (videoBaseWidth <= 0 || videoBaseHeight <= 0) return;
  const scale = Math.max(1, Number(el.pVideoScale.value || 100));
  el.pVideoWidth.value = String(Math.max(1, Math.round(videoBaseWidth * scale / 100)));
  el.pVideoHeight.value = String(Math.max(1, Math.round(videoBaseHeight * scale / 100)));
}
function syncPptScaleFromDimensions() {
  if (pptBaseWidth <= 0 || pptBaseHeight <= 0) return;
  const widthPx = Number(el.pPptWidth.value || 0);
  const heightPx = Number(el.pPptHeight.value || 0);
  if (widthPx <= 0 || heightPx <= 0) return;
  const ratioW = widthPx / pptBaseWidth;
  const ratioH = heightPx / pptBaseHeight;
  const ratio = (ratioW + ratioH) / 2;
  const scale = Math.max(1, Math.round(ratio * 100));
  el.pPptScale.value = String(scale);
}
function applyPptScaleToDimensions() {
  if (pptBaseWidth <= 0 || pptBaseHeight <= 0) return;
  const scale = Math.max(1, Number(el.pPptScale.value || 100));
  el.pPptWidth.value = String(Math.max(1, Math.round(pptBaseWidth * scale / 100)));
  el.pPptHeight.value = String(Math.max(1, Math.round(pptBaseHeight * scale / 100)));
}
async function setDimensionsFromImage(url, force = false) {
  const src = String(url || '').trim();
  if (!src) return;
  const dims = await loadImageNaturalSize(src);
  if (!dims || dims.width <= 0 || dims.height <= 0) return;
  imageBaseWidth = dims.width;
  imageBaseHeight = dims.height;
  const hasWidth = Number(el.pImageWidth.value || 0) > 0;
  const hasHeight = Number(el.pImageHeight.value || 0) > 0;
  if (force || !hasWidth || !hasHeight) {
    el.pImageWidth.value = String(dims.width);
    el.pImageHeight.value = String(dims.height);
    el.pImageScale.value = '100';
  } else {
    syncScaleFromDimensions();
  }
  syncDataJson();
  syncPreview();
}
async function setDimensionsFromVideo(url, force = false) {
  const src = String(url || '').trim();
  if (!src) return;
  const dims = await loadVideoNaturalSize(src);
  if (!dims || dims.width <= 0 || dims.height <= 0) return;
  videoBaseWidth = dims.width;
  videoBaseHeight = dims.height;
  const hasWidth = Number(el.pVideoWidth.value || 0) > 0;
  const hasHeight = Number(el.pVideoHeight.value || 0) > 0;
  if (force || !hasWidth || !hasHeight) {
    el.pVideoWidth.value = String(dims.width);
    el.pVideoHeight.value = String(dims.height);
    el.pVideoScale.value = '100';
  } else {
    syncVideoScaleFromDimensions();
  }
  syncDataJson();
  syncPreview();
}
async function setDimensionsFromPpt(url, force = false) {
  const src = String(url || '').trim();
  if (!src) return;
  try {
    const probe = await apiGet('/api/content_ppt_probe.php?url=' + encodeURIComponent(src));
    pptProbeCache[src] = probe || {};
    const width = Math.max(0, Number(probe.width_px || 0));
    const height = Math.max(0, Number(probe.height_px || 0));
    if (width > 0 && height > 0) {
      pptBaseWidth = width;
      pptBaseHeight = height;
    }
    const pages = Math.max(0, Number(probe.pages || 0));
    const hasWidth = Number(el.pPptWidth.value || 0) > 0;
    const hasHeight = Number(el.pPptHeight.value || 0) > 0;
    if (width > 0 && height > 0 && (force || !hasWidth || !hasHeight)) {
      el.pPptWidth.value = String(width);
      el.pPptHeight.value = String(height);
      el.pPptScale.value = '100';
    } else if (width > 0 && height > 0) {
      syncPptScaleFromDimensions();
    }
    if (pages > 0) {
      el.pPptTotalPages.value = String(pages);
    } else {
      const pagesFromUrl = parsePdfPageCount(src);
      if (pagesFromUrl > 0) el.pPptTotalPages.value = String(pagesFromUrl);
    }
    syncDataJson();
    syncPreview();
  } catch (_) {
    pptProbeCache[src] = {};
    const pagesFromUrl = parsePdfPageCount(src);
    if (pagesFromUrl > 0) {
      el.pPptTotalPages.value = String(pagesFromUrl);
      syncDataJson();
      syncPreview();
    }
  }
}
function nowDraft() {
  state.currentType = String(pendingCreateType || 'image');
  state.currentId = 0;
  el.cActive.value = '1';
  el.cTitle.value = '';
  el.cAnimation.value = 'none';
  el.cMediaUrl.value = '';
  setHtmlValue('');
  el.pImageWidth.value = '';
  el.pImageHeight.value = '';
  el.pImageScale.value = '100';
  el.pImageFluidMode.value = 'fixed';
  imageBaseWidth = 0;
  imageBaseHeight = 0;
  el.pImagePosition.value = 'center';
  el.pVideoWidth.value = '';
  el.pVideoHeight.value = '';
  el.pVideoScale.value = '100';
  el.pVideoFluidMode.value = 'fixed';
  el.pVideoPosition.value = 'center';
  el.pVideoLoop.value = '1';
  el.pVideoSound.value = '0';
  videoBaseWidth = 0;
  videoBaseHeight = 0;
  el.pPptWidth.value = '';
  el.pPptHeight.value = '';
  el.pPptScale.value = '100';
  el.pPptFluidMode.value = 'fixed';
  pptBaseWidth = 0;
  pptBaseHeight = 0;
  el.pPptPosition.value = 'center';
  el.pPptStartPage.value = '1';
  el.pPptTotalPages.value = '1';
  el.pPptInterval.value = '5';
  el.pPptLoop.value = '1';
  const pptAnim = document.getElementById('pPptPageAnim');
  const pptAnimMs = document.getElementById('pPptAnimDuration');
  if (pptAnim) pptAnim.value = 'fade';
  if (pptAnimMs) pptAnimMs.value = '700';
  syncDataJson();
  syncPreview();
}
function renderLibrary() {
  if (!el.imageLibrary) return;
  el.imageLibrary.innerHTML = '';
  if (!Array.isArray(state.library) || state.library.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'libraryName';
    empty.textContent = state.libraryMode === 'video' ? 'Пока нет загруженных видео' : 'Пока нет загруженных изображений';
    el.imageLibrary.appendChild(empty);
    return;
  }
  const currentUrl = selectedLibraryUrl || String(el.cMediaUrl.value || '').trim();
  for (const item of state.library) {
    const card = document.createElement('div');
    card.className = 'libraryItem' + (currentUrl === String(item.url || '') ? ' active' : '');
    card.title = String(item.name || '');
    card.onclick = () => {
      selectedLibraryUrl = String(item.url || '');
      selectedLibraryName = String(item.name || '');
      renderLibrary();
    };
    if (state.libraryMode === 'video') {
      const video = document.createElement('video');
      video.src = String(item.url || '');
      video.muted = true;
      video.playsInline = true;
      video.preload = 'metadata';
      video.style.width = '100%';
      video.style.height = '70px';
      video.style.objectFit = 'cover';
      video.style.borderRadius = '4px';
      video.style.display = 'block';
      video.style.background = '#e8edf5';
      card.appendChild(video);
    } else if (state.libraryMode === 'ppt') {
      const previewUrl = String(item.preview_url || '').trim();
      if (previewUrl) {
        const img = document.createElement('img');
        img.src = previewUrl;
        img.alt = String(item.name || '');
        card.appendChild(img);
      } else {
        const placeholder = document.createElement('div');
        placeholder.style.width = '100%';
        placeholder.style.height = '70px';
        placeholder.style.borderRadius = '4px';
        placeholder.style.display = 'flex';
        placeholder.style.alignItems = 'center';
        placeholder.style.justifyContent = 'center';
        placeholder.style.background = '#e8edf5';
        placeholder.style.color = '#334155';
        placeholder.style.fontSize = '12px';
        placeholder.textContent = 'PPT/PDF';
        card.appendChild(placeholder);
      }
    } else {
      const img = document.createElement('img');
      img.src = String(item.url || '');
      img.alt = String(item.name || '');
      card.appendChild(img);
    }
    const name = document.createElement('div');
    name.className = 'libraryName';
    name.textContent = String(item.name || '');
    card.appendChild(name);
    el.imageLibrary.appendChild(card);
  }
}
function openLibraryModal() {
  const modal = document.getElementById('imageLibraryModal');
  const uploadBtn = document.getElementById('libraryUploadBtn');
  const deleteBtn = document.getElementById('libraryDeleteBtn');
  const selectBtn = document.getElementById('librarySelectBtn');
  if (uploadBtn) uploadBtn.textContent = state.libraryMode === 'video' ? 'Загрузка видео' : 'Загрузка';
  if (deleteBtn) deleteBtn.textContent = 'Удалить';
  if (selectBtn) selectBtn.textContent = 'Выбрать';
  const baseUrl = state.libraryMode === 'html_insert' ? '' : String(el.cMediaUrl.value || '').trim();
  selectedLibraryUrl = baseUrl;
  const selected = state.library.find((i) => String(i.url || '') === baseUrl);
  selectedLibraryName = selected ? String(selected.name || '') : '';
  if (modal) modal.classList.add('open');
}
function closeLibraryModal() {
  const modal = document.getElementById('imageLibraryModal');
  if (modal) modal.classList.remove('open');
  state.libraryMode = 'image';
}
function insertHtmlImageToEditor(url) {
  const src = String(url || '').trim();
  if (!src) return;
  ensureHtmlEditor().then((editor) => {
    if (!editor) return;
    editor.focus();
    editor.execute('insertImage', { source: [{ src }] });
    syncHtmlShadow();
    if (state.currentType === 'html') syncPreview();
  }).catch((error) => {
    setStatus(String(error.message || error), true);
  });
}
function openDeleteImageModal() {
  const modal = document.getElementById('deleteImageModal');
  const text = document.getElementById('deleteImageText');
  if (text) {
    if (state.libraryMode === 'video') {
      text.textContent = selectedLibraryName ? `Delete video "${selectedLibraryName}"?` : 'Delete selected video?';
    } else if (state.libraryMode === 'ppt') {
      text.textContent = selectedLibraryName ? `Delete presentation "${selectedLibraryName}"?` : 'Delete selected presentation?';
    } else {
      text.textContent = selectedLibraryName ? `Delete image "${selectedLibraryName}"?` : 'Delete selected image?';
    }
  }
  if (modal) modal.classList.add('open');
}
function closeDeleteImageModal() {
  const modal = document.getElementById('deleteImageModal');
  if (modal) modal.classList.remove('open');
}
function openDeleteContentModal() {
  const modal = document.getElementById('deleteContentModal');
  const text = document.getElementById('deleteContentText');
  const title = String(el.cTitle.value || '').trim();
  if (text) text.textContent = title ? `Удалить контент "${title}"?` : 'Удалить выбранный контент?';
  if (modal) modal.classList.add('open');
}
function closeDeleteContentModal() {
  const modal = document.getElementById('deleteContentModal');
  if (modal) modal.classList.remove('open');
}
function openDuplicateContentModal() {
  const modal = document.getElementById('duplicateContentModal');
  const text = document.getElementById('duplicateContentText');
  const title = String(el.cTitle.value || '').trim();
  if (text) text.textContent = title ? `Дублировать контент "${title}"?` : 'Дублировать выбранный контент?';
  if (modal) modal.classList.add('open');
}
function closeDuplicateContentModal() {
  const modal = document.getElementById('duplicateContentModal');
  if (modal) modal.classList.remove('open');
}
let pendingCreateType = 'image';
function openNewTypeModal() {
  const modal = document.getElementById('newTypeModal');
  pendingCreateType = 'image';
  document.querySelectorAll('#typeGrid .typeBtn').forEach((n) => n.classList.remove('active'));
  const first = document.querySelector('#typeGrid .typeBtn[data-type="image"]');
  if (first) first.classList.add('active');
  modal.classList.add('open');
}
function closeNewTypeModal() {
  const modal = document.getElementById('newTypeModal');
  modal.classList.remove('open');
}
function resetEditor() {
  state.currentType = 'image';
  state.currentId = 0;
  el.cActive.value = '1';
  el.cTitle.value = '';
  el.cAnimation.value = 'none';
  el.cMediaUrl.value = '';
  setHtmlValue('');
  el.pImageWidth.value = '';
  el.pImageHeight.value = '';
  el.pImageScale.value = '100';
  el.pImageFluidMode.value = 'fixed';
  imageBaseWidth = 0;
  imageBaseHeight = 0;
  el.pImagePosition.value = 'center';
  el.pVideoWidth.value = '';
  el.pVideoHeight.value = '';
  el.pVideoScale.value = '100';
  el.pVideoFluidMode.value = 'fixed';
  el.pVideoPosition.value = 'center';
  el.pVideoLoop.value = '1';
  el.pVideoSound.value = '0';
  videoBaseWidth = 0;
  videoBaseHeight = 0;
  el.pPptWidth.value = '';
  el.pPptHeight.value = '';
  el.pPptScale.value = '100';
  el.pPptFluidMode.value = 'fixed';
  pptBaseWidth = 0;
  pptBaseHeight = 0;
  el.pPptPosition.value = 'center';
  el.pPptStartPage.value = '1';
  el.pPptTotalPages.value = '1';
  el.pPptInterval.value = '5';
  el.pPptLoop.value = '1';
  const pptAnim = document.getElementById('pPptPageAnim');
  const pptAnimMs = document.getElementById('pPptAnimDuration');
  if (pptAnim) pptAnim.value = 'fade';
  if (pptAnimMs) pptAnimMs.value = '700';
  syncDataJson();
  syncPreview();
  setStatus('');
  setEditorVisible(false, 'image');
}

async function apiGet(url) {
  const res = await fetch(url, { cache: 'no-store' });
  const p = await res.json();
  if (!p.ok) throw new Error(p.error || 'Ошибка API');
  return p.data;
}
async function apiPost(url, payload) {
  const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(payload) });
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
function setLibraryUploadState(inProgress, text = '') {
  const uploadBtn = document.getElementById('libraryUploadBtn');
  const reloadBtn = document.getElementById('reloadLibraryBtn');
  const delBtn = document.getElementById('libraryDeleteBtn');
  const selectBtn = document.getElementById('librarySelectBtn');
  const closeBtn = document.getElementById('closeLibraryBtn');
  const progressWrap = document.getElementById('libraryUploadProgressWrap');
  const progressBar = document.getElementById('libraryUploadProgressBar');
  const progressText = document.getElementById('libraryUploadProgressText');
  const active = !!inProgress;
  libraryUploadInProgress = active;

  if (uploadBtn) {
    if (!uploadBtn.dataset.defaultLabel) uploadBtn.dataset.defaultLabel = uploadBtn.textContent || 'Загрузка';
    uploadBtn.disabled = active;
    uploadBtn.textContent = uploadBtn.dataset.defaultLabel;
  }
  if (reloadBtn) reloadBtn.disabled = active;
  if (delBtn) delBtn.disabled = active;
  if (selectBtn) selectBtn.disabled = active;
  if (closeBtn) closeBtn.disabled = active;

  if (progressWrap) progressWrap.style.display = active ? 'flex' : 'none';
  if (!active) {
    if (libraryUploadProgressTimer) {
      clearInterval(libraryUploadProgressTimer);
      libraryUploadProgressTimer = null;
    }
    libraryUploadFinalizing = false;
    if (progressBar) progressBar.style.width = '0%';
    if (progressText) progressText.textContent = '0%';
  }
}


function renderList() {
  el.list.innerHTML = '';
  for (const row of state.list) {
    const d = document.createElement('div');
    d.className = 'item' + (Number(row.id) === Number(state.currentId) ? ' active' : '');
    const t = String(row.type || 'image');
    const label = t === 'html' ? 'HTML' : (t === 'video' ? 'Видео' : (t === 'ppt' ? 'Презентация' : 'Изображение'));
    const labelMapSafe = { image: 'Изображение', html: 'HTML', video: 'Видео', ppt: 'Презентация' };
    const safeLabel = labelMapSafe[t] || 'Изображение';
    d.textContent = `[${safeLabel}] ${row.title} (ID ${row.id})`;
    d.onclick = () => loadById(row.id);
    el.list.appendChild(d);
  }
}

async function reloadList() {
  try {
    const type = String(state.listFilterType || '').trim();
    const query = type ? ('/api/content_list.php?active=-1&type=' + encodeURIComponent(type)) : '/api/content_list.php?active=-1';
    state.list = await apiGet(query);
    renderList();
  } catch (e) {
    setStatus(String(e.message || e), true);
  }
}
async function reloadLibrary() {
  try {
    const endpoint = state.libraryMode === 'video' ? '/api/content_video_library.php' : (state.libraryMode === 'ppt' ? '/api/content_ppt_library.php' : '/api/content_image_library.php');
    state.library = await apiGet(endpoint);
    renderLibrary();
  } catch (e) {
    setStatus(String(e.message || e), true);
  }
}

async function loadById(id) {
  try {
    const row = await apiGet('/api/content_get.php?content_id=' + encodeURIComponent(id));
    state.currentId = Number(row.id);
    state.currentType = String(row.type || 'image');
    el.cActive.value = String(Number(row.is_active || 0));
    el.cTitle.value = row.title || '';
    el.cMediaUrl.value = row.media_url || '';
    setHtmlValue(String(row.body || ''));

    const data = parseJsonSafe(row.data_json || '');
    const image = data.image && typeof data.image === 'object' ? data.image : {};
    const video = data.video && typeof data.video === 'object' ? data.video : {};
    const ppt = data.ppt && typeof data.ppt === 'object' ? data.ppt : {};
    el.cAnimation.value = ['none', 'fade_in', 'slide_up', 'slide_left', 'zoom_in'].includes(String(data.animation || ''))
      ? String(data.animation || 'none')
      : 'none';
    el.pImageWidth.value = image.width_px ? String(Number(image.width_px || 0)) : '';
    el.pImageHeight.value = image.height_px ? String(Number(image.height_px || 0)) : '';
    el.pImageScale.value = image.scale_pct ? String(Number(image.scale_pct || 100)) : '100';
    el.pImageFluidMode.value = (image.fluid === true || String(image.mode || '') === 'fluid') ? 'fluid' : 'fixed';
    el.pImagePosition.value = String(image.position || 'center');
    el.pVideoWidth.value = video.width_px ? String(Number(video.width_px || 0)) : '';
    el.pVideoHeight.value = video.height_px ? String(Number(video.height_px || 0)) : '';
    el.pVideoScale.value = video.scale_pct ? String(Number(video.scale_pct || 100)) : '100';
    el.pVideoFluidMode.value = (video.fluid === true || String(video.mode || '') === 'fluid') ? 'fluid' : 'fixed';
    el.pVideoPosition.value = String(video.position || 'center');
    el.pVideoLoop.value = video.loop === false ? '0' : '1';
    el.pVideoSound.value = video.sound === true ? '1' : '0';
    el.pPptWidth.value = ppt.width_px ? String(Number(ppt.width_px || 0)) : '';
    el.pPptHeight.value = ppt.height_px ? String(Number(ppt.height_px || 0)) : '';
    el.pPptScale.value = ppt.scale_pct ? String(Number(ppt.scale_pct || 100)) : '100';
    el.pPptFluidMode.value = (ppt.fluid === true || String(ppt.mode || '') === 'fluid') ? 'fluid' : 'fixed';
    el.pPptPosition.value = String(ppt.position || 'center');
    el.pPptStartPage.value = String(Math.max(1, Number(ppt.start_page || 1)));
    el.pPptTotalPages.value = String(Math.max(1, Number(ppt.total_pages || 1)));
    el.pPptInterval.value = String(Math.max(1, Number(ppt.interval_sec || 5)));
    el.pPptLoop.value = ppt.loop === false ? '0' : '1';
    const pptAnim = document.getElementById('pPptPageAnim');
    const pptAnimMs = document.getElementById('pPptAnimDuration');
    if (pptAnim) {
      const a = String(ppt.page_animation || 'fade');
      pptAnim.value = ['none', 'fade', 'slide_left', 'slide_up', 'zoom', 'flip'].includes(a) ? a : 'fade';
    }
    if (pptAnimMs) {
      pptAnimMs.value = String(Math.max(100, Math.min(5000, Number(ppt.animation_ms || 700))));
    }

    syncDataJson();
    syncPreview();
    if (state.currentType === 'image') {
      await setDimensionsFromImage(el.cMediaUrl.value || '', false);
    } else if (state.currentType === 'video') {
      await setDimensionsFromVideo(el.cMediaUrl.value || '', false);
    } else if (state.currentType === 'ppt') {
      setDimensionsFromPpt(el.cMediaUrl.value || '', false);
    } else if (state.currentType === 'html') {
      await ensureHtmlEditor();
      bindHtmlEditorEvents();
      setHtmlValue(String(row.body || ''));
    }
    setEditorVisible(true, state.currentType);
    renderList();
    setStatus(state.currentType === 'html' ? 'HTML загружен' : (state.currentType === 'video' ? 'Видео загружено' : (state.currentType === 'ppt' ? 'Презентация загружена' : 'Изображение загружено')));
  } catch (e) {
    setStatus(String(e.message || e), true);
  }
}

async function uploadFile() {
  try {
    const file = el.uploadFile.files && el.uploadFile.files[0] ? el.uploadFile.files[0] : null;
    if (!file) { setStatus('Сначала выберите файл', true); return; }
    const fd = new FormData();
    const isVideoMode = state.libraryMode === 'video';
    const isPptMode = state.libraryMode === 'ppt';
    fd.append(isVideoMode ? 'video' : (isPptMode ? 'ppt' : 'image'), file);
    const endpoint = isVideoMode ? '/api/content_upload_video.php' : (isPptMode ? '/api/content_upload_ppt.php' : '/api/content_upload_image.php');

    setLibraryUploadState(true);
    const payload = await uploadWithProgress(endpoint, fd, (pct) => {
      const progressBar = document.getElementById('libraryUploadProgressBar');
      const progressText = document.getElementById('libraryUploadProgressText');
      if (progressBar) progressBar.style.width = String(pct) + '%';
      if (progressText) progressText.textContent = String(pct) + '%';
      if (pct >= 96 && !libraryUploadFinalizing) {
        libraryUploadFinalizing = true;
        if (libraryUploadProgressTimer) clearInterval(libraryUploadProgressTimer);
        libraryUploadProgressTimer = setInterval(() => {
          const current = progressBar ? parseInt(progressBar.style.width || '96', 10) : 96;
          if (current >= 99) return;
          const next = current + 1;
          if (progressBar) progressBar.style.width = String(next) + '%';
          if (progressText) progressText.textContent = String(next) + '%';
        }, 500);
      }
    });
    if (libraryUploadProgressTimer) {
      clearInterval(libraryUploadProgressTimer);
      libraryUploadProgressTimer = null;
    }
    const progressBar = document.getElementById('libraryUploadProgressBar');
    const progressText = document.getElementById('libraryUploadProgressText');
    if (progressBar) progressBar.style.width = '100%';
    if (progressText) progressText.textContent = '100%';

    selectedLibraryUrl = String(payload.data.url || '');
    await reloadLibrary();
    renderLibrary();
    setStatus(isVideoMode ? 'Видео загружено. Нажите "Выбрать"' : (isPptMode ? 'Презентация загружена. Нажите "Выбрать"' : 'Файл загружен. Нажите "Выбрать"'));
  } catch (e) {
    setStatus(String(e.message || e), true);
  } finally {
    setLibraryUploadState(false);
  }
}
async function deleteLibraryImage() {
  if (!selectedLibraryUrl) {
    setStatus(state.libraryMode === 'video' ? 'Сначала выберите видео' : (state.libraryMode === 'ppt' ? 'Сначала выберите презентацию' : 'Сначала выберите изображение'), true);
    return;
  }
  try {
    const isVideoMode = state.libraryMode === 'video';
    const isPptMode = state.libraryMode === 'ppt';
    await apiPost(isVideoMode ? '/api/content_video_delete.php' : (isPptMode ? '/api/content_ppt_delete.php' : '/api/content_image_delete.php'), {
      url: selectedLibraryUrl,
      current_content_id: state.currentId || 0
    });
    if (String(el.cMediaUrl.value || '').trim() === selectedLibraryUrl) {
      el.cMediaUrl.value = '';
      syncPreview();
    }
    selectedLibraryUrl = '';
    selectedLibraryName = '';
    await reloadLibrary();
    renderLibrary();
    closeDeleteImageModal();
    setStatus(isVideoMode ? 'Видео удалено из библиотеки' : (isPptMode ? 'Презентация удалена из библиотеки' : 'Изображение удалено из библиотеки'));
  } catch (e) {
    closeDeleteImageModal();
    setStatus(String(e.message || e), true);
  }
}

async function saveCurrent() {
  if (state.saveInProgress) return;
  state.saveInProgress = true;
  setSaveButtonDisabled(true);
  try {
    const payload = {
      content_id: state.currentId || 0,
      type: state.currentType,
      title: el.cTitle.value || '',
      body: state.currentType === 'html' ? getHtmlValue() : '',
      media_url: (state.currentType === 'image' || state.currentType === 'video' || state.currentType === 'ppt') ? (el.cMediaUrl.value || '') : '',
      data_json: syncDataJson(),
      is_active: el.cActive.value,
      publish_from: '',
      publish_to: ''
    };
    const res = await apiPost('/api/content_save.php', payload);
    state.currentId = Number(res.content_id);
    await reloadList();
    await loadById(state.currentId);
    setStatus(state.currentType === 'html' ? 'HTML сохранен' : (state.currentType === 'video' ? 'Видео сохранено' : (state.currentType === 'ppt' ? 'Презентация сохранена' : 'Изображение сохранено')));
  } catch (e) {
    setStatus(String(e.message || e), true);
  } finally {
    state.saveInProgress = false;
    setSaveButtonDisabled(false);
  }
}

async function deleteCurrent() {
  if (state.currentId <= 0) { setStatus('Сначала выберите запись', true); return; }
  openDeleteContentModal();
}

async function confirmDeleteCurrent() {
  if (state.currentId <= 0) { closeDeleteContentModal(); setStatus('Сначала выберите запись', true); return; }
  try {
    await apiPost('/api/content_delete.php', { content_id: state.currentId });
    closeDeleteContentModal();
    resetEditor();
    await reloadList();
    setStatus('Контент удален');
  } catch (e) {
    closeDeleteContentModal();
    setStatus(String(e.message || e), true);
  }
}

document.getElementById('newBtn').onclick = openNewTypeModal;
document.getElementById('duplicateBtn').onclick = () => {
  if (state.currentId <= 0) { setStatus('Сначала выберите запись', true); return; }
  openDuplicateContentModal();
};
async function confirmDuplicateContent() {
  if (state.currentId <= 0) { closeDuplicateContentModal(); setStatus('Сначала выберите запись', true); return; }
  try {
    const d = await apiPost('/api/content_duplicate.php', { content_id: state.currentId });
    const newId = Number(d.content_id || 0);
    if (newId <= 0) throw new Error('Некорректный ответ API');
    closeDuplicateContentModal();
    await reloadList();
    await loadById(newId);
    setStatus('Контент дублирован');
  } catch (e) {
    closeDuplicateContentModal();
    setStatus(String(e.message || e), true);
  }
}
document.getElementById('reloadBtn').onclick = reloadList;
document.getElementById('contentTypeFilter').onchange = async (event) => {
  state.listFilterType = String(event.target && event.target.value ? event.target.value : '');
  await reloadList();
};
document.getElementById('reloadLibraryBtn').onclick = reloadLibrary;
document.getElementById('saveBtn').onclick = saveCurrent;
document.getElementById('deleteBtn').onclick = deleteCurrent;
document.getElementById('openLibraryBtn').onclick = async () => {
  state.libraryMode = 'image';
  el.uploadFile.accept = 'image/png,image/jpeg,image/webp,image/gif,image/svg+xml';
  await reloadLibrary();
  openLibraryModal();
};
document.getElementById('openVideoLibraryBtn').onclick = async () => {
  state.libraryMode = 'video';
  el.uploadFile.accept = 'video/mp4,video/webm,video/ogg,video/quicktime,.m4v,.mov';
  await reloadLibrary();
  openLibraryModal();
};
document.getElementById('openPptLibraryBtn').onclick = async () => {
  state.libraryMode = 'ppt';
  el.uploadFile.accept = '.pdf,application/pdf';
  await reloadLibrary();
  openLibraryModal();
};
document.getElementById('openHtmlLibraryBtn').onclick = async () => {
  saveHtmlSelection();
  state.libraryMode = 'html_insert';
  el.uploadFile.accept = 'image/png,image/jpeg,image/webp,image/gif,image/svg+xml';
  await reloadLibrary();
  openLibraryModal();
};
document.getElementById('libraryUploadBtn').onclick = () => { if (libraryUploadInProgress) return; el.uploadFile.click(); };
document.getElementById('libraryDeleteBtn').onclick = () => {
  if (!selectedLibraryUrl) {
    setStatus(state.libraryMode === 'video' ? 'Сначала выберите видео' : (state.libraryMode === 'ppt' ? 'Сначала выберите презентацию' : 'Сначала выберите изображение'), true);
    return;
  }
  openDeleteImageModal();
};
document.getElementById('librarySelectBtn').onclick = () => {
  const pickedMode = state.libraryMode;
  if (!selectedLibraryUrl) {
    setStatus(pickedMode === 'video' ? 'Сначала выберите видео' : (pickedMode === 'ppt' ? 'Сначала выберите презентацию' : 'Сначала выберите изображение'), true);
    return;
  }
  if (pickedMode === 'html_insert') {
    insertHtmlImageToEditor(selectedLibraryUrl);
    syncPreview();
  } else {
    el.cMediaUrl.value = selectedLibraryUrl;
    syncPreview();
    if (pickedMode === 'image') {
      setDimensionsFromImage(selectedLibraryUrl, true);
    } else if (pickedMode === 'video') {
      setDimensionsFromVideo(selectedLibraryUrl, true);
    } else if (pickedMode === 'ppt') {
      setDimensionsFromPpt(selectedLibraryUrl, true);
    }
  }
  closeLibraryModal();
  renderLibrary();
  if (pickedMode === 'html_insert') {
    setStatus('Изображение вставлено в HTML');
  } else if (pickedMode === 'video') {
    setStatus('Видео выбрано из библиотеки');
  } else if (pickedMode === 'ppt') {
    setStatus('Презентация выбрана из библиотеки');
  } else {
    setStatus('Изображение выбрано из библиотеки');
  }
  state.libraryMode = 'image';
};
document.getElementById('closeLibraryBtn').onclick = closeLibraryModal;
document.getElementById('deleteImageCancelBtn').onclick = closeDeleteImageModal;
document.getElementById('deleteImageConfirmBtn').onclick = deleteLibraryImage;
document.getElementById('deleteContentCancelBtn').onclick = closeDeleteContentModal;
document.getElementById('deleteContentConfirmBtn').onclick = confirmDeleteCurrent;
document.getElementById('duplicateContentCancelBtn').onclick = closeDuplicateContentModal;
document.getElementById('duplicateContentConfirmBtn').onclick = confirmDuplicateContent;
document.getElementById('imageLibraryModal').onclick = (event) => {
  if (event.target && event.target.id === 'imageLibraryModal') closeLibraryModal();
};
document.getElementById('deleteImageModal').onclick = (event) => {
  if (event.target && event.target.id === 'deleteImageModal') closeDeleteImageModal();
};
document.getElementById('deleteContentModal').onclick = (event) => {
  if (event.target && event.target.id === 'deleteContentModal') closeDeleteContentModal();
};
document.getElementById('duplicateContentModal').onclick = (event) => {
  if (event.target && event.target.id === 'duplicateContentModal') closeDuplicateContentModal();
};
document.getElementById('toTemplateBtn').onclick = () => { window.location.href = '/template/'; };
document.getElementById('newTypeCancelBtn').onclick = closeNewTypeModal;
document.getElementById('newTypeCreateBtn').onclick = () => {
  const run = async () => {
    closeNewTypeModal();
    nowDraft();
    if (state.currentType === 'html') {
      await ensureHtmlEditor();
      bindHtmlEditorEvents();
      setHtmlValue('');
    }
    setEditorVisible(true, state.currentType);
    renderList();
    if (state.currentType === 'html') {
      setStatus('Черновик: HTML');
    } else if (state.currentType === 'video') {
      setStatus('Черновик: Видео');
    } else if (state.currentType === 'ppt') {
      setStatus('Черновик: Презентация');
    } else {
      setStatus('Черновик: Изображение');
    }
  };
  run();
};
document.querySelectorAll('#typeGrid .typeBtn').forEach((btn) => {
  btn.addEventListener('click', () => {
    if (btn.disabled) return;
    pendingCreateType = String(btn.getAttribute('data-type') || 'image');
    document.querySelectorAll('#typeGrid .typeBtn').forEach((n) => n.classList.remove('active'));
    btn.classList.add('active');
  });
});

el.cMediaUrl.addEventListener('input', () => { syncPreview(); renderLibrary(); });
if (el.cAnimation) {
  el.cAnimation.addEventListener('change', () => { syncDataJson(); syncPreview(); });
}
el.cMediaUrl.addEventListener('change', () => {
  if (state.currentType === 'image') {
    setDimensionsFromImage(el.cMediaUrl.value || '', false);
  } else if (state.currentType === 'video') {
    setDimensionsFromVideo(el.cMediaUrl.value || '', false);
  } else if (state.currentType === 'ppt') {
    setDimensionsFromPpt(el.cMediaUrl.value || '', false);
  }
});
el.uploadFile.addEventListener('change', async () => {
  if (el.uploadFile.files && el.uploadFile.files.length > 0) {
    await uploadFile();
    el.uploadFile.value = '';
  }
});
el.pImageWidth.addEventListener('input', () => { syncScaleFromDimensions(); syncDataJson(); syncPreview(); });
el.pImageHeight.addEventListener('input', () => { syncScaleFromDimensions(); syncDataJson(); syncPreview(); });
el.pImageScale.addEventListener('input', () => { applyScaleToDimensions(); syncDataJson(); syncPreview(); });
el.pImageFluidMode.addEventListener('change', () => { syncDataJson(); syncPreview(); });
el.pImagePosition.addEventListener('change', () => { syncDataJson(); syncPreview(); });
el.pVideoWidth.addEventListener('input', () => { syncVideoScaleFromDimensions(); syncDataJson(); syncPreview(); });
el.pVideoHeight.addEventListener('input', () => { syncVideoScaleFromDimensions(); syncDataJson(); syncPreview(); });
el.pVideoScale.addEventListener('input', () => { applyVideoScaleToDimensions(); syncDataJson(); syncPreview(); });
el.pVideoFluidMode.addEventListener('change', () => { syncDataJson(); syncPreview(); });
el.pVideoPosition.addEventListener('change', () => { syncDataJson(); syncPreview(); });
el.pVideoLoop.addEventListener('change', () => { syncDataJson(); syncPreview(); });
el.pVideoSound.addEventListener('change', () => { syncDataJson(); syncPreview(); });
el.pPptWidth.addEventListener('input', () => { syncPptScaleFromDimensions(); syncDataJson(); syncPreview(); });
el.pPptHeight.addEventListener('input', () => { syncPptScaleFromDimensions(); syncDataJson(); syncPreview(); });
el.pPptScale.addEventListener('input', () => { applyPptScaleToDimensions(); syncDataJson(); syncPreview(); });
el.pPptFluidMode.addEventListener('change', () => { syncDataJson(); syncPreview(); });
el.pPptPosition.addEventListener('change', () => { syncDataJson(); syncPreview(); });
el.pPptStartPage.addEventListener('input', () => { syncDataJson(); syncPreview(); });
el.pPptTotalPages.addEventListener('input', () => { syncDataJson(); syncPreview(); });
el.pPptInterval.addEventListener('input', () => { syncDataJson(); syncPreview(); });
el.pPptLoop.addEventListener('change', () => { syncDataJson(); syncPreview(); });
document.addEventListener('input', (event) => {
  const id = event && event.target && event.target.id ? String(event.target.id) : '';
  if (id === 'pPptAnimDuration') { syncDataJson(); syncPreview(); }
});
document.addEventListener('change', (event) => {
  const id = event && event.target && event.target.id ? String(event.target.id) : '';
  if (id === 'pPptPageAnim') { syncDataJson(); syncPreview(); }
});
if (el.cHtmlBody) {
  el.cHtmlBody.addEventListener('input', () => { if (state.currentType === 'html') syncPreview(); });
}

(async function boot() {
  normalizeInspectorTexts();
  resetEditor();
  await reloadList();
  await reloadLibrary();
})();
</script>
</body>
</html>
