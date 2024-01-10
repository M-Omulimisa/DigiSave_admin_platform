<?php

namespace App\Admin\Controllers;

use App\Models\Cycle;
use App\Models\SocialFund;
use App\Models\Sacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SocialFundController extends AdminController
{
    protected $title = 'Social Funds';

    protected function grid()
    {
        $grid = new Grid(new SocialFund());
        $u = Auth::user();
    
        if (!$u->isRole('admin')) {
        }  
        $grid->model()->where('sacco_id', $u->sacco_id)
        ->whereHas('sacco', function ($query) use ($u) {
            $query->where('administrator_id', $u->id);
        });
        $grid->disableBatchActions();
        $grid->quickSearch('user_name')->placeholder('Search by User');
        $grid->disableExport();
        $grid->disableActions();

        $grid->column('id', __('ID'))->sortable();
        $grid->column('created_at', __('Created At'))->sortable();
        $grid->column('user_id', __('User'))->sortable();
        $grid->column('amount_paid', __('Amount Paid'))->sortable();
        $grid->column('cycle_id', __('Cycle'))->sortable();
        $grid->column('sacco_id', __('Sacco'))->sortable();
        $grid->column('meeting_number', __('Meeting Number'))->sortable();

        $grid->column('remaining_balance', __('Remaining Balance'))->sortable();
        

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(SocialFund::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('created_at', __('Created At'));
        $show->field('user_id', __('User'))->as(function ($userId) {
            $user = \App\Models\User::find($userId);
            return $user ? $user->name : 'Unknown';
        });
        $show->field('cycle_id', __('Cycle')); // Include cycle_id
        $show->field('sacco_id', __('Sacco')); // Include sacco_id
        $show->field('amount_paid', __('Amount Paid'));
        $show->field('meeting_number', __('Meeting Number'));

        return $show;
    }

    protected function form()
{
    $form = new Form(new SocialFund());

    $u = Admin::user();
    if (!$u->isRole('sacco')) {
        admin_error("You are not allowed to create a new Social Fund Record");
        return back();
    }

    $saccoId = $u->isRole('sacco') ? $u->sacco_id : null;
    $sacco_members = \App\Models\User::where('sacco_id', $u->sacco_id)->get()->pluck('name', 'id');

    $form->select('user_id', __('Select Member'))->options($sacco_members)->rules('required');
    $form->text('amount_paid', __('Amount Paid'))->rules('required|numeric');
    $form->text('meeting_number', __('Meeting Number'))->rules('required');

    $activeCycle = Cycle::where('sacco_id', $saccoId)
                        ->where('status', 'Active')
                        ->first();

    if ($activeCycle) {
        // Calculate expected amount from the active cycle
        $expectedAmount = $activeCycle->amount_required_per_meeting;
    
        // Set 'cycle_id' in the form model
        $form->cycle_id = $activeCycle->id;
        $form->display('cycle_id', __('Cycle'))->value($activeCycle->name);
        $form->hidden('cycle_id')->default($activeCycle->id); // Ensure 'cycle_id' is set as default
    
        // Set 'sacco_id' in the form model
        $form->display('sacco_id', __('Sacco'))->value($saccoId ? Sacco::find($saccoId)->name : 'N/A');
        $form->hidden('sacco_id')->default($saccoId);
    
        // Hook into the saving event to set the 'cycle_id' and 'remaining_balance' before saving
        $form->saving(function (Form $form) use ($activeCycle) {
            $user = User::find($form->user_id);
            $sacco = Sacco::find($user->sacco_id);

            $previousRemainingBalance = 0;

// Fetch the previous social fund only if the current meeting is beyond the first meeting
if ($form->meeting_number > 1) {
    $previousSocialFund = SocialFund::where('user_id', $user->id)
        ->where('sacco_id', $sacco->id)
        ->where('cycle_id', $activeCycle->id)
        ->where('meeting_number', $form->meeting_number - 1)
        ->first();

    if ($previousSocialFund) {
        $previousRemainingBalance = $previousSocialFund->remaining_balance;
    } else {
        // Calculate the amount required for the first meeting (if not paid)
        $firstMeetingRequiredAmount = $activeCycle->amount_required_per_meeting;
        $previousRemainingBalance = $firstMeetingRequiredAmount;
    }
}

$requiredAmount = $activeCycle->amount_required_per_meeting;
$newBalance = $previousRemainingBalance + $requiredAmount - $form->amount_paid;


            // Set 'cycle_id' before saving
            $form->cycle_id = $activeCycle->id;

            // Set 'remaining_balance' before saving
            $form->remaining_balance = $newBalance;
        });
        
        // Set 'remaining_balance' in the form model for display
        $form->display('remaining_balance', __('Remaining Balance'));
        
    }
    
    $form->display('created_by_id')->value(Admin::user()->id);

    $form->saving(function (Form $form) use ($saccoId) {
        $form->sacco_id = $saccoId;
        $form->created_by_id = Admin::user()->id;
    });                     

    return $form;
}

}
