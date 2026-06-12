<?php

namespace App\Admin\Extensions\Filter;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\Filter\Between;
use Dcat\Admin\Grid\Filter\Presenter\DateTime;
use Illuminate\Support\Arr;

/**
 * 日期范围过滤器
 *
 * 用单个输入框 + 弹出面板替代原生 between-datetime 的双输入框布局，
 * 避免窄列宽下标签、两个输入框和 "To" 分隔符挤在一行导致样式错乱。
 * 查询参数格式与原生 between 完全一致（column[start] / column[end]）。
 */
class DateRange extends Between
{
    protected $view = 'admin.filters.date-range';

    /**
     * @var bool
     */
    protected static $styleLoaded = false;

    public function datetime($options = [])
    {
        DateTime::collectAssets();

        $this->setupStyle();
        $this->setupScript($options);

        return $this;
    }

    protected function setupStyle()
    {
        if (static::$styleLoaded) {
            return;
        }
        static::$styleLoaded = true;

        Admin::style(
            <<<'CSS'
.date-range-filter .date-range-display[readonly] {
    background-color: #fff;
    cursor: pointer;
}
body.dark-mode .date-range-filter .date-range-display[readonly] {
    background-color: #2c2c43;
}
.date-range-filter .date-range-panel {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1060;
    width: 340px;
    margin-top: 4px;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .18);
}
.date-range-filter .date-range-shortcuts {
    display: flex;
    gap: 4px;
}
.date-range-filter .date-range-shortcuts .range-shortcut {
    flex: 1;
    padding-left: 0;
    padding-right: 0;
    white-space: nowrap;
}
CSS
        );
    }

    protected function setupScript($options = [])
    {
        $options['format'] = Arr::get($options, 'format', 'YYYY-MM-DD HH:mm:ss');
        $options['locale'] = Arr::get($options, 'locale', config('app.locale'));

        $script = str_replace(
            ['__OPTIONS__', '__START__', '__END__'],
            [json_encode($options), $this->id['start'], $this->id['end']],
            <<<'JS'
(function () {
    var $start = $('#__START__'),
        $end = $('#__END__'),
        $display = $('#__START___display'),
        $panel = $('#__START___panel'),
        $startPicker = $('#__START___picker'),
        $endPicker = $('#__END___picker');

    if (! $display.length || $display.data('date-range-init')) {
        return;
    }
    $display.data('date-range-init', 1);

    $startPicker.datetimepicker(__OPTIONS__);
    $endPicker.datetimepicker($.extend({useCurrent: false}, __OPTIONS__));

    var startApi = $startPicker.data('DateTimePicker'),
        endApi = $endPicker.data('DateTimePicker');

    $startPicker.on('dp.change', function (e) {
        endApi.minDate(e.date || false);
    });
    $endPicker.on('dp.change', function (e) {
        startApi.maxDate(e.date || false);
    });

    function refreshDisplay() {
        var s = $start.val(), e = $end.val();
        $display.val(s && e ? s + ' ~ ' + e : (s ? s + ' 之后' : (e ? e + ' 之前' : '')));
    }

    $display.on('click', function (e) {
        e.stopPropagation();
        $('.date-range-panel').not($panel).hide();
        $panel.toggle();
    });
    $panel.on('click', function (e) {
        e.stopPropagation();
    });
    $(document).off('click.__START__').on('click.__START__', function () {
        $panel.hide();
    });

    $panel.find('.range-shortcut').on('click', function () {
        var ranges = {
            today: [moment().startOf('day'), moment().endOf('day')],
            yesterday: [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
            last7: [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
            month: [moment().startOf('month'), moment().endOf('month')],
            lastMonth: [moment().subtract(1, 'months').startOf('month'), moment().subtract(1, 'months').endOf('month')]
        };
        var range = ranges[$(this).data('range')];
        startApi.maxDate(false);
        endApi.minDate(false);
        startApi.date(range[0]);
        endApi.date(range[1]);
    });

    $panel.find('.range-confirm').on('click', function () {
        $start.val($startPicker.val());
        $end.val($endPicker.val());
        refreshDisplay();
        $panel.hide();
    });

    $panel.find('.range-clear').on('click', function () {
        startApi.maxDate(false);
        endApi.minDate(false);
        startApi.clear();
        endApi.clear();
        $start.val('');
        $end.val('');
        refreshDisplay();
    });
})();
JS
        );

        Admin::script($script);
    }
}
