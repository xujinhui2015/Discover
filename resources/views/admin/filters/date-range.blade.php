@php
    $startValue = request($name['start'], \Illuminate\Support\Arr::get($value, 'start'));
    $endValue = request($name['end'], \Illuminate\Support\Arr::get($value, 'end'));

    if ($startValue && $endValue) {
        $displayValue = $startValue.' ~ '.$endValue;
    } elseif ($startValue) {
        $displayValue = $startValue.' 之后';
    } elseif ($endValue) {
        $displayValue = $endValue.' 之前';
    } else {
        $displayValue = '';
    }
@endphp
<div class="filter-input col-sm-{{ $width }}" style="{!! $style !!}">
    <div class="form-group date-range-filter" style="position: relative">
        <div class="input-group input-group-sm">
            <div class="input-group-prepend">
                <span class="input-group-text bg-white text-capitalize"><b>{!! $label !!}</b>&nbsp;<i class="feather icon-calendar"></i></span>
            </div>
            <input autocomplete="off" type="text" class="form-control date-range-display" id="{{ $id['start'] }}_display"
                   placeholder="点击选择时间范围" readonly value="{{ $displayValue }}">
        </div>
        <input type="hidden" id="{{ $id['start'] }}" name="{{ $name['start'] }}" value="{{ $startValue }}">
        <input type="hidden" id="{{ $id['end'] }}" name="{{ $name['end'] }}" value="{{ $endValue }}">

        <div class="card date-range-panel" id="{{ $id['start'] }}_panel" style="display: none">
            <div class="card-body p-2">
                <div class="mb-1 date-range-shortcuts">
                    <button type="button" class="btn btn-white btn-sm range-shortcut" data-range="today">今天</button>
                    <button type="button" class="btn btn-white btn-sm range-shortcut" data-range="yesterday">昨天</button>
                    <button type="button" class="btn btn-white btn-sm range-shortcut" data-range="last7">近7天</button>
                    <button type="button" class="btn btn-white btn-sm range-shortcut" data-range="month">本月</button>
                    <button type="button" class="btn btn-white btn-sm range-shortcut" data-range="lastMonth">上月</button>
                </div>
                <div class="input-group input-group-sm mb-1">
                    <div class="input-group-prepend"><span class="input-group-text">从</span></div>
                    <input autocomplete="off" type="text" class="form-control" id="{{ $id['start'] }}_picker"
                           placeholder="开始时间" value="{{ $startValue }}">
                </div>
                <div class="input-group input-group-sm mb-1">
                    <div class="input-group-prepend"><span class="input-group-text">至</span></div>
                    <input autocomplete="off" type="text" class="form-control" id="{{ $id['end'] }}_picker"
                           placeholder="结束时间" value="{{ $endValue }}">
                </div>
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-white btn-sm range-clear" style="margin-right: 5px">清空</button>
                    <button type="button" class="btn btn-primary btn-sm range-confirm">确定</button>
                </div>
            </div>
        </div>
    </div>
</div>
