<?php

namespace App\Admin\Controllers;

use App\Models\Loan;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\Auth;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class LoanController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Loan';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Loan());

        // $grid->disableCreateButton();
        $u = auth()->user();
        // $grid->disableBatchActions();
        // $grid->model()->where('sacco_id', $u->sacco_id)
        //     ->orderBy('created_at', 'desc');

        $saccoCycleIds = \App\Models\Cycle::where('sacco_id', $u->sacco_id)
        ->where('status', 'Active') 
        ->pluck('id')
        ->toArray();

        $grid->model()->where('sacco_id', $u->sacco_id)
        ->whereIn('cycle_id', $saccoCycleIds) // Filter loans by sacco's active cycle_ids
        ->orderBy('created_at', 'desc');

        $grid->column('id', __('ID'))->sortable();
        $grid->column('created_at', __('Created'))->sortable();
        $grid->column('user_id', __('Beneficiary'))->display(function ($user_id) {
            $user = \App\Models\User::find($user_id);
            return $user->name;
        });
        
        $grid->column('id', __('Loan Id'))->sortable();;

        $grid->column('loan_scheem_id', __('Scheme'))
            ->display(function ($loan_scheem_id) {
                $loan_scheem = \App\Models\LoanScheem::find($loan_scheem_id);
                return $loan_scheem->name;
            });
        $grid->column('amount', __('Amount'))->sortable();
        $grid->column('balance', __('Balance'))->sortable();
        $grid->column('is_fully_paid', __('Is fully paid'));
            // ->display(function ($is_fully_paid) {
            //     return $is_fully_paid == 'yes' ? 'Yes' : 'No';
            // })->sortable();

        $grid->column('cycle_id', __('Cycle ID'))->display(function () use ($saccoCycleIds) {
            return implode(', ', $saccoCycleIds);
        });

        $grid->column('scheme_description', __('Scheme description'))->hide();
        $grid->column('scheme_initial_interest_type', __('Scheme initial interest type'))->hide();
        $grid->column('scheme_initial_interest_flat_amount', __('Scheme initial interest flat amount'))->hide();
        $grid->column('scheme_initial_interest_percentage', __('Scheme initial interest percentage'))->hide();
        $grid->column('scheme_bill_periodically', __('Scheme bill periodically'))->hide();
        $grid->column('scheme_billing_period', __('Scheme billing period'))->hide();
        $grid->column('scheme_periodic_interest_type', __('Scheme periodic interest type'))->hide();
        $grid->column('scheme_periodic_interest_percentage', __('Scheme periodic interest percentage'))->hide();
        $grid->column('scheme_periodic_interest_flat_amount', __('Scheme periodic interest flat amount'))->hide();
        $grid->column('scheme_min_amount', __('Scheme min amount'))->hide();
        $grid->column('scheme_max_amount', __('Scheme max amount'))->hide();
        $grid->column('scheme_min_balance', __('Scheme min balance'))->hide();
        $grid->column('scheme_max_balance', __('Scheme max balance'))->hide();

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
        $show = new Show(Loan::findOrFail($id));

       $loan = Loan::findOrFail($id);
       $saccoId = $loan->sacco_id;

       $saccoCycleIds = \App\Models\Cycle::where('sacco_id', $saccoId)
        ->where('status', 'Active') 
        ->pluck('id')
        ->toArray();

       $show->field('id', __('Id'));
       $show->field('cycle_id', __('Cycle ID'))->as(function () use ($saccoCycleIds) {
        return implode(', ', $saccoCycleIds);
     });
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('sacco_id', __('Sacco id'));
        $show->field('user_id', __('User id'));
        $show->field('loan_scheem_id', __('Loan scheem id'));
        $show->field('amount', __('Amount'));
        $show->field('balance', __('Balance'));
        $show->field('is_fully_paid', __('Is fully paid'));
        $show->field('scheme_name', __('Scheme name'));
        $show->field('scheme_description', __('Scheme description'));
        $show->field('scheme_initial_interest_type', __('Scheme initial interest type'));
        $show->field('scheme_initial_interest_flat_amount', __('Scheme initial interest flat amount'));
        $show->field('scheme_initial_interest_percentage', __('Scheme initial interest percentage'));
        $show->field('scheme_bill_periodically', __('Scheme bill periodically'));
        $show->field('scheme_billing_period', __('Scheme billing period'));
        $show->field('scheme_periodic_interest_type', __('Scheme periodic interest type'));
        $show->field('scheme_periodic_interest_percentage', __('Scheme periodic interest percentage'));
        $show->field('scheme_periodic_interest_flat_amount', __('Scheme periodic interest flat amount'));
        $show->field('scheme_min_amount', __('Scheme min amount'));
        $show->field('scheme_max_amount', __('Scheme max amount'));
        $show->field('scheme_min_balance', __('Scheme min balance'));
        $show->field('scheme_max_balance', __('Scheme max balance'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Loan());

        $u = Admin::user();
        if (!$u->isRole('sacco')) {
            admin_error("You are not allowed to create a new Loan Fund Record");
            return back();
        }

       $saccoCycleIds = \App\Models\Cycle::where('sacco_id', $u->sacco_id)
        ->where('status', 'Active') 
        ->pluck('id')
        ->toArray();

       $form->display('cycle_id', __('Cycle ID'))->with(function () use ($saccoCycleIds) {
        return implode(', ', $saccoCycleIds);
       });

        $form->number('sacco_id', __('Sacco id'));
        $form->number('user_id', __('User id'));
        $form->number('loan_scheem_id', __('Loan scheem id'));
        $form->number('amount', __('Amount'));
        $form->number('balance', __('Balance'));
        $form->text('is_fully_paid', __('Is fully paid'))->default('no');
        $form->textarea('scheme_name', __('Scheme name'));
        $form->textarea('scheme_description', __('Scheme description'));
        $form->text('scheme_initial_interest_type', __('Scheme initial interest type'))->default('Flat');
        $form->number('scheme_initial_interest_flat_amount', __('Scheme initial interest flat amount'));
        $form->number('scheme_initial_interest_percentage', __('Scheme initial interest percentage'));
        $form->text('scheme_bill_periodically', __('Scheme bill periodically'))->default('No');
        $form->number('scheme_billing_period', __('Scheme billing period'));
        $form->text('scheme_periodic_interest_type', __('Scheme periodic interest type'));
        $form->number('scheme_periodic_interest_percentage', __('Scheme periodic interest percentage'));
        $form->number('scheme_periodic_interest_flat_amount', __('Scheme periodic interest flat amount'));
        $form->number('scheme_min_amount', __('Scheme min amount'));
        $form->number('scheme_max_amount', __('Scheme max amount'));
        $form->number('scheme_min_balance', __('Scheme min balance'));
        $form->number('scheme_max_balance', __('Scheme max balance'));

        return $form;
    }
}
