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

namespace App\Admin\Controllers;

use App\Admin\Forms\PersonalConfigForm;
use Dcat\Admin\Admin;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Models\Repositories\Administrator;
use Dcat\Admin\Widgets\Tab;

class PersonalConfigController extends AdminController
{
    public function index(Content $content)
    {
        // 添加统一的背景样式
        Admin::style(<<<'CSS'
.personal-settings-container {
    background: #fff;
    padding: 20px;
    border-radius: 4px;
}
CSS
        );

        $tab = Tab::make();

        // 个人信息 tab
        $userForm = $this->userInfoForm();
        $userForm->tools(function (Form\Tools $tools) {
            $tools->disableList();
        });
        $tab->add('个人信息', $userForm->edit(Admin::user()->getKey()), true);

        // 单据相关 tab（原个性化配置）
        $tab->add('个性皮肤', new PersonalConfigForm());

        $tab->appendHtmlAttribute('class', 'personal-settings-container');

        return $content
            ->title('个人设置')
            ->description('管理个人信息和偏好设置')
            ->body($tab);
    }

    /**
     * 用户信息表单
     *
     * @return Form
     */
    protected function userInfoForm()
    {
        return new Form(new Administrator(), function (Form $form) {
            $form->action(admin_url('personal-config/user'));

            $form->disableCreatingCheck();
            $form->disableEditingCheck();
            $form->disableViewCheck();
            $form->disableHeader();

            $form->tools(function (Form\Tools $tools) {
                $tools->disableView();
                $tools->disableDelete();
            });

            $form->display('username', trans('admin.username'));
            $form->text('name', trans('admin.name'))->required();
            $form->image('avatar', trans('admin.avatar'))->autoUpload();

            $form->password('old_password', trans('admin.old_password'));

            $form->password('password', trans('admin.password'))
                ->minLength(5)
                ->maxLength(20)
                ->customFormat(function ($v) use ($form) {
                    if ($v == $form->model()->password) {
                        return;
                    }

                    return $v;
                });
            $form->password('password_confirmation', trans('admin.password_confirmation'))->same('password');

            $form->ignore(['password_confirmation', 'old_password']);

            $form->saving(function (Form $form) {
                if ($form->password && $form->model()->password != $form->password) {
                    $form->password = bcrypt($form->password);
                }

                if (! $form->password) {
                    $form->deleteInput('password');
                }
            });

            $form->saved(function (Form $form) {
                return $form->redirect(
                    admin_url('personal-config'),
                    trans('admin.update_succeeded')
                );
            });
        });
    }

    /**
     * 更新用户信息
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateUser()
    {
        $form = $this->userInfoForm();

        if (! $this->validateCredentialsWhenUpdatingPassword()) {
            $form->responseValidationMessages('old_password', trans('admin.old_password_error'));
        }

        return $form->update(Admin::user()->getKey());
    }

    /**
     * 验证旧密码
     *
     * @return bool
     */
    protected function validateCredentialsWhenUpdatingPassword()
    {
        $user = Admin::user();

        $oldPassword = \request('old_password');
        $newPassword = \request('password');

        if (
            (! $newPassword)
            || ($newPassword === $user->getAuthPassword())
        ) {
            return true;
        }

        if (! $oldPassword) {
            return false;
        }

        return Admin::guard()
            ->getProvider()
            ->validateCredentials($user, ['password' => $oldPassword]);
    }
}
