<?php

namespace App\Admin\Controllers;

use App\Models\Training;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TrainingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Training';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Training());

        $grid->column('id', __('Id'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
        $grid->column('name', __('Name'));
        $grid->column('description', __('Description'));
        $grid->column('date', __('Date'));
        $grid->column('logo', __('Logo'));
        $grid->column('administrator_id', __('Administrator id'));

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
        $show = new Show(Training::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('name', __('Name'));
        $show->field('description', __('Description'));
        $show->field('date', __('Date'));
        $show->field('logo', __('Logo'));
        $show->field('administrator_id', __('Administrator id'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Training());

        $form->text('name', __('Name'))
            ->rules('required');

        $form->text('description', __('Description'))->default('text');
        $form->date('date', __('Date'));
        $form->file('logo', __('Logo'));



        $form->select('administrator_id', __('BY ADMINISTRATOR '))
            ->options(User::all()->pluck('name', 'id'));

        return $form;
    }
}
