<?php

namespace App\Admin\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TransactionController extends AdminController
{
    protected $title = '资金流水明细';

    /**
     * 列表页 (Grid)
     */
    protected function grid()
    {
        $grid = new Grid(new Transaction());

        // 按时间倒序，最新的在最上面
        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', 'ID');

        // 显示用户名
        $grid->column('user.name', '用户名');
        $grid->column('user_id', '用户ID');

        // 交易类型映射
        $grid->column('type', '类型')->using([
            1 => '充值',
            2 => '提现',
            3 => '下注',
            4 => '派彩',
            5 => '系统调整/退款',
        ])->label([
            1 => 'success', // 绿色
            2 => 'warning', // 黄色
            3 => 'info',    // 蓝色
            4 => 'danger',  // 红色 (派彩是庄家亏钱，也可以标红，或者标绿看你喜好)
            5 => 'default',
        ]);

        // 变动金额 (颜色区分：正数为绿，负数为红)
        $grid->column('amount', '变动金额')->display(function ($amount) {
            if ($amount > 0) {
                return "<span style='color:green; font-weight:bold'>+{$amount}</span>";
            }
            return "<span style='color:red; font-weight:bold'>{$amount}</span>";
        })->sortable();

        // 余额快照
        $grid->column('before_balance', '变动前');
        $grid->column('after_balance', '变动后');

        $grid->column('reference_id', '关联单号')->copyable();
        $grid->column('remark', '备注')->limit(30); // 备注太长则截断

        $grid->column('created_at', '发生时间')->sortable();

        // --- 核心：禁用所有修改操作 ---
        $grid->disableCreateButton(); // 禁用新增
        $grid->actions(function ($actions) {
            $actions->disableEdit();   // 禁用编辑
            $actions->disableDelete(); // 禁用删除
        });

        // 批量操作也禁用删除
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        // --- 过滤器 (搜索功能) ---
        $grid->filter(function($filter){
            // 禁用默认的ID搜索
            $filter->disableIdFilter();

            // 搜索用户ID
            $filter->equal('user_id', '用户ID');

            // 筛选类型
            $filter->equal('type', '类型')->select([
                1 => '充值',
                2 => '提现',
                3 => '下注',
                4 => '派彩',
                5 => '系统调整'
            ]);

            // 按时间段搜索
            $filter->between('created_at', '发生时间')->datetime();

            // 搜索备注
            $filter->like('remark', '备注');
        });

        return $grid;
    }

    /**
     * 详情页 (Detail)
     */
    protected function detail($id)
    {
        $show = new Show(Transaction::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('user.name', '用户');
        $show->field('type', '类型')->using([1=>'充值', 2=>'提现', 3=>'下注', 4=>'派彩', 5=>'调整']);
        $show->field('amount', '变动金额');
        $show->field('before_balance', '变动前余额');
        $show->field('after_balance', '变动后余额');
        $show->field('reference_id', '关联ID');
        $show->field('remark', '备注');
        $show->field('created_at', '时间');

        return $show;
    }

    // form 方法不需要实现，因为我们禁用了 Create 和 Edit
}
