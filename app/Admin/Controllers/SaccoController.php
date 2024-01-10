<?php

namespace App\Admin\Controllers;

use App\Models\Sacco;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SaccoController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'VLSA Groups';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Sacco());

        $u = Admin::user();
        if (!$u->isRole('admin')) {
            $grid->model()->where('administrator_id', $u->id);
            $grid->disableCreateButton();
            //dsable delete
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
            });
            $grid->disableFilter();
        }
        $grid->disableBatchActions();
        $grid->quickSearch('name')->placeholder('Search by name');
        $grid->disableExport();
        $grid->model()->orderBy('name', 'desc');
        $grid->column('name', __('Name'))->sortable();
        $grid->column('phone_number', __('Phone number'))->sortable();
        $grid->column('share_price', __('Share (UGX)'))
            ->display(function ($price) {
                return number_format($price);
            })->sortable(); 
        $grid->column('physical_address', __('Physical address'))->sortable();
        $grid->column('establishment_date', __('Established'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })->sortable();
        $grid->column('chairperson_name', __('Chairperson name'))->sortable();
        $grid->column('chairperson_phone_number', __('Chairperson phone number'))->hide();
        $grid->column('chairperson_email_address', __('Chairperson email address'))->hide();
        $grid->column('about', __('About'))->hide();
        $grid->column('terms', __('Terms'))->hide();
        $grid->column('mission', __('Mission'));
        $grid->column('vision', __('Vision'))->hide();
        $grid->column('logo', __('Logo'))->hide();

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
        $show = new Show(Sacco::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('administrator_id', __('Administrator id'));
        $show->field('name', __('Name'));
        $show->field('phone_number', __('Phone number'));
        $show->field('email_address', __('Email address'));
        $show->field('physical_address', __('Physical address'));
        $show->field('establishment_date', __('Establishment date'));
        $show->field('registration_number', __('Registration number'));
        $show->field('chairperson_name', __('Chairperson name'));
        $show->field('chairperson_phone_number', __('Chairperson phone number'));
        $show->field('chairperson_email_address', __('Chairperson email address'));
        $show->field('about', __('About'));
        $show->field('terms', __('Terms'));
        $show->field('mission', __('Mission'));
        $show->field('vision', __('Vision'));
        $show->field('logo', __('Logo'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Sacco());

        $u = Admin::user();

        if (!$u->isRole('admin')) {
            if ($form->isCreating()) {
                admin_error("You are not allowed to create new Sacco");
                return back();
            }
        } else {
            $ajax_url = url(
                '/api/ajax?'
                    . "search_by_1=name"
                    . "&search_by_2=id"
                    . "&model=User"
            );
            $form->select('administrator_id', "Sacco Administrator")
                ->options(function ($id) {
                    $a = Administrator::find($id);
                    if ($a) {
                        return [$a->id => "#" . $a->id . " - " . $a->name];
                    }
                })
                ->ajax($ajax_url)->rules('required');
        }



        $form->text('name', __('Name'))->rules('required');
        $form->decimal('share_price', __('Share Price'))
            ->help('UGX')
            ->rules('required|numeric|min:0');
        $form->text('phone_number', __('Phone number'))->rules('required');
        $form->text('email_address', __('Email address'));
        $form->text('physical_address', __('Physical address'));
        $form->datetime('establishment_date', __('Establishment date'))->rules('required');
        $form->text('registration_number', __('Registration number'));
        $form->text('chairperson_name', __('Chairperson name'))->rules('required');
        $form->text('chairperson_phone_number', __('Chairperson phone number'))->rules('required');
        $form->text('chairperson_email_address', __('Chairperson email address'));
        $form->text('mission', __('Mission'))->rules('required');
        $form->text('vision', __('Vision'))->rules('required');
        $form->textarea('about', __('About'))->rules('required');

        $form->quill('terms', __('Sacco Terms'))->rules('required');
        $form->image('logo', __('Sacco Logo'))->rules('required');

        return $form;
    }
}
