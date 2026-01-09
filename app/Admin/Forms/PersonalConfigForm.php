<?php

/*
 * // +----------------------------------------------------------------------
 * // | erp
 * // +----------------------------------------------------------------------
 * // | Copyright (c) 2006~2020 erp All rights reserved.
 * // +----------------------------------------------------------------------
 * // | Licensed ( LICENSE-1.0.0 )
 * // +----------------------------------------------------------------------
 */

namespace App\Admin\Forms;

use App\Models\PersonalConfigModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Widgets\Form;

class PersonalConfigForm extends Form
{
    private const MATERIAL_DETAIL_STYLE = 'material_detail_style';
    private const STYLE_NAME = 'name';
    private const STYLE_DETAIL = 'detail';

    public function handle(array $input)
    {
        $style = $input[self::MATERIAL_DETAIL_STYLE] ?? self::STYLE_NAME;

        if (! array_key_exists($style, $this->styleOptions())) {
            return $this->error('物料明细样式不合法');
        }

        $userId = $this->userId();
        if (! $userId) {
            return $this->error('请先登录');
        }

        PersonalConfigModel::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'config_key' => self::MATERIAL_DETAIL_STYLE,
            ],
            [
                'config_value' => $style,
            ]
        );

        return $this->success('保存成功');
    }

    public function form()
    {
        Admin::style(<<<'CSS'
.personal-config-form {
    background: #fff;
}
CSS
        );
        $this->appendHtmlAttribute('class', 'personal-config-form');

        $this->tab('单据相关', function () {
            $this->radio(self::MATERIAL_DETAIL_STYLE, '物料明细样式')
                ->options($this->styleOptions())
                ->default($this->currentStyle())
                ->required();
        });

        $this->disableResetButton();
    }

    private function currentStyle(): string
    {
        $userId = $this->userId();
        if (! $userId) {
            return self::STYLE_NAME;
        }

        $style = PersonalConfigModel::query()
            ->where('user_id', $userId)
            ->where('config_key', self::MATERIAL_DETAIL_STYLE)
            ->value('config_value');

        return array_key_exists($style, $this->styleOptions())
            ? $style
            : self::STYLE_NAME;
    }

    private function userId(): int
    {
        return (int) optional(Admin::user())->id;
    }

    private function styleOptions(): array
    {
        return [
            self::STYLE_NAME => '物料名称',
            self::STYLE_DETAIL => '物料明细',
        ];
    }
}
