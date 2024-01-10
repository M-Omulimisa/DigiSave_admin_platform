<?php

namespace App\Admin\Controllers;

use App\Models\LoanTransaction;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class LoanTransactionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'LoanTransaction';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LoanTransaction());

        $grid->disableCreateButton();
        $grid->column('id', __('Id'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
        $grid->column('loan_id', __('Loan id'));
        $grid->column('user_id', __('User id'));
        $grid->column('sacco_id', __('Sacco id'));
        $grid->column('cycle_id', __('Cycle id'));
        $grid->column('amount', __('Amount'));
        $grid->column('balance', __('Balance'));
        $grid->column('description', __('Description'));

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
        $show = new Show(LoanTransaction::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('loan_id', __('Loan id'));
        $show->field('user_id', __('User id'));
        $show->field('sacco_id', __('Sacco id'));
        $show->field('cycle_id', __('Cycle id'));
        $show->field('amount', __('Amount'));
        $show->field('balance', __('Balance'));
        $show->field('description', __('Description'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new LoanTransaction());

        $form->number('loan_id', __('Loan id'));
        $form->number('user_id', __('User id'));
        $form->number('sacco_id', __('Sacco id'));
        $form->number('cycle_id', __('Cycle id'));
        $form->number('amount', __('Amount'));
        $form->number('balance', __('Balance'));
        $form->textarea('description', __('Description'));

        return $form;
    }
}
