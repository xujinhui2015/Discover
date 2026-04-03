<?php

namespace App\Admin\Metrics;

use App\Models\PurchaseOrderModel;
use App\Models\SaleOrderModel;
use App\Models\TaskModel;
use Dcat\Admin\Widgets\Metrics\Round;
use Illuminate\Http\Request;

class OrderStatus extends Round
{
    protected $labels = [];

    protected function init()
    {
        parent::init();

        $this->title('订单状态统计');
        $this->height(260);
        $this->class('dashboard-metric-tall', true);
        $this->chartLabels(['销售订单', '采购订单', '生产任务']);
        $this->dropdown([
            'all' => '全部订单',
            'sale' => '销售订单',
            'purchase' => '采购订单',
            'task' => '生产任务',
        ]);
    }

    public function handle(Request $request)
    {
        $option = $request->get('option', 'all');

        switch ($option) {
            case 'sale':
                $doing = SaleOrderModel::query()
                    ->where('status', SaleOrderModel::STATUS_DOING)
                    ->count();
                $send = SaleOrderModel::query()
                    ->where('status', SaleOrderModel::STATUS_SEND)
                    ->count();
                $sign = SaleOrderModel::query()
                    ->where('status', SaleOrderModel::STATUS_SIGN)
                    ->count();
                $returned = SaleOrderModel::query()
                    ->where('status', SaleOrderModel::STATUS_RETURNED)
                    ->count();

                $this->labels = ['受理中', '已发货', '已签收', '已退回'];
                $this->withContent($doing, $send, $sign, $returned);
                $this->withChart([$doing, $send, $sign, $returned]);
                $this->chartLabels(['受理中', '已发货', '已签收', '已退回']);
                $this->chartTotal('总数', $doing + $send + $sign + $returned);
                break;

            case 'purchase':
                $wait = PurchaseOrderModel::query()
                    ->where('status', PurchaseOrderModel::STATUS_WAIT)
                    ->count();
                $arrive = PurchaseOrderModel::query()
                    ->where('status', PurchaseOrderModel::STATUS_ARRIVE)
                    ->count();
                $returning = PurchaseOrderModel::query()
                    ->where('status', PurchaseOrderModel::STATUS_RETURNING)
                    ->count();
                $returned = PurchaseOrderModel::query()
                    ->where('status', PurchaseOrderModel::STATUS_RETURNED)
                    ->count();
                $partReturned = PurchaseOrderModel::query()
                    ->where('status', PurchaseOrderModel::STATUS_PART_RETURNED)
                    ->count();

                $this->labels = ['待收货', '已收货', '退回中', '已退回', '部分收货'];
                $this->withContent($wait, $arrive, $returning, $returned, $partReturned);
                $this->withChart([$wait, $arrive, $returning, $returned, $partReturned]);
                $this->chartLabels(['待收货', '已收货', '退回中', '已退回', '部分收货']);
                $this->chartTotal('总数', $wait + $arrive + $returning + $returned + $partReturned);
                break;

            case 'task':
                $wait = TaskModel::query()
                    ->where('status', TaskModel::STATUS_WAIT)
                    ->count();
                $draw = TaskModel::query()
                    ->where('status', TaskModel::STATUS_DRAW)
                    ->count();
                $finish = TaskModel::query()
                    ->where('status', TaskModel::STATUS_FINISH)
                    ->count();
                $stop = TaskModel::query()
                    ->where('status', TaskModel::STATUS_STOP)
                    ->count();

                $this->labels = ['待领料', '已领料', '已完成', '停止'];
                $this->withContent($wait, $draw, $finish, $stop);
                $this->withChart([$wait, $draw, $finish, $stop]);
                $this->chartLabels(['待领料', '已领料', '已完成', '停止']);
                $this->chartTotal('总数', $wait + $draw + $finish + $stop);
                break;

            case 'all':
            default:
                $saleCount = SaleOrderModel::query()->count();
                $purchaseCount = PurchaseOrderModel::query()->count();
                $taskCount = TaskModel::query()->count();

                $this->labels = ['销售订单', '采购订单', '生产任务'];
                $this->withContent($saleCount, $purchaseCount, $taskCount);
                $this->withChart([$saleCount, $purchaseCount, $taskCount]);
                $this->chartLabels(['销售订单', '采购订单', '生产任务']);
                $this->chartTotal('总数', $saleCount + $purchaseCount + $taskCount);
                break;
        }
    }

    public function withChart(array $data)
    {
        $total = array_sum($data);
        $percentages = $total > 0
            ? array_map(fn ($v) => round($v / $total * 100, 1), $data)
            : array_map(fn () => 0, $data);

        return $this->chart([
            'series' => $percentages,
        ]);
    }

    public function withContent($item1, $item2, $item3 = 0, $item4 = 0, $item5 = 0)
    {
        $label1 = $this->labels[0] ?? '项目1';
        $label2 = $this->labels[1] ?? '项目2';
        $label3 = $this->labels[2] ?? '项目3';
        $label4 = $this->labels[3] ?? '项目4';
        $label5 = $this->labels[4] ?? '项目5';

        $html = <<<HTML
<div class="col-12 d-flex flex-column flex-wrap text-center" style="max-width: 220px">
    <div class="chart-info d-flex justify-content-between mb-1 mt-2" >
          <div class="series-info d-flex align-items-center">
              <i class="fa fa-circle-o text-bold-700 text-primary"></i>
              <span class="text-bold-600 ml-50">{$label1}</span>
          </div>
          <div class="product-result">
              <span>{$item1}</span>
          </div>
    </div>

    <div class="chart-info d-flex justify-content-between mb-1">
          <div class="series-info d-flex align-items-center">
              <i class="fa fa-circle-o text-bold-700 text-warning"></i>
              <span class="text-bold-600 ml-50">{$label2}</span>
          </div>
          <div class="product-result">
              <span>{$item2}</span>
          </div>
    </div>
HTML;

        if ($item3 > 0) {
            $html .= <<<HTML
     <div class="chart-info d-flex justify-content-between mb-1">
          <div class="series-info d-flex align-items-center">
              <i class="fa fa-circle-o text-bold-700 text-success"></i>
              <span class="text-bold-600 ml-50">{$label3}</span>
          </div>
          <div class="product-result">
              <span>{$item3}</span>
          </div>
    </div>
HTML;
        }

        if ($item4 > 0) {
            $html .= <<<HTML
     <div class="chart-info d-flex justify-content-between mb-1">
          <div class="series-info d-flex align-items-center">
              <i class="fa fa-circle-o text-bold-700 text-danger"></i>
              <span class="text-bold-600 ml-50">{$label4}</span>
          </div>
          <div class="product-result">
              <span>{$item4}</span>
          </div>
    </div>
HTML;
        }

        if ($item5 > 0) {
            $html .= <<<HTML
     <div class="chart-info d-flex justify-content-between mb-1">
          <div class="series-info d-flex align-items-center">
              <i class="fa fa-circle-o text-bold-700 text-info"></i>
              <span class="text-bold-600 ml-50">{$label5}</span>
          </div>
          <div class="product-result">
              <span>{$item5}</span>
          </div>
    </div>
HTML;
        }

        $html .= '</div>';

        return $this->content($html);
    }
}
