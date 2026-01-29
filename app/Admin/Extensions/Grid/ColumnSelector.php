<?php

namespace App\Admin\Extensions\Grid;

use Dcat\Admin\Grid\Tools\AbstractTool;

class ColumnSelector extends AbstractTool
{
    protected $columns = [];

    public function __construct(array $columns = [])
    {
        $this->columns = $columns;
    }

    public function render()
    {
        $columns = json_encode($this->columns, JSON_UNESCAPED_UNICODE);
        $id = 'column-selector-' . uniqid();

        return <<<HTML
<style>
.column-selector-dropdown {
    min-width: 280px;
    padding: 0;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border: 1px solid #e0e0e0;
    z-index: 9999 !important;
    position: absolute !important;
    top: 100% !important;
    left: 0 !important;
    margin-top: 2px !important;
}

.column-selector-header {
    padding: 12px 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    font-size: 14px;
    border-radius: 6px 6px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.column-selector-actions {
    display: flex;
    gap: 8px;
}

.column-selector-actions button {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 2px 8px;
    font-size: 12px;
    border-radius: 3px;
    cursor: pointer;
    transition: all 0.2s;
}

.column-selector-actions button:hover {
    background: rgba(255,255,255,0.3);
    border-color: rgba(255,255,255,0.5);
}

.column-selector-body {
    max-height: 450px;
    overflow-y: auto;
    padding: 8px 0;
}

.column-selector-item {
    padding: 10px 16px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #f5f5f5;
}

.column-selector-item:last-child {
    border-bottom: none;
}

.column-selector-item:hover {
    background: #f8f9fa;
}

.column-selector-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    margin: 0;
    accent-color: #667eea;
}

.column-selector-item label {
    margin: 0 0 0 12px;
    cursor: pointer;
    font-size: 14px;
    color: #333;
    font-weight: 400;
    flex: 1;
    user-select: none;
}

.column-selector-body::-webkit-scrollbar {
    width: 6px;
}

.column-selector-body::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.column-selector-body::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.column-selector-body::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.column-count {
    font-size: 12px;
    opacity: 0.9;
    font-weight: normal;
}
</style>

<div class="btn-group" style="margin-right: 10px;">
    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        <i class="feather icon-columns"></i> 列显示 <span class="caret"></span>
    </button>
    <div class="dropdown-menu column-selector-dropdown {$id}">
        <div class="column-selector-header">
            <span>选择显示列 <span class="column-count"></span></span>
            <div class="column-selector-actions">
                <button type="button" class="select-all-btn">全选</button>
            </div>
        </div>
        <div class="column-selector-body">
            <!-- 列选项将由 JavaScript 生成 -->
        </div>
    </div>
</div>

<script>
(function() {
    var columns = {$columns};
    var storageKey = 'grid_column_visibility_' + window.location.pathname;
    var dropdown = jQuery('.{$id}');
    var dropdownBody = dropdown.find('.column-selector-body');
    var columnCount = dropdown.find('.column-count');
    
    // 从 localStorage 加载保存的设置
    function loadSettings() {
        var saved = localStorage.getItem(storageKey);
        return saved ? JSON.parse(saved) : {};
    }
    
    // 保存设置到 localStorage
    function saveSettings(settings) {
        localStorage.setItem(storageKey, JSON.stringify(settings));
    }
    
    // 更新显示计数
    function updateCount() {
        var settings = loadSettings();
        var visibleCount = 0;
        columns.forEach(function(column) {
            if (settings[column.name] !== false) {
                visibleCount++;
            }
        });
        columnCount.text('(' + visibleCount + '/' + columns.length + ')');
    }
    
    // 生成列选择项
    function renderCheckboxes() {
        var settings = loadSettings();
        var html = '';
        
        columns.forEach(function(column) {
            var isVisible = settings[column.name] !== false; // 默认显示
            var checked = isVisible ? 'checked' : '';
            
            html += '<div class="column-selector-item">';
            html += '  <input type="checkbox" id="col_' + column.name + '" value="' + column.name + '" ' + checked + '>';
            html += '  <label for="col_' + column.name + '">' + column.label + '</label>';
            html += '</div>';
        });
        
        dropdownBody.html(html);
        updateCount();
        
        // 应用当前设置
        applySettings(settings);
    }
    
    // 应用列显示/隐藏设置
    function applySettings(settings) {
        columns.forEach(function(column) {
            var isVisible = settings[column.name] !== false;
            var columnClass = '.column-' + column.name;
            
            // 查找表头
            var header = jQuery('th' + columnClass);
            if (header.length > 0) {
                var columnIndex = header.index();
                
                // 显示或隐藏表头和对应的所有单元格
                if (isVisible) {
                    header.show();
                    jQuery('tbody tr').each(function() {
                        jQuery(this).find('td').eq(columnIndex).show();
                    });
                } else {
                    header.hide();
                    jQuery('tbody tr').each(function() {
                        jQuery(this).find('td').eq(columnIndex).hide();
                    });
                }
            }
        });
    }
    
    // 点击整行切换复选框（排除直接点击 input 的情况）
    dropdown.on('click', '.column-selector-item', function(e) {
        var target = jQuery(e.target);
        // 如果点击的不是 checkbox 本身，则切换状态
        if (!target.is('input[type="checkbox"]')) {
            e.preventDefault();
            e.stopPropagation();
            var checkbox = jQuery(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        }
    });
    
    // 阻止 label 的默认行为，统一由上面的点击处理
    dropdown.on('click', '.column-selector-item label', function(e) {
        e.preventDefault();
    });
    
    // 监听复选框变化
    dropdown.on('change', 'input[type="checkbox"]', function(e) {
        e.stopPropagation();
        var columnName = jQuery(this).val();
        var isChecked = jQuery(this).prop('checked');
        var settings = loadSettings();
        
        settings[columnName] = isChecked;
        saveSettings(settings);
        updateCount();
        
        // 找到对应的列并显示/隐藏
        var columnClass = '.column-' + columnName;
        var header = jQuery('th' + columnClass);
        if (header.length > 0) {
            var columnIndex = header.index();
            
            if (isChecked) {
                header.show();
                jQuery('tbody tr').each(function() {
                    jQuery(this).find('td').eq(columnIndex).show();
                });
            } else {
                header.hide();
                jQuery('tbody tr').each(function() {
                    jQuery(this).find('td').eq(columnIndex).hide();
                });
            }
        }
    });
    
    // 全选按钮
    dropdown.on('click', '.select-all-btn', function(e) {
        e.stopPropagation();
        var settings = loadSettings();
        dropdown.find('input[type="checkbox"]').prop('checked', true);
        
        columns.forEach(function(column) {
            settings[column.name] = true;
        });
        saveSettings(settings);
        updateCount();
        applySettings(settings);
    });
    
    // 阻止下拉菜单关闭
    dropdown.on('click', function(e) {
        e.stopPropagation();
    });
    
    // 初始化
    jQuery(document).ready(function() {
        renderCheckboxes();
        
        // 监听表格刷新事件（翻页、筛选等）
        Dcat.grid.on('pjax:complete', function() {
            var settings = loadSettings();
            applySettings(settings);
        });
        
        // 也监听普通的表格加载
        setTimeout(function() {
            var settings = loadSettings();
            applySettings(settings);
        }, 100);
    });
})();
</script>
HTML;
    }
}
