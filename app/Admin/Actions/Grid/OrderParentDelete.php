<?php

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Grid\Tools\AbstractTool;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderParentDelete extends AbstractTool
{

    protected $title = '删除单据';

    public function handle(Request $request)
    {
        $id         = $request->input('id');
        $model      = $request->input('model');
        $modelClass = "\\App\Models\\" . $model;
        $table = $request->input('table');

        $modelObject =   $modelClass::find($id);

        if ($modelObject->review_status == $modelObject::REVIEW_STATUS_OK) {
            return $this->response()->error('无法删除已审核的单据');
        }
        try {
            $modelObject->delete();
            return $this->response()->success("单据删除成功！请关闭页面！")->script("
    setTimeout(function() {
        if (parent && parent.layer) parent.layer.closeAll();
    }, 1000);
");
        } catch (\Exception $exception) {
            return $this->response()->error("单据删除成功失败！". $exception->getMessage());
        }
    }

    /**
     * @return string
     */
    public function html(): string
    {
        return <<<HTML
<a {$this->formatHtmlAttributes()}><button class="btn btn-primary btn-mini"><i class="feather icon-user-check"></i> {$this->title()}</button></a>
HTML;
    }

    /**
     * @return string
     */
    public function getModel(): string
    {
        return admin_controller_name() . 'Model';
    }

    /**
     * @return array
     */
    protected function parameters(): array
    {
        return [
            'table'         => $this->getTable(),
            'model'         => $this->getModel(),
            'id' => request()->route()->parameter($this->getTable()),
        ];
    }

    public function getTable():string
    {
        return Str::snake(admin_controller_name());
    }

    /**
     * @return array
     */
    public function confirm(): array
    {
        return ["你确定要删除单据?"];
    }
}
