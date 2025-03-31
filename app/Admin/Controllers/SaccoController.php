<?php

namespace App\Admin\Controllers;

use App\Models\MemberPosition;
use App\Models\OrgAllocation;
use App\Models\Sacco;
use App\Models\VslaOrganisationSacco;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Cycle;
use App\Models\Meeting;
use App\Models\Transaction;
use App\Models\Loan;
use App\Models\LoanScheem;
use App\Models\SocialFund;
use Encore\Admin\Layout\Content;
use App\Admin\Actions\DeleteConfirmation;

class SaccoController extends AdminController
{
    protected $title = 'VSLA Groups';

    protected function grid()
    {
        $grid = new Grid(new Sacco());

        $admin = Admin::user();
        $adminId = $admin->id;

        // Default sort order
        $sortOrder = request()->get('_sort', 'desc');
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $grid->model()->whereNotNull('name')->where('name', '!=', '');

        // Filter out groups without a chairperson name or phone number
        $grid->model()->whereHas('users', function ($query) {
            $query->whereHas('position', function ($positionQuery) {
                $positionQuery->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
            })
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '');
        });

        // Apply filters based on user's role
        if (!$admin->isRole('admin')) {
            $orgAllocation = OrgAllocation::where('user_id', $adminId)->first();
            if ($orgAllocation) {
                $orgId = $orgAllocation->vsla_organisation_id;
                $adminRegion = trim($orgAllocation->region);

                if (empty($adminRegion)) {
                    // If no region is specified, get all SACCOs for the organization
                    $saccoIds = VslaOrganisationSacco::where('vsla_organisation_id', $orgId)
                        ->pluck('sacco_id')
                        ->toArray();
                } else {
                    // Get only SACCOs in the admin's region
                    $saccoIds = VslaOrganisationSacco::join('saccos', 'vsla_organisation_sacco.sacco_id', '=', 'saccos.id')
                        ->where('vsla_organisation_sacco.vsla_organisation_id', $orgId)
                        ->whereRaw('LOWER(saccos.district) = ?', [strtolower($adminRegion)])
                        ->pluck('sacco_id')
                        ->toArray();
                }

                $grid->model()
                    ->whereIn('id', $saccoIds)
                    ->whereNotIn('status', ['deleted', 'inactive'])
                    ->orderBy('created_at', $sortOrder);
                $grid->disableCreateButton();
            }
        } else {
            // For admins, display all records ordered by created_at
            $grid->model()
                ->whereNotIn('status', ['deleted', 'inactive'])
                ->orderBy('created_at', $sortOrder);
        }

        // Show export button
        $grid->showExportBtn();
        $grid->disableBatchActions();
        $grid->quickSearch('name')->placeholder('Search by name');

        // Define columns
        $grid->column('name', __('Name'))->sortable()->display(function ($name) {
            return ucwords(strtolower($name));
        });

        $grid->column('chairperson_name', __('Leader'))
            ->sortable()
            ->display(function () {
                $user = \App\Models\User::where('sacco_id', $this->id)
                    ->whereHas('position', function ($query) {
                        $query->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
                    })
                    ->first();

                return $user ? ucwords(strtolower($user->name)) : '';
        });

        $grid->column('phone_number', __('Contact'))
            ->sortable()
            ->display(function () {
                $user = \App\Models\User::where('sacco_id', $this->id)
                    ->whereHas('position', function ($query) {
                        $query->whereIn('name', ['Chairperson', 'Secretary', 'Treasurer']);
                    })
                    ->first();

                return $user ? $user->phone_number : '';
        });

        $grid->column('district', __('District'))->sortable()->display(function ($district) {
            if (!empty($district)) {
                // If district is available, format and display it
                return ucwords(strtolower($district));
            } elseif (!empty($this->physical_address)) {
                // If district is not available but physical_address is, format and display physical_address
                return ucwords(strtolower($this->physical_address));
            } else {
                // If neither is available, display 'No District'
                return 'No District';
            }
        });

