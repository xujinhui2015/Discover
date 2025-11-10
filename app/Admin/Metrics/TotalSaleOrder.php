<?php

namespace App\Admin\Metrics;

use App\Helpers\AccuracyCalc;
use App\Models\SaleOrderModel;
use Carbon\Carbon;
use Closure;
use Dcat\Admin\Widgets\Metrics\Card;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;

class TotalSaleOrder extends Card
{
    /**
     * 卡片底部内容.
     */
    protected Renderable|Closure|string|null $footer = null;

    // 构造方法参数必须设置默认值
    public function __construct()
    {
        parent::__construct();
    }

    protected function init(): void
    {
        parent::init();

        // 设置标题
        $this->title('销售订单数');
        // 设置下拉菜单
        $this->dropdown([
            '7' => '最近7天',
            '28' => '最近28天',
            '30' => '最近一个月',
            '365' => '最近一年',
        ]);
    }

    /**
     * 处理请求.
     *
     * @param Request $request
     *
     * @return void
     */
    public function handle(Request $request): void
    {
        switch ($request->get('option')) {
            case '365':
                $count = SaleOrderModel::query()
                    ->whereBetween('created_at', [Carbon::now()->subYear(), Carbon::now()])
                    ->count();
                $countLast = SaleOrderModel::query()
                    ->whereBetween('created_at', [Carbon::now()->subYears(2), Carbon::now()->subYear()])
                    ->count();
                $this->content($count);
                if ($countLast > 0) {
                    $percent = AccuracyCalc::begin($countLast)->proportion($count)->result();
                    $this->up($percent);
                } else {
                    $this->footer('');
                }
                break;
            case '30':
                $count = SaleOrderModel::query()
                    ->whereBetween('created_at', [Carbon::now()->subMonth(), Carbon::now()])
                    ->count();
                $countLast = SaleOrderModel::query()
                    ->whereBetween('created_at', [Carbon::now()->subMonths(2), Carbon::now()->subMonth()])
                    ->count();
                $this->content($count);
                if ($countLast > 0) {
                    $percent = AccuracyCalc::begin($countLast)->proportion($count)->result();
                    $this->up($percent);
                } else {
                    $this->footer('');
                }
                break;
            case '28':
                $count = SaleOrderModel::query()
                    ->whereBetween('created_at', [Carbon::now()->subDays(28), Carbon::now()])
                    ->count();
                $countLast = SaleOrderModel::query()
                    ->whereBetween('created_at', [Carbon::now()->subDays(56), Carbon::now()->subDays(28)])
                    ->count();
                $this->content($count);
                if ($countLast > 0) {
                    $percent = AccuracyCalc::begin($countLast)->proportion($count)->result();
                    $this->up($percent);
                } else {
                    $this->footer('');
                }
                break;
            case '7':
            default:
                $count = SaleOrderModel::query()
                    ->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()])
                    ->count();
                $countLast = SaleOrderModel::query()
                    ->whereBetween('created_at', [Carbon::now()->subDays(14), Carbon::now()->subDays(7)])
                    ->count();
                $this->content($count);
                if ($countLast > 0) {
                    $percent = AccuracyCalc::begin($countLast)->proportion($count)->result();
                    $this->up($percent);
                } else {
                    $this->footer('');
                }
        }
    }

    /**
     * @param int $percent
     *
     * @return $this
     */
    public function up(int $percent): static
    {
        return $this->footer(
            "<i class=\"feather icon-trending-up text-success\"></i> $percent% 提升"
        );
    }

    /**
     * @param int $percent
     *
     * @return $this
     */
    public function down(int $percent): static
    {
        return $this->footer(
            "<i class=\"feather icon-trending-down text-danger\"></i> $percent% 降低"
        );
    }

    /**
     * 设置卡片底部内容
     *
     * @param Closure|string|Renderable $footer
     *
     * @return $this
     */
    public function footer(Renderable|Closure|string $footer): static
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * 渲染卡片内容.
     *
     * @return string
     */
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

