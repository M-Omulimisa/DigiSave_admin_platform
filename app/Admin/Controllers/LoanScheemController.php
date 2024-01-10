<?php

namespace App\Admin\Controllers;

use App\Models\LoanScheem;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class LoanScheemController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Loan Schemes';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LoanScheem());
        $u = auth()->user();
        $grid->disableBatchActions();
        $grid->model()->where('sacco_id', $u->sacco_id)
            ->orderBy('name', 'asc');

        $grid->column('name', __('Scheme Name'))->sortable();
        $grid->column('initial_interest_type', __('Initial interest type'))->hide();
        $grid->column('initial_interest_flat_amount', __('Initial interest flat amount'))->hide();
        $grid->column('initial_interest_percentage', __('Initial interest percentage'))->hide();
        $grid->column('bill_periodically', __('Bill periodically'))->hide();
        $grid->column('billing_period', __('Billing period'))->hide();
        $grid->column('periodic_interest_type', __('Periodic interest type'))->hide();
        $grid->column('periodic_interest_percentage', __('Periodic interest percentage'))->hide();
        $grid->column('periodic_interest_flat_amount', __('Periodic interest flat amount'))->hide();
        $grid->column('min_amount', __('Min amount (UGX)'))->sortable();
        $grid->column('max_amount', __('Max amount'));
        $grid->column('min_balance', __('Min balance'));
        $grid->column('description', __('Description'))->hide();
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
        $show = new Show(LoanScheem::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('sacco_id', __('Sacco id'));
        $show->field('name', __('Name'));
        $show->field('description', __('Description'));
        $show->field('initial_interest_type', __('Initial interest type'));
        $show->field('initial_interest_flat_amount', __('Initial interest flat amount'));
        $show->field('initial_interest_percentage', __('Initial interest percentage'));
        $show->field('bill_periodically', __('Bill periodically'));
        $show->field('billing_period', __('Billing period'));
        $show->field('periodic_interest_type', __('Periodic interest type'));
        $show->field('periodic_interest_percentage', __('Periodic interest percentage'));
        $show->field('periodic_interest_flat_amount', __('Periodic interest flat amount'));
        $show->field('min_amount', __('Min amount'));
        $show->field('max_amount', __('Max amount'));
        $show->field('min_balance', __('Min balance'));
        $show->field('max_balance', __('Max balance'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new LoanScheem());
        $u = auth()->user();
        $form->hidden('sacco_id', __('Sacco id'))->default($u->sacco_id);
        $form->text('name', __('Loan Scheme Name'))->rules('required');

        $form->radio('initial_interest_type', __('Initial Interest Type'))
            ->options(['Flat' => 'Flat', 'Percentage' => 'Percentage'])
            ->stacked()
            ->rules('required')
            ->when('Flat', function (Form $form) {
                $form->decimal('initial_interest_flat_amount', __('Initial interest flat amount'))
                    ->rules('required');
            })
            ->when('Percentage', function (Form $form) {
                $form->decimal('initial_interest_percentage', __('Initial interest percentage'))
                    ->rules('required');
            });

        $form->radio('bill_periodically', __('Bill periodically'))
            ->options(['No' => 'No', 'Yes' => 'Yes'])
            ->stacked()
            ->rules('required')
            ->when('Yes', function (Form $form) {
                $form->decimal('billing_period', __('Billing period (in months)'))
                    ->rules('required');

                $form->radio('periodic_interest_type', __('Periodic interest type'))
                    ->options(['Flat' => 'Flat', 'Percentage' => 'Percentage'])
                    ->stacked()
                    ->rules('required')
                    ->when('Flat', function (Form $form) {
                        $form->decimal('periodic_interest_flat_amount', __('Periodic interest flat amount'))
                            ->rules('required');
                    })
                    ->when('Percentage', function (Form $form) {
                        $form->decimal('periodic_interest_percentage', __('Periodic interest percentage'))
                            ->rules('required');
                    });
            });

        $form->decimal('min_amount', __('Minimum Loan Amount'))->rules('required');
        $form->decimal('max_amount', __('Maximum Amount'))->rules('required');
        $form->decimal('min_balance', __('Minimum deposit/saving'))->rules('required');
        $form->textarea('description', __('Loan Scheme Details'))->rules('required');
        $form->hidden('max_balance', __('Maximum deposit/saving'))->default(0);

        $form->disableCreatingCheck();
        $form->disableReset();
        $form->disableViewCheck();

        return $form;
    }
}