        $grid->column('amount_required_per_meeting', __('Welfare'))
        ->display(function () {
            $latestCycle = $this->cycles()->orderBy('created_at', 'desc')->first();
            return $latestCycle ? number_format($latestCycle->amount_required_per_meeting) : 'N/A';
        })->sortable()
        ->editable();

        // $grid->column('amount_required_per_meeting', __('Welfare'))
        // ->display(function () {
        //     // Get the latest cycle associated with the sacco
        //     $latestCycle = $this->cycles()->orderBy('created_at', 'desc')->first();
        //     return $latestCycle ? number_format($latestCycle->amount_required_per_meeting) : 'N/A';
        // })->sortable();

        // Adding new columns for uses_cash and uses_shares
        $grid->column('uses_cash', __('Uses Cash'))
            ->display(function () {
                return $this->uses_shares == 0 ? 'Yes' : 'No';
        })->sortable();

        // Adding new columns for share_price and min_cash_savings
        $grid->column('min_cash_savings', __('Minimum Cash Savings (UGX)'))
            ->display(function () {
                return $this->uses_shares == 0 ? number_format($this->share_price) : '0';
        })->sortable();

        $grid->column('uses_shares', __('Uses Shares'))
            ->display(function () {
                return $this->uses_shares == 1 ? 'Yes' : 'No';
        })->sortable();

        $grid->column('share_price', __('Share Price (UGX)'))
            ->display(function () {
                return $this->uses_shares == 1 ? number_format($this->share_price) : '0';
        })->sortable();

        // Adding the "View Transactions" column
        $grid->column('view_transactions', __('View Transactions'))
            ->display(function () {
                return '<a href="' . url('/transactions?sacco_id=' . $this->id) . '">View Transactions</a>';
        });

        // $grid->column('view_credit', __('Credit Score'))
        // ->display(function () {
        //     return '<a href="' . url('/credit?sacco_id=' . $this->id) . '">View Credit</a>';
        // });

        $grid->column('created_at', __('Created At'))
                ->display(function ($date) {
                    return date('d M Y', strtotime($date));
        })->sortable();

        // Replace the default delete action with our custom delete confirmation
        $grid->actions(function ($actions) {
            // Remove the default delete action
            $actions->disableDelete();

            // Add our custom delete action using the proper RowAction class
            $actions->add(new DeleteConfirmation());
        });

