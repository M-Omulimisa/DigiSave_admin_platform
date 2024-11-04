<?php

namespace App\Admin\Controllers;

use App\Models\DistrictAgent;
use App\Models\District;
use App\Models\AdminRoleUser; // Import the AdminRoleUser model
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Hash;

class DistrictAgentController extends AdminController
{
    protected $title = 'District Agents';

    // public function __construct()
    // {
    //     ini_set('max_execution_time', 300); // Increase to 5 minutes
    //     DistrictAgent::registerDefaultAgents(); // Call your registration method
    // }

    /**
     * Create the grid view for DistrictAgent.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DistrictAgent());

        // Filter the grid to show only district agents with role_id = 6
        $grid->model()->whereHas('adminRoleUsers', function ($query) {
            $query->where('role_id', 6);
        });

        // Display basic fields in the grid
        $grid->column('id', __('ID'))->sortable();
        $grid->addColumn('full_name', 'Full Name')->display(function () {
            return $this->first_name . ' ' . $this->last_name;
        })->sortable();
        $grid->column('phone_number', __('Phone Number'))->sortable();
        $grid->column('email', __('Email'))->sortable();
        $grid->column('district.name', __('District'))->sortable();
        $grid->column('created_at', __('Created At'))->sortable();
        $grid->column('updated_at', __('Updated At'))->sortable();

        // Quick search functionality for name, phone, or email
        // $grid->quickSearch('full_name', 'phone_number', 'email');

        return $grid;
    }

    /**
     * Create the detail view for DistrictAgent.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(DistrictAgent::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('full_name', __('Full Name'));
        $show->field('phone_number', __('Phone Number'));
        $show->field('email', __('Email'));
        $show->field('district.name', __('District'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    /**
     * Create the form for creating/editing DistrictAgent.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new DistrictAgent());

        // Add fields for creating or editing a district agent
        $form->text('first_name', 'First Name')->rules('required');
        $form->text('last_name', 'Last Name')->rules('required');
        $form->mobile('phone_number', __('Phone Number'))->options(['mask' => '9999999999'])->rules('required|numeric');
        $form->email('email', __('Email'));
        $form->date('dob', __('Date of Birth'))->rules('required|date');
        $form->select('sex', __('Gender'))->options(['male' => 'Male', 'female' => 'Female'])->rules('required');

        // Password fields
        $form->password('password', trans('admin.password'))->rules('confirmed|required');
        $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });
        $form->ignore(['password_confirmation']);
        $form->ignore(['change_password']);

        // Dropdown to select the district
        $form->select('district_id', __('District'))->options(District::all()->pluck('name', 'id'))->rules('required');

        // Hidden fields for relations like sacco data if needed
        $form->hidden('remember_token');

        // On saving, you can customize logic (for example, SMS notifications)
        $form->saving(function (Form $form) {
            $form->input('password', Hash::make($form->password));
        });

        return $form;
    }
}
