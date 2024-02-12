<?php

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */

use App\Models\Utils;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\Auth;
use App\Admin\Extensions\Nav\Shortcut;
use App\Admin\Extensions\Nav\Dropdown;

Utils::system_boot();


Admin::navbar(function (\Encore\Admin\Widgets\Navbar $navbar) {

    /*     $u = Auth::user();
    $navbar->left(view('admin.search-bar', [
        'u' => $u
    ]));

    $navbar->left(Shortcut::make([
        'News post' => 'news-posts/create',
        'Products or Services' => 'products/create',
        'Jobs and Opportunities' => 'jobs/create',
        'Event' => 'events/create',
    ], 'fa-plus')->title('ADD NEW'));
    $navbar->left(Shortcut::make([
        'Person with disability' => 'people/create',
        'Association' => 'associations/create',
        'Group' => 'groups/create',
        'Service provider' => 'service-providers/create',
        'Institution' => 'institutions/create',
        'Counselling Centre' => 'counselling-centres/create',
    ], 'fa-wpforms')->title('Register new'));

    $navbar->left(new Dropdown());

    $navbar->right(Shortcut::make([
        'How to update your profile' => '',
        'How to register a new person with disability' => '',
        'How to register as service provider' => '',
        'How to register to post a products & services' => '',
        'How to register to apply for a job' => '',
        'How to register to use mobile App' => '',
        'How to register to contact us' => '',
        'How to register to give a testimonial' => '',
        'How to register to contact counselors' => '',
    ], 'fa-question')->title('HELP')); */
});



Encore\Admin\Form::forget(['map', 'editor']);
// Admin::css(url('/assets/css/bootstrap.css'));
Admin::css('/assets/css/styles.css');
Admin::style('.main-footer strong {display: none;}');

//disable delete on form tools
Encore\Admin\Form::init(function (Encore\Admin\Form $form) {
    $form->tools(function ($tools) {
        $tools->disableDelete();
        $tools->disableView();
    });
    $form->disableReset();
    $form->disableViewCheck();
});

//grid each see for their respective sacco_id
Encore\Admin\Grid::init(function (Encore\Admin\Grid $grid) {
    $u = Admin::user();
    //get current segment
    $current_segment = request()->segment(1);

    $exclude = ['saccos', 
    'organisation',
    'social', 'gens', 'loan-scheems', 'trainings','loans', 'meetings', 'crops', 'crop-protocols', 'gardens', 'garden-activities',  'service-providers', 'groups', 'associations', 'people', 'disabilities', 'institutions', 'counselling-centres', 'jobs', 'job-applications', 'course-categories', 'courses', 'settings', 'participants', 'members', 'post-categories', 'news-posts', 'events', 'event-bookings', 'products', 'product-orders', ];

    if (!$u->isRole('admin')) {
        if (!in_array($current_segment, $exclude)) {
            $grid->model()->where('sacco_id', $u->sacco_id);
        }
    }
    $grid->model();
});
//show each see for their respective sacco_id
Encore\Admin\Show::init(function (Encore\Admin\Show $show) {
    $u = Admin::user();
    if (!$u->isRole('admin')) {
        $show->panel()->tools(function ($tools) {
            $tools->disableDelete();
            $tools->disableEdit();
        });
    }
});