        // Adding search filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->like('name', 'Name');
            $filter->like('phone_number', 'Phone Number');
            $filter->like('physical_address', 'Physical Address');
            $filter->between('created_at', 'Created At')->datetime();
        });

        // Filtering logic based on location_search
        if ($search = request()->get('location_search')) {
            $grid->model()->where(function ($query) use ($search) {
                $query->where('district', 'like', "%{$search}%")
                      ->orWhere('physical_address', 'like', "%{$search}%");
            });
        }

        // Apply Created At date range filter if present
    if ($createdFrom = request('created_from')) {
        $grid->model()->whereDate('created_at', '>=', $createdFrom);
    }

    if ($createdTo = request('created_to')) {
        $grid->model()->whereDate('created_at', '<=', $createdTo);
    }

        // Adding custom dropdowns and search input in the tools section
        $grid->tools(function ($tools) {
            $tools->append('
                <style>
                    .custom-tool-container {
                        width: 80%;
                    }
                    .custom-tool-container .button-row {
                        display: flex;
                        flex-wrap: wrap;
                        margin-bottom: 10px;
                        margin-top: 10px;
                    }
                    .custom-tool-container .search-row {
                        margin-top: 10px;
                    }
                    /* Modal Styling */
                    .modal-header, .modal-footer {
                        background-color: #f5f5f5;
                    }
                </style>
                <div class="custom-tool-container">
                    <div class="button-row">
                        <!-- Sort Dropdown -->
                        <div class="btn-group" style="margin-right: 5px;">
                            <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown">
                                Sort by Established <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="' . url()->current() . '?_sort=asc">Ascending</a></li>
                                <li><a href="' . url()->current() . '?_sort=desc">Descending</a></li>
                            </ul>
                        </div>
                        <!-- Filter Dropdown -->
                        <div class="btn-group" style="margin-right: 5px;">
                            <button type="button" class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown">
                                Filter by Usage <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="' . url()->current() . '?uses_shares=0">Uses Cash</a></li>
                                <li><a href="' . url()->current() . '?uses_shares=1">Uses Shares</a></li>
                                <li><a href="' . url()->current() . '">All</a></li>
                            </ul>
                        </div>
                        <!-- Filter by Created At Button -->
                        <div class="btn-group" style="margin-right: 5px;">
                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#createdAtFilterModal">
                                Filter by Date Created
                            </button>
                        </div>
                        <!-- Reset Filters Button -->
                        <div class="btn-group" style="margin-right: 5px;">
                            <a href="' . url()->current() . '" class="btn btn-sm btn-warning">
                                <i class="fa fa-refresh"></i> Reset Filters
                            </a>
                        </div>
                    </div>
                    <div class="search-row">
                        <form action="' . url()->current() . '" method="GET" pjax-container>
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <input type="text" name="location_search" class="form-control" placeholder="Search District or Address" value="' . request('location_search', '') . '">
                                <div class="input-group-btn">
                                    <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Created At Filter Modal -->
                <div class="modal fade" id="createdAtFilterModal" tabindex="-1" role="dialog" aria-labelledby="createdAtFilterModalLabel">
                  <div class="modal-dialog" role="document">
                    <form action="' . url()->current() . '" method="GET">
                      <div class="modal-content">
                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                          <h4 class="modal-title" id="createdAtFilterModalLabel">Filter by Created At</h4>
                        </div>
                        <div class="modal-body">
                          <!-- Preserve existing filters -->
                          <input type="hidden" name="uses_shares" value="' . request('uses_shares', '') . '">
                          <input type="hidden" name="location_search" value="' . request('location_search', '') . '">
                          <input type="hidden" name="_sort" value="' . request('_sort', 'desc') . '">

                          <div class="form-group">
                            <label for="created_from">Start Date:</label>
                            <input type="text" class="form-control datepicker" id="created_from" name="created_from" placeholder="Select start date" value="' . request('created_from', '') . '" required>
                          </div>
                          <div class="form-group">
                            <label for="created_to">End Date:</label>
                            <input type="text" class="form-control datepicker" id="created_to" name="created_to" placeholder="Select end date" value="' . request('created_to', '') . '" required>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-primary">Apply Filter</button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>

                <script>
                    $(document).ready(function(){
                        // Initialize datepickers
                        $(".datepicker").datepicker({
                            format: "yyyy-mm-dd",
                            autoclose: true,
                            todayHighlight: true
                        });
                    });
                </script>
            ');
        });

        // Adding the filtering logic based on the dropdown selection
        if (request()->has('uses_shares')) {
            $uses_shares = request()->get('uses_shares');
            if (in_array($uses_shares, ['0', '1'])) {
                $grid->model()->where('uses_shares', $uses_shares);
            }
        }

        if (request()->has('transactions')) {
            $transactions = request()->get('transactions');
            if ($transactions === 'yes') {
                $grid->model()->whereHas('transactions');
            } elseif ($transactions === 'no') {
                $grid->model()->whereDoesntHave('transactions');
            }
        }

        // Include jQuery UI
        Admin::js('https://code.jquery.com/ui/1.12.1/jquery-ui.min.js');
        Admin::css('https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

        // JavaScript code to fetch districts and initialize autocomplete
        Admin::script("
            $(function() {
                var csrfToken = $('meta[name=\"csrf-token\"]').attr('content');
                var districts = [];

                // Fetch all districts when the page loads
                $.ajax({
                    url: '" . url('/api/district') . "',
                    type: 'GET',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(data) {
                        districts = data.data.map(function(item) {
                            return item.name;
                        });

                        // Initialize the autocomplete after fetching districts
                        $('input[name=\"location_search\"]').autocomplete({
                            source: districts,
                            minLength: 1,
                        });
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error: ' + status + ': ' + error);
                    }
                });
            });
        ");

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Sacco::findOrFail($id));

        $show->field('name', __('Name'))->as(function ($name) {
            return ucwords(strtolower($name));
        });

        $show->field('phone_number', __('Phone Number'));

        $show->field('share_price', __('Share (UGX)'))->as(function ($price) {
            return number_format($price);
        });

        $show->field('physical_address', __('Physical Address'))->as(function ($address) {
            return ucwords(strtolower($address));
        });

        $show->field('created_at', __('Created At'))->as(function ($date) {
            return date('d M Y', strtotime($date));
        });

        $show->field('chairperson_name', __('Chairperson Name'))->as(function () {
            $chairperson = \App\Models\User::where('sacco_id', $this->id)
                ->whereHas('position', function ($query) {
                    $query->where('name', 'Chairperson');
                })
                ->first();

            return $chairperson ? ucwords(strtolower($chairperson->name)) : '';
        });

        return $show;
    }

    protected function form()
    {
        $form = new Form(new Sacco());

        $u = Admin::user();

        if (!$u->isRole('admin')) {
            if ($form->isCreating()) {
                admin_error("You are not allowed to create new Sacco");
                return back();
            }
        }

        // Sacco Details
        $form->text('name', __('Name'))->rules('required');

        // Adding saving type select field as non-database field
        $form->select('saving_types', __('Saving Type'))->options([
            'shares' => 'Shares',
            'cash' => 'Cash'
        ])->rules('required')->attribute(['name' => 'saving_types']);

        // Adding conditionally rendered fields for share price and cash savings
        $form->decimal('share_price', __('Share Price (UGX)'))->help('UGX')->rules('numeric|min:0')->attribute(['class' => 'shares']);
        $form->decimal('share_price', __('Minimum Cash Savings (UGX)'))->help('UGX')->rules('numeric|min:0')->attribute(['class' => 'cash']);

        $form->hidden('uses_shares');
        $form->hidden('phone_number');

        $form->text('email_address', __('Email Address'));
        $form->text('physical_address', __('Physical Address'));
        $form->datetime('created_at', __('Establishment Date'))->rules('required');
        $form->image('logo', __('VSLA Logo'));

        // Add location fields
        $form->text('district', __('District'));
        $form->text('subcounty', __('Subcounty'));
        $form->text('parish', __('Parish'));
        $form->text('village', __('Village'));

        // Add a select field for transactions view
        // $form->select('view_transactions', __('View Transactions'))->options([
        //     'yes' => 'Yes',
        //     'no' => 'No'
        // ])->rules('required');

        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                // Generate phone number in the background
                if (!$form->phone_number) {
                    $initialPhoneNumber = '0701399995'; // Dummy initial phone number to generate from
                    $form->phone_number = substr($initialPhoneNumber, 3, 3) . date('Y') . strtoupper(Str::random(2));
                }

                // Set uses_shares based on saving type
                $form->uses_shares = request()->get('saving_types') == 'shares' ? 1 : 0;

                // Create new administrator
                $user = new Administrator();
                $name = $form->name;  // Using Sacco name for admin name
                $phone_number = $form->phone_number;
                $x = explode(' ', $name);

                if (isset($x[0]) && isset($x[1])) {
                    $user->first_name = $x[0];
                    $user->last_name = $x[1];
                } else {
                    $user->first_name = $name;
                    $user->last_name = '';
                }

                // Generate 8-digit code combining phone number digits, current year, and random letters
                $code = substr($phone_number, 3, 3) . date('Y') . strtoupper(Str::random(2));
                $user->username = $code;
                $user->phone_number = $phone_number;
                $user->name = $name;
                $user->password = bcrypt($code);

                $user->save();

                $form->administrator_id = $user->id;
            } else {
                // For editing, ensure administrator_id is not null
                if (!$form->administrator_id) {
                    $sacco = Sacco::find($form->model()->id);
                    $form->administrator_id = $sacco->administrator_id;
                }
            }

            // Set uses_shares based on saving type for both create and update
            $form->uses_shares = request()->get('saving_types') == 'shares' ? 1 : 0;
        });

        $form->saved(function (Form $form) {
            if ($form->isCreating()) {
                // Manually save Sacco with administrator ID
                $sacco = new Sacco();
                $sacco->name = $form->name;
                $sacco->phone_number = $form->phone_number;
                $sacco->email_address = $form->email_address;
                $sacco->physical_address = $form->physical_address;
                $sacco->created_at = $form->created_at;
                $sacco->district = $form->district;
                $sacco->subcounty = $form->subcounty;
                $sacco->parish = $form->parish;
                $sacco->village = $form->village;
                $sacco->administrator_id = $form->administrator_id;
                $sacco->uses_shares = $form->uses_shares;

                if (request()->get('saving_types') == 'shares') {
                    $sacco->share_price = $form->share_price;
                } else {
                    $sacco->share_price = $form->min_cash_savings;
                }

                $sacco->save();

                // Create default positions
                $positions = ['Chairperson', 'Secretary', 'Treasurer'];
                foreach ($positions as $positionName) {
                    MemberPosition::create([
                        'name' => $positionName,
                        'sacco_id' => $sacco->id,
                    ]);
                }

                // Display success message
                admin_success('Success', 'Group ' . $sacco->name . ' created successfully');
            } else {
                // Update existing Sacco
                $sacco = Sacco::find($form->model()->id);
                $sacco->name = $form->name;
                $sacco->phone_number = $form->phone_number;
                $sacco->email_address = $form->email_address;
                $sacco->physical_address = $form->physical_address;
                $sacco->created_at = $form->created_at;
                $sacco->district = $form->district;
                $sacco->subcounty = $form->subcounty;
                $sacco->parish = $form->parish;
                $sacco->village = $form->village;
                $sacco->uses_shares = $form->uses_shares;
                $sacco->administrator_id = $form->administrator_id;

                if (request()->get('saving_types') == 'shares') {
                    $sacco->share_price = $form->share_price;
                } else {
                    $sacco->share_price = $form->share_price;
                }

                $sacco->save();

                // Display success message
                admin_success('Success', 'Group ' . $sacco->name . ' updated successfully');
            }
        });

        $form->hidden('administrator_id');

        // Adding JavaScript for toggling fields
        $form->html('<script>
            $(document).ready(function() {
                toggleSavingTypeFields();
                $("select[name=\'saving_types\']").change(function() {
                    toggleSavingTypeFields();
                });

                function toggleSavingTypeFields() {
                    var savingType = $("select[name=\'saving_types\']").val();
                    if (savingType === "shares") {
                        $(".cash").parents(".form-group").hide();
                        $(".shares").parents(".form-group").show();
                        $("input[name=\'uses_shares\']").val(1); // Set uses_shares to 1 for shares
                    } else {
                        $(".shares").parents(".form-group").hide();
                        $(".cash").parents(".form-group").show();
                        $("input[name=\'uses_shares\']").val(0); // Set uses_shares to 0 for cash
                    }
                }
            });
        </script>');

        return $form;
    }

    /**
     * Show the confirmation page for deleting a group with all related data.
     *
     * @param int $id
     * @return Content
     */
    public function deleteConfirmation($id)
    {
        $sacco = Sacco::findOrFail($id);

        // Define the deletion order with try-catch blocks to handle missing tables
        $deletionOrder = [];

        // Get all related data with count, handling missing tables gracefully
        try {
            $users = User::where('sacco_id', $id)->get();
            $deletionOrder[] = [
                'name' => 'Users',
                'model' => 'User',
                'count' => $users->count(),
                'route' => 'admin.sacco.delete-related',
                'icon' => 'fa-users',
                'description' => 'Member records for this group',
                'dependent_on' => ['Loans', 'Meetings', 'Transactions', 'Share Records']
            ];
        } catch (\Exception $e) {
            // Table doesn't exist or other error
        }

        try {
            $cycles = Cycle::where('sacco_id', $id)->get();
            $deletionOrder[] = [
                'name' => 'Cycles',
                'model' => 'Cycle',
                'count' => $cycles->count(),
                'route' => 'admin.sacco.delete-related',
                'icon' => 'fa-sync',
                'description' => 'Cycle records for this group',
                'dependent_on' => ['Meetings', 'Loans', 'Transactions']
            ];
        } catch (\Exception $e) {
            // Table doesn't exist or other error
        }

        try {
            $meetings = Meeting::where('sacco_id', $id)->get();
            $deletionOrder[] = [
                'name' => 'Meetings',
                'model' => 'Meeting',
                'count' => $meetings->count(),
                'route' => 'admin.sacco.delete-related',
                'icon' => 'fa-users',
                'description' => 'Meeting records for this group',
                'dependent_on' => ['Transactions', 'Share Records']
            ];
        } catch (\Exception $e) {
            // Table doesn't exist or other error
        }

        try {
            $transactions = Transaction::where('sacco_id', $id)->get();
            $deletionOrder[] = [
                'name' => 'Transactions',
                'model' => 'Transaction',
                'count' => $transactions->count(),
                'route' => 'admin.sacco.delete-related',
                'icon' => 'fa-exchange-alt',
                'description' => 'Transaction records for this group',
                'dependent_on' => []
            ];
        } catch (\Exception $e) {
            // Table doesn't exist or other error
        }

        try {
            $loans = Loan::where('sacco_id', $id)->get();
            $deletionOrder[] = [
                'name' => 'Loans',
                'model' => 'Loan',
                'count' => $loans->count(),
                'route' => 'admin.sacco.delete-related',
                'icon' => 'fa-hand-holding-usd',
                'description' => 'Loan records for this group',
                'dependent_on' => ['Loan Transactions']
            ];
        } catch (\Exception $e) {
            // Table doesn't exist or other error
        }

        try {
            $shareRecords = \App\Models\ShareRecord::where('sacco_id', $id)->count();
            $deletionOrder[] = [
                'name' => 'Share Records',
                'model' => 'ShareRecord',
                'count' => $shareRecords,
                'route' => 'admin.sacco.delete-related',
                'icon' => 'fa-chart-pie',
                'description' => 'Share records for this group',
                'dependent_on' => []
            ];
        } catch (\Exception $e) {
            // Table doesn't exist or other error
        }

        try {
            $loanTransactions = \App\Models\LoanTransaction::whereIn('loan_id', isset($loans) ? $loans->pluck('id') : [])->count();
            $deletionOrder[] = [
                'name' => 'Loan Transactions',
                'model' => 'LoanTransaction',
                'count' => $loanTransactions,
                'route' => 'admin.sacco.delete-related',
                'icon' => 'fa-exchange-alt',
                'description' => 'Loan transaction records for this group',
                'dependent_on' => []
            ];
        } catch (\Exception $e) {
            // Table doesn't exist or other error
        }

        try {
            $positions = MemberPosition::where('sacco_id', $id)->get();
            $deletionOrder[] = [
                'name' => 'Member Positions',
                'model' => 'MemberPosition',
                'count' => $positions->count(),
                'route' => 'admin.sacco.delete-related',
                'icon' => 'fa-user-tag',
                'description' => 'Position records for this group',
                'dependent_on' => ['Users']
            ];
        } catch (\Exception $e) {
            // Table doesn't exist or other error
        }

        try {
            $loanSchemes = LoanScheem::where('sacco_id', $id)->get();
            $deletionOrder[] = [
                'name' => 'Loan Schemes',
                'model' => 'LoanScheem',
                'count' => $loanSchemes->count(),
                'route' => 'admin.sacco.delete-related',
                'icon' => 'fa-percent',
                'description' => 'Loan scheme configurations for this group',
                'dependent_on' => ['Loans']
            ];
        } catch (\Exception $e) {
            // Table doesn't exist or other error
        }

        try {
            $meetingSchedules = \App\Models\MeetingSchedule::where('sacco_id', $id)->count();
            $deletionOrder[] = [
                'name' => 'Meeting Schedules',
                'model' => 'MeetingSchedule',
                'count' => $meetingSchedules,
                'route' => 'admin.sacco.delete-related',
                'icon' => 'fa-calendar-alt',
                'description' => 'Meeting schedules for this group',
                'dependent_on' => ['Meetings']
            ];
        } catch (\Exception $e) {
            // Table doesn't exist or other error
        }

        try {
            $socialFunds = \App\Models\SocialFund::where('sacco_id', $id)->get();
            $deletionOrder[] = [
                'name' => 'Social Funds',
                'model' => 'SocialFund',
                'count' => $socialFunds->count(),
                'route' => 'admin.sacco.delete-related',
                'icon' => 'fa-money-bill-wave',
                'description' => 'Social fund records for this group',
                'dependent_on' => []
            ];
        } catch (\Exception $e) {
            // Table doesn't exist or other error
        }

        try {
            $orgAssociations = \App\Models\VslaOrganisationSacco::where('sacco_id', $id)->get();
            $deletionOrder[] = [
                'name' => 'Organization Associations',
                'model' => 'VslaOrganisationSacco',
                'count' => $orgAssociations->count(),
                'route' => 'admin.sacco.delete-related',
                'icon' => 'fa-building',
                'description' => 'Organization links for this group',
                'dependent_on' => []
            ];
        } catch (\Exception $e) {
            // Table doesn't exist or other error
        }

        // Return view with all data
        return Admin::content(function (Content $content) use ($sacco, $deletionOrder) {
            $content->header('Delete VSLA Group: ' . $sacco->name);
            $content->description('Please delete all associated data before deleting the group.');

            // Set the title for the page
            Admin::script('$(document).ready(function() { document.title = "Delete ' . $sacco->name . ' | ' . config('admin.title') . '"; });');

            $content->body(view('admin.delete-sacco-confirmation', [
                'sacco' => $sacco,
                'deletionOrder' => $deletionOrder,
                'header' => 'Delete VSLA Group: ' . $sacco->name,  // Add header to the view data
            ]));
        });
    }

    /**
     * Handle the deletion of related records for a specific SACCO.
     *
     * @param int $id
     * @param string $model
     * @return \Illuminate\Http\Response
     */
    public function deleteRelated($id, $model)
    {
        $sacco = Sacco::findOrFail($id);
        $modelClass = "\\App\\Models\\" . $model;
        $modelNameFormatted = preg_replace('/(?<!^)[A-Z]/', ' $0', $model);

        try {
            switch ($model) {
                case 'User':
                    $count = $modelClass::where('sacco_id', $id)->count();
                    $modelClass::where('sacco_id', $id)->update(['sacco_id' => null]);
                    break;

                case 'LoanTransaction':
                    $loanIds = \App\Models\Loan::where('sacco_id', $id)->pluck('id')->toArray();
                    $count = $modelClass::whereIn('loan_id', $loanIds)->count();
                    $modelClass::whereIn('loan_id', $loanIds)->delete();
                    break;

                case 'VslaOrganisationSacco':
                    $count = $modelClass::where('sacco_id', $id)->count();
                    $modelClass::where('sacco_id', $id)->delete();
                    break;

                default:
                    $count = $modelClass::where('sacco_id', $id)->count();
                    $modelClass::where('sacco_id', $id)->delete();
                    break;
            }

            $successMessage = "All {$count} {$modelNameFormatted} records for {$sacco->name} have been deleted successfully.";

            // Handle AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => $successMessage
                ]);
            }

            // Handle regular requests
            admin_success('Success', $successMessage);
        } catch (\Exception $e) {
            $errorMessage = "Failed to delete {$modelNameFormatted} records: {$e->getMessage()}";

            // Handle AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $errorMessage
                ], 500);
            }

            // Handle regular requests
            admin_error('Error', $errorMessage);
        }

        // For regular requests, redirect back to the confirmation page
        if (!request()->ajax()) {
            return redirect("admin/saccos/{$id}/delete-confirmation");
        }
    }

    /**
     * Handle the actual deletion of a group after all related data is removed.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $sacco = Sacco::findOrFail($id);

        // Check if any related data still exists
        $relatedDataExists = false;
        $errorMessage = '';

        // Function to safely check for related records
        $checkForRelatedRecords = function($model, $errorMsg) use (&$relatedDataExists, &$errorMessage, $id) {
            try {
                $count = $model::where('sacco_id', $id)->count();
                if ($count > 0) {
                    $relatedDataExists = true;
                    $errorMessage = $errorMsg;
                }
            } catch (\Exception $e) {
                // Table may not exist, just continue
            }
        };

        // Check for users
        $checkForRelatedRecords(User::class, 'Cannot delete Group as there are associated members. Please remove all members first.');

        // Check for cycles
        $checkForRelatedRecords(Cycle::class, 'Cannot delete Group as there are associated cycles. Please remove all cycles first.');

        // Check for meetings
        $checkForRelatedRecords(Meeting::class, 'Cannot delete Group as there are associated meetings. Please remove all meetings first.');

        // Check for transactions
        $checkForRelatedRecords(Transaction::class, 'Cannot delete Group as there are associated transactions. Please remove all transactions first.');

        // Check for loans
        $checkForRelatedRecords(Loan::class, 'Cannot delete Group as there are associated loans. Please remove all loans first.');

        // Check for positions
        $checkForRelatedRecords(MemberPosition::class, 'Cannot delete Group as there are associated positions. Please remove all positions first.');

        // Check for loan schemes
        $checkForRelatedRecords(LoanScheem::class, 'Cannot delete Group as there are associated loan schemes. Please remove all loan schemes first.');

        // Check for social funds
        try {
            $socialFundsCount = \App\Models\SocialFund::where('sacco_id', $id)->count();
            if ($socialFundsCount > 0) {
                $relatedDataExists = true;
                $errorMessage = 'Cannot delete Group as there are associated social funds. Please remove all social funds first.';
            }
        } catch (\Exception $e) {
            // Table may not exist, just continue
        }

        // Check for organization associations
        try {
            $orgAssociationsCount = \App\Models\VslaOrganisationSacco::where('sacco_id', $id)->count();
            if ($orgAssociationsCount > 0) {
                $relatedDataExists = true;
                $errorMessage = 'Cannot delete Group as there are associated organization links. Please remove all organization associations first.';
            }
        } catch (\Exception $e) {
            // Table may not exist, just continue
        }

        if ($relatedDataExists) {
            // Handle AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $errorMessage
                ], 422);
            }

            // Handle regular requests
            admin_error('Delete Failed', $errorMessage);
            return redirect("admin/saccos/{$id}/delete-confirmation");
        }

        // If we've gotten this far, it's safe to delete the group
        $saccoName = $sacco->name;

        try {
            // Set status to deleted instead of actually deleting
            $sacco->status = 'deleted';
            $sacco->save();

            // Handle AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Group {$saccoName} has been successfully marked as deleted."
                ]);
            }

            // Handle regular requests
            admin_success('Success', "Group {$saccoName} has been successfully marked as deleted.");
            return redirect('admin/saccos');
        } catch (\Exception $e) {
            // Handle AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Failed to delete group: {$e->getMessage()}"
                ], 500);
            }

            // Handle regular requests
            admin_error('Error', "Failed to delete group: {$e->getMessage()}");
            return back();
        }
    }
}

