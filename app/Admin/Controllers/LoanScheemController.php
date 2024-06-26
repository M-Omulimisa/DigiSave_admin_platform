<?php

namespace App\Admin\Controllers;

use App\Models\LoanScheem;
use App\Models\Sacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class LoanScheemController extends AdminController
{
    protected $title = 'Loan Schemes';

    protected function grid()
    {
        $grid = new Grid(new LoanScheem());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('sacco_id', __('Sacco'))->display(function ($saccoId) {
            return Sacco::find($saccoId)->name ?? 'N/A';
        })->sortable();
        $grid->column('name', __('Name'))->sortable();
        $grid->column('description', __('Description'));
        $grid->column('initial_interest_type', __('Initial Interest Type'));
        $grid->column('initial_interest_flat_amount', __('Initial Interest Flat Amount'));
        $grid->column('initial_interest_percentage', __('Initial Interest Percentage'));
        $grid->column('bill_periodically', __('Bill Periodically'));
        $grid->column('billing_period', __('Billing Period'));
        $grid->column('periodic_interest_type', __('Periodic Interest Type'));
        $grid->column('periodic_interest_percentage', __('Periodic Interest Percentage'));
        $grid->column('periodic_interest_flat_amount', __('Periodic Interest Flat Amount'));
        $grid->column('min_amount', __('Min Amount'));
        $grid->column('max_amount', __('Max Amount'));
        $grid->column('min_balance', __('Min Balance'));
        $grid->column('max_balance', __('Max Balance'));

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('name', 'Name');
            $filter->like('description', 'Description');

            // Search by Sacco name
            $filter->where(function ($query) {
                $query->whereHas('sacco', function ($query) {
                    $query->where('name', 'like', "%{$this->input}%");
                });
            }, 'Sacco Name');
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(LoanScheem::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('sacco_id', __('Sacco'))->as(function ($saccoId) {
            return Sacco::find($saccoId)->name ?? 'N/A';
        });
        $show->field('name', __('Name'));
        $show->field('description', __('Description'));
        $show->field('initial_interest_type', __('Initial Interest Type'));
        $show->field('initial_interest_flat_amount', __('Initial Interest Flat Amount'));
        $show->field('initial_interest_percentage', __('Initial Interest Percentage'));
        $show->field('bill_periodically', __('Bill Periodically'));
        $show->field('billing_period', __('Billing Period'));
        $show->field('periodic_interest_type', __('Periodic Interest Type'));
        $show->field('periodic_interest_percentage', __('Periodic Interest Percentage'));
        $show->field('periodic_interest_flat_amount', __('Periodic Interest Flat Amount'));
        $show->field('min_amount', __('Min Amount'));
        $show->field('max_amount', __('Max Amount'));
        $show->field('min_balance', __('Min Balance'));
        $show->field('max_balance', __('Max Balance'));

        return $show;
    }

    protected function form()
    {
        $form = new Form(new LoanScheem());

        $form->select('sacco_id', __('Sacco'))->options(Sacco::all()->pluck('name', 'id'))->rules('required');
        $form->text('name', __('Name'))->rules('required');
        $form->textarea('description', __('Description'))->rules('required');
        $form->select('initial_interest_type', __('Initial Interest Type'))->options(['Flat' => 'Flat', 'Percentage' => 'Percentage'])->default('Flat');
        $form->number('initial_interest_flat_amount', __('Initial Interest Flat Amount'));
        $form->number('initial_interest_percentage', __('Initial Interest Percentage'));
        $form->select('bill_periodically', __('Bill Periodically'))->options(['Yes' => 'Yes', 'No' => 'No'])->default('No');
        $form->number('billing_period', __('Billing Period'));
        $form->select('periodic_interest_type', __('Periodic Interest Type'))->options(['Flat' => 'Flat', 'Percentage' => 'Percentage']);
        $form->number('periodic_interest_percentage', __('Periodic Interest Percentage'));
        $form->number('periodic_interest_flat_amount', __('Periodic Interest Flat Amount'));
        $form->number('min_amount', __('Min Amount'))->rules('required');
        $form->number('max_amount', __('Max Amount'))->rules('required');
        $form->number('min_balance', __('Min Balance'))->rules('required');
        $form->number('max_balance', __('Max Balance'))->rules('required');

        return $form;
    }
}
?>
