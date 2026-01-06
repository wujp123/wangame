<?php

namespace App\Admin\Controllers;

use App\Models\GameLog;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class GameLogController extends AdminController
{
    /**
     * 页面标题
     */
    protected $title = '游戏记录';

    /**
     * 列表页 (Grid)
     */
    protected function grid()
    {
        $grid = new Grid(new GameLog());

        // 按时间倒序，最新的在上面
        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', 'ID');

        // 关联显示：直接显示 User 表里的 username，而不是显示 user_id 数字
        $grid->column('user.username', '玩家账号');

        // 如果你想同时看ID，可以保留下面这行，或者去掉
        // $grid->column('user_id', '玩家ID');

        $grid->column('game_id', '游戏代码');
        $grid->column('bet_amount', '下注金额')->sortable(); // 加上 sortable 可以点击表头排序

        $grid->column('win_amount', '中奖金额')->sortable();

        // 优化显示：大于0显示绿色，小于0显示红色
        $grid->column('profit', '玩家盈亏')->display(function ($profit) {
            if ($profit > 0) {
                return "<span style='color:green'>+{$profit}</span>";
            } elseif ($profit < 0) {
                return "<span style='color:red'>{$profit}</span>";
            }
            return $profit;
        })->sortable();

        $grid->column('result_matrix', '开奖结果'); // 如果是 JSON，可能需要进一步处理显示

        $grid->column('created_at', '下注时间');

        // 禁用创建按钮（游戏记录通常是系统生成的，不需要管理员手动创建）
        $grid->disableCreateButton();

        return $grid;
    }

    /**
     * 详情页 (Detail)
     */
    protected function detail($id)
    {
        $show = new Show(GameLog::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('user_id', '玩家ID');
        $show->field('game_id', '游戏代码');
        $show->field('bet_amount', '下注金额');
        $show->field('win_amount', '中奖金额');
        $show->field('profit', '盈亏');
        $show->field('result_matrix', '开奖结果');
        $show->field('created_at', '下注时间');
        $show->field('updated_at', '更新时间');

        return $show;
    }

    /**
     * 表单页 (Form) - 通常用于查看，不建议手动改
     */
    protected function form()
    {
        $form = new Form(new GameLog());

        $form->display('id', 'ID');
        $form->display('user_id', '玩家ID');
        $form->text('game_id', '游戏代码');
        $form->decimal('bet_amount', '下注金额');
        $form->decimal('win_amount', '中奖金额');
        $form->decimal('profit', '盈亏');
        $form->textarea('result_matrix', '开奖结果');

        return $form;
    }
}
