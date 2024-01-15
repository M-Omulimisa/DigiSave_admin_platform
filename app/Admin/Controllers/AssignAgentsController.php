<?php

namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\Organization;
use App\Models\AgentAllocation;
use App\Models\Sacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;

class AssignAgentsController extends AdminController
{
    protected $title = 'Organization Assignments';

    protected function grid()
    {
        $grid = new Grid(new AgentAllocation());

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
        $grid->column('agent.full_name', 'Agent');
        $grid->column('sacco.name', 'Sacco');

        $grid->created_at('Created At')->sortable();
        $grid->updated_at('Updated At')->sortable();

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(AgentAllocation::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('agent.full_name', 'Agent');
        $show->field('sacco.name', 'Sacco');

        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        return $show;
    }

    protected function form()
{
    $form = new Form(new AgentAllocation());

    $u = Admin::user();

    if (!$u->isRole('admin')) {
        if ($form->isCreating()) {
            admin_error("You are not allowed to create new Agent");
            return back();
        }
    }

    $form->display('id', 'ID');

    $form->select('agent_id', 'Agent')->options(Agent::all()->pluck('full_name', 'id'));
    $form->select('sacco_id', 'Sacco')->options(Sacco::all()->pluck('name', 'id'));

    $form->display('created_at', 'Created At');
    $form->display('updated_at', 'Updated At');

    return $form;
}

}
