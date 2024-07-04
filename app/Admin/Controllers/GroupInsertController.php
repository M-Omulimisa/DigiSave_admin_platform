<?php

namespace App\Admin\Controllers;

use App\Models\GroupInsert;
use App\Models\Sacco;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\grid;

class GroupInsertController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Groups';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Sacco());

        // Default sort order
        $sortOrder = request()->get('_sort', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $grid->model()->orderBy('created_at', $sortOrder);

        $grid->quickSearch('name', 'email_address', 'phone_number')->placeholder('Search by name, email or phone number');

        $grid->column('name', __('Name'))->sortable();
        $grid->column('share_price', __('Share Price'))->sortable();
        $grid->column('register_fee', __('Register Fee'))->sortable();
        $grid->column('uses_shares', __('Uses Shares'))->sortable();
        $grid->column('phone_number', __('Phone Number'))->sortable();
        $grid->column('email_address', __('Email Address'))->sortable();
        $grid->column('physical_address', __('Physical Address'))->sortable();
        $grid->column('establishment_date', __('Establishment Date'))->sortable()->display(function ($date) {
            return date('d M Y', strtotime($date));
        });
        $grid->column('registration_number', __('Registration Number'))->sortable();
        $grid->column('chairperson_name', __('Chairperson Name'))->sortable();
        $grid->column('administrator_id', __('Administrator ID'))->sortable();
        $grid->column('created_at', __('Created At'))->sortable()->display(function ($date) {
            return date('d M Y', strtotime($date));
        });

        // Adding search filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->like('name', 'Name');
            $filter->like('email_address', 'Email Address');
            $filter->like('phone_number', 'Phone Number');
            $filter->like('registration_number', 'Registration Number');
            $filter->like('chairperson_name', 'Chairperson Name');
        });

        // Adding custom dropdown for sorting
        $grid->tools(function ($tools) {
            $tools->append('
                <div class="btn-group pull-right" style="margin-right: 10px; margin-left: 10px;">
                    <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown">
                        Sort by Created Date <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        <li><a href="'.url()->current().'?_sort=asc">Ascending</a></li>
                        <li><a href="'.url()->current().'?_sort=desc">Descending</a></li>
                    </ul>
                </div>
            ');
        });

        return $grid;
    }

    /**
     * Make a grid builder.
     *
     * @param mixed $id
     * @return grid
     */
    protected function detail($id)
    {
        $grid = new grid(GroupInsert::findOrFail($id));

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('share_price', __('Share Price'));
        $grid->column('register_fee', __('Register Fee'));
        $grid->column('uses_shares', __('Uses Shares'));
        $grid->column('phone_number', __('Phone Number'));
        $grid->column('email_address', __('Email Address'));
        $grid->column('physical_address', __('Physical Address'));
        $grid->column('establishment_date', __('Establishment Date'))->as(function ($date) {
            return date('d M Y', strtotime($date));
        });
        $grid->column('registration_number', __('Registration Number'));
        $grid->column('chairperson_name', __('Chairperson Name'));
        $grid->column('administrator_id', __('Administrator ID'));
        $grid->column('created_at', __('Created At'))->as(function ($date) {
            return date('d M Y', strtotime($date));
        });
        $grid->column('updated_at', __('Updated At'))->as(function ($date) {
            return date('d M Y', strtotime($date));
        });

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new GroupInsert());

        $form->text('name', __('Name'))->rules('required');
        $form->decimal('share_price', __('Share Price'))->rules('required|numeric');
        $form->decimal('register_fee', __('Register Fee'))->rules('required|numeric');
        $form->switch('uses_shares', __('Uses Shares'))->default(1);
        $form->mobile('phone_number', __('Phone Number'))->rules('required|regex:/^(\+256|0)?[3-9][0-9]{8}$/');
        $form->email('email_address', __('Email Address'))->rules('required|email');
        $form->textarea('physical_address', __('Physical Address'))->rules('required');
        $form->date('establishment_date', __('Establishment Date'))->rules('required|date');
        $form->text('registration_number', __('Registration Number'))->rules('required');
        $form->text('chairperson_name', __('Chairperson Name'))->rules('required');
        $form->select('administrator_id', __('Administrator ID'))->rules('required')->options(
            // Assuming you have an Administrator model to fetch the administrators list
            User::all()->pluck('name', 'id')
        );

        $form->saving(function (Form $form) {
            // Perform any necessary logic before saving the form
        });

        $form->saved(function (Form $form) {
            // Logic after saving the form
        });

        return $form;
    }
}
?>
