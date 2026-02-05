<?php

namespace App\Admin\Extensions;

use Dcat\Admin\Admin;

class BarcodeDrawer
{
    public static function render($value, int $limit = 10): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        self::ensureAssets();

        $preview = mb_substr($value, 0, $limit, 'UTF-8');
        $isOverflow = mb_strlen($value, 'UTF-8') > $limit;
        if ($isOverflow) {
            $preview .= '…';
        }

        $escapedValue = e($value);
        $escapedPreview = e($preview);

        $toggleHtml = '';
        if ($isOverflow) {
            $toggleHtml = '<button type="button" class="barcode-toggle" data-barcode="' . $escapedValue . '" aria-label="查看专属编码">'
                . '<i class="fa fa-eye"></i>'
                . '</button>';
        }

        return <<<HTML
<div class="barcode-cell">
    <span class="barcode-preview" title="{$escapedValue}">{$escapedPreview}</span>
    {$toggleHtml}
</div>
HTML;
    }

    protected static function ensureAssets(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        Admin::style(<<<CSS
.barcode-cell{display:flex;align-items:center;gap:8px;}
.barcode-preview{max-width:220px;display:inline-block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.barcode-toggle{padding:0;border:none;background:transparent;line-height:1;cursor:pointer;color:#333;}
.barcode-toggle:focus{outline:none;}
.barcode-drawer-mask{position:fixed;inset:0;background:rgba(0,0,0,.35);opacity:0;visibility:hidden;transition:all .2s ease;z-index:1998;}
.barcode-drawer{position:fixed;top:0;right:0;height:100%;width:420px;max-width:92vw;background:#fff;box-shadow:-6px 0 18px rgba(0,0,0,.12);transform:translateX(100%);transition:transform .25s ease;z-index:1999;display:flex;flex-direction:column;}
.barcode-drawer.is-open{transform:translateX(0);}
.barcode-drawer-mask.is-open{opacity:1;visibility:visible;}
.barcode-drawer__header{padding:16px 18px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between;}
.barcode-drawer__title{font-weight:600;font-size:15px;color:#333;}
.barcode-drawer__header-actions{display:flex;align-items:center;gap:8px;}
.barcode-drawer__body{padding:16px 18px;overflow:auto;flex:1;}
.barcode-drawer__list{margin:0;padding-left:18px;}
.barcode-drawer__list li{margin:6px 0;line-height:1.6;color:#333;word-break:break-all;}
@media (max-width: 576px){.barcode-drawer{width:92vw;}}
CSS);

        Admin::script(<<<JS
(function () {
    if (window.__barcodeDrawerInit) {
        return;
    }
    window.__barcodeDrawerInit = true;

    var drawerHtml = ''
        + '<div class="barcode-drawer-mask" id="barcode-drawer-mask"></div>'
        + '<div class="barcode-drawer" id="barcode-drawer">'
        + '  <div class="barcode-drawer__header">'
        + '    <div class="barcode-drawer__title">专属编码</div>'
        + '    <div class="barcode-drawer__header-actions">'
        + '      <button type="button" class="btn btn-sm btn-outline-primary" data-action="copy-all">复制全部</button>'
        + '      <button type="button" class="btn btn-sm btn-light" data-action="close">关闭</button>'
        + '    </div>'
        + '  </div>'
        + '  <div class="barcode-drawer__body">'
        + '    <ul class="barcode-drawer__list" id="barcode-drawer-list"></ul>'
        + '  </div>'
        + '</div>';

    document.body.insertAdjacentHTML('beforeend', drawerHtml);

    var drawer = document.getElementById('barcode-drawer');
    var mask = document.getElementById('barcode-drawer-mask');
    var list = document.getElementById('barcode-drawer-list');
    var currentButton = null;
    var currentRawText = '';

    function splitLines(text) {
        return text.split(/[；;]+/)
            .map(function (item) { return item.trim(); })
            .filter(function (item) { return item.length; });
    }

    function formatLine(text) {
        return text.replace(/\s*\/\s*/g, ' / ');
    }

    function renderList(text) {
        var items = splitLines(text).map(formatLine);
        if (!items.length) {
            list.innerHTML = '';
            return;
        }
        list.innerHTML = items.map(function (item) {
            var safe = item.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return '<li>' + safe + '</li>';
        }).join('');
    }

    function openDrawer(rawText, button) {
        currentButton = button || null;
        currentRawText = rawText || '';
        renderList(currentRawText);
        drawer.classList.add('is-open');
        mask.classList.add('is-open');
        if (currentButton) {
            var icon = currentButton.querySelector('i');
            if (icon) {
                icon.className = 'fa fa-eye-slash';
            }
        }
    }

    function closeDrawer() {
        drawer.classList.remove('is-open');
        mask.classList.remove('is-open');
        if (currentButton) {
            var icon = currentButton.querySelector('i');
            if (icon) {
                icon.className = 'fa fa-eye';
            }
        }
        currentButton = null;
    }

    function copyText(text) {
        if (!text) {
            if (window.Dcat && Dcat.error) {
                Dcat.error('请先选中内容');
            } else {
                alert('请先选中内容');
            }
            return;
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text);
        } else {
            var temp = document.createElement('textarea');
            temp.value = text;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
        }
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        var toggle = target.closest('.barcode-toggle');
        if (toggle) {
            var rawText = toggle.getAttribute('data-barcode') || '';
            openDrawer(rawText, toggle);
            return;
        }

        var actionButton = target.closest('[data-action]');
        if (actionButton) {
            var action = actionButton.getAttribute('data-action');
            if (action === 'close') {
                closeDrawer();
                return;
            }
            if (action === 'copy-all') {
                copyText(currentRawText);
                return;
            }
        }

        if (target === mask) {
            closeDrawer();
        }
    });
})();
JS);
    }
}
