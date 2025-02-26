<?php

use App\Admin\Controllers\DistrictsController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\MainController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Models\District;
use App\Models\Gen;
use App\Models\Loan;
use App\Models\LoanTransaction;
use App\Models\Sacco;
use App\Models\User;
use App\Models\Utils;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


Route::get('export-groups', function () {
    //set unlimited time limit
    set_time_limit(0);
    //set memory limit
    ini_set('memory_limit', '1024M');
    //set maximum execution time
    ini_set('max_execution_time', 0);
    //set output header to be excel file
    // header('Content-Type: application/vnd.ms-excel'); //mime type
    /* 
    "id" => 4
    "created_at" => "2024-03-13 20:12:36"
    "updated_at" => "2024-06-11 08:09:23"
    "administrator_id" => 15
    "name" => "Phil Test"
    "phone_number" => "+256077603519"
    "email_address" => null
    "physical_address" => "cheddar, uk"
    "establishment_date" => null
    "registration_number" => null
    "chairperson_name" => null
    "chairperson_phone_number" => null
    "chairperson_email_address" => null
    "share_price" => 10
    "about" => null
    "terms" => null
    "register_fee" => 40
    "uses_shares" => 0
    "status" => "inactive"
    "processed" => "yes"
    "district" => null
    "subcounty" => null
    "parish" => null
    "village" => null
    "saving_types" => "cash
*/
    $groups = Sacco::all();
    //create an exvel file
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set column headers
    $headers = [
        'Created At',
        'Name',
        'ID',
        'Village',
        'District',
        'Number Of Members',
        'Number Of Loans',
        'Total Principal',
        'Total Interest',
        'Total Principal Paid',
        'Total Interest Paid',
        'Number Of Savings Accounts',
        'Total Savings Balance',
        'Total Social Fund Savings Balance',
        'Total Principal Outstanding',
        'Total Interest Outstanding',
        'Number Of Men',
        'Number Of Loans To Men',
        'Total Disbursed To Men',
        'Total Savings Accounts For Men',
        'Total Savings Balance For Men',
        'Total Social Fund Savings Balance For Men',
        'Number Of Women',
        'Number Of Loans To Women',
        'Total Disbursed To Women',
        'Total Savings Accounts For Women',
        'Total Savings Balance For Women',
        'Total Social Fund Savings Balance For Women',
        'Number Of Refugees',
        'Number Of Loans To Refugees',
        'Total Disbursed To Refugees',
        'Total Savings Accounts For Refugees',
        'Total Savings Balance For Refugees',
        'Total Social Fund Savings Balance For Refugees',
        'Number Of Youth',
        'Number Of Loans To Youth',
        'Total Disbursed To Youth',
        'Total Savings Accounts For Youth',
        'Total Savings Balance For Youth',
        'Total Social Fund Savings Balance For Youth'
    ];
    foreach ($headers as $col => $header) {
        $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
    }

    /* 
      $group->id, $group->created_at, $group->updated_at, $group->administrator_id,
            $group->name, $group->phone_number, $group->email_address, $group->physical_address,
            $group->establishment_date, $group->registration_number, $group->chairperson_name,
            $group->chairperson_phone_number, $group->chairperson_email_address,
            $group->share_price, $group->about, $group->terms, $group->register_fee,
            $group->uses_shares, $group->status, $group->processed, $group->district,
            $group->subcounty, $group->parish, $group->village, $group->saving_types
*/

/* 
    "id" => 1
    "created_at" => "2024-03-22 11:02:04"
    "updated_at" => "2024-03-22 11:02:04"
    "sacco_id" => 37
    "user_id" => 98
    "loan_scheem_id" => 26
    "amount" => 60000
    "balance" => -63000
    "is_fully_paid" => "No"
    "scheme_name" => "Loan Fund"
    "scheme_description" => "Loan fund loan details"
    "scheme_initial_interest_type" => "Percentage"
    "scheme_initial_interest_flat_amount" => null
    "scheme_initial_interest_percentage" => 5
    "scheme_bill_periodically" => "Yes"
    "scheme_billing_period" => null
    "scheme_periodic_interest_type" => "RemainingBalance"
    "scheme_periodic_interest_percentage" => null
    "scheme_periodic_interest_flat_amount" => null
    "scheme_min_amount" => 10000
    "scheme_max_amount" => 400000
    "scheme_min_balance" => 1000
    "scheme_max_balance" => 800000
    "reason" => "Food"
    "cycle_id" => 10
*/
    dd(LoanTransaction::first());
    dd(Loan::first());
    $row = 2;
    $x = 0;
    foreach ($groups as $group) {
        $district_text = District::find($group->district) ? District::find($group->district)->name :   $group->district;
        if (empty($district_text)) $district_text = $group->physical_address;
        $x++;
        if ($x > 10) break;
        $data = [
            'created_at' => date('Y-m-d', strtotime($group->created_at)),
            'name' => $group->name,
            'id' => $group->id,
            'village' => $group->village,
            'district' => $district_text,
            'number_of_members' => User::where('sacco_id', $group->id)->count(),
            'number_of_loans' => Loan::where('sacco_id', $group->id)->count(), 
            'total_principal' => Loan::where('sacco_id', $group->id)->sum('amount'),  
            'asas' => '==============================',
            'total_interest' => $group->total_interest,
            'total_principal_paid' => $group->total_principal_paid,
            'total_interest_paid' => $group->total_interest_paid,
            'number_of_savings_accounts' => $group->number_of_savings_accounts,
            'total_savings_balance' => $group->total_savings_balance,
            'total_social_fund_savings_balance' => $group->total_social_fund_savings_balance,
            'total_principal_outstanding' => $group->total_principal_outstanding,
            'total_interest_outstanding' => $group->total_interest_outstanding,
            'number_of_men' => $group->number_of_men,
            'number_of_loans_to_men' => $group->number_of_loans_to_men,
            'total_disbursed_to_men' => $group->total_disbursed_to_men,
            'total_savings_accounts_for_men' => $group->total_savings_accounts_for_men,
            'total_savings_balance_for_men' => $group->total_savings_balance_for_men,
            'total_social_fund_savings_balance_for_men' => $group->total_social_fund_savings_balance_for_men,
            'number_of_women' => $group->number_of_women,
            'number_of_loans_to_women' => $group->number_of_loans_to_women,
            'total_disbursed_to_women' => $group->total_disbursed_to_women,
            'total_savings_accounts_for_women' => $group->total_savings_accounts_for_women,
            'total_savings_balance_for_women' => $group->total_savings_balance_for_women,
            'total_social_fund_savings_balance_for_women' => $group->total_social_fund_savings_balance_for_women,
            'number_of_refugees' => $group->number_of_refugees,
            'number_of_loans_to_refugees' => $group->number_of_loans_to_refugees,
            'total_disbursed_to_refugees' => $group->total_disbursed_to_refugees,
            'total_savings_accounts_for_refugees' => $group->total_savings_accounts_for_refugees,
            'total_savings_balance_for_refugees' => $group->total_savings_balance_for_refugees,
            'total_social_fund_savings_balance_for_refugees' => $group->total_social_fund_savings_balance_for_refugees,
            'number_of_youth' => $group->number_of_youth,
            'number_of_loans_to_youth' => $group->number_of_loans_to_youth,
            'total_disbursed_to_youth' => $group->total_disbursed_to_youth,
            'total_savings_accounts_for_youth' => $group->total_savings_accounts_for_youth,
            'total_savings_balance_for_youth' => $group->total_savings_balance_for_youth,
            'total_social_fund_savings_balance_for_youth' => $group->total_social_fund_savings_balance_for_youth
        ];
        foreach ($data as $col => $value) {
            $sheet->setCellValueByColumnAndRow(array_search($col, array_keys($data)) + 1, $row, $value);
        }
        $row++;
    }

    dd($data);
    // Format all cells as numbers
    $lastRow = $row - 1;
    $sheet->getStyle("A1:Z$lastRow")->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);

    // Save file
    $writer = new Xlsx($spreadsheet);
    $writer->save('groups.xlsx');
    return response()->download('groups.xlsx');


    /* 
    created_at
name
id
village
district
number_of_members
number_of_loans
total_principal
total_interest
total_principal_paid
total_interest_paid
number_of_savings_accounts
total_savings_balance
total_social_fund_savings_balance
total_principal_outstanding
total_interest_outstanding
number_of_men
number_of_loans_to_men
total_disbursed_to_men
total_savings_accounts_for_men
total_savings_balance_for_men
total_social_fund_savings_balance_for_men
number_of_women
number_of_loans_to_women
total_disbursed_to_women
total_savings_accounts_for_women
total_savings_balance_for_women
total_social_fund_savings_balance_for_women
number_of_refugees
number_of_loans_to_refugees
total_disbursed_to_refugees
total_savings_accounts_for_refugees
total_savings_balance_for_refugees
total_social_fund_savings_balance_for_refugees
number_of_youth
number_of_loans_to_youth
total_disbursed_to_youth
total_savings_accounts_for_youth
total_savings_balance_for_youth
total_social_fund_savings_balance_for_youth
 */
});
Route::get('policy', function () {
    return view('policy');
});
Route::get('process-users', function () {
    $users = User::where(['processed' => 'No'])->get();
    echo "Total users: " . count($users) . "<br>";
    foreach ($users as $user) {
        $phone_number = Utils::prepare_phone_number($user->phone_number);
        if (!Utils::phone_number_is_valid($phone_number)) {
            $user->process_status = 'Failed';
            $user->processed = 'Yes';
            $user->process_message = 'Invalid phone number: ' . $user->phone_number;
            $user->save();
            echo $user->process_message . "<br>";
            continue;
        }
        $user->processed = 'Yes';
        $user->process_status = 'Valid';
        $user->process_message = 'Phone number is valid.';
        try {
            echo "Processing " . $user->phone_number . "<br>";
            $user->save();
        } catch (\Throwable $th) {
            $user->process_status = 'Failed';
            $user->process_message = $th->getMessage();
            $user->save();
            echo $user->process_message . "<br>";
        }
    }
});

