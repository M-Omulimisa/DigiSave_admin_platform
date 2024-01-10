<?php

namespace App\Admin\Controllers;

use App\Models\ShareRecord;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ShareRecordController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Shares';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ShareRecord());
        $grid->disableBatchActions();
        $grid->quickSearch('name')->placeholder('Search by name');
        $grid->disableExport();
        $grid->disableActions();

        //CREATE filter
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('user_id', 'Shareholder');
            $filter->like('cycle_id', 'Cycle');
            $filter->like('created_by_id', 'Created By');
            $filter->like('description', 'Description');
        });

        // $share = ShareRecord::find(1);
        // $share->processTansactions();
        // die();

        $grid->column('created_at', __('Created'))
            ->display(function ($date) {
                return date('d M, Y', strtotime($date));
            })->sortable();
        $grid->column('user_id', __('Shareholder'))
            ->display(function ($user_id) {
                $user = \App\Models\User::find($user_id);
                if ($user == null) {
                    return "Unknown";
                }
                return $user->name;
            })->sortable();
        $grid->column('single_share_amount', __('Single Share (UGX)'))
            ->display(function ($price) {
                return number_format($price);
            })->sortable();
        $grid->column('number_of_shares', __('Number of Shares'))
            ->display(function ($number_of_shares) {
                return number_format($number_of_shares);
            })->sortable();
        $grid->column('total_amount', __('Total Amount'))
            ->display(function ($total_amount) {
                return number_format($total_amount);
            })->sortable()
            ->totalRow(function ($amount) {
                return "<span class='text-success'><b>UGX" . number_format($amount) . "</span>";
            });
        $grid->column('cycle_id', __('Cycle'))
            ->display(function ($cycle_id) {
                $cycle = \App\Models\Cycle::find($cycle_id);
                if ($cycle == null) {
                    return "Unknown";
                }
                return $cycle->name;
            })->sortable();
        $grid->column('created_by_id', __('Created By'))
            ->display(function ($created_by_id) {
                $user = \App\Models\User::find($created_by_id);
                if ($user == null) {
                    return "Unknown";
                }
                return $user->name;
            })->sortable();
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
        $show = new Show(ShareRecord::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('user_id', __('User id'));
        $show->field('single_share_amount', __('Single share amount'));
        $show->field('number_of_shares', __('Number of shares'));
        $show->field('total_amount', __('Total amount'));
        $show->field('cycle_id', __('Cycle id'));
        $show->field('sacco_id', __('Sacco id'));
        $show->field('created_by_id', __('Created by id'));
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
        $form = new Form(new ShareRecord());

        //if not sacc admin, then cannot create new share record
        $u = Admin::user();
        if (!$u->isRole('sacco')) {
            admin_error("You are not allowed to create new Share Record");
            return back();
        }

        $form->hidden('sacco_id')->value($u->sacco_id);

        $sacco = \App\Models\Sacco::find($u->sacco_id);

        $sacco_members = \App\Models\User::where('sacco_id', $u->sacco_id)->get()->pluck('name', 'id');
 

        $active_cycle = \App\Models\Cycle::where('sacco_id', $u->sacco_id)->where('status', 'Active')->first();
        if ($active_cycle == null) {
            admin_error("You do not have an active cycle");
            return back();
        }

        $form->display('single_share_amount', __('Single share amount'))
            ->default('UGX ' . number_format($sacco->share_price));
        $form->display('cycle', __('Cycle'))
            ->default($active_cycle->name);
        $form->hidden('created_by_id')->value($u->id);

        $form->divider();
        $form->select('user_id', __('Select Member'))->options($sacco_members)->rules('required');



        $form->decimal('number_of_shares', __('Number of shares bought'))
            ->default(1)
            ->rules('required|numeric|min:1');

        $form->text('description', __('Description'))
            ->rules('required');

        return $form;
    }
}
