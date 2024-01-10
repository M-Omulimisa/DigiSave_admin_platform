<?php

namespace App\Admin\Controllers;

use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class MembersController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Sacco Members';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        $u = Admin::user();
        if (!$u->isRole('admin')) {
            /* $grid->model()->where('sacco_id', $u->sacco_id);
  */           if (!$u->isRole('sacco')) {
                $grid->disableCreateButton();
                //dsable delete
                $grid->actions(function (Grid\Displayers\Actions $actions) {
                    $actions->disableDelete();
                });
                $grid->disableFilter();
            }
            $grid->model()->where('sacco_id', $u->sacco_id);
        } else {
        }
        $grid->disableBatchActions();
        $grid->quickSearch('first_name', 'last_name', 'email', 'phone_number')->placeholder('Search by name, email or phone number');

        $grid->column('first_name', __('First name'))->sortable();
        $grid->column('last_name', __('Last name'))->sortable();
        $grid->column('email', __('Email'))->sortable();
        $grid->column('sex', __('Gender'))->sortable();
        $grid->column('phone_number', __('Phone Number'));
        $grid->column('sacco_join_status', __('Status'))
            ->label([
                'Pending' => 'warning',
                'Approved' => 'success',
            ])
            ->sortable();
        $grid->column('address', __('Address'));
        $grid->column('created_at', __('Date Joined'))
            ->display(function ($date) {
                return date('d M Y', strtotime($date));
            })->sortable();


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
        $show->field('first_name', __('First name'));
        $show->field('last_name', __('Last name'));
        $show->field('reg_date', __('Reg date'));
        $show->field('last_seen', __('Last seen'));
        $show->field('email', __('Email'));
        $show->field('approved', __('Approved'));
        $show->field('profile_photo', __('Profile photo'));
        $show->field('user_type', __('User type'));
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
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('remember_token', __('Remember token'));
        $show->field('avatar', __('Avatar'));
        $show->field('name', __('Name'));
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
        $u = Admin::user();

        if ((!$u->isRole('admin')) && (!$u->isRole('sacco'))) {
            if ($form->isCreating()) {
                admin_error("You are not allowed to create new Sacco");
                return back();
            }
        }

        if (!$u->isRole('admin')) {
            $form->hidden('sacco_id', __('Sacco'))->default($u->sacco_id)->readonly();
        } else {
            $form->select('sacco_id', __('Sacco'))->options(\App\Models\Sacco::pluck('name', 'id'))->rules('required');
        }

        $form->text('first_name', __('First name'))
            ->rules('required');
        $form->text('last_name', __('Last name'))
            ->rules('required');
        $form->text('phone_number', __('Phone number'))
            ->rules('required');



        $form->radio('sex', __('Gender'))
            ->options([
                'Male' => 'Male',
                'Female' => 'Female',
            ])
            ->rules('required');

        $form->text('address', __('Address'));


        $form->image('avatar', __('Passport Size Photo'));


        $form->image('profile_photo_large', __('National ID Front'));
        $form->image('profile_photo', __('National ID Back'));


        $form->datetime('dob', __('Date of Birth'))->default(date('Y-m-d H:i:s' . strtotime('-18 years')));



        $form->image('id_front', __('Id front'));
        $form->image('id_back', __('Id back'));
        $form->hidden('user_type', __('Id back'))->default('Member');



        $form->divider('MEMBERSHIP STATUS');


        $form->radioCard('status', __('User Status'))
            ->options([
                'Pending' => 'Pending',
                'Approved' => 'Approved',
            ]);


        $form->radioCard('sacco_join_status', __('Membership Status'))
            ->options([
                'Pending' => 'Pending',
                'Approved' => 'Approved',
            ]);

        $form->password('password', trans('admin.password'))->rules('confirmed|required');
        $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
            ->default(function ($form) {
                return $form->model()->password;
            });
        $form->ignore(['password_confirmation']);
        $form->ignore(['change_password']);

        return $form;
    }
}
