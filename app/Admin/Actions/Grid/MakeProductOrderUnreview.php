<?php

namespace App\Admin\Actions\Grid;

use App\Models\MakeProductOrderModel;
use App\Services\MakeProductOrderUnreviewService;
use Dcat\Admin\Grid\Tools\AbstractTool;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MakeProductOrderUnreview extends AbstractTool
{
    protected $title = '反审核';

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
        $order = MakeProductOrderModel::query()->find($id);

        if (! $order) {
            return $this->response()->error('未找到生产入库单');
        }

        try {
            app(MakeProductOrderUnreviewService::class)->unreview($order);
            return $this->response()->success('单据反审核成功！')->refresh();
        } catch (\Throwable $exception) {
            return $this->response()->error('单据反审核失败！' . $exception->getMessage());
        }
    }

    public function html(): string
    {
        return <<<HTML
<a {$this->formatHtmlAttributes()}><button class="btn btn-primary btn-mini"><i class="feather icon-rotate-ccw"></i> {$this->title()}</button></a>
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
            'permission_slug' => order_unreview_permission_slug(),
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

        return order_unreview_permission_slug($controller);
    }

    public function confirm(): array
    {
        return ["你确定要反审核单据?"];
    }
}
