<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <title>{{ $orderName }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            color: #000;
            background: #fff;
            padding: 24px;
            font-family: "Microsoft YaHei", "PingFang SC", "Helvetica Neue", Arial, sans-serif;
        }

        .print-area {
            max-width: 210mm;
            margin: 0 auto;
        }

        .toolbar {
            margin-bottom: 18px;
            display: flex;
            justify-content: flex-end;
        }

        .print-btn {
            padding: 10px 22px;
            background: #2c7be5;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 10px rgba(44, 123, 229, 0.28);
            transition: all 0.2s ease;
        }

        .print-btn:hover {
            background: #1967d2;
            box-shadow: 0 4px 14px rgba(25, 103, 210, 0.35);
        }

        .invoice-wrapper {
            position: relative;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 24px;
            box-shadow: none;
        }

        .invoice {
            padding: 18px 22px 32px 22px;
            font-family: "Courier New", Courier, monospace;
            font-size: 14px;
            line-height: 1.4;
            width: 100%;
            position: relative;
        }

        .status-mark {
            position: absolute;
            right: 28px;
            top: 34px;
            width: 120px;
            opacity: 0.95;
            z-index: 2;
        }

        .status-mark img {
            width: 100%;
            display: block;
        }

        .invoice-header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 0;
            position: relative;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .invoice-title {
            font-size: 18px;
            font-weight: 600;
            color: #000;
            margin-bottom: 6px;
        }

        .contact-info {
            font-size: 16px;
            color: #000;
        }

        .customer-info {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-bottom: 14px;
            padding: 8px 0;
            column-gap: 18px;
            row-gap: 12px;
        }

        .customer-info>div {
            font-size: 14px;
            white-space: normal;
            line-height: 1.5;
            word-break: break-all;
        }

        .customer-info label {
            font-weight: bold;
            color: #000;
            margin-right: 4px;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            border: 1.2px solid #000;
        }

        .product-table th {
            background: #fff;
            font-weight: bold;
            border: 1.2px solid #000;
            padding: 6px 5px;
            text-align: center;
            font-size: 14px;
        }

        .product-table td {
            border: 1.2px solid #000;
            padding: 6px 5px;
            text-align: center;
            font-size: 14px;
        }

        @media screen {
            .product-table tbody tr:nth-child(even) td {
                background: transparent;
            }
        }

        .page-info {
            text-align: center;
            font-size: 14px;
            color: #000;
            margin-top: 8px;
            font-family: "Courier New", Courier, monospace;
        }

        .page-info span {
            font-weight: bold;
            margin: 0 3px;
        }

        @media print {
            body {
                margin: 0 !important;
                padding: 0 !important;
                font-size: 14px;
                line-height: 1.4;
            }

            @page {
                size: auto;
                margin: 0mm !important;
            }

            .no-print {
                display: none !important;
            }

            .invoice-wrapper {
                box-shadow: none;
                border-radius: 0;
                margin: 0;
            }

            .product-table th {
                background: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .invoice-header::after {
                border-color: transparent !important;
            }
        }
    </style>
</head>

<body>
    <div class="print-area">
        <div class="toolbar no-print">
            <button class="print-btn" id="triggerPrint" type="button">打印票据</button>
        </div>

        @foreach($orders as $order)
            <div class="invoice-wrapper">
                <div class="invoice">
                    <div class="status-mark">
                        <img src="{{ store_order_img($order->review_status) }}" alt="order status">
                    </div>
                    <div class="invoice-header">
                        <div class="company-name">{{ config('app.name') ?? '' }}</div>
                        <div class="invoice-title">{{ $orderName }}</div>
                        <div class="contact-info">电话：020-86326688 传真：020-36265293</div>
                    </div>

                    <div class="customer-info">
                        @foreach($orderField as $field)
                            @foreach($field as $key => $value)
                                    <div>
                                        <label>{{ $value }}：</label>
                                        <span>{{
                                collect(explode(".", $key))->reduce(function ($object, $field) use ($order) {
                                    return $object ? $object->$field : $order->$field;
                                })
                                                                                                                                                                                }}</span>
                                    </div>
                            @endforeach
                        @endforeach
                    </div>

                    <table class="product-table">
                        @php
                            $printSlug = request()->input('slug');
                            $itemFieldForPrint = $itemField;
                            $showTotalAmount = false;

                            if ($printSlug === 'sale-out-order') {
                                $itemFieldForPrint = collect($itemField)->reject(function ($label) {
                                    return $label === '分类';
                                })->toArray();
                                $showTotalAmount = true;
                            }

                            // 确保 items 可遍历
                            if (!$order->items instanceof Illuminate\Database\Eloquent\Collection) {
                                $order->items = [$order->items];
                            }

                            // 构建列信息数组，识别需要合计的列
                            $columns = [];
                            foreach ($itemFieldForPrint as $key => $label) {
                                // 列标题包含"数量"、"价格"或"金额"的列需要合计
                                $isSummable = (strpos($label, '数量') !== false || strpos($label, '价格') !== false || strpos($label, '金额') !== false);
                                $columns[] = [
                                    'key' => $key,
                                    'label' => $label,
                                    'is_summable' => $isSummable,
                                    'total' => 0
                                ];
                            }
                            // 如果有额外的"金额"列（sale-out-order）
                            if ($showTotalAmount) {
                                $columns[] = [
                                    'key' => '__calc_amount__',
                                    'label' => '金额',
                                    'is_summable' => true,
                                    'is_calc' => true,
                                    'total' => 0
                                ];
                            }

                            // 遍历明细计算合计
                            foreach ($order->items as $item) {
                                foreach ($columns as &$col) {
                                    if (!$col['is_summable'])
                                        continue;

                                    // 数量列使用3位小数精度，其他使用2位
                                    $precision = (strpos($col['label'], '数量') !== false) ? 3 : 2;

                                    if (isset($col['is_calc']) && $col['is_calc']) {
                                        // 计算列：数量 * 单价
                                        $val = bcmul($item->actual_num ?? 0, $item->price ?? 0, $precision);
                                    } else {
                                        // 动态列：从明细中获取值
                                        $rawVal = collect(explode(".", $col['key']))->reduce(function ($o, $v) use ($item) {
                                            return $o ? $o->$v : $item->$v;
                                        });
                                        $val = is_numeric($rawVal) ? $rawVal : 0;
                                    }
                                    $col['total'] = bcadd((string) $col['total'], (string) $val, $precision);
                                }
                            }
                            unset($col);

                            // 找到第一个需要合计的列的索引，用于确定"合计"标签的colspan
                            $firstSummableIdx = -1;
                            foreach ($columns as $idx => $col) {
                                if ($col['is_summable']) {
                                    $firstSummableIdx = $idx;
                                    break;
                                }
                            }
                            // 如果没有找到可合计的列，默认占所有列
                            if ($firstSummableIdx === -1) {
                                $firstSummableIdx = count($columns);
                            }
                            // 检查是否有任何可合计的列
                            $hasSummableColumns = ($firstSummableIdx < count($columns));
                        @endphp
                        <thead>
                            <tr>
                                @foreach($columns as $col)
                                    <th>{{ $col['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    @foreach($columns as $col)
                                        <td>
                                            @if(isset($col['is_calc']) && $col['is_calc'])
                                                {{ bcmul($item->actual_num ?? 0, $item->price ?? 0, 2) }}
                                            @else
                                                                {{ collect(explode(".", $col['key']))->reduce(function ($o, $v) use ($item) {
                                                    return $o ? $o->$v : $item->$v;
                                                }) }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                            {{-- 合计行 - 只在有可合计列时显示 --}}
                            @if($hasSummableColumns)
                                <tr style="font-weight: bold;">
                                    @if($firstSummableIdx > 0)
                                        <td colspan="{{ $firstSummableIdx }}">合 &nbsp; 计</td>
                                    @else
                                        <td>合 &nbsp; 计</td>
                                    @endif
                                    @for($i = ($firstSummableIdx > 0 ? $firstSummableIdx : 1); $i < count($columns); $i++)
                                        <td>
                                            @if($columns[$i]['is_summable'])
                                                @php
                                                    // 数量列保留3位小数，价格/金额列保留2位小数
                                                    $decimals = (strpos($columns[$i]['label'], '数量') !== false) ? 3 : 2;
                                                @endphp
                                                {{ number_format((float) $columns[$i]['total'], $decimals, '.', '') }}
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endif
                        </tbody>
                    </table>

                    <div class="page-info">
                        第<span>{{ $loop->iteration }}</span>单 · 共<span>{{ count($orders) }}</span>单
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        (function () {
            const button = document.getElementById('triggerPrint');
            if (button) {
                button.addEventListener('click', () => window.print());
            }

            // 保持原有打开即打印的体验
            window.addEventListener('load', () => {
                setTimeout(() => window.print(), 300);
            });
        })();
    </script>
</body>

</html>