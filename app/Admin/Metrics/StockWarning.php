<?php

namespace App\Admin\Metrics;

use App\Models\SkuStockModel;
use Closure;
use Dcat\Admin\Widgets\Metrics\Card;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;

class StockWarning extends Card
{
    /**
     * 卡片底部内容.
     */
    protected Renderable|Closure|string|null $footer = null;

    public function __construct()
    {
        parent::__construct();
    }

    protected function init(): void
    {
        parent::init();

        $this->title('库存预警');
        $this->height(260);
        $this->class('dashboard-metric-tall', true);
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
        
        if ($total > 0) {
            $warningCount = $lowStockCount + $nearStockCount;
            $this->content($warningCount);
            
            if ($warningCount > 0) {
                $this->footer(
                    "<span class='text-danger'><i class=\"feather icon-alert-triangle\"></i> 低库存: {$lowStockCount} | 接近安全库存: {$nearStockCount}</span>"
                );
            } else {
                $this->footer(
                    "<span class='text-success'><i class=\"feather icon-check-circle\"></i> 库存正常</span>"
                );
            }
        } else {
            $this->content(0);
            $this->footer('');
        }
    }

    public function footer(Renderable|Closure|string $footer): static
    {
        $this->footer = $footer;

        return $this;
    }

    public function renderContent(): string
    {
        $content = parent::renderContent();

        return <<<HTML
<div class="d-flex justify-content-between align-items-center mt-1" style="margin-bottom: 2px">
    <h2 class="ml-1 font-large-1">{$content}</h2>
</div>
<div class="ml-1 mt-1 font-weight-bold text-80">
    {$this->renderFooter()}
</div>
HTML;
    }

    public function renderFooter(): string
    {
        return $this->toString($this->footer);
    }
}
