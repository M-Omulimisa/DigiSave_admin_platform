<?php

namespace App\Admin\Controllers;

use App\Models\Village;
use App\Models\Parish;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class VillagesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Villages';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Village());

        $grid->column('village_id', __('ID'));
        $grid->column('village_name', __('Village'));
        $grid->column('parish.parish_name', __('Parish'));

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
        $show = new Show(Village::findOrFail($id));

        $show->field('village_id', __('ID'));
        $show->field('village_name', __('Village Name'));
        $show->field('parish.parish_name', __('Parish'));

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
        $form = new Form(new Village());

        $form->text('village_name', __('Village Name'));
        $form->select('parish_id', __('Parish'))->options(Parish::pluck('parish_name', 'parish_id'));

        // Add other fields as needed

        return $form;
    }
}
