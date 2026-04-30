<?php

namespace App\Admin\Actions\Grid;

use App\Models\PurchaseOrderModel;
use App\Services\PurchaseOrderFinishService;
use Dcat\Admin\Grid\Tools\AbstractTool;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PurchaseOrderFinish extends AbstractTool
{
    protected $title = '提前完结';

    protected function authorize($user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->can($this->getPermissionSlug(request()));
    }

    public function handle(Request $request)
    {
        $id = $request->input('id');
        $order = PurchaseOrderModel::query()->find($id);

        if (! $order) {
            return $this->response()->error('未找到采购订单');
        }

        try {
            app(PurchaseOrderFinishService::class)->finish($order);
            return $this->response()->success('单据已提前完结！')->refresh();
        } catch (\Throwable $exception) {
            return $this->response()->error('单据提前完结失败！' . $exception->getMessage());
        }
    }

    public function html(): string
    {
        return <<<HTML
<a {$this->formatHtmlAttributes()}><button class="btn btn-primary btn-mini"><i class="feather icon-check-circle"></i> {$this->title()}</button></a>
HTML;
    }

    public function getTable(): string
    {
        return Str::snake(admin_controller_name());
    }

    protected function parameters(): array
    {
        return [
            'id' => request()->route()->parameter($this->getTable()),
            'permission_slug' => order_review_permission_slug(),
        ];
    }

    protected function getPermissionSlug(?Request $request = null): string
    {
        $request = $request ?: request();

        if ($request && $request->filled('permission_slug')) {
            return (string) $request->input('permission_slug');
        }

        $table = $request ? $request->input('table') : null;
        $controller = $table ? Str::studly(str_replace(['-', '_'], ' ', $table)) : null;

        return order_review_permission_slug($controller);
    }

    public function confirm(): array
    {
        return ['确认提前完结该单据，不再继续补货？'];
    }
}
