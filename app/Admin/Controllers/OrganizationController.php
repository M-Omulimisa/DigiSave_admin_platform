<?php


namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\AgentAllocation;
use App\Models\Organization;
use App\Models\Sacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;

class OrganizationController extends AdminController
{
    protected $title = 'Organizations';

    protected function grid()
    {
        $grid = new Grid(new Organization());

        $u = Admin::user();
        if (!$u->isRole('admin')) {
            $grid->model()->where('administrator_id', $u->id);
            $grid->disableCreateButton();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
            });
            $grid->disableFilter();
        }

        $grid->column('id', 'ID')->sortable();
        $grid->column('name', 'Name')->sortable();
        $grid->column('phone_number', 'Phone Number')->sortable();
        $grid->column('unique_code', 'Unique Code')->sortable();
        $grid->column('address', 'Address')->sortable();

        $grid->created_at('Created At')->sortable();
        $grid->updated_at('Updated At')->sortable();

        return $grid;
    }
    
    protected function detail($id)
    {
        $show = new Show(Organization::findOrFail($id));
    
        $show->field('id', 'ID');
        $show->field('name', 'Name');
        $show->field('phone_number', 'Phone Number');
        $show->field('address', 'Address');
        $show->field('unique_code', 'Unique Code'); 
    
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');
    
        return $show;
    }
    

    protected function form()
    {
        $form = new Form(new Organization());

        $u = Admin::user();

        if (!$u->isRole('admin')) {
            if ($form->isCreating()) {
                admin_error("You are not allowed to create new Agent");
                return back();
            }
        }

        $form->display('id', 'ID');

        $form->text('name', 'Name')->rules('required');
        $form->text('phone_number', 'Phone Number')->rules('required');
        $form->text('unique_code', 'Unique Code')->readonly();
        $form->text('address', 'Address');

        $form->display('created_at', 'Created At');
        $form->display('updated_at', 'Updated At');

        return $form;
    }
}
