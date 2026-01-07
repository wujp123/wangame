<?php

namespace App\Admin\Controllers;

use App\Models\FundFlow;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FundFlowController extends AdminController
{
    protected $title = '资金流水 (充提/佣金)';

    /**
     * 列表页 (Grid)
     */
    protected function grid()
    {
        $grid = new Grid(new FundFlow());

        // 按时间倒序
        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', 'ID');

        // 关联用户显示
        $grid->column('user.name', '用户名');
        $grid->column('user_id', '用户ID');

        // 业务类型美化 (根据你的业务定义)
        // 1:充值, 2:提现, 5:人工调整, 6:佣金
        $grid->column('type', '业务类型')->using([
            1 => '充值入款',
            2 => '提现扣款',
            5 => '系统调整',
            6 => '代理佣金',
        ])->label([
            1 => 'success', // 绿
            2 => 'warning', // 黄
            5 => 'info',    // 蓝
            6 => 'primary', // 深蓝
        ]);

        // 变动金额 (带符号和颜色)
        $grid->column('amount', '变动金额')->display(function ($amount) {
            $formatted = number_format(abs($amount), 2);

            if ($amount > 0) {
                // 正数显示绿色
                return "<span style='color:#28a745; font-weight:bold'>+{$formatted}</span>";
            }
            // 负数显示红色
            return "<span style='color:#dc3545; font-weight:bold'>-{$formatted}</span>";
        })->sortable();

        // 余额显示两位小数
        $grid->column('before_balance', '变动前')->display(fn($v) => number_format($v, 2));
        $grid->column('after_balance', '变动后')->display(fn($v) => number_format($v, 2));

        $grid->column('order_no', '关联单号')->copyable();
        $grid->column('remark', '备注')->limit(30);
        $grid->column('created_at', '发生时间')->sortable();

        // --- 禁止操作 (财务数据不可删改) ---
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableDelete();
        });
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        // --- 筛选器 ---
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->equal('user_id', '用户ID');
            $filter->like('order_no', '订单号');
            $filter->equal('type', '类型')->select([
                1 => '充值', 2 => '提现', 5 => '调整', 6 => '佣金'
            ]);
            $filter->between('created_at', '时间')->datetime();
        });

        return $grid;
    }

    /**
     * 详情页 (Detail)
     */
    protected function detail($id)
    {
        $show = new Show(FundFlow::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('user.name', '用户');
        $show->field('type', '类型')->using([1=>'充值', 2=>'提现', 5=>'调整', 6=>'佣金']);
        $show->field('amount', '变动金额');
        $show->field('before_balance', '变动前');
        $show->field('after_balance', '变动后');
        $show->field('order_no', '单号');
        $show->field('remark', '备注');
        $show->field('created_at', '时间');

        return $show;
    }
}
