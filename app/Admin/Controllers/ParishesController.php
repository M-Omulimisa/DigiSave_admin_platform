<?php

namespace App\Admin\Controllers;

use App\Models\Parish;
use App\Models\Subcounty;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ParishesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Parishes';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Parish());

        $grid->column('parish_id', __('ID'));
        $grid->column('parish_name', __('Parish Name'));
        $grid->column('subcounty.sub_county', __('Subcounty'));

        // Add other columns as needed

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
        $show = new Show(Parish::findOrFail($id));

        $show->field('parish_id', __('ID'));
        $show->field('parish_name', __('Parish Name'));
        $show->field('subcounty.sub_county', __('Subcounty'));

        // Add other fields as needed

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Parish());

        $form->text('parish_name', __('Parish Name'));
        $form->select('subcounty_id', __('Subcounty'))->options(Subcounty::pluck('sub_county', 'id'));

        // Add other fields as needed

        return $form;
    }
}
