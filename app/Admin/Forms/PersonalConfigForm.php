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

    private const GRID_STYLE = 'grid_style';
    private const GRID_STYLE_DEFAULT = 'default';
    private const GRID_STYLE_FIXED_HEADER = 'fixed_header';

    public function handle(array $input)
    {
        $userId = $this->userId();
        if (! $userId) {
            return $this->error('请先登录');
        }

        // Handle Material Detail Style
        $style = $input[self::MATERIAL_DETAIL_STYLE] ?? self::STYLE_NAME;
        if (array_key_exists($style, $this->styleOptions())) {
            PersonalConfigModel::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'config_key' => self::MATERIAL_DETAIL_STYLE,
                ],
                [
                    'config_value' => $style,
                ]
            );
        }

        // Handle Grid Style
        $gridStyle = $input[self::GRID_STYLE] ?? self::GRID_STYLE_DEFAULT;
        if (array_key_exists($gridStyle, $this->gridStyleOptions())) {
            PersonalConfigModel::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'config_key' => self::GRID_STYLE,
                ],
                [
                    'config_value' => $gridStyle,
                ]
            );
        }

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

        $this->radio(self::MATERIAL_DETAIL_STYLE, '明细样式')
            ->options($this->styleOptions())
            ->default($this->currentStyle())
            ->help('设置单据中物料明细的显示方式')
            ->required();

        $this->radio(self::GRID_STYLE, '列表样式')
            ->options($this->gridStyleOptions())
            ->default($this->currentGridStyle())
            ->help('设置列表页面的显示样式');

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

    private function currentGridStyle(): string
    {
        $userId = $this->userId();
        if (! $userId) {
            return self::GRID_STYLE_DEFAULT;
        }

        $style = PersonalConfigModel::query()
            ->where('user_id', $userId)
            ->where('config_key', self::GRID_STYLE)
            ->value('config_value');

        return array_key_exists($style, $this->gridStyleOptions())
            ? $style
            : self::GRID_STYLE_DEFAULT;
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

    private function gridStyleOptions(): array
    {
        return [
            self::GRID_STYLE_DEFAULT => '默认',
            self::GRID_STYLE_FIXED_HEADER => '固定表头 (超出屏幕滚动)',
        ];
    }
}
