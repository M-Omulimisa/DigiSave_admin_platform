<?php

namespace App\Admin\Controllers;

use App\Models\AdminRole;
use App\Models\OrgAllocation;
use App\Models\OrganizationAssignment;
use App\Models\User;
use App\Models\VslaOrganisation; // Import the VslaOrganisation model
use App\Models\VslaOrganisationSacco;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMail;
use Encore\Admin\Show; // Import the Show class
use Exception;
use Illuminate\Support\Facades\Hash;

class AssignOrganisationAdminController extends AdminController
{
    protected $title = 'Assign Org Admins';

    protected function grid()
    {
        $grid = new Grid(new OrgAllocation());

        $admin = Admin::user();
        $adminId = $admin->id;
        if (!$admin->isRole('admin')) {
    
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if ($orgAllocation) {
                $orgId = $orgAllocation->vsla_organisation_id;
                // die(print_r($orgId));
                $organizationAssignments = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)->get();

                // Extracting Sacco IDs from the assignments
                $OrgAdmins = OrgAllocation::where('vsla_organisation_id', $orgId)->pluck('user_id')->toArray();
                // die(print_r($saccoIds));

                $saccoIds = $organizationAssignments->pluck('sacco_id')->toArray();
                $grid->model()->where('vsla_organisation_id', $orgId);            }
        }
    
        $grid->column('id', 'ID')->sortable();
    
        $grid->column('admin', 'Admin')->display(function () {
            return $this->admin ? $this->admin->first_name . ' ' . $this->admin->last_name : 'N/A';
        })->sortable();
    
        $grid->column('admin_email', 'Admin Email')->display(function () {
            return $this->admin ? $this->admin->email : 'N/A';
        });
    
        $grid->column('admin_phone', 'Admin Phone')->display(function () {
            return $this->admin ? $this->admin->phone_number : 'N/A';
        });
    
        $grid->column('organization', 'VSLA Organisation')->display(function () {
            return $this->organization ? $this->organization->name : 'N/A';
        })->sortable();
    
        $grid->created_at('Created At')->sortable();
        $grid->updated_at('Updated At')->sortable();
    
        return $grid;
    }

    // Add the detail method
    protected function detail($id)
    {
        $show = new Show(OrgAllocation::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('admin', 'Admin')->as(function () {
            return $this->admin ? $this->admin->first_name . ' ' . $this->admin->last_name : 'N/A';
        });
        $show->field('admin_phone', 'Admin Phone')->as(function () {
            return $this->admin ? $this->admin->phone_number : 'N/A';
        });
        $show->field('organization', 'VSLA Organisation')->as(function () {
            return $this->organization ? $this->organization->name : 'N/A';
        });
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        return $show;
    }

    protected function form()
{
    $form = new Form(new OrgAllocation());

    $admin = Admin::user();

   $adminId = $admin->id;

    $form->display('id', 'ID');

    // Select only users whose type is 'org' (assuming this role corresponds to VSLA organization admins)
    $orgRole = AdminRole::where('name', 'org')->first();
    $orgUsers = User::where('user_type', $orgRole->id)
        ->get(['first_name', 'last_name', 'id'])
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'full_name' => $user->first_name . ' ' . $user->last_name,
            ];
        })
        ->pluck('full_name', 'id');

    $form->select('user_id', 'Admin')->options($orgUsers); // Change 'admin' to 'user_id'
    if (!$admin->isRole('admin')) {
    
        $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
        if ($orgAllocation) {
            $orgId = $orgAllocation->vsla_organisation_id;  
            $form->select('vsla_organisation_id', 'VSLA Organisation')->options(VslaOrganisation::where('id', $orgId)->pluck('name', 'id'));
        }
    }
    else {
        $form->select('vsla_organisation_id', 'VSLA Organisation')->options(VslaOrganisation::all()->pluck('name', 'id'));
    }

    $form->display('created_at', 'Created At');
    $form->display('updated_at', 'Updated At');

    // Add custom validation to ensure uniqueness of admin within organization
    $form->saving(function (Form $form) {
        $existingAllocation = OrgAllocation::where('user_id', $form->user_id)
            ->where('vsla_organisation_id', $form->vsla_organisation_id)
            ->exists();

        if ($existingAllocation) {
            admin_error("This admin is already assigned to the selected organization.");
            return back();
        }
        else
            // Check if a user with the same phone number already exists
            $adminUser = User::find($form->user_id);
            $password = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

            // Update the password field with the generated password
            $adminUser->password = Hash::make($password);
            $adminUser->save();

            $org = VslaOrganisation::where('id', $form->vsla_organisation_id)->first();
            
    
            // Custom message for registration
            $platformLink = "https://digisave.m-omulimisa.com/";
            $message = "Hello {$adminUser->first_name} {$adminUser->last_name}, you have sucessfully been registered as {$org->name} organisation administrator. Your login details are: Phone Number: {$adminUser->phone_number}, Password: {$password}. Click here to access the platform: {$platformLink}";
            $email_info = [
                "first_name"=>$adminUser->first_name,
                "last_name"=>$adminUser->last_name,
                "phone_number"=>$adminUser->phone_number,
                "password"=>$password,
                "platformLink"=>$platformLink
            ];
            // die($message);
            // Sending SMS
            $resp = null;
            try {
                Mail::to($form->email)->send(new SendMail($email_info));
                
                // Log a success message
                admin_toastr("Email sent successfully to {$form->email}");
        
                // $resp = Utils::send_sms($form->phone_number, $message);
            } catch (Exception $e) {
                // die('Failed to send email because');
                // Log the error
                admin_error('Failed to send email because ' . $e->getMessage());
        
                // Throw an exception
                throw new Exception('Failed to send email because ' . $e->getMessage());
                
                // Return an error message
                // return admin_error('Failed to send email because ' . $e->getMessage());
            }
    });

    return $form;
}

    
}
