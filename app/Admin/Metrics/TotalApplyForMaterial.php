<?php

namespace App\Admin\Metrics;

use App\Helpers\AccuracyCalc;
use App\Models\ApplyForOrderModel;
use App\Models\BaseModel;
use Carbon\Carbon;
use Closure;
use Dcat\Admin\Widgets\Metrics\Card;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;

class TotalApplyForMaterial extends Card
{
    protected Renderable|Closure|string|null $footer = null;

    public function __construct()
    {
        parent::__construct();
    }

    protected function init(): void
    {
        parent::init();

        $this->title('物料申领单数');
        $this->dropdown([
            '7' => '最近7天',
            '28' => '最近28天',
            '30' => '最近一个月',
            '365' => '最近一年',
        ]);
    }

    public function handle(Request $request): void
    {
        $query = fn ($start, $end) => ApplyForOrderModel::query()
            ->where('review_status', BaseModel::REVIEW_STATUS_OK)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        switch ($request->get('option')) {
            case '365':
                $num = $query(Carbon::now()->subYear(), Carbon::now());
                $numLast = $query(Carbon::now()->subYears(2), Carbon::now()->subYear());
                break;
            case '30':
                $num = $query(Carbon::now()->subMonth(), Carbon::now());
                $numLast = $query(Carbon::now()->subMonths(2), Carbon::now()->subMonth());
                break;
            case '28':
                $num = $query(Carbon::now()->subDays(28), Carbon::now());
                $numLast = $query(Carbon::now()->subDays(56), Carbon::now()->subDays(28));
                break;
            case '7':
            default:
                $num = $query(Carbon::now()->subDays(7), Carbon::now());
                $numLast = $query(Carbon::now()->subDays(14), Carbon::now()->subDays(7));
        }

        $this->content($num);
        if ($numLast > 0) {
            $percent = AccuracyCalc::begin($numLast)->proportion($num)->result();
            $this->up($percent);
        } else {
            $this->footer('');
        }
    }

    public function up(int $percent): static
    {
        return $this->footer(
            "<i class=\"feather icon-trending-up text-success\"></i> $percent% 提升"
        );
    }

    public function down(int $percent): static
    {
        return $this->footer(
            "<i class=\"feather icon-trending-down text-danger\"></i> $percent% 降低"
        );
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
