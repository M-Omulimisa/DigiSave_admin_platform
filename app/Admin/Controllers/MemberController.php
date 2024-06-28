<?php

namespace App\Admin\Controllers;

use App\Models\MemberPosition;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class MemberController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'User';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        $grid->model()->orderBy('id', 'desc');
        $grid->quickSearch('name')->placeholder('Search by name');
        $grid->disableBatchActions();

        $grid->column('id', __('Id'))->sortable()->hide();
        $grid->column('username', __('Username'))->sortable()->filter('like');
        $grid->column('name', __('Name'))->sortable();
        $grid->column('avatar', __('Avatar'))->image(
            null,
            50,
            50
        )->sortable();
        $grid->column('created_at', __('Created'))
            ->sortable()
            ->display(function ($created_at) {
                return date('d-m-Y', strtotime($created_at));
            });
        $grid->column('position_id', __('Position'))
            ->display(function ($position_id) {
                $pos = MemberPosition::find($position_id);
                if ($pos) {
                    return $pos->name;
                }
                return 'N/A';
            })->sortable();
        $grid->column('pwd', __('Pwd'))->sortable()
            ->display(function ($pwd) {
                return $pwd == 'yes' ? 'Yes' : 'No';
            })->filter([
                'Yes' => 'Yes',
                'No' => 'No'
            ]);
        $grid->column('first_name', __('First Name'))->sortable();
        $grid->column('last_name', __('Last name'))->sortable();
        $grid->column('email', __('Email'))->hide()->sortable();
        $grid->column('profile_photo', __('Profile photo'))->hide();
        $grid->column('sex', __('Gender'))->sortable();
        $grid->column('phone_number', __('Phone number'))->sortable()->filter('like');
        $grid->column('about', __('About'))->hide();
        $grid->column('address', __('Address'))->hide();
        $grid->column('dob', __('Dob'))->sortable();
        $grid->column('processed', __('Processed'))->sortable()
            ->display(function ($processed) {
                return $processed == 'yes' ? 'Yes' : 'No';
            })->filter([
                'Yes' => 'Yes',
                'No' => 'No'
            ]);
        $grid->column('process_status', __('Process status'))->sortable()
            ->display(function ($process_status) {
                return $process_status == 'success' ? 'Success' : 'Pending';
            })->filter([
                'Success' => 'Success',
                'Pending' => 'Pending'
            ])->label([
                'Pending' => 'warning',
                'Success' => 'success'
            ]); 
        $grid->column('process_message', __('Process message'))->sortable()->hide();

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
        $show = new Show(User::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('username', __('Username'));
        $show->field('password', __('Password'));
        $show->field('name', __('Name'));
        $show->field('avatar', __('Avatar'));
        $show->field('remember_token', __('Remember token'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('position_id', __('Position id'));
        $show->field('pwd', __('Pwd'));
        $show->field('first_name', __('First name'));
        $show->field('last_name', __('Last name'));
        $show->field('reg_date', __('Reg date'));
        $show->field('last_seen', __('Last seen'));
        $show->field('email', __('Email'));
        $show->field('approved', __('Approved'));
        $show->field('profile_photo', __('Profile photo'));
        $show->field('user_type', __('User type'));
        $show->field('user_type_name', __('User type name'));
        $show->field('sex', __('Sex'));
        $show->field('reg_number', __('Reg number'));
        $show->field('country', __('Country'));
        $show->field('occupation', __('Occupation'));
        $show->field('profile_photo_large', __('Profile photo large'));
        $show->field('phone_number', __('Phone number'));
        $show->field('location_lat', __('Location lat'));
        $show->field('location_long', __('Location long'));
        $show->field('facebook', __('Facebook'));
        $show->field('twitter', __('Twitter'));
        $show->field('whatsapp', __('Whatsapp'));
        $show->field('linkedin', __('Linkedin'));
        $show->field('website', __('Website'));
        $show->field('other_link', __('Other link'));
        $show->field('cv', __('Cv'));
        $show->field('language', __('Language'));
        $show->field('about', __('About'));
        $show->field('address', __('Address'));
        $show->field('campus_id', __('Campus id'));
        $show->field('complete_profile', __('Complete profile'));
        $show->field('title', __('Title'));
        $show->field('dob', __('Dob'));
        $show->field('intro', __('Intro'));
        $show->field('sacco_id', __('Sacco id'));
        $show->field('sacco_join_status', __('Sacco join status'));
        $show->field('id_front', __('Id front'));
        $show->field('id_back', __('Id back'));
        $show->field('status', __('Status'));
        $show->field('balance', __('Balance'));
        $show->field('district_id', __('District id'));
        $show->field('subcounty_id', __('Subcounty id'));
        $show->field('parish_id', __('Parish id'));
        $show->field('village_id', __('Village id'));
        $show->field('is_synced', __('Is synced'));
        $show->field('processed', __('Processed'));
        $show->field('process_status', __('Process status'));
        $show->field('process_message', __('Process message'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new User());

        $form->text('username', __('Username'));
        $form->password('password', __('Password'));
        $form->text('name', __('Name'));
        $form->image('avatar', __('Avatar'));
        $form->text('remember_token', __('Remember token'));
        $form->number('position_id', __('Position id'));
        $form->password('pwd', __('Pwd'))->default('no');
        $form->text('first_name', __('First name'));
        $form->text('last_name', __('Last name'));
        $form->text('reg_date', __('Reg date'));
        $form->text('last_seen', __('Last seen'));
        $form->email('email', __('Email'));
        $form->switch('approved', __('Approved'));
        $form->text('profile_photo', __('Profile photo'));
        $form->text('user_type', __('User type'));
        $form->text('user_type_name', __('User type name'));
        $form->text('sex', __('Sex'));
        $form->text('reg_number', __('Reg number'));
        $form->text('country', __('Country'));
        $form->text('occupation', __('Occupation'));
        $form->textarea('profile_photo_large', __('Profile photo large'));
        $form->text('phone_number', __('Phone number'));
        $form->text('location_lat', __('Location lat'));
        $form->text('location_long', __('Location long'));
        $form->text('facebook', __('Facebook'));
        $form->text('twitter', __('Twitter'));
        $form->text('whatsapp', __('Whatsapp'));
        $form->text('linkedin', __('Linkedin'));
        $form->text('website', __('Website'));
        $form->text('other_link', __('Other link'));
        $form->text('cv', __('Cv'));
        $form->text('language', __('Language'));
        $form->text('about', __('About'));
        $form->text('address', __('Address'));
        $form->text('campus_id', __('Campus id'));
        $form->switch('complete_profile', __('Complete profile'));
        $form->text('title', __('Title'));
        $form->datetime('dob', __('Dob'))->default(date('Y-m-d H:i:s'));
        $form->textarea('intro', __('Intro'));
        $form->number('sacco_id', __('Sacco id'));
        $form->text('sacco_join_status', __('Sacco join status'))->default('No Sacco');
        $form->textarea('id_front', __('Id front'));
        $form->textarea('id_back', __('Id back'));
        $form->text('status', __('Status'))->default('Active');
        $form->number('balance', __('Balance'));
        $form->number('district_id', __('District id'));
        $form->number('subcounty_id', __('Subcounty id'));
        $form->number('parish_id', __('Parish id'));
        $form->number('village_id', __('Village id'));
        $form->switch('is_synced', __('Is synced'))->default(1);
        $form->text('processed', __('Processed'))->default('No');
        $form->text('process_status', __('Process status'))->default('Pending');
        $form->textarea('process_message', __('Process message'));

        return $form;
    }
}
