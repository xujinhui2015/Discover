<?php

namespace App\Admin\Widgets;

use App\Models\StockHistoryModel;
use Carbon\Carbon;
use Dcat\Admin\Admin;
use Dcat\Admin\Widgets\Widget;
use Illuminate\Support\Collection;

class TodayStockChange extends Widget
{
    public function html()
    {
        $dateInput = request('stock_date', Carbon::today()->toDateString());
        $date = Carbon::parse($dateInput);
        $dateStr = $date->toDateString();
        $isToday = $date->isToday();
        $dateLabel = $isToday ? '今日' : $date->format('Y年m月d日');

        $records = StockHistoryModel::query()
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        $datePicker = $this->buildDatePicker($dateStr);
        $summary = $this->buildSummary($records);
        $typeChart = $this->buildTypeBreakdown($records);
        $table = $this->buildTable($records, $dateLabel);

        return <<<HTML
<div class="stock-change-dashboard">
    {$datePicker}
    {$summary}
    {$typeChart}
    {$table}
</div>
HTML;
    }

    protected function buildDatePicker(string $currentDate): string
    {
        $color = Admin::color();
        $url = request()->url();

        return <<<HTML
<div class="card" style="border-radius: 8px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px;">
    <div class="card-body" style="padding: 16px 20px;">
        <form id="stock-date-form" method="GET" action="{$url}" style="display: flex; align-items: center; gap: 12px;">
            <i class="feather icon-calendar" style="font-size: 18px; color: {$color->primary()};"></i>
            <span style="font-weight: 600; color: #333;">选择日期</span>
            <input type="date" name="stock_date" value="{$currentDate}"
                   style="border: 1px solid #ddd; border-radius: 6px; padding: 6px 12px; font-size: 14px; outline: none; cursor: pointer;"
                   onchange="this.form.submit();" />
            <a href="{$url}?stock_date=" style="font-size: 13px; color: {$color->primary()}; text-decoration: none;">回到今天</a>
        </form>
    </div>
</div>
HTML;
    }

    protected function buildSummary(Collection $records): string
    {
        $color = Admin::color();
        $totalCount = $records->count();
        $inCount = $records->where('flag', StockHistoryModel::IN)->count();
        $outCount = $records->where('flag', StockHistoryModel::OUT)->count();
        $totalInNum = $records->where('flag', StockHistoryModel::IN)->sum('in_num');
        $totalOutNum = $records->where('flag', StockHistoryModel::OUT)->sum('out_num');
        $netChange = $totalInNum - $totalOutNum;
        $netColor = $netChange >= 0 ? $color->success() : $color->danger();
        $netSign = $netChange >= 0 ? '+' : '';

        return <<<HTML
<div class="row" style="margin-bottom: 20px;">
    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
        <div class="card" style="border-radius: 8px; overflow: hidden; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div class="card-body" style="padding: 20px;">
                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: {$color->primary()}20; display: flex; align-items: center; justify-content: center;">
                        <i class="feather icon-activity" style="color: {$color->primary()}; font-size: 18px;"></i>
                    </div>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: #333;">{$totalCount}</div>
                <div style="font-size: 13px; color: #888; margin-top: 4px;">变动次数</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
        <div class="card" style="border-radius: 8px; overflow: hidden; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div class="card-body" style="padding: 20px;">
                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: {$color->success()}20; display: flex; align-items: center; justify-content: center;">
                        <i class="feather icon-arrow-down-circle" style="color: {$color->success()}; font-size: 18px;"></i>
                    </div>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: {$color->success()};">{$inCount}</div>
                <div style="font-size: 13px; color: #888; margin-top: 4px;">入库次数</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
        <div class="card" style="border-radius: 8px; overflow: hidden; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div class="card-body" style="padding: 20px;">
                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: {$color->danger()}20; display: flex; align-items: center; justify-content: center;">
                        <i class="feather icon-arrow-up-circle" style="color: {$color->danger()}; font-size: 18px;"></i>
                    </div>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: {$color->danger()};">{$outCount}</div>
                <div style="font-size: 13px; color: #888; margin-top: 4px;">出库次数</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
        <div class="card" style="border-radius: 8px; overflow: hidden; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div class="card-body" style="padding: 20px;">
                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: {$color->success()}20; display: flex; align-items: center; justify-content: center;">
                        <i class="feather icon-package" style="color: {$color->success()}; font-size: 18px;"></i>
                    </div>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: {$color->success()};">{$totalInNum}</div>
                <div style="font-size: 13px; color: #888; margin-top: 4px;">入库总量</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
        <div class="card" style="border-radius: 8px; overflow: hidden; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div class="card-body" style="padding: 20px;">
                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: {$color->danger()}20; display: flex; align-items: center; justify-content: center;">
                        <i class="feather icon-truck" style="color: {$color->danger()}; font-size: 18px;"></i>
                    </div>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: {$color->danger()};">{$totalOutNum}</div>
                <div style="font-size: 13px; color: #888; margin-top: 4px;">出库总量</div>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
        <div class="card" style="border-radius: 8px; overflow: hidden; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div class="card-body" style="padding: 20px;">
                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: {$netColor}20; display: flex; align-items: center; justify-content: center;">
                        <i class="feather icon-trending-up" style="color: {$netColor}; font-size: 18px;"></i>
                    </div>
                </div>
                <div style="font-size: 24px; font-weight: 700; color: {$netColor};">{$netSign}{$netChange}</div>
                <div style="font-size: 13px; color: #888; margin-top: 4px;">净变动量</div>
            </div>
        </div>
    </div>
</div>
HTML;
    }