Route::get('/gen-form', function () {
    die(Gen::find($_GET['id'])->make_forms());
})->name("gen-form");


Route::get('generate-class', [MainController::class, 'generate_class']);
Route::get('/gen', function () {
    die(Gen::find($_GET['id'])->do_get());
})->name("register");


/*

Route::get('generate-variables', [MainController::class, 'generate_variables']);
Route::get('/', [MainController::class, 'index'])->name('home');
Route::get('/about-us', [MainController::class, 'about_us']);
Route::get('/our-team', [MainController::class, 'our_team']);
Route::get('/news-category/{id}', [MainController::class, 'news_category']);
Route::get('/news-category', [MainController::class, 'news_category']);
Route::get('/news', [MainController::class, 'news_category']);
Route::get('/news/{id}', [MainController::class, 'news']);
Route::get('/members', [MainController::class, 'members']);
Route::get('/dinner', [MainController::class, 'dinner']);
Route::get('/ucc', function(){ return view('chair-person-message'); });
Route::get('/vision-mission', function(){ return view('vision-mission'); });
Route::get('/constitution', function(){ return view('constitution'); });
Route::get('/register', [AccountController::class, 'register'])->name('register');

Route::get('/login', [AccountController::class, 'login'])->name('login')
    ->middleware(RedirectIfAuthenticated::class);

Route::post('/register', [AccountController::class, 'register_post'])
    ->middleware(RedirectIfAuthenticated::class);

Route::post('/login', [AccountController::class, 'login_post'])
    ->middleware(RedirectIfAuthenticated::class);


Route::get('/dashboard', [AccountController::class, 'dashboard'])
    ->middleware(Authenticate::class);


Route::get('/account-details', [AccountController::class, 'account_details'])
    ->middleware(Authenticate::class);

Route::post('/account-details', [AccountController::class, 'account_details_post'])
    ->middleware(Authenticate::class);

Route::get('/logout', [AccountController::class, 'logout']);
 */
