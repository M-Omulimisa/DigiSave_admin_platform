<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {


    $router->resource('trainings', TrainingController::class);
    $router->get('/', 'HomeController@index')->name('home');
    $router->resource('gens', GenController::class);
    $router->resource('saccos', SaccoController::class);
    $router->resource('districts', DistrictsController::class);
    $router->post('districts/import-csv', 'DistrictsController@importCsv')->name('districts.import.csv');
    $router->resource('agents', AgentController::class);
    $router->resource('organisation', OrganizationController::class);
    $router->resource('org-admin', OrganisationAdminController::class);
    $router->resource('assign-org-admin', AssignOrganisationAdminController::class);
    $router->resource('assign-org', OrganizationAllocationController::class);
    $router->resource('assign-agent', AssignAgentsController::class);
    $router->resource('loan-scheems', LoanScheemController::class);
    $router->resource('loans', LoanController::class);
    $router->resource('meetings', MeetingController::class);
    $router->resource('loan-transactions', LoanTransactionController::class);
    $router->resource('admin-roles', AdminRoleController::class);

    /* ========================START OF NEW THINGS===========================*/

    $router->resource('crops', CropController::class);
    $router->resource('crop-protocols', CropProtocolController::class);
    $router->resource('gardens', GardenController::class);
    $router->resource('garden-activities', GardenActivityController::class);
    $router->resource('cycles', CycleController::class);
    $router->resource('social', SocialFundController::class);
    $router->resource('share-records', ShareRecordController::class);

    /* ========================END OF NEW THINGS=============================*/

    $router->resource('service-providers', ServiceProviderController::class);
    $router->resource('groups', GroupController::class);
    $router->resource('associations', AssociationController::class);
    $router->resource('people', PersonController::class);
    $router->resource('disabilities', DisabilityController::class);
    $router->resource('institutions', InstitutionController::class);
    $router->resource('counselling-centres', CounsellingCentreController::class);
    $router->resource('jobs', JobController::class);
    $router->resource('job-applications', JobApplicationController::class);

    $router->resource('course-categories', CourseCategoryController::class);
    $router->resource('courses', CourseController::class);
    $router->resource('settings', UserController::class);
    $router->resource('participants', ParticipantController::class);
    $router->resource('members', MembersController::class);
    $router->resource('post-categories', PostCategoryController::class);
    $router->resource('news-posts', NewsPostController::class);
    $router->resource('events', EventController::class);
    $router->resource('event-bookings', EventBookingController::class);
    $router->resource('products', ProductController::class);
    $router->resource('product-orders', ProductOrderController::class);
    $router->resource('transactions', TransactionController::class);
    $router->resource('transactions-all', TransactionAllController::class);
});
