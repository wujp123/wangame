<?php

namespace App\Admin\Controllers;

use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class UserController extends AdminController
{
    /**
     * 页面标题
     */
    protected $title = '玩家管理';

    /**
     * 列表页 (Grid)
     */
    protected function grid()
    {
        $grid = new Grid(new User());

        // 按注册时间倒序
        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', 'ID');

        // 显示头像图片 (宽高 50px)
        $grid->column('avatar', '头像')->image('', 50, 50);

        $grid->column('username', '用户名');
        $grid->column('tg_id', 'TG ID')->copyable(); // 点击可复制

        // 余额字段，支持排序
        $grid->column('balance', '余额')->sortable()->display(function ($money) {
            return '<span style="color:red; font-weight:bold;">' . $money . '</span>';
        });

        $grid->column('frozen_balance', '冻结金额');

        // 状态：使用开关显示 (需要数据库字段是 tinyint/int)
        $grid->column('status', '状态')->switch([
            'on'  => ['value' => 1, 'text' => '正常', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '封禁', 'color' => 'danger'],
        ]);

        // 显示上级代理的名字 (需要在 User 模型定义 parent 关联)
        $grid->column('parent.username', '上级代理');

        $grid->column('invite_code', '邀请码');
        $grid->column('created_at', '注册时间');

        // 添加搜索过滤器
        $grid->filter(function($filter){
            $filter->disableIdFilter(); // 去掉默认的ID搜索
            $filter->like('username', '用户名');
            $filter->equal('tg_id', 'TG ID');
            $filter->equal('invite_code', '邀请码');
        });

        // 隐藏不必要的列 (密码、Token等)
        // $grid->column('password', '密码');

        return $grid;
    }

    /**
     * 详情页 (Detail)
     */
    protected function detail($id)
    {
        $show = new Show(User::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('tg_id', 'TG ID');
        $show->field('username', '用户名');
        $show->field('avatar', '头像')->image();
        $show->field('balance', '余额');
        $show->field('frozen_balance', '冻结金额');
        $show->field('status', '状态')->using([1 => '正常', 0 => '封禁']);
        $show->field('invite_code', '邀请码');
        $show->field('parent_path', '关系路径');
        $show->field('created_at', '注册时间');
        $show->field('updated_at', '最近更新');

        return $show;
    }

    /**
     * 表单页 (Form) - 用于管理员手动修改用户信息
     */
    protected function form()
    {
        $form = new Form(new User());

        $form->display('id', 'ID');
        $form->text('tg_id', 'TG ID')->readonly(); // 建议 TG ID 不允许修改
        $form->text('username', '用户名');

        $form->image('avatar', '头像');

        // 允许管理员手动加钱/扣钱
        $form->decimal('balance', '余额')->default(0.00);
        $form->decimal('frozen_balance', '冻结金额')->default(0.00);

        $form->switch('status', '状态')->default(1);

        $form->display('invite_code', '邀请码'); // 邀请码通常自动生成，不建议改

        // 这里可以直接填上级ID，或者以后改成下拉选择框
        $form->text('parent_id', '上级代理ID');

        return $form;
    }
}