    protected function buildTypeBreakdown(Collection $records): string
    {
        if ($records->isEmpty()) {
            return '';
        }

        $color = Admin::color();
        $typeGroups = $records->groupBy('type');
        $colors = [
            $color->primary(), $color->success(), $color->warning(),
            $color->danger(), $color->info(), '#8B5CF6',
            '#EC4899', '#F59E0B', '#10B981', '#6366F1',
            '#EF4444', '#14B8A6', '#F97316',
        ];

        $badges = '';
        $index = 0;
        foreach ($typeGroups as $type => $items) {
            $typeName = StockHistoryModel::TYPE[$type] ?? '未知';
            $count = $items->count();
            $totalIn = $items->sum('in_num');
            $totalOut = $items->sum('out_num');
            $badgeColor = $colors[$index % count($colors)];

            $detail = '';
            if ($totalIn > 0) {
                $detail .= "<span style='color: {$color->success()}; font-size: 12px;'>+{$totalIn}</span>";
            }
            if ($totalOut > 0) {
                if ($detail) {
                    $detail .= ' ';
                }
                $detail .= "<span style='color: {$color->danger()}; font-size: 12px;'>-{$totalOut}</span>";
            }

            $badges .= <<<HTML
<div style="display: inline-flex; align-items: center; background: {$badgeColor}10; border: 1px solid {$badgeColor}30; border-radius: 8px; padding: 10px 16px; margin: 4px 8px 4px 0; gap: 8px;">
    <span style="width: 8px; height: 8px; border-radius: 50%; background: {$badgeColor}; display: inline-block;"></span>
    <span style="font-weight: 600; color: #333;">{$typeName}</span>
    <span style="background: {$badgeColor}; color: #fff; border-radius: 10px; padding: 1px 8px; font-size: 12px; font-weight: 600;">{$count}</span>
    {$detail}
</div>
HTML;
            $index++;
        }

        return <<<HTML
<div class="card" style="border-radius: 8px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px;">
    <div class="card-body" style="padding: 20px;">
        <h4 style="font-size: 15px; font-weight: 600; color: #333; margin-bottom: 16px;">
            <i class="feather icon-pie-chart" style="margin-right: 6px; color: {$color->primary()};"></i>
            按业务类型分布
        </h4>
        <div style="display: flex; flex-wrap: wrap;">
            {$badges}
        </div>
    </div>
</div>
HTML;
    }

