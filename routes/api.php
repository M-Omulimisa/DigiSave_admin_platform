<?php

use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\ApiResurceController;
use App\Http\Controllers\PositionController;
use App\Http\Middleware\EnsureTokenIsValid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get("manifest", [ApiResurceController::class, "manifest"]);
Route::get("loan-schemes", [ApiResurceController::class, "loan_schemes"]);
Route::post("loan-schemes", [ApiAuthController::class, "create_scheme"]);
Route::get("loans", [ApiResurceController::class, "loans"]);
Route::get("cycles", [ApiResurceController::class, "cycles"]);
Route::get("organisation", [ApiResurceController::class, "get_orgs"]);
Route::get('positions', [ApiResurceController::class, 'get_positions']);
Route::get("share-records", [ApiResurceController::class, "share_records"]);
Route::post("share-records", [ApiResurceController::class, "share_record_create"]);
Route::get("social-funds", [ApiResurceController::class, "socialFundRecords"]);
Route::post("social-funds", [ApiResurceController::class, "socialFundCreate"]);
Route::post("request-agent-otp-sms", [ApiResurceController::class, "request_otp_sms"]);
Route::post("request-otp-sms", [ApiResurceController::class, "request_otp_sms"]);
Route::get("transactions", [ApiResurceController::class, "transactions"]);
Route::get("loan-transactions", [ApiResurceController::class, "loan_transactions"]);
Route::get("saccos", [ApiResurceController::class, "saccos"]);
Route::get("agent-allocation", [ApiResurceController::class, "agent_allocations"]);
Route::get("eligibleMembers", [ApiResurceController::class, "eligible_members"]);
Route::post("sacco-join-request", [ApiResurceController::class, "sacco_join_request"]);
Route::POST("transactions-create", [ApiResurceController::class, "transactions_create"]);
Route::POST("loans-create", [ApiResurceController::class, "loan_create"]);
Route::POST("transactions-transfer", [ApiResurceController::class, "transactions_transfer"]);

Route::get('district', [ApiResurceController::class, 'get_districts']);
Route::get('parish', [ApiResurceController::class, 'get_parishes']);
Route::get('village', [ApiResurceController::class, 'get_villages']);


Route::post("agent-meeting", [ApiResurceController::class, "scheduleMeeting"]);
Route::get("agent-meeting", [ApiResurceController::class, "get_agent_meetings"]);


Route::get("sacco-members", [ApiResurceController::class, "sacco_members"]);
Route::post("sacco-members-review", [ApiResurceController::class, "sacco_members_review"]);
Route::post("cycles", [ApiResurceController::class, "cycles_create"]);
Route::put('end-cycles/{cycleId}', [ApiResurceController::class, 'deactivateCycle']);
Route::put('update-cycles/{saccoId}/{cycleId}', [ApiResurceController::class, 'cycles_update']);
Route::post("members-review", [ApiResurceController::class, "sacco_members_review"]);
Route::get("my-sacco-membership", [ApiResurceController::class, "my_sacco_membership"]);

Route::get("gardens", [ApiResurceController::class, "gardens"]);
Route::get("garden-activities", [ApiResurceController::class, "garden_activities"]);
Route::get("garden-activities", [ApiResurceController::class, "garden_activities"]);
Route::POST("gardens", [ApiResurceController::class, "garden_create"]);
Route::POST("products", [ApiResurceController::class, "product_create"]);
Route::POST("garden-activities", [ApiResurceController::class, "activity_submit"]);

Route::get("crops", [ApiResurceController::class, "crops"]);
Route::get('sacco/{sacco_id}', [ApiResurceController::class, 'getSaccoData']);
Route::get('cycle-history', [ApiResurceController::class, 'getMemberDetailsByCycle']);
Route::get('agent-saccos/{saccoIds}', [ApiResurceController::class, 'agent_saccos']);





Route::POST("users/login", [ApiAuthController::class, "login"]);
Route::POST("login", [ApiAuthController::class, "login"]);
Route::POST("shareout", [ApiAuthController::class, "new_shareout"]);
Route::POST("users/register", [ApiAuthController::class, "register"]);
Route::post('positions', [ApiAuthController::class, 'new_position']);
Route::post('groups/register', [ApiAuthController::class, 'registerGroup']);
Route::post('roles/register', [ApiAuthController::class, 'registerRole']);
Route::POST("users/update", [ApiAuthController::class, "update_user"]);
Route::POST("group/update", [ApiAuthController::class, "update_group"]);
Route::POST("member/update", [ApiAuthController::class, "member_update"]);
Route::get("people", [ApiResurceController::class, "people"]);
Route::POST("people", [ApiResurceController::class, "person_create"]);
Route::get("jobs", [ApiResurceController::class, "jobs"]);
Route::get('api/{model}', [ApiResurceController::class, 'index']);
Route::get('groups', [ApiResurceController::class, 'groups']);
Route::get('meetings', [ApiResurceController::class, 'meetings']);
Route::POST('meetings', [ApiResurceController::class, 'register_meeting']);
Route::get('associations', [ApiResurceController::class, 'associations']);
Route::get('institutions', [ApiResurceController::class, 'institutions']);
Route::get('service-providers', [ApiResurceController::class, 'service_providers']);
Route::get('counselling-centres', [ApiResurceController::class, 'counselling_centres']);
Route::get('products', [ApiResurceController::class, 'products']);
Route::get('events', [ApiResurceController::class, 'events']);
Route::get('news-posts', [ApiResurceController::class, 'news_posts']);

Route::put('/update/{userId}', [ApiAuthController::class,'updateUser']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('ajax', function (Request $r) {

    $_model = trim($r->get('model'));
    $conditions = [];
    foreach ($_GET as $key => $v) {
        if (substr($key, 0, 6) != 'query_') {
            continue;
        }
        $_key = str_replace('query_', "", $key);
        $conditions[$_key] = $v;
    }

    if (strlen($_model) < 2) {
        return [
            'data' => []
        ];
    }

    $model = "App\Models\\" . $_model;
    $search_by_1 = trim($r->get('search_by_1'));
    $search_by_2 = trim($r->get('search_by_2'));

    $q = trim($r->get('q'));

    $res_1 = $model::where(
        $search_by_1,
        'like',
        "%$q%"
    )
        ->where($conditions)
        ->limit(20)->get();
    $res_2 = [];

    if ((count($res_1) < 20) && (strlen($search_by_2) > 1)) {
        $res_2 = $model::where(
            $search_by_2,
            'like',
            "%$q%"
        )
            ->where($conditions)
            ->limit(20)->get();
    }

    $data = [];
    foreach ($res_1 as $key => $v) {
        $name = "";
        if (isset($v->name)) {
            $name = " - " . $v->name;
        }
        $data[] = [
            'id' => $v->id,
            'text' => "#$v->id" . $name
        ];
    }
    foreach ($res_2 as $key => $v) {
        $name = "";
        if (isset($v->name)) {
            $name = " - " . $v->name;
        }
        $data[] = [
            'id' => $v->id,
            'text' => "#$v->id" . $name
        ];
    }

    return [
        'data' => $data
    ];
});
