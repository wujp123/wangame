<?php

namespace App\Admin\Controllers;

use App\Models\PaymentChannel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class PaymentChannelController extends AdminController
{
    protected $title = '支付通道配置';

    /**
     * 列表页 (Grid)
     */
    protected function grid()
    {
        $grid = new Grid(new PaymentChannel());

        $grid->column('id', 'ID');

        $grid->column('name', '通道名称')->editable(); // 支持列表直接改名

        $grid->column('slug', '唯一标识(Slug)')->copyable()->help('前端接口传参用');

        $grid->column('driver', '驱动类型')
            ->using(PaymentService::getOptions(), '未知驱动');

        $grid->column('exchange_rate', '汇率 (1:N)')->editable();

        // 快速开关
        $grid->column('is_active', '状态')->switch([
            'on'  => ['value' => 1, 'text' => '开启', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => '关闭', 'color' => 'danger'],
        ]);

        $grid->column('min_amount', '最小金额');
        $grid->column('max_amount', '最大金额');

        $grid->column('updated_at', '更新时间')->sortable();

        return $grid;
    }

    /**
     * 详情页 (Detail)
     */
    protected function detail($id)
    {
        $show = new Show(PaymentChannel::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('name', '通道名称');
        $show->field('slug', '唯一标识');
        $show->field('driver', '驱动类名');
        $show->field('is_active', '状态')->using([1=>'开启', 0=>'关闭']);

        // 美化 JSON 显示
        $show->field('config', '详细配置')->json();

        $show->field('min_amount', '最小充值');
        $show->field('max_amount', '最大充值');
        $show->field('created_at', '创建时间');

        return $show;
    }

    /**
     * 表单页 (Form)
     */
    protected function form()
    {
        $form = new Form(new PaymentChannel());

        $form->text('name', '通道名称')
            ->rules('required')
            ->help('例如: USDT-TRC20, 支付宝扫码');

        // 唯一性校验，update时排除自身ID
        $form->text('slug', '唯一标识 (Slug)')
            ->creationRules(['required', "unique:payment_channels"])
            ->updateRules(['required', "unique:payment_channels,slug,{{id}}"])
            ->help('前端调用时使用的代码，例如: usdt_trc20');

        $form->select('driver', '处理驱动')
            ->options(PaymentService::getOptions()) // 自动获取所有驱动
            ->default('Manual')
            ->rules('required')
            ->help('请选择对应的支付处理逻辑');


        $form->decimal('exchange_rate', '兑换汇率')
            ->default(1.00)
            ->rules('required|numeric|min:0.01')
            ->help('用户充值 1 单位货币，实际到账多少积分？<br>例如填 <b>10</b>，则用户充值 <b>100 USDT</b>，到账 <b>1000 积分</b>');

        $form->switch('is_active', '是否开启')->default(1);

        $form->currency('min_amount', '最小充值')->default(10)->symbol('💰');
        $form->currency('max_amount', '最大充值')->default(50000)->symbol('💰');

        $form->divider('参数配置 (Config)');

        // ★★★ 核心功能：键值对配置 ★★★
        // 这会将数据存为 JSON 格式
        $form->keyValue('config', '驱动参数')
            ->help('根据驱动类型填写。例如 Manual 填 wallet_address; API 填 app_id, secret_key 等');

        return $form;
    }
}
