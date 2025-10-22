<?php

/*
 * // +----------------------------------------------------------------------
 * // | erp
 * // +----------------------------------------------------------------------
 * // | Copyright (c) 2006~2020 erp All rights reserved.
 * // +----------------------------------------------------------------------
 * // | Licensed ( LICENSE-1.0.0 )
 * // +----------------------------------------------------------------------
 * // | Author: yxx <1365831278@qq.com>
 * // +----------------------------------------------------------------------
 */

use Dcat\Admin\Admin;
use Dcat\Admin\Grid;
use Dcat\Admin\Form;
use Dcat\Admin\Grid\Filter;

/**
 * Dcat-admin - admin builder based on Laravel.
 * @author jqh <https://github.com/jqhph>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 *
 * extend custom field:
 * Dcat\Admin\Form::extend('php', PHPEditor::class);
 * Dcat\Admin\Grid\Column::extend('php', PHPEditor::class);
 * Dcat\Admin\Grid\Filter::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 */

Filter::resolving(function (Filter $filter) {
    $filter->panel();
    $filter->expand();
});

Grid::resolving(function (Grid $grid) {
    $grid->setActionClass(\Dcat\Admin\Grid\Displayers\Actions::class);
    $grid->model()->orderBy("id", "desc");
    $grid->disableViewButton();
    $grid->showQuickEditButton();
    $grid->enableDialogCreate();
    $grid->disableBatchDelete();
    $grid->actions(function (\Dcat\Admin\Grid\Displayers\Actions $actions) {
        $actions->disableView();
        $actions->disableDelete();
        $actions->disableEdit();
    });
    $grid->option("dialog_form_area", ["70%", "80%"]);
});

Form\Field::macro('enableHorizontal', function () {
    $this->horizontal = true;
    return $this;
});

\App\Admin\Extensions\Form\Select::macro();
//\App\Admin\Extensions\Form\SelectTable::macro();

Dcat\Admin\Grid\Column::extend('emp', \App\Admin\Extensions\Grid\EmptyData::class);
Dcat\Admin\Grid\Column::extend('fee', \App\Admin\Extensions\Grid\Fee::class);
Dcat\Admin\Grid\Column::extend('edit', \App\Admin\Extensions\Grid\Edit::class);
Dcat\Admin\Grid\Column::extend('selectplus', \App\Admin\Extensions\Grid\SelectPlus::class);
Dcat\Admin\Grid\Column::extend('batch_detail', \App\Admin\Extensions\Grid\BatchDeail::class);
Dcat\Admin\Form::extend('fee', \App\Admin\Extensions\Form\Fee::class);
Dcat\Admin\Form::extend('num', \App\Admin\Extensions\Form\Num::class);
Dcat\Admin\Form::extend('tableDecimal', \App\Admin\Extensions\Form\TableDecimal::class);
Dcat\Admin\Form::extend('ipt', \App\Admin\Extensions\Form\Input::class);
Dcat\Admin\Form::extend('reviewicon', \App\Admin\Extensions\Form\ReviewIcon::class);

$script = <<<'JS'
        $("#grid-table > tbody > tr").on("dblclick",function(event) {
           var obj = $(this).find(".feather.icon-edit");

           if (obj.attr('unique') == "true") {
               return
           }
           if (obj.length == 1) {
               obj.trigger("click")
               obj.attr('unique',true);
           }
        })
JS;
Admin::script($script);


//Admin::style(<<<CSS
//    span[aria-labelledby*="product_id"] {
//    width: 100vw !important;
//}
//CSS
//);

// 调整过滤框字体大小
//Admin::style(<<<CSS
//.input-group-sm>.input-group-prepend>.input-group-text {
//   font-size: .9rem !important;
//}
//.input-group-sm>.form-control::placeholder{
//font-size: .8rem !important;
//}
//.input-group-sm .select2-container--default .select2-selection--single{
//font-size: .8rem!important;
//}
//.custom-data-table-header .table-responsive .top .dataTables_filter .form-control::placeholder {
//    font-size: .8rem;
//}
//CSS
//);

app('view')->prependNamespace('admin', resource_path('views/vendor/laravel-admin'));

// 添加用户信息区域样式优化
Admin::style(<<<'CSS'
.user-nav {
    display: flex;
    align-items: center;
    padding: 8px 0;
}
.user-name {
    margin-right: 8px;
    font-weight: 500;
}
.user-status {
    margin-right: 4px;
}
.user-status i {
    margin-right: 2px;
}
.dropdown-toggle {
    display: flex;
    align-items: center;
}
CSS
);

// 添加基本JavaScript交互
Admin::script(<<<'JS'
    // 响应式侧边栏切换
    (function() {
        const toggle = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.admin-sidebar');

        if (toggle && sidebar) {
            toggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');

                // 保存侧边栏状态到本地存储
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            });

            // 从本地存储恢复侧边栏状态
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('collapsed');
            }
        }
    })();

    // 平滑滚动
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
JS);


Admin::css('/static/css/custom-select2.css');
