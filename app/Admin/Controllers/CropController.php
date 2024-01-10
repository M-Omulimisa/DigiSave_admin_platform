<?php

namespace App\Admin\Controllers;

use App\Models\Crop;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CropController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Supported Crops';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Crop());
        $grid->disableBatchActions();

        $grid->column('photo', __('Photo'))
            ->display(function ($avatar) {
                $img = url("storage/" . $avatar);
                return '<img class="img-fluid " style="border-radius: 10px;"  src="' . $img . '" >';
            })
            ->width(80)
            ->sortable();
        $grid->column('name', __('Name'))->sortable();
        $grid->column('details', __('Details'))->hide();

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
        $show = new Show(Crop::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('name', __('Name'));
        $show->field('photo', __('Photo'));
        $show->field('details', __('Details'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Crop());

        $form->text('name', __('Name'))->required();
        $form->image('photo', __('Photo'))->required();
        $form->textarea('details', __('Details'))->required();
        $form->divider('Production Guides');
        $form->morphMany('activities', 'Click on new to add a production guid', function (Form\NestedForm $form) {


            $form->select('crop_id', __('Select crop'))
                ->options(Crop::all()->pluck('name', 'id'))->rules('required');
            $form->text('name', __('Activity Name'))->required();
            $form->decimal('step', __('Step'))->required();
            $form->decimal('value', __('Value (Out of 5)'))->required();
            $form->radio('is_before_planting', 'Actvity type')
                ->options([
                    'Pre-planting' => 'Pre-planting',
                    'Post-planting' => 'Post-planting',
                ])
                ->rules('required');
            $form->decimal('days_before_planting', __('Days before planting'));
            $form->decimal('days_after_planting', __('Days after planting'));

            $form->decimal('acceptable_timeline', __('Acceptable period (In Days)'))->required();

            $form->radio('is_activity_required', __('Is this activity compulsory?'))
                ->options([
                    'Yes' => 'Yes',
                    'No' => 'No',
                ])
                ->rules('required');
            $form->textarea('details', __('Details'));
        });
        $form->disableCreatingCheck();
        $form->disableViewCheck();
        $form->disableReset();

        return $form;
    }
}
