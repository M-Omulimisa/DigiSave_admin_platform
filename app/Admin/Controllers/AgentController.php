<?php

namespace App\Admin\Controllers;

use App\Models\AdminRole;
use App\Models\Agent;
use App\Models\District;
use App\Models\Parish;
use App\Models\Sacco;
use App\Models\Subcounty;
use App\Models\User;
use App\Models\Village;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Hash;

class AgentController extends AdminController
{
    protected $title = 'Agents';

    protected function grid()
    {
        $grid = new Grid(new User());
    
        $u = Admin::user();
        if (!$u->isRole('admin')) {
            // $grid->model()->where('administrator_id', $u->id);
            $grid->disableCreateButton();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
            });
            $grid->disableFilter();
        }
    
        $grid->id('ID')->sortable();
        $grid->addColumn('full_name', 'Full Name')->display(function () {
            return $this->first_name . ' ' . $this->last_name;
        })->sortable();
        $grid->phone_number('Phone Number')->sortable();
        $grid->dob('Date of Birth')->sortable();
        $grid->sex('Gender')->sortable();
    
        // $grid->district('District')->display(function ($district) {
        //     return $district['name'];
        // })->sortable();
        // $grid->parish('Parish')->display(function ($parish) {
        //     return $parish['parish_name'];
        // })->sortable();
        // $grid->village('Village')->display(function ($village) {
        //     return $village['village_name'];
        // })->sortable();
    
        // Filter users by user type ID based on AdminRole name 'agent'
        $agentRoleId = AdminRole::where('name', 'agent')->value('id');
        $grid->model()->where('user_type', '=', $agentRoleId);
    
        return $grid;
    }
    

    protected function form()
    {
        $form = new Form(new User());

        $u = Admin::user();

        if (!$u->isRole('admin')) {
            if ($form->isCreating()) {
                admin_error("You are not allowed to create new Agent");
                return back();
            }
        }

        $form->text('first_name', 'First Name')->rules('required');
        $form->text('last_name', 'Last Name')->rules('required');
        $form->text('phone_number', 'Phone Number')->rules('required');
        $roles = AdminRole::pluck('name', 'id');
        $form->select('user_type', 'User Type')->options($roles)->rules('required');
        $form->text('email', 'Email');
        $form->date('dob', 'Date of Birth')->rules('required');
        $form->select('sex', 'Gender')->options(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'])->rules('required');
        // $form->text('national_id', 'National ID');

        // Fetch options from models
        $districtOptions = District::pluck('name', 'id');
        // $subcountyOptions = Subcounty::pluck('sub_county', 'id'); 
        $parishOptions = Parish::pluck('parish_name', 'parish_id');
        $villageOptions = Village::pluck('village_name', 'village_id');

        // Foreign key relationships
        $form->select('district_id', 'District')->options($districtOptions);
        // $form->select('subcounty_id', 'Subcounty')->options($subcountyOptions)->rules('required');
        $form->select('parish_id', 'Parish')->options($parishOptions);
        $form->select('village_id', 'Village')->options($villageOptions);
        
        
        // Password fields
        $form->password('password', trans('admin.password'))->rules('confirmed|required');
        $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });
        $form->ignore(['password_confirmation']);
        $form->ignore(['change_password']);

        // Saving the hashed password before saving the model
        $form->saving(function (Form $form) {
            $form->input('password', Hash::make($form->password));
        });

        return $form;
    }
}

