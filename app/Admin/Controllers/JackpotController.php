<?php

namespace App\Admin\Controllers;

use App\Models\Jackpot;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class JackpotController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '奖池管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Jackpot());

        $grid->column('id', 'ID');
        $grid->column('name', '名称');
        $grid->column('balance', '奖池余额')->display(function ($money) {
            return number_format($money, 2); // 格式化显示金额
        });
        $grid->column('tax_rate', '抽水比例(%)');
        $grid->column('created_at', '创建时间');
        $grid->column('updated_at', '更新时间');

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Jackpot::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('name', '名称');
        $show->field('balance', '奖池余额');
        $show->field('tax_rate', '抽水比例(%)');
        $show->field('created_at', '创建时间');
        $show->field('updated_at', '更新时间');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Jackpot());

        $form->text('name', '名称')->default('default')->required();
        $form->decimal('balance', '奖池余额')->default(0.00)->help('初始奖池金额');
        $form->decimal('tax_rate', '抽水比例(%)')->default(5.00)->help('每笔下注进入奖池前扣除的手续费比例');

        return $form;
    }
}
