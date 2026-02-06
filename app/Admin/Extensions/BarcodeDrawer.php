<?php

namespace App\Admin\Extensions;

use Dcat\Admin\Admin;

class BarcodeDrawer
{
    public static function render($value, $luxuryBrandSeries = '', int $limit = 8): string
    {
        if (is_int($luxuryBrandSeries)) {
            $limit = $luxuryBrandSeries;
            $luxuryBrandSeries = '';
        }

        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $luxuryBrandSeries = trim((string) $luxuryBrandSeries);

        self::ensureAssets();

        $preview = mb_substr($value, 0, $limit, 'UTF-8');
        $isOverflow = mb_strlen($value, 'UTF-8') > $limit;
        if ($isOverflow) {
            $preview .= '…';
        }

        $escapedValue = e($value);
        $escapedPreview = e($preview);
        $escapedLuxury = e($luxuryBrandSeries);

        $toggleHtml = '';
        if ($isOverflow) {
            $toggleHtml = '<button type="button" class="barcode-toggle" data-barcode="' . $escapedValue . '" data-luxury="' . $escapedLuxury . '" aria-label="查看专属编码">'
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
 .barcode-toggle{width:24px;height:24px;padding:0;border:1px solid #d7dde4;border-radius:4px;background:#fff;line-height:22px;cursor:pointer;color:#4f5d6b;text-align:center;}
 .barcode-toggle:focus{outline:none;}
 .barcode-modal-mask{position:fixed;inset:0;background:rgba(0,0,0,.25);opacity:0;visibility:hidden;transition:all .2s ease;z-index:1998;}
 .barcode-modal{position:fixed;top:0;right:0;height:100%;width:560px;max-width:94vw;background:#fff;border-left:1px solid #e5e9ef;box-shadow:-10px 0 28px rgba(0,0,0,.16);transform:translateX(100%);transition:transform .22s ease;z-index:1999;display:flex;flex-direction:column;}
 .barcode-modal.is-open{transform:translateX(0);}
 .barcode-modal-mask.is-open{opacity:1;visibility:visible;}
 .barcode-modal__header{padding:16px 20px;border-bottom:1px solid #eceff3;display:flex;align-items:center;justify-content:space-between;}
 .barcode-modal__title{font-size:15px;font-weight:600;line-height:1;color:#333;margin-right:auto;}
 .barcode-modal__actions{display:flex;align-items:center;gap:12px;}
 .barcode-modal__btn{height:34px;padding:0 18px;border:1px solid #d5dce5;border-radius:4px;background:#fff;color:#5d6874;font-size:14px;line-height:32px;cursor:pointer;}
 .barcode-modal__btn--primary{border-color:#5977ff;color:#5977ff;box-shadow:0 0 0 1px rgba(89,119,255,.18) inset;}
 .barcode-modal__body{padding:12px 14px 20px;overflow:auto;flex:1;}
 .barcode-modal__section{padding:0 2px;}
 .barcode-modal__divider{margin:18px 0;border-top:1px solid #d7dce2;}
 .barcode-modal__tag{display:inline-flex;align-items:center;justify-content:center;min-width:160px;height:50px;padding:0 14px;margin-bottom:16px;border:2px solid #9ea4aa;border-radius:8px;background:#fff;color:#4d535a;font-size:16px;font-weight:700;line-height:1;}
 .barcode-modal__list{margin:0;padding:0;list-style:none;}
 .barcode-modal__item{position:relative;margin:0 0 10px;padding-left:14px;color:#4a5159;font-size:16px;line-height:1.55;word-break:break-all;}
 .barcode-modal__item:before{content:'';position:absolute;left:0;top:.72em;width:5px;height:5px;border-radius:50%;background:#6b737c;transform:translateY(-50%);}
 .barcode-modal__empty{color:#9aa3ad;font-size:14px;line-height:1.4;}
 @media (max-width: 576px){.barcode-modal{width:92vw;}.barcode-modal__title{font-size:15px;}.barcode-modal__btn{padding:0 12px;}.barcode-modal__tag{min-width:130px;}}
CSS);

        Admin::script(<<<JS
(function () {
    if (window.__barcodeDrawerInit) {
        return;
    }
    window.__barcodeDrawerInit = true;

    var drawerHtml = ''
        + '<div class="barcode-modal-mask" id="barcode-modal-mask"></div>'
        + '<div class="barcode-modal" id="barcode-modal">'
        + '  <div class="barcode-modal__header">'
        + '    <div class="barcode-modal__title">专属编码</div>'
        + '    <div class="barcode-modal__actions">'
        + '      <button type="button" class="barcode-modal__btn barcode-modal__btn--primary" data-action="copy-all">复制全部</button>'
        + '      <button type="button" class="barcode-modal__btn" data-action="close">关闭</button>'
        + '    </div>'
        + '  </div>'
        + '  <div class="barcode-modal__body">'
        + '    <div class="barcode-modal__section">'
        + '      <div class="barcode-modal__tag">本厂香型</div>'
        + '      <ul class="barcode-modal__list" id="barcode-factory-list"></ul>'
        + '    </div>'
        + '    <div class="barcode-modal__divider"></div>'
        + '    <div class="barcode-modal__section">'
        + '      <div class="barcode-modal__tag">对应大牌</div>'
        + '      <ul class="barcode-modal__list" id="barcode-brand-list"></ul>'
        + '    </div>'
        + '  </div>'
        + '</div>';

    document.body.insertAdjacentHTML('beforeend', drawerHtml);

    var drawer = document.getElementById('barcode-modal');
    var mask = document.getElementById('barcode-modal-mask');
    var factoryList = document.getElementById('barcode-factory-list');
    var brandList = document.getElementById('barcode-brand-list');
    var currentButton = null;
    var currentRawText = '';
    var currentLuxuryText = '';

    function splitLines(text) {
        return text.split(/[；;]+/)
            .map(function (item) { return item.trim(); })
            .filter(function (item) { return item.length; });
    }

    function safeText(text) {
        return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function renderList(container, items) {
        if (!items.length) {
            container.innerHTML = '<li class="barcode-modal__empty">暂无</li>';
            return;
        }
        container.innerHTML = items.map(function (item) {
            return '<li class="barcode-modal__item">' + safeText(item) + '</li>';
        }).join('');
    }

    function openDrawer(rawText, luxuryText, button) {
        currentButton = button || null;
        currentRawText = rawText || '';
        currentLuxuryText = luxuryText || '';
        renderList(factoryList, splitLines(currentRawText));
        renderList(brandList, splitLines(currentLuxuryText));
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

        if (window.Dcat && Dcat.success) {
            Dcat.success('已复制到剪贴板');
        }
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        var toggle = target.closest('.barcode-toggle');
        if (toggle) {
            var rawText = toggle.getAttribute('data-barcode') || '';
            var luxuryText = toggle.getAttribute('data-luxury') || '';
            openDrawer(rawText, luxuryText, toggle);
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
                var mergedText = currentRawText;
                if (currentLuxuryText) {
                    mergedText += (mergedText ? '\\n' : '') + currentLuxuryText;
                }
                copyText(mergedText);
                return;
            }
        }

        if (target === mask) {
            closeDrawer();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDrawer();
        }
    });
})();
JS);
    }
}
