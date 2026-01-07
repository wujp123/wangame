<?php

namespace App\Admin\Controllers;

use App\Models\Withdrawal;
use App\Models\User;
use App\Models\Transaction;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends AdminController
{
    protected $title = '提现审核';

    protected function grid()
    {
        $grid = new Grid(new Withdrawal());

        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', 'ID');
        $grid->column('user.name', '用户');
        $grid->column('amount', '提现金额');
        $grid->column('actual_amount', '实到金额');
        $grid->column('to_address', '提现地址');

        $grid->column('status', '状态')->using([
            0 => '<span class="label label-warning">审核中</span>',
            1 => '<span class="label label-success">已打款</span>',
            2 => '<span class="label label-danger">已驳回</span>',
        ]);

        $grid->column('created_at', '申请时间');

        // 禁用创建按钮（提现只能由用户发起）
        $grid->disableCreateButton();
        $grid->disableDeleteButton();

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new Withdrawal());

        $form->display('id', 'ID');
        $form->display('user_id', '用户ID');
        $form->display('amount', '申请金额');
        $form->display('to_address', '提现地址');

        // 只有状态是 0 (审核中) 的时候允许修改状态
        $form->radio('status', '审核状态')
            ->options([
                0 => '待审核',
                1 => '通过 (已打款)',
                2 => '驳回 (退款)',
            ])
            ->default(0);

        $form->text('remark', '备注/驳回理由');

        // ★★★ 核心：保存时的钩子函数 ★★★
        $form->saved(function (Form $form) {
            $model = $form->model();

            // 如果原来的状态是0，现在的状态变了，才执行逻辑
            if ($form->model()->wasRecentlyCreated) return; // 忽略新建

            // 获取修改前的状态 (Laravel 11 获取原始属性方法)
            $originalStatus = $model->getOriginal('status');
            $newStatus = $model->status;

            // 只处理从 0 变到 1 或 2 的情况，防止重复操作
            if ($originalStatus == 0 && $newStatus != 0) {

                DB::transaction(function () use ($model, $newStatus) {
                    $user = User::lockForUpdate()->find($model->user_id);

                    if ($newStatus == 2) {
                        // === 驳回：把钱退给用户 ===
                        $refundAmount = $model->amount; // 全额退款

                        $before = $user->balance;
                        $user->increment('balance', $refundAmount);
                        $after = $user->balance;

                        Transaction::create([
                            'user_id' => $user->id,
                            'type' => 5, // 5: 系统退款/调整
                            'amount' => $refundAmount,
                            'before_balance' => $before,
                            'after_balance' => $after,
                            'reference_id' => $model->id,
                            'remark' => "提现被驳回，资金退回"
                        ]);
                    }
                    elseif ($newStatus == 1) {
                        // === 通过：钱已经在申请时扣过了，这里只记录状态 ===
                        // 真实场景这里可能需要调用自动打款接口 (API)
                    }
                });
            }
        });

        return $form;
    }
}
