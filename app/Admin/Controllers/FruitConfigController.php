<?php

namespace App\Admin\Controllers;

use App\Models\FruitConfig;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FruitConfigController extends AdminController
{
    protected $title = '赔率与概率设置';

    protected function grid()
    {
        $grid = new Grid(new FruitConfig());
        $grid->model()->orderBy('id', 'asc');

        $grid->column('id', 'ID');
        $grid->column('name', '水果名称');

        $grid->column('multiplier', '赔率 (倍)');

        // 显示权重，并计算出百分比方便你看
        $grid->column('weight', '权重值')->editable(); // 支持列表直接编辑

        $grid->column('probability', '理论概率')->display(function () {
            $total = FruitConfig::sum('weight');
            if ($total == 0) return '0%';
            $percent = round(($this->weight / $total) * 100, 2);
            return "{$percent}%";
        });

        $grid->disableCreateButton(); // ID固定，不允许新增
        $grid->disableDeleteButton(); // 不允许删除
        $grid->actions(function ($actions) {
            $actions->disableDelete();
        });

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new FruitConfig());

        $form->display('id', 'ID');
        $form->display('name', '水果名称');
        $form->number('multiplier', '赔率 (倍)')->rules('required|min:1');
        $form->number('weight', '权重')->help('数值越大，出现的概率越高')->rules('required|min:0');

        return $form;
    }
}
