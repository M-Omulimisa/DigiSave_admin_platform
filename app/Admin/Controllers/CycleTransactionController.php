<?php

namespace App\Admin\Controllers;

use App\Models\Transaction;
use App\Models\Cycle;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Form;
use Encore\Admin\Layout\Content;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Log;

class CycleTransactionController extends AdminController
{
    protected $title = 'Transactions';

    public function index(Content $content)
    {
        // Get sacco_id and cycle_id from request
        $saccoId = request()->get('sacco_id');
        $cycleId = request()->get('cycle_id');

        // Fetch the cycle and group (Sacco) name
        $cycle = Cycle::find($cycleId);
        $groupName = $cycle ? $cycle->sacco->name : 'Unknown Group';

        $title = "{$groupName} - Cycle {$cycle->name} Transactions";

        return $content
            ->header($title)
            ->body($this->grid($cycleId, $saccoId));
    }

    protected function grid($cycleId, $saccoId)
{
    $grid = new Grid(new Transaction());

    // Default sort order
        $sortOrder = request()->get('_sort', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

    // dd($saccoId);

    // Filter transactions by cycle_id and sacco_id if provided in the request
    $grid->model()
         ->where('sacco_id', $saccoId)
         ->where('cycle_id', $cycleId)
         ->orderBy('created_at', $sortOrder);

    $grid->disableCreateButton();

    // Custom create button if needed
    $grid->tools(function ($tools) use ($saccoId, $cycleId) {
        $url = url('/cycle-transactions/create?sacco_id=' . $saccoId . '&cycle_id=' . $cycleId);
        $tools->append("<a class='btn btn-sm btn-success' href='{$url}'>Create New Transaction</a>");
    });

    // Columns setup
    $grid->column('id', __('ID'))->sortable();
    $grid->column('user.name', __('Account'))->sortable();
    $grid->column('type', __('Type'))->display(function ($type) {
        return $type === 'REGESTRATION' ? 'REGISTRATION' : $type;
    })->sortable();
    $grid->column('amount', __('Amount (UGX)'))->display(function ($amount) {
        return number_format($amount, 2, '.', ',');
    })->sortable();
    $grid->column('description', __('Description'));
    $grid->column('created_at', __('Created At'))->display(function ($createdAt) {
        return \Carbon\Carbon::parse($createdAt)->format('Y-m-d H:i:s');
    })->sortable();
    // Filters
    $grid->filter(function ($filter) {
        $filter->disableIdFilter();
        $filter->like('user.name', 'User Name');
        $filter->equal('type', 'Type')->select(['SHARE' => 'SHARE', 'Send' => 'Send', 'Receive' => 'Receive', 'REGESTRATION' => 'Registration']);
        $filter->between('amount', 'Amount');
        $filter->between('created_at', 'Created At')->datetime();
    });

    return $grid;
}


    protected function detail($id)
    {
        $show = new Show(Transaction::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('user.name', __('User Name'));
        $show->field('type', __('Type'))->as(function ($type) {
            return $type === 'REGESTRATION' ? 'Registration' : $type;
        });
        $show->field('amount', __('Amount'))->as(function ($amount) {
            return number_format($amount, 2, '.', ',') . ' UGX';
        });
        $show->field('description', __('Description'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    protected function form()
{
    $form = new Form(new Transaction());
    $saccoId = $form->model()->sacco_id ?? request()->get('sacco_id');
    $cycleId = $form->model()->cycle_id ?? request()->get('cycle_id');

    // dd("Processing Excel file for sacco_id: {$saccoId}, cycle_id: {$cycleId}");

    // Add radio buttons to let the user choose between form input or Excel upload
    $form->radio('input_type', __('Input Type'))
         ->options([
             'manual' => 'Manual Entry',
             'excel' => 'Upload Excel'
         ])
         ->when('manual', function (Form $form) {
             // Fields for manual entry
             $form->select('source_user_id', __('User'))
                  ->options(function () use ($form) {
                      $saccoId = $form->model()->sacco_id ?? request()->get('sacco_id');
                      return User::where('sacco_id', $saccoId)
                                 ->where(function ($query) {
                                     $query->whereNull('user_type')->orWhere('user_type', '!=', 'Admin');
                                 })
                                 ->pluck('first_name', 'id');
                  })
                  ->rules('required');

             $form->select('type', __('Type'))
                  ->options(Transaction::select('type')->distinct()->pluck('type', 'type')->toArray())
                  ->rules('required');

             $form->datetime('created_at', __('Created At'))
                  ->default(date('Y-m-d H:i:s'))
                  ->rules('required|date');

             $form->decimal('amount', __('Amount'))
                  ->rules('required|numeric|min:0');

             $form->textarea('description', __('Description'));
         })
         ->when('excel', function (Form $form) {
             // Field for Excel upload
             $form->file('excel_file', __('Upload Excel File'))
                  ->help('Upload an Excel file to import transactions.')
                  ->rules('required');
         })
         ->default('manual')
         ->rules('required');

         Log::info("Before processing Excel file for sacco_id: {$saccoId}, cycle_id: {$cycleId}");

         $form->hidden('sacco_id')->default($saccoId);
         $form->hidden('cycle_id')->default($cycleId);



    // Process Excel file if selected
    $form->submitted(function (Form $form) use ($saccoId, $cycleId) {
        // Check if the input type is 'excel'
        if (request('input_type') === 'excel' && request()->hasFile('excel_file')) {
            $file = request()->file('excel_file');
            Log::info("Processing Excel file for sacco_id: {$saccoId}, cycle_id: {$cycleId}");

            // Process the Excel file
            $this->processExcelFile($file, $saccoId, $cycleId);

            // Return false to prevent saving the form data to the database
            return false; // This fully stops the saving process
        }
    });

    return $form;
}


protected function processExcelFile($file, $saccoId, $cycleId)
{

    // Log the sacco_id and cycle_id for debugging
    Log::info("Processing Excel file for sacco_id: {$saccoId}, cycle_id: {$cycleId}");

    try {
        // Load the spreadsheet
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        foreach ($rows as $index => $row) {
            // Skip the header row
            if ($index === 0) {
                Log::info("Skipping header row.");
                continue;
            }

            // Extract and sanitize data from each row
            $groupName = $row[0] ?? null;
            $cycleName = $row[1] ?? null;
            $memberName = $row[2] ?? null;
            $registrationType = strtoupper(trim($row[3] ?? '')); // Sanitize and convert to uppercase

            // Log the registration type for debugging
            Log::info("Processing row {$index}: Registration Type - {$registrationType}");

            $amount = $row[4] ?? null;
            $description = $row[5] ?? null;
            $createdAt = \Carbon\Carbon::parse($row[6] ?? now())->toDateTimeString();

            // Check if the transaction type is valid
            if (!in_array($registrationType, array_keys(TRANSACTION_TYPES))) {
                Log::error("Invalid transaction type: {$registrationType}");
                continue; // Skip this row if invalid
            }

            // Split member name into first and last names
            $nameParts = explode(' ', $memberName);
            $firstName = $nameParts[0] ?? null;
            $lastName = $nameParts[1] ?? '';

            // Log the user details being searched
            Log::info("Looking for user: First Name - {$firstName}, Last Name - {$lastName}");

            // Find the user by first and last name
            $user = User::where('first_name', $firstName)
                        ->where('last_name', $lastName)
                        ->first();

            if ($user) {
                // Log user found
                Log::info("User found: {$user->first_name} {$user->last_name} (ID: {$user->id})");

                // Create the transaction
                Transaction::create([
                    'user_id' => $user->id,
                    'type' => $registrationType,  // Use sanitized and validated type
                    'amount' => $amount,
                    'description' => $description,
                    'created_at' => $createdAt,
                    'sacco_id' => $saccoId,  // Use sacco_id from the request
                    'cycle_id' => $cycleId,  // Use cycle_id from the request
                ]);

                // Log successful transaction creation
                Log::info("Transaction created successfully for user ID: {$user->id}, Type: {$registrationType}, Amount: {$amount}");
            } else {
                // Log and handle case where user is not found
                Log::warning("User not found for name: {$firstName} {$lastName}");
            }
        }
    } catch (\Exception $e) {
        // Log any exception that occurs during processing
        Log::error("Error processing Excel file: " . $e->getMessage());
    }
}

    // protected function form()
    // {
    //     $form = new Form(new Transaction());

    //     // Conditional default values only if it's a new model (i.e., creating)
    //     if ($form->isCreating()) {
    //         $saccoId = request()->get('sacco_id');
    //         $cycleId = request()->get('cycle_id');
    //         $adminUserId = User::where('sacco_id', $saccoId)->where('user_type', '=', 'admin')->first()->id ?? null;

    //         $form->hidden('user_id')->default($adminUserId);
    //         $form->hidden('sacco_id')->default($saccoId);
    //         $form->hidden('cycle_id')->default($cycleId);
    //     }

    //     // Common form fields setup
    //     $form->select('source_user_id', __('User'))
    //          ->options(function () use ($form) {
    //             $saccoId = $form->model()->sacco_id ?? request()->get('sacco_id');
    //             return User::where('sacco_id', $saccoId)
    //                        ->where(function ($query) {
    //                            $query->whereNull('user_type')->orWhere('user_type', '!=', 'Admin');
    //                        })
    //                        ->pluck('first_name', 'id');
    //          })
    //          ->rules('required');

    //     $form->select('type', __('Type'))
    //          ->options(Transaction::select('type')->distinct()->pluck('type', 'type')->toArray())
    //          ->rules('required');

    //     $form->datetime('created_at', __('Created At'))
    //          ->default(date('Y-m-d H:i:s'))
    //          ->rules('required|date');

    //     $form->decimal('amount', __('Amount'))
    //          ->rules('required|numeric|min:0');

    //     $form->textarea('description', __('Description'));

    //     return $form;
    // }



    public function create(Content $content)
    {
        $saccoId = request()->get('sacco_id');
        $cycleId = request()->get('cycle_id');

        $cycle = Cycle::find($cycleId);
        $groupName = $cycle ? $cycle->sacco->name : 'Unknown Group';
        $title = "{$groupName} - Cycle {$cycle->name} Transactions - Create New";

        return $content
            ->header($title)
            ->body($this->form());
    }
}
