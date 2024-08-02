<?php

namespace App\Admin\Controllers;

use App\Models\Transaction;
use App\Models\Cycle;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Form;
use Encore\Admin\Layout\Content;

class CycleTransactionController extends AdminController
{
    protected $title = 'Transactions';

    public function index(Content $content)
    {
        // Get sacco_id and cycle_id from request
        $saccoId = request()->get('sacco_id');
        $cycleId = request()->get('cycle_id');

        // Fetch the cycle and group (Sacco) name
        $cycle = Cycle::find($cycleId);
        $groupName = $cycle ? $cycle->sacco->name : 'Unknown Group';

        $title = "{$groupName} - Cycle {$cycle->name} Transactions";

        return $content
            ->header($title)
            ->body($this->grid($cycleId, $saccoId));
    }

    protected function grid($cycleId, $saccoId)
{
    $grid = new Grid(new Transaction());

    // Default sort order
        $sortOrder = request()->get('_sort', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

    // dd($saccoId);

    // Filter transactions by cycle_id and sacco_id if provided in the request
    $grid->model()
         ->where('sacco_id', $saccoId)
         ->where('cycle_id', $cycleId)
         ->orderBy('created_at', $sortOrder);

    $grid->disableCreateButton();

    // Custom create button if needed
    $grid->tools(function ($tools) use ($saccoId, $cycleId) {
        $url = url('/cycle-transactions/create?sacco_id=' . $saccoId . '&cycle_id=' . $cycleId);
        $tools->append("<a class='btn btn-sm btn-success' href='{$url}'>Create New Transaction</a>");
    });

    // Columns setup
    $grid->column('id', __('ID'))->sortable();
    $grid->column('user.name', __('Account'))->sortable();
    $grid->column('type', __('Type'))->display(function ($type) {
        return $type === 'REGESTRATION' ? 'REGISTRATION' : $type;
    })->sortable();
    $grid->column('amount', __('Amount (UGX)'))->display(function ($amount) {
        return number_format($amount, 2, '.', ',');
    })->sortable();
    $grid->column('description', __('Description'));
    $grid->column('created_at', __('Created At'))->sortable();

    // Filters
    $grid->filter(function ($filter) {
        $filter->disableIdFilter();
        $filter->like('user.name', 'User Name');
        $filter->equal('type', 'Type')->select(['SHARE' => 'SHARE', 'Send' => 'Send', 'Receive' => 'Receive', 'REGESTRATION' => 'Registration']);
        $filter->between('amount', 'Amount');
        $filter->between('created_at', 'Created At')->datetime();
    });

    return $grid;
}


    protected function detail($id)
    {
        $show = new Show(Transaction::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('user.name', __('User Name'));
        $show->field('type', __('Type'))->as(function ($type) {
            return $type === 'REGESTRATION' ? 'Registration' : $type;
        });
        $show->field('amount', __('Amount'))->as(function ($amount) {
            return number_format($amount, 2, '.', ',') . ' UGX';
        });
        $show->field('description', __('Description'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    protected function form()
    {
        $form = new Form(new Transaction());

        // Conditional default values only if it's a new model (i.e., creating)
        if ($form->isCreating()) {
            $saccoId = request()->get('sacco_id');
            $cycleId = request()->get('cycle_id');
            $adminUserId = User::where('sacco_id', $saccoId)->where('user_type', '=', 'admin')->first()->id ?? null;

            $form->hidden('user_id')->default($adminUserId);
            $form->hidden('sacco_id')->default($saccoId);
            $form->hidden('cycle_id')->default($cycleId);
        }

        // Common form fields setup
        $form->select('source_user_id', __('User'))
             ->options(function () use ($form) {
                $saccoId = $form->model()->sacco_id ?? request()->get('sacco_id');
                return User::where('sacco_id', $saccoId)
                           ->where(function ($query) {
                               $query->whereNull('user_type')->orWhere('user_type', '!=', 'Admin');
                           })
                           ->pluck('first_name', 'id');
             })
             ->rules('required');

        $form->select('type', __('Type'))
             ->options(Transaction::select('type')->distinct()->pluck('type', 'type')->toArray())
             ->rules('required');

        $form->datetime('created_at', __('Created At'))
             ->default(date('Y-m-d H:i:s'))
             ->rules('required|date');

        $form->decimal('amount', __('Amount'))
             ->rules('required|numeric|min:0');

        $form->textarea('description', __('Description'));

        return $form;
    }



    public function create(Content $content)
    {
        $saccoId = request()->get('sacco_id');
        $cycleId = request()->get('cycle_id');

        $cycle = Cycle::find($cycleId);
        $groupName = $cycle ? $cycle->sacco->name : 'Unknown Group';
        $title = "{$groupName} - Cycle {$cycle->name} Transactions - Create New";

        return $content
            ->header($title)
            ->body($this->form());
    }
}
