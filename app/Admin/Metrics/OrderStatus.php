<?php

namespace App\Admin\Metrics;

use App\Models\PurchaseOrderModel;
use App\Models\SaleOrderModel;
use Dcat\Admin\Widgets\Metrics\Round;
use Illuminate\Http\Request;

class OrderStatus extends Round
{
    protected $labels = [];

    protected function init()
    {
        parent::init();

        $this->title('订单状态统计');
        $this->chartLabels(['销售订单', '采购订单']);
        $this->dropdown([
            'sale' => '销售订单',
            'purchase' => '采购订单',
            'all' => '全部订单',
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
                
            case 'all':
            default:
                $saleCount = SaleOrderModel::query()->count();
                $purchaseCount = PurchaseOrderModel::query()->count();
                
                $this->labels = ['销售订单', '采购订单'];
                $this->withContent($saleCount, $purchaseCount);
                $this->withChart([$saleCount, $purchaseCount]);
                $this->chartLabels(['销售订单', '采购订单']);
                $this->chartTotal('总数', $saleCount + $purchaseCount);
                break;
        }
    }

    public function withChart(array $data)
    {
        return $this->chart([
            'series' => $data,
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

