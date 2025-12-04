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
            color: #222;
            background: #f8f9fa;
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
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        }

        .invoice {
            padding: 18px 22px 32px 22px;
            font-family: "Courier New", Courier, monospace;
            font-size: 12px;
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
            margin-bottom: 16px;
            padding-bottom: 10px;
            position: relative;
        }

        .invoice-header::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 10%;
            width: 80%;
            height: 1px;
            background: linear-gradient(to right, transparent, #666, transparent);
            border: none;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .invoice-title {
            font-size: 15px;
            font-weight: 600;
            color: #222;
            margin-bottom: 6px;
        }

        .contact-info {
            font-size: 11px;
            color: #666;
        }

        .customer-info {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 14px;
            padding: 8px 0;
            column-gap: 18px;
            row-gap: 12px;
        }

        .customer-info > div {
            font-size: 12px;
            white-space: normal;
            line-height: 1.5;
            word-break: break-all;
        }

        .customer-info label {
            font-weight: bold;
            color: #333;
            margin-right: 4px;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            border: 1px solid #ddd;
        }

        .product-table th {
            background: #f5f5f5;
            font-weight: bold;
            border: 1px solid #ccc;
            padding: 6px 5px;
            text-align: center;
            font-size: 12px;
        }

        .product-table td {
            border: 1px solid #ccc;
            padding: 6px 5px;
            text-align: center;
            font-size: 12px;
        }

        @media screen {
            .product-table tbody tr:nth-child(even) td {
                background: #fafafa;
            }
        }

        .page-info {
            text-align: center;
            font-size: 12px;
            color: #333;
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
                font-size: 12px;
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
                background: #f5f5f5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .invoice-header::after {
                border-color: #333 !important;
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
                    <div class="contact-info">打印时间：{{ now()->format('Y-m-d H:i') }}</div>
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
                    <thead>
                    <tr>
                        @foreach($itemField as $field)
                            <th>{{ $field }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @php
                        if(! $order->items instanceof Illuminate\Database\Eloquent\Collection) {
                            $order->items = [$order->items];
                        }
                    @endphp
                    @foreach($order->items as $item)
                        <tr>
                            @foreach($itemField as $key => $field)
                                <td>{{
                                    collect(explode(".", $key))->reduce(function ($object, $value) use ($item) {
                                        return $object ? $object->$value : $item->$value;
                                    })
                                }}</td>
                            @endforeach
                        </tr>
                    @endforeach
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
