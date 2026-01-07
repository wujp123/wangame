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
    protected $title = 'Jackpot';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Jackpot());

        $grid->column('id', __('Id'));
        $grid->column('balance', __('Balance'));
        $grid->column('tax_rate', __('Tax rate'));
        $grid->column('name', __('Name'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

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

        $show->field('id', __('Id'));
        $show->field('balance', __('Balance'));
        $show->field('tax_rate', __('Tax rate'));
        $show->field('name', __('Name'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

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

        $form->decimal('balance', __('Balance'))->default(0.00);
        $form->decimal('tax_rate', __('Tax rate'))->default(5.00);
        $form->text('name', __('Name'))->default('default');

        return $form;
    }
}
