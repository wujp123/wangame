<?php

namespace App\Admin\Controllers;

use App\Models\Deposit;
use App\Models\User;
use App\Models\Transaction;
use App\Models\FundFlow; // ★★★ 新增
use App\Models\PaymentChannel; // ★★★ 新增
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepositController extends AdminController
{
    protected $title = '充值订单管理';

    /**
     * 列表页 (Grid)
     */
    protected function grid()
    {
        $grid = new Grid(new Deposit());

        // 按 ID 倒序排列
        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', 'ID');

        // 关联显示用户名称
        $grid->column('user.name', '用户名');
        $grid->column('user_id', '用户ID');

        $grid->column('order_no', '订单号')->copyable();

        $grid->column('amount', '充值金额')->sortable()->totalRow();

        $grid->column('payment_method', '支付方式');

        // 状态显示优化
        $grid->column('status', '状态')->using([
            0 => '<span class="label label-warning">待支付</span>',
            1 => '<span class="label label-success">支付成功</span>',
            2 => '<span class="label label-danger">支付失败</span>',
        ]);

        $grid->column('created_at', '创建时间')->sortable();

        // 过滤器
        $grid->filter(function($filter){
            $filter->disableIdFilter();
            $filter->like('order_no', '订单号');
            $filter->equal('user_id', '用户ID');
            $filter->equal('status', '状态')->select([
                0 => '待支付',
                1 => '支付成功',
                2 => '支付失败'
            ]);
        });

        $grid->actions(function ($actions) {
            $actions->disableDelete();
        });

        return $grid;
    }

    /**
     * 详情页 (Detail)
     */
    protected function detail($id)
    {
        $show = new Show(Deposit::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('order_no', '订单号');
        $show->field('user.name', '用户');
        $show->field('amount', '金额');

        $show->field('status', '状态')->using([
            0 => '待支付',
            1 => '成功',
            2 => '失败'
        ]);

        $show->field('payment_method', '方式');
        $show->field('tx_hash', '交易哈希/流水号');
        $show->field('created_at', '创建时间');
        $show->field('updated_at', '更新时间');

        return $show;
    }

    /**
     * 表单页 (Form) - 包含核心加币逻辑
     */
    protected function form()
    {
        $form = new Form(new Deposit());

        $form->display('id', 'ID');

        // 用户选择
        $form->select('user_id', '用户')
            ->options(function ($id) {
                $user = User::find($id);
                if ($user) {
                    return [$user->id => $user->name . " (ID: $user->id)"];
                }
            })
            // 注意：如果没有配置这个API路由，建议临时改为 ->options(User::limit(20)->pluck('name', 'id'))
            ->ajax('/admin/api/users')
            ->rules('required');

        if ($form->isCreating()) {
            $form->text('order_no', '订单号')->default('MANUAL_' . date('YmdHis') . Str::random(4))->readonly();
        } else {
            $form->display('order_no', '订单号');
        }

        $form->currency('amount', '充值金额')->symbol('￥/$')->rules('required|min:0.01');

        // 这里建议做成下拉选择，防止手动输入错误的 method 导致找不到汇率
        // 但为了兼容性，保留文本框，或者你可以改为 select PaymentChannel::pluck('name', 'slug')
        $form->text('payment_method', '支付方式')->default('Admin_Manual')->help('请填入通道Slug或Name，系统将自动读取汇率');

        $form->text('tx_hash', '交易Hash');

        $form->radio('status', '状态')
            ->options([
                0 => '待支付',
                1 => '支付成功 (系统将自动加币)',
                2 => '支付失败',
            ])
            ->default(0)
            ->help('注意：将状态从【待支付】改为【支付成功】时，系统会自动给用户增加余额！');

        $form->display('created_at', '创建时间');

        // =================================================================
        // ★★★ 核心钩子：保存后触发 (汇率换算 + 双写流水) ★★★
        // =================================================================
        $form->saved(function (Form $form) {
            $model = $form->model();

            $originalStatus = $model->getOriginal('status');
            $newStatus = $model->status;
            $shouldAddBalance = false;

            // 情况A: 编辑模式，从 0 变 1
            if (!$form->isCreating() && $originalStatus != 1 && $newStatus == 1) {
                $shouldAddBalance = true;
            }
            // 情况B: 新建模式，直接选了 1
            if ($form->isCreating() && $newStatus == 1) {
                $shouldAddBalance = true;
            }

            if ($shouldAddBalance) {
                DB::transaction(function () use ($model) {
                    $user = User::lockForUpdate()->find($model->user_id);
                    if (!$user) return;

                    // 1. ★★★ 获取汇率 ★★★
                    // 尝试根据 payment_method 查找通道配置
                    $channel = PaymentChannel::where('name', $model->payment_method)
                        ->orWhere('slug', $model->payment_method)
                        ->first();

                    // 如果找不到通道，默认为 1:1
                    $rate = $channel ? $channel->exchange_rate : 1.00;

                    // 计算实际到账积分
                    $addBalance = $model->amount * $rate;

                    // 2. 更新用户余额
                    $beforeBalance = $user->balance;
                    $user->increment('balance', $addBalance);
                    $afterBalance = $user->balance;

                    // 3. ★★★ 写入资金流水 (财务账本：记录充值本金) ★★★
                    FundFlow::create([
                        'user_id' => $user->id,
                        'type' => 1, // 充值
                        'amount' => $addBalance, // 记录实际增加的积分
                        'before_balance' => $beforeBalance,
                        'after_balance' => $afterBalance,
                        'order_no' => $model->order_no,
                        'remark' => "后台入款: {$model->amount} (汇率: {$rate})"
                    ]);

                    // 4. ★★★ 写入积分流水 (用户账单：记录余额变动) ★★★
                    Transaction::create([
                        'user_id' => $user->id,
                        'type' => 1, // 充值
                        'amount' => $addBalance,
                        'before_balance' => $beforeBalance,
                        'after_balance' => $afterBalance,
                        'reference_id' => $model->order_no,
                        'remark' => "系统充值"
                    ]);
                });
            }
        });

        return $form;
    }
}
