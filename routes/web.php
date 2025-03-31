<?php

use App\Admin\Controllers\DistrictsController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\MainController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Models\Cycle;
use App\Models\District;
use App\Models\Gen;
use App\Models\Loan;
use App\Models\LoanScheem;
use App\Models\LoanTransaction;
use App\Models\Sacco;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Utils;
use Carbon\Carbon;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Add SaccoController delete-related route
Route::delete('admin/saccos/{id}/delete-related/{model}', [\App\Admin\Controllers\SaccoController::class, 'deleteRelated'])
    ->middleware('admin')
    ->name('admin.sacco.delete-related');

Route::get('gen-dummy', function () {


    return;

    //generate dummy data
    $faker = \Faker\Factory::create();
    $saccos = Sacco::all();
    $loans = Loan::all();
    $users = User::all();
    $schemes = LoanScheem::all();
    $loanTransaction = LoanTransaction::all();
    /*
LoanTransaction
    "id" => 1
    "created_at" => "2024-03-22 11:02:04"
    "updated_at" => "2024-03-22 11:02:04"
    "loan_id" => 1
    "user_id" => 98
    "sacco_id" => 37
    "amount" => -60000
    "balance" => -63000
    "description" => "Borrowed UGX 60,000 from New buja dairy youth group - Loan Fund. Reference: 1."
    "cycle_id" => null
*/

    // Create at least 3 loan transactions for each loan
    foreach ($loans as $loan) {
        for ($i = 0; $i < 3; $i++) {
            $transaction = new LoanTransaction();
            $transaction->loan_id = $loan->id;
            $transaction->user_id = $loan->user_id;
            $transaction->sacco_id = $loan->sacco_id;
            $transaction->amount = $faker->randomFloat(2, -$loan->amount / 10, $loan->amount / 10);
            $transaction->balance = $loan->balance + $transaction->amount;
            $transaction->description = $faker->sentence;
            $transaction->cycle_id = $loan->cycle_id;
            $transaction->save();

            // Update loan balance
            $loan->balance = $transaction->balance;
            $loan->save();
            echo $transaction->id . ". " . $transaction->balance . "<br>";
        }
    }

    die('all done');


    /* loans table


Full texts
id
created_at
updated_at
sacco_id
user_id
loan_scheem_id
amount
balance
is_fully_paid
scheme_name
scheme_description
scheme_initial_interest_type
scheme_initial_interest_flat_amount
scheme_initial_interest_percentage
scheme_bill_periodically
scheme_billing_period
scheme_periodic_interest_type
scheme_periodic_interest_percentage
scheme_periodic_interest_flat_amount
scheme_min_amount
scheme_max_amount
scheme_min_balance
scheme_max_balance
reason
cycle_id
principal_amount
amount_paid
amount_not_paid
is_processed
sex_of_beneficiary
is_refugee
interest_amount
amount_to_be_paid

 */


    // Create 10 loans for each sacco
    foreach ($saccos as $sacco) {
        $sacco_members = $users->where('sacco_id', $sacco->id);
        if ($sacco_members->count() == 0) {
            echo "No members for " . $sacco->name . "<br>";
            continue;
        }
        $schemes = LoanScheem::where('sacco_id', $sacco->id)->get();
        if ($schemes->count() == 0) {
            echo "No schemes for " . $sacco->name . ", NOW HAS " . $schemes->count() . "<br>";
            continue;
        }
        for ($i = 0; $i < 10; $i++) {
            $loan = new Loan();
            $loan->sacco_id = $sacco->id;
            $loan->user_id = $sacco_members->random()->id;
            $scheme = $schemes->random();
            $loan->loan_scheem_id = $schemes->random()->id;
            $min = $scheme->min_amount;
            $max = $scheme->max_amount;
            $loan->amount = $faker->randomFloat(2, $min, $max);

            //make sure amount is a multiple of 1000
            $loan->amount = round($loan->amount / 1000) * 1000;
            //make sure is not less than max
            if ($loan->amount > $max) {
                $loan->amount = $loan->amount - $max;
            }

            //amount is zero, make it min
            if ($loan->amount == 0) {
                $loan->amount = $min;
            }


            $loan->is_fully_paid = 'No';
            $loan->scheme_name = $faker->randomElement(['Loan Fund', 'Emergency Loan', 'Education Loan', 'Business Loan']);
            $loan->scheme_description = $faker->sentence;
            $loan->scheme_initial_interest_type = $faker->randomElement(['Percentage', 'Flat']);
            $loan->scheme_initial_interest_percentage = $faker->randomFloat(2, 5, 20);
            $loan->scheme_bill_periodically = $faker->randomElement(['Yes', 'No']);
            $loan->scheme_billing_period = $faker->randomElement([1, 3, 6, 12]);
            $loan->scheme_periodic_interest_type = $faker->randomElement(['RemainingBalance', 'OriginalPrincipal']);
            $loan->scheme_periodic_interest_percentage = $faker->randomFloat(2, 1, 10);
            $loan->reason = $faker->randomElement(['Education', 'Business', 'Emergency', 'Personal', 'Health', 'Agriculture', 'Food', 'Rent', 'Transport', 'Other']);
            $loan->cycle_id = $faker->randomNumber(2);
            $loan->principal_amount = $loan->amount;
            $loan->amount_paid = 0;
            $loan->amount_not_paid = $loan->amount;
            $loan->is_processed = 'No';
            $loan->sex_of_beneficiary = $faker->randomElement(['Male', 'Female']);
            $loan->is_refugee = $faker->randomElement(['Yes', 'No']);
            $loan->interest_amount = $loan->amount * ($loan->scheme_initial_interest_percentage / 100);
            $loan->amount_to_be_paid = $loan->amount + $loan->interest_amount;
            $loan->scheme_min_amount = 10000;
            $loan->scheme_max_amount = 100000;
            $loan->save();

            $loan = Loan::find($loan->id);
            echo $loan->id . ". " . $loan->balance . "<br>";
        }
    }

    die('all done');
    /* LoanScheem
        "id" => 1
    "created_at" => "2024-03-19 12:00:57"
    "updated_at" => "2024-03-19 12:00:57"
    "sacco_id" => 7
    "name" => "Loan Fund"
    "description" => "Loan fund loan details"
    "initial_interest_type" => "Percentage"
    "initial_interest_flat_amount" => null
    "initial_interest_percentage" => 10
    "bill_periodically" => "Yes"
    "billing_period" => null
    "periodic_interest_type" => "OriginalPrincipal"
    "periodic_interest_percentage" => null
    "periodic_interest_flat_amount" => null
    "min_amount" => 10000
    "max_amount" => 100000
    "min_balance" => 30000
    "max_balance" => 800000
    "savings_percentage" => null
    */

    //truncate all tables
    // DB::table(( new LoanScheem())->getTable())->truncate();

    //create loan scheem for each sacco
    foreach ($saccos as $key => $sacco) {
        $scheem = new LoanScheem();
        $scheem->sacco_id = $sacco->id;
        $scheem->name = $faker->randomElement(['Loan Fund', 'Emergency Loan', 'Education Loan', 'Business Loan']);
        $scheem->description = $faker->randomElement(['Loan fund loan details', 'Emergency loan details', 'Education loan details', 'Business loan details']);
        $scheem->initial_interest_type = $faker->randomElement(["Percentage", "Flat"]);
        $scheem->bill_periodically = $faker->randomElement(["Yes", "No"]);
        $scheem->periodic_interest_type = $faker->randomElement(["RemainingBalance", "OriginalPrincipal"]);
        $scheem->min_amount = $faker->randomElement([10000, 20000, 30000, 40000, 50000, 60000, 70000, 80000, 90000, 100000]);
        $scheem->max_amount = $faker->randomElement([100000, 200000, 300000, 400000, 500000, 600000, 700000, 800000, 900000, 1000000]);
        $scheem->min_balance = $faker->randomElement([10000, 20000, 30000, 40000, 50000, 60000, 70000, 80000, 90000, 100000]);
        $scheem->max_balance = $faker->randomElement([100000, 200000, 300000, 400000, 500000, 600000, 700000, 800000, 900000, 1000000]);
        $scheem->savings_percentage = $faker->randomElement([5, 10, 15, 20, 25, 30, 35, 40, 45, 50]);
        $scheem->initial_interest_percentage = $faker->randomElement([5, 10, 15, 20, 25, 30, 35, 40, 45, 50]);
        $scheem->initial_interest_flat_amount = $faker->randomElement([10000, 20000, 30000, 40000, 50000, 60000, 70000, 80000, 90000, 100000]);
        $scheem->periodic_interest_percentage = $faker->randomElement([5, 10, 15, 20, 25, 30, 35, 40, 45, 50]);
        $scheem->periodic_interest_flat_amount = $faker->randomElement([10000, 20000, 30000, 40000, 50000, 60000, 70000, 80000, 90000, 100000]);
        $scheem->billing_period = $faker->randomElement([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
        $scheem->save();
    }


    dd($schemes[0]);
    //sacco_id
    foreach ($users as $key => $u) {
        //get random sacco
        $saco = $saccos->random();
        $u->sacco_id = $saco->id;
        $u->save();
    }

    dd($loans);
    /*
        "id" => 1
        "created_at" => "2024-03-22 11:02:04"
        "updated_at" => "2024-03-22 11:02:04"
        "sacco_id" => 1
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
        "principal_amount" => 60000
        "amount_paid" => 0
        "amount_not_paid" => -63000
        "is_processed" => "Yes"
        "sex_of_beneficiary" => "Male"
        "is_refugee" => "No"
        "interest_amount" => 3000
        "amount_to_be_paid" => -63000
*/
    $sacco_names = [
        'Kampala Savings Group',
        'Buganda Women Empowerment Sacco',
        'Pearl Farmers Sacco',
        'Rwenzori Development Sacco',
        'Victoria Lake Traders Sacco',
        'Masaka Cooperative Sacco',
        'Jinja Youth Savings Sacco',
        'Mbale United Sacco',
        'Gulu Progressive Sacco',
        'Arua Business Sacco',
        'Fort Portal Savings Sacco',
        'Mbarara Farmers Sacco',
        'Kabale Women Sacco',
        'Hoima Oil Workers Sacco',
        'Soroti Development Sacco',
        'Lira Community Sacco',
        'Entebbe Traders Sacco',
        'Iganga Savings Sacco',
        'Tororo Cooperative Sacco',
        'Kasese Farmers Sacco',
        'Mukono Business Sacco',
        'Bushenyi Development Sacco',
        'Ntungamo Savings Sacco',
        'Masindi Cooperative Sacco',
        'Kiryandongo Farmers Sacco',
        'Kitgum Women Sacco',
        'Pader Youth Sacco',
        'Nebbi Traders Sacco',
        'Adjumani Savings Sacco',
        'Koboko Development Sacco',
        'Yumbe Cooperative Sacco',
        'Zombo Farmers Sacco',
        'Kapchorwa Savings Sacco',
        'Kumi Women Sacco',
        'Bukedea Youth Sacco',
        'Amuria Traders Sacco',
        'Katakwi Development Sacco',
        'Moroto Cooperative Sacco',
        'Nakapiripirit Farmers Sacco',
        'Kotido Savings Sacco',
        'Abim Women Sacco',
        'Kaabong Youth Sacco',
        'Napak Traders Sacco',
        'Amudat Development Sacco',
        'Buliisa Cooperative Sacco',
        'Kagadi Farmers Sacco',
        'Kibaale Savings Sacco',
        'Kyenjojo Women Sacco',
        'Kyegegwa Youth Sacco',
        'Bundibugyo Traders Sacco'
    ];
    foreach ($saccos as $sacco) {
        shuffle($sacco_names);
        $admin = User::find($sacco->administrator_id);
        if (!$admin) {
            $sacco->administrator_id = 1;
        }
        $sacco->name = $sacco_names[10];
        $sacco->phone_number = $faker->phoneNumber;
        $sacco->email_address = $faker->email;
        $sacco->physical_address = $faker->address;
        $sacco->establishment_date = $faker->date();
        $sacco->registration_number = $faker->randomNumber(5);
        $sacco->chairperson_name = $faker->name;
        $sacco->chairperson_phone_number = $faker->phoneNumber;
        $sacco->chairperson_email_address = $faker->email;
        $sacco->share_price = $faker->randomNumber(4);
        $sacco->about = $faker->text;
        $sacco->terms = $faker->text;
        $sacco->register_fee = $faker->randomNumber(4);
        $sacco->uses_shares = $faker->randomElement([1, 0]);
        $sacco->status = $faker->randomElement(['Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Inactive']);
        $sacco->processed = $faker->randomElement(['Yes', 'No']);
        $sacco->district = $faker->randomElement(District::all()->pluck('id')->toArray());
        $sacco->subcounty = $faker->city;
        $sacco->parish = $faker->city;
        $sacco->village = $faker->city;
        $sacco->saving_types = $faker->randomElement(['Cash', 'Mobile Money', 'Bank']);
        $sacco->save();
        echo $sacco->id . ". " . $sacco->name . "<br>";
    }
    die();
});
Route::get('export-groups', function () {



    /*
    "phone_number" => "+256701035193"
    "email_address" => null
    "physical_address" => "Busukuma"
    "establishment_date" => null
    "registration_number" => null
    "chairperson_name" => null
    "chairperson_phone_number" => null
    "chairperson_email_address" => null
    "share_price" => 1000
    "about" => null
    "terms" => null
    "register_fee" => 5000
    "uses_shares" => 0
    "status" => "inactive"
    "processed" => "yes"
    "district" => null
    "subcounty" => null
    "parish" => null
    "village" => null
    "saving_types" => "cash"
*/
    // Remove echoes and set correct headers to avoid corruption
    set_time_limit(0);
    ini_set('memory_limit', '1024M');
    ini_set('max_execution_time', 0);

    // Process loans
    $loans = Loan::where('is_processed', 'No')->get();
    foreach ($loans as $loan) {
        Loan::process_loan($loan->id);
    }

    // Build spreadsheet
    $groups = Sacco::all();
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set column headers

    /*

number of loans given to men
total amount loans given to men
number of  women
number of loans given to women
total amount loans given to women
number of  FemaleFemale
number of loans given to refugees
total amount loans given to refugees
total savings amount
total savings withdrawn amount
total savings balance
total_disbursed_to_men
total_disbursed_to_women
total_disbursed_to_refugees

sex_of_beneficiary
is_refugee

amount_to_be_paid



 */
    // Fill rows
    $row = 2;
    foreach ($groups as $group) {
        $district_text = District::find($group->district) ? District::find($group->district)->name : $group->district;
        if (!$district_text) {
            $district_text = $group->physical_address;
        }
        $data = [
            'Created' => date('Y-m-d', strtotime($group->created_at)),
            'Group Name' => $group->name,
            'Group ID' => $group->id,
            'Village' => $group->village,
            'District' => $district_text,
            'Number Of Members' => User::where('sacco_id', $group->id)->count(),
            'Number Of Loans' => Loan::where('sacco_id', $group->id)->count(),
            'Total Principal Amount' => Loan::where('sacco_id', $group->id)->sum('principal_amount'),
            'Total Interest Amount' => Loan::where('sacco_id', $group->id)->sum('interest_amount'),
            'Total Accumulated Amount' => Loan::where('sacco_id', $group->id)->sum('amount'),
            'Total Amount Paid' => Loan::where('sacco_id', $group->id)->sum('amount_paid'),
            'Total Amount Not Paid' => Loan::where('sacco_id', $group->id)->sum('amount_not_paid'),
            'Total Number Of Loans' => Loan::where('sacco_id', $group->id)->count(),
            'Total Number Of Loans Fully Paid' => Loan::where('sacco_id', $group->id)->where('is_fully_paid', 'Yes')->count(),
            'Most Common Reason For Loan' => Loan::where('sacco_id', $group->id)
                ->selectRaw('reason, COUNT(*) as reason_count')
                ->groupBy('reason')
                ->orderByDesc('reason_count')
                ->first()
                ->reason ?? 'N/A',
            'Total Number Of Loans Not Fully Paid' => Loan::where('sacco_id', $group->id)->where('is_fully_paid', 'No')->count(),
            'Number Of Men Beneficiaries' => Loan::where('sacco_id', $group->id)->where('sex_of_beneficiary', 'Male')->count(),
            'Total Amount Of Laon to Men' => Loan::where('sacco_id', $group->id)->where('sex_of_beneficiary', 'Male')->sum('amount'),
            'Total Amount Paid By Men' => Loan::where('sacco_id', $group->id)->where('sex_of_beneficiary', 'Male')->sum('amount_paid'),
            'Total Amount Not Paid By Men' => Loan::where('sacco_id', $group->id)->where('sex_of_beneficiary', 'Male')->sum('amount_not_paid'),

            'Number Of Female Beneficiaries' => Loan::where('sacco_id', $group->id)->where('sex_of_beneficiary', 'Female')->count(),
            'Total Amount Of Laon to Female' => Loan::where('sacco_id', $group->id)->where('sex_of_beneficiary', 'Female')->sum('amount'),
            'Total Amount Paid By Female' => Loan::where('sacco_id', $group->id)->where('sex_of_beneficiary', 'Female')->sum('amount_paid'),
            'Total Amount Not Paid By Female' => Loan::where('sacco_id', $group->id)->where('sex_of_beneficiary', 'Female')->sum('amount_not_paid'),

            'Number Of Refugee Beneficiaries' => Loan::where('sacco_id', $group->id)->where('is_refugee', 'Yes')->count(),
            'Total Refugee Of Laon to Female' => Loan::where('sacco_id', $group->id)->where('is_refugee', 'Yes')->sum('amount'),
            'Total Refugee Paid By Refugee' => Loan::where('sacco_id', $group->id)->where('is_refugee', 'Yes')->sum('amount_paid'),
            'Total Refugee Not Paid By  Female' => Loan::where('sacco_id', $group->id)->where('is_refugee', 'Yes')->sum('amount_not_paid'),

            'Total Savings Amount' => Transaction::where('sacco_id', $group->id)->where('amount', '>', 0)->sum('amount'),
            'Total Savings Withdrawn Amount' => Transaction::where('sacco_id', $group->id)->where('amount', '<', 0)->sum('amount'),
            'Total Savings Balance' => Transaction::where('sacco_id', $group->id)->sum('amount'),
        ];
        $i = 1;
        foreach ($data as $value) {
            $sheet->setCellValueByColumnAndRow($i++, $row, $value);
        }
        $row++;
    }

    $headers = array_keys($data); // Get the headers from the last row
    foreach ($headers as $col => $header) {
        $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
    }


    // Format and save
    $sheet->getStyle("A1:Z" . ($row - 1))
        ->getNumberFormat()
        ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);

    $writer = new Xlsx($spreadsheet);
    $fileName = 'groups.xlsx';
    $writer->save($fileName);

    return response()->download($fileName)->deleteFileAfterSend(true);
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
