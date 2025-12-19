<?php

namespace App\Admin\Metrics;

use App\Models\PurchaseOrderAmountModel;
use Carbon\Carbon;
use Dcat\Admin\Admin;
use Dcat\Admin\Widgets\Metrics\Bar;
use Illuminate\Http\Request;

class PurchaseTrend extends Bar
{
    protected function init()
    {
        parent::init();

        $color = Admin::color();
        $dark35 = $color->dark35();

        $this->contentWidth(5, 7);
        $this->title('采购趋势');
        $this->height(260);
        $this->class('dashboard-metric-tall', true);
        $this->dropdown([
            '7' => '最近7天',
            '28' => '最近28天',
            '30' => '最近一个月',
            '365' => '最近一年',
        ]);
        $this->chartColors([
            $dark35,
            $dark35,
            $color->primary(),
            $dark35,
            $dark35,
            $dark35
        ]);
    }

    public function handle(Request $request)
    {
        $option = $request->get('option', '7');
        
        switch ($option) {
            case '365':
                $days = 12;
                $startDate = Carbon::now()->subYear()->startOfMonth();
                $interval = 30; // 按月统计
                break;
            case '30':
                $days = 30;
                $startDate = Carbon::now()->subDays(30)->startOfDay();
                $interval = 1;
                break;
            case '28':
                $days = 28;
                $startDate = Carbon::now()->subDays(28)->startOfDay();
                $interval = 1;
                break;
            case '7':
            default:
                $days = 7;
                $startDate = Carbon::now()->subDays(7)->startOfDay();
                $interval = 1;
                break;
        }

        $data = [];
        $totalAmount = 0;

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i * $interval)->startOfDay();
            $nextDate = $date->copy()->addDays($interval)->startOfDay();
            
            $amount = PurchaseOrderAmountModel::query()
                ->whereBetween('created_at', [$date, $nextDate])
                ->sum('should_amount');
            
            $data[] = (float) $amount;
            $totalAmount += $amount;
        }

        $this->withContent('¥' . number_format($totalAmount, 2), '', 'success');
        $this->withChart([
            [
                'name' => '采购金额',
                'data' => $data,
            ],
        ]);
    }

    public function withChart(array $data)
    {
        return $this->chart([
            'series' => $data,
        ]);
    }

    public function withContent($title, $value = '', $style = 'success')
    {
        $label = strtolower(
            $this->dropdown[request()->option] ?? '最近7天'
        );

        return $this->content(
            <<<HTML
<div class="d-flex p-1 flex-column justify-content-between" style="padding-top: 0;width: 100%;height: 100%;">
    <div class="text-left">
        <h1 class="font-lg-2 mt-2 mb-0" style="font-size: 22px; line-height: 1.2; white-space: nowrap;">{$title}</h1>
        <h5 class="font-medium-2" style="margin-top: 10px;">
            <span class="text-{$style}">{$value} </span>
            <span>vs {$label}</span>
        </h5>
    </div>
</div>
HTML
        );
    }
}
