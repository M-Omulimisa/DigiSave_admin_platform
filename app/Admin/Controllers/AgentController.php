<?php

namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\District;
use App\Models\Parish;
use App\Models\Sacco;
use App\Models\Subcounty;
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
    $grid = new Grid(new Agent());

    $u = Admin::user();
    if (!$u->isRole('admin')) {
        $grid->model()->where('administrator_id', $u->id);
        $grid->disableCreateButton();
        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
        });
        $grid->disableFilter();
    }

    $grid->id('ID')->sortable();
    $grid->full_name('Full Name')->sortable();
    $grid->phone_number('Phone Number')->sortable();
    $grid->email('Email')->sortable();
    $grid->date_of_birth('Date of Birth')->sortable();
    $grid->gender('Gender')->sortable();
    $grid->national_id('National ID')->sortable();

    $grid->district('District')->display(function ($district) {
        return $district['name'];
    })->sortable();
    $grid->subcounty('Subcounty')->display(function ($subcounty) {
        return $subcounty['sub_county'];
    })->sortable();
    $grid->parish('Parish')->display(function ($parish) {
        return $parish['parish_name'];
    })->sortable();
    $grid->village('Village')->display(function ($village) {
        return $village['village_name'];
    })->sortable();

    $grid->created_at('Created At')->sortable();
    $grid->updated_at('Updated At')->sortable();

    return $grid;
}


    protected function detail($id)
    {
        $show = new Show(Agent::findOrFail($id));

        $show->id('ID');
        $show->full_name('Full Name');
        $show->phone_number('Phone Number');
        $show->email('Email');
        $show->date_of_birth('Date of Birth');
        $show->gender('Gender');
        $show->national_id('National ID');

        $show->district()->name('District');
        $show->subcounty()->name('Subcounty'); 
        $show->parish()->name('Parish');
        $show->village()->name('Village');

        $show->created_at('Created At');
        $show->updated_at('Updated At');

        return $show;
    }

    protected function form()
    {
        $form = new Form(new Agent());

        $u = Admin::user();

        if (!$u->isRole('admin')) {
            if ($form->isCreating()) {
                admin_error("You are not allowed to create new Agent");
                return back();
            }
        }

        $form->text('full_name', 'Full Name')->rules('required');
        $form->text('phone_number', 'Phone Number')->rules('required');
        $form->text('email', 'Email');
        $form->date('date_of_birth', 'Date of Birth')->rules('required');
        $form->select('gender', 'Gender')->options(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'])->rules('required');
        $form->text('national_id', 'National ID');

        // Fetch options from models
        $districtOptions = District::pluck('name', 'id');
        $subcountyOptions = Subcounty::pluck('sub_county', 'id'); 
        $parishOptions = Parish::pluck('parish_name', 'parish_id');
        $villageOptions = Village::pluck('village_name', 'village_id');

        // Foreign key relationships
        $form->select('district_id', 'District')->options($districtOptions)->rules('required');
        $form->select('subcounty_id', 'Subcounty')->options($subcountyOptions)->rules('required');
        $form->select('parish_id', 'Parish')->options($parishOptions)->rules('required');
        $form->select('village_id', 'Village')->options($villageOptions)->rules('required');
        
        
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

