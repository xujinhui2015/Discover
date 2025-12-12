<?php

namespace App\Admin\Metrics;

use App\Models\SkuStockModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Widgets\Metrics\Donut;
use Illuminate\Http\Request;

class StockWarning extends Donut
{
    protected array $labels = ['库存正常', '接近安全库存', '低于安全库存'];

    /**
     * 使用实心圆饼图（pie）展示占比.
     */
    protected function defaultChartOptions()
    {
        $color = Admin::color();

        return [
            'chart' => [
                'type' => 'pie',
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'colors' => [$color->success(), $color->warning(), $color->danger()],
            'legend' => [
                'show' => false,
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'stroke' => [
                'width' => 0,
            ],
        ];
    }

    public function __construct()
    {
        parent::__construct();
    }

    protected function init(): void
    {
        parent::init();

        $color = Admin::color();
        $colors = [$color->success(), $color->warning(), $color->danger()];

        $this->title('库存预警');
        $this->height(260);
        $this->class('dashboard-metric-tall', true);
        $this->chartLabels($this->labels);
        $this->chartColors($colors);
        $this->chartHeight(140);
        $this->contentWidth(6, 6);

        // pie 下居中展示图表
        $this->chart->style('margin: 0 auto;width: 160px;float:none;display:block;');
    }

    public function handle(Request $request): void
    {
        // 低库存商品数量
        $lowStockCount = SkuStockModel::query()
            ->warningStatus(SkuStockModel::WARNING_STATUS_LOW)
            ->count();
        
        // 接近安全库存的商品数量
        $nearStockCount = SkuStockModel::query()
            ->warningStatus(SkuStockModel::WARNING_STATUS_NEAR)
            ->count();
        
        // 正常库存商品数量
        $normalStockCount = SkuStockModel::query()
            ->warningStatus(SkuStockModel::WARNING_STATUS_NORMAL)
            ->count();

        $total = $lowStockCount + $nearStockCount + $normalStockCount;
        $normalPercent = $total > 0 ? round($normalStockCount / $total * 100, 1) : 0;
        $nearPercent = $total > 0 ? round($nearStockCount / $total * 100, 1) : 0;
        $lowPercent = $total > 0 ? round($lowStockCount / $total * 100, 1) : 0;

        $this->withContent($normalStockCount, $nearStockCount, $lowStockCount, $normalPercent, $nearPercent, $lowPercent);
        $this->withChart([$normalStockCount, $nearStockCount, $lowStockCount]);
    }

    protected function withContent(
        int $normalCount,
        int $nearCount,
        int $lowCount,
        float $normalPercent,
        float $nearPercent,
        float $lowPercent
    ): static {
        $success = Admin::color()->success();
        $warning = Admin::color()->warning();
        $danger = Admin::color()->danger();

        $style = 'margin-bottom: 8px';
        $labelWidth = 120;

        return $this->content(
            <<<HTML
<div class="d-flex pl-1 pr-1 pt-1" style="{$style}">
    <div style="width: {$labelWidth}px">
        <i class="fa fa-circle" style="color: {$success}"></i> {$this->labels[0]}
    </div>
    <div>{$normalPercent}% ({$normalCount})</div>
</div>
<div class="d-flex pl-1 pr-1" style="{$style}">
    <div style="width: {$labelWidth}px">
        <i class="fa fa-circle" style="color: {$warning}"></i> {$this->labels[1]}
    </div>
    <div>{$nearPercent}% ({$nearCount})</div>
</div>
<div class="d-flex pl-1 pr-1" style="{$style}">
    <div style="width: {$labelWidth}px">
        <i class="fa fa-circle" style="color: {$danger}"></i> {$this->labels[2]}
    </div>
    <div>{$lowPercent}% ({$lowCount})</div>
</div>
HTML
        );
    }

    public function withChart(array $data): static
    {
        return $this->chart([
            'series' => $data,
        ]);
    }
}
