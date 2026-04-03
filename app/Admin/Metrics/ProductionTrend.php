<?php

namespace App\Admin\Metrics;

use App\Models\MakeProductOrderModel;
use Carbon\Carbon;
use Dcat\Admin\Widgets\Metrics\Line;
use Illuminate\Http\Request;

class ProductionTrend extends Line
{
    protected function init()
    {
        parent::init();

        $this->title('生产入库单趋势');
        $this->height(260);
        $this->class('dashboard-metric-tall', true);
        $this->dropdown([
            '7' => '最近7天',
            '28' => '最近28天',
            '30' => '最近一个月',
            '365' => '最近一年',
        ]);
    }

    public function handle(Request $request)
    {
        $option = $request->get('option', '7');

        switch ($option) {
            case '365':
                $days = 12;
                $startDate = Carbon::now()->subYear()->startOfMonth();
                $interval = 30;
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
        $totalNum = 0;

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i * $interval)->startOfDay();
            $nextDate = $date->copy()->addDays($interval)->startOfDay();

            $count = MakeProductOrderModel::query()
                ->whereBetween('created_at', [$date, $nextDate])
                ->count();

            $data[] = $count;
            $totalNum += $count;
        }

        $this->withContent($totalNum . ' 单');
        $this->withChart($data);
    }

    public function withChart(array $data)
    {
        return $this->chart([
            'series' => [
                [
                    'name' => '入库单数',
                    'data' => $data,
                ],
            ],
        ]);
    }

    public function withContent($content)
    {
        return $this->content(
            <<<HTML
<div class="d-flex justify-content-between align-items-center mt-1" style="margin-bottom: 2px">
    <h2 class="ml-1 font-lg-1">{$content}</h2>
    <span class="mb-0 mr-1 text-80">{$this->title}</span>
</div>
HTML
        );
    }
}