    protected function buildTable(Collection $records, string $dateLabel = '今日'): string
    {
        $color = Admin::color();

        if ($records->isEmpty()) {
            return <<<HTML
<div class="card" style="border-radius: 8px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <div class="card-body" style="padding: 60px 20px; text-align: center;">
        <i class="feather icon-inbox" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 16px;"></i>
        <div style="font-size: 16px; color: #999;">{$dateLabel}暂无库存变动记录</div>
    </div>
</div>
HTML;
        }

        $rows = '';
        foreach ($records as $record) {
            $typeName = StockHistoryModel::TYPE[$record->type] ?? '未知';
            $flagName = StockHistoryModel::FLAG[$record->flag] ?? '未知';

            // 标志颜色
            $flagColor = match ((int) $record->flag) {
                StockHistoryModel::IN => $color->success(),
                StockHistoryModel::OUT => $color->danger(),
                StockHistoryModel::INVENTORY => $color->warning(),
                StockHistoryModel::TRANSFER => $color->info(),
                default => '#888',
            };

            $skuName = '';
            if ($record->sku && $record->sku->product) {
                $skuName = $record->sku->product->name;
                if ($record->sku->attr_value_ids_str) {
                    $skuName .= ' (' . $record->sku->attr_value_ids_str . ')';
                }
            }

            $inPosition = $record->in_position->name ?? '-';
            $outPosition = $record->out_position->name ?? '-';

            $inNumHtml = $record->in_num > 0
                ? "<span style='color: {$color->success()}; font-weight: 600;'>+{$record->in_num}</span>"
                : "<span style='color: #ccc;'>-</span>";
            $outNumHtml = $record->out_num > 0
                ? "<span style='color: {$color->danger()}; font-weight: 600;'>-{$record->out_num}</span>"
                : "<span style='color: #ccc;'>-</span>";

            $time = $record->created_at ? $record->created_at->format('H:i:s') : '-';
            $userName = $record->user->name ?? '-';
            $orderNo = $record->with_order_no ?: '-';

            $rows .= <<<HTML
<tr style="border-bottom: 1px solid #f0f0f0;">
    <td style="padding: 12px 16px; white-space: nowrap; font-size: 13px; color: #888;">{$userName}</td>
    <td style="padding: 12px 16px; white-space: nowrap; color: #888; font-size: 13px;">{$time}</td>
    <td style="padding: 12px 16px; white-space: nowrap; font-weight: 500;">{$skuName}</td>
    <td style="padding: 12px 16px; white-space: nowrap;">
        <span style="display: inline-block; background: {$flagColor}15; color: {$flagColor}; border-radius: 4px; padding: 2px 10px; font-size: 12px; font-weight: 600;">{$flagName}</span>
    </td>
    <td style="padding: 12px 16px; white-space: nowrap;">
        <span style="background: #f5f5f5; border-radius: 4px; padding: 2px 8px; font-size: 12px;">{$typeName}</span>
    </td>
    <td style="padding: 12px 16px; white-space: nowrap; text-align: right;">{$inNumHtml}</td>
    <td style="padding: 12px 16px; white-space: nowrap; text-align: right;">{$outNumHtml}</td>
    <td style="padding: 12px 16px; white-space: nowrap; font-weight: 600; text-align: right;">{$record->balance_num}</td>
    <td style="padding: 12px 16px; white-space: nowrap; font-size: 13px;">{$inPosition}</td>
    <td style="padding: 12px 16px; white-space: nowrap; font-size: 13px;">{$outPosition}</td>
    <td style="padding: 12px 16px; white-space: nowrap; font-size: 13px; color: #888;">{$orderNo}</td>
</tr>
HTML;
        }

        $totalCount = $records->count();

        return <<<HTML
<div class="card" style="border-radius: 8px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <div class="card-body" style="padding: 0;">
        <div style="padding: 20px 20px 12px; display: flex; justify-content: space-between; align-items: center;">
            <h4 style="font-size: 15px; font-weight: 600; color: #333; margin: 0;">
                <i class="feather icon-list" style="margin-right: 6px; color: {$color->primary()};"></i>
                变动明细
            </h4>
            <span style="font-size: 13px; color: #888;">共 {$totalCount} 条记录</span>
        </div>
        <div style="overflow-x: auto;">
            <table style="min-width: 1100px; width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #fafafa; border-top: 1px solid #f0f0f0; border-bottom: 1px solid #f0f0f0;">
                        <th style="padding: 10px 16px; font-size: 12px; font-weight: 600; color: #888; white-space: nowrap;">操作人</th>
                        <th style="padding: 10px 16px; font-size: 12px; font-weight: 600; color: #888; white-space: nowrap;">时间</th>
                        <th style="padding: 10px 16px; font-size: 12px; font-weight: 600; color: #888; white-space: nowrap;">物料</th>
                        <th style="padding: 10px 16px; font-size: 12px; font-weight: 600; color: #888; white-space: nowrap;">方向</th>
                        <th style="padding: 10px 16px; font-size: 12px; font-weight: 600; color: #888; white-space: nowrap;">类型</th>
                        <th style="padding: 10px 16px; font-size: 12px; font-weight: 600; color: #888; white-space: nowrap; text-align: right;">入库</th>
                        <th style="padding: 10px 16px; font-size: 12px; font-weight: 600; color: #888; white-space: nowrap; text-align: right;">出库</th>
                        <th style="padding: 10px 16px; font-size: 12px; font-weight: 600; color: #888; white-space: nowrap; text-align: right;">结余</th>
                        <th style="padding: 10px 16px; font-size: 12px; font-weight: 600; color: #888; white-space: nowrap;">入库位置</th>
                        <th style="padding: 10px 16px; font-size: 12px; font-weight: 600; color: #888; white-space: nowrap;">出库位置</th>
                        <th style="padding: 10px 16px; font-size: 12px; font-weight: 600; color: #888; white-space: nowrap;">关联单号</th>
                    </tr>
                </thead>
                <tbody>
                    {$rows}
                </tbody>
            </table>
        </div>
    </div>
</div>
HTML;
    }
}
