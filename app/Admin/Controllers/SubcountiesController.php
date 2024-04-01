<?php

namespace App\Admin\Controllers;

use App\Models\Subcounty;
use App\Models\District;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Http\Request;

class SubcountiesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Subcounties';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Subcounty());

        $grid->column('id', __('ID'));
        $grid->column('sub_county', __('Subcounty Name'));
        $grid->column('district.name', __('District'));


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
        $show = new Show(Subcounty::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('sub_county', __('Subcounty Name'));
        $show->field('district.name', __('District'));

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
        $form = new Form(new Subcounty());

        $form->text('sub_county', __('Subcounty Name'));
        $form->select('district_id', __('District'))->options(District::pluck('name', 'id'));

        // Add other fields as needed

        return $form;
    }
}
