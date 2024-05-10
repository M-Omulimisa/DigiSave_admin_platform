<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\Agent;
use App\Models\Cycle;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use App\Models\LoanScheem;
use App\Models\Sacco;
use App\Models\User;
use App\Models\GroupInsert;
use App\Models\RolesInsert;
use App\Models\PermissionInsert;
use App\Models\MemberPosition;
use App\Models\Shareout;
use App\Models\Utils;
use App\Models\VslaOrganisationSacco;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Encore\Admin\Auth\Database\Administrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class ApiAuthController extends Controller
{

    use ApiResponser;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {

        /* $token = auth('api')->attempt([
            'username' => 'admin',
            'password' => 'admin',
        ]);
        die($token); */
        $this->middleware('auth:api', ['except' => ['login', 'register', 'agent_login', 'registerGroup', 'update_admin', 'new_position', 'updateUser', 'registerRole']]);
    }


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $query = auth('api')->user();
        return $this->success($query, $message = "Profile details", 200);
    }


    //     public function login(Request $r)
    // {
    //     if ($r->username == null || $r->password == null) {
    //         return $this->error('Username and password are required.');
    //     }

    //     $u = User::where('phone_number', $r->username)
    //         ->orWhere('email', $r->username)
    //         ->orWhere('username', $r->username)
    //         ->first();

    //     if ($u == null) {
    //         return $this->error('User account not found.');
    //     }

    //     // Ensure that the login credentials match the registration credentials
    //     if ($u->password !== $r->password) {
    //         return $this->error('Wrong credentials.');
    //     }

    //     JWTAuth::factory()->setTTL(60 * 24 * 30 * 365);

    //     $token = auth('api')->login($u); // Authenticate the user using their model instance

    //     if ($token == null) {
    //         return $this->error('Failed to authenticate user.');
    //     }

    //     $u->token = $token;
    //     $u->remember_token = $token;

    //     return $this->success($u, 'Logged in successfully.');
    // }

    public function login(Request $r)
    {
        if ($r->username == null) {
            return $this->error('Username is nullable.');
        }

        if ($r->password == null) {
            return $this->error('Password is required.');
        }

        // Check if the user exists by username, email, or phone number
        $u = User::where('username', $r->username)->first();
        if ($u == null) {
            $u = User::where('phone_number', $r->username)->first();
        }
        if ($u == null) {
            $u = User::where('email', $r->username)->first();
        }

        if ($u == null) {
            return $this->error('User account not found.');
        }

        // Verify the password
        if (!Hash::check($r->password, $u->password)) {
            return $this->error('Wrong credentials.');
        }

        // Generate JWT token
        JWTAuth::factory()->setTTL(60 * 24 * 30 * 365);

        $token = auth('api')->attempt([
            'username' => $u->username,
            'password' => $r->password,
        ]);

        if ($token == null) {
            return $this->error('Failed to authenticate user.');
        }

        // Attach token to user object
        $u->token = $token;
        $u->remember_token = $token;

        return $this->success($u, 'Logged in successfully.');
    }


    //    public function login(Request $r)
    //     {
    //         if ($r->username == null) {
    //             return $this->error('Username is nullable.');
    //         }

    //         if ($r->password == null) {
    //             return $this->error('Password is required.');
    //         }

    //         // die($r->password);

    //         // $r->username = trim($r->username);

    //         $u = User::where('username', $r->username)->first();
    //         if ($u == null) {
    //             $u = User::where('phone_number', $r->username)
    //                 ->first();
    //         }
    //         // die($u->id);
    //         if ($u == null) {
    //             $u = User::where('email', $r->username)->first();
    //         }

    //         $phone_number = $r->username;

    //         // if ($u == null) {

    //         //     $phone_number = Utils::prepare_phone_number($r->username);


    //         //     if (Utils::phone_number_is_valid($phone_number)) {

    //         //         $u = User::where('phone_number', $phone_number)->first();

    //         //         if ($u == null) {
    //         //             $u = User::where('username', $phone_number)
    //         //                 ->first();
    //         //         }
    //         //     }
    //         // }


    //         if ($u == null) {
    //             return $this->error('Group account not found for (' . $phone_number . '.)');
    //         }


    //         JWTAuth::factory()->setTTL(60 * 24 * 30 * 365);

    //         $token = auth('api')->attempt([
    //             'id' => $u->id,
    //             'password' => trim($r->password),
    //         ]);


    //         if ($token == null) {
    //             return $this->error('Wrong credentials.');
    //         }



    //         $u->token = $token;
    //         $u->remember_token = $token;

    //         return $this->success($u, 'Logged in successfully.');
    //     }

    // public function login(Request $r)
    // {
    //     if ($r->username == null) {
    //         return $this->error('Username is nullable.');
    //     }

    //     if ($r->password == null) {
    //         return $this->error('Password is required.');
    //     }

    //     // die($r->password);

    //     // $r->username = trim($r->username);

    //     $u = User::where('phone_number', $r->username)->first();
    //     if ($u == null) {
    //         $u = User::where('email', $r->username)
    //             ->first();
    //     }
    //     // die($u->id);
    //     if ($u == null) {
    //         $u = User::where('username', $r->username)->first();
    //     }


    //     if ($u == null) {

    //         // $phone_number = Utils::prepare_phone_number($r->username);
    //         $phone_number = $r->username;



    //         if (Utils::phone_number_is_valid($phone_number)) {

    //             $u = User::where('phone_number', $phone_number)->first();

    //             if ($u == null) {
    //                 $u = User::where('username', $phone_number)
    //                     ->first();
    //             }
    //         }
    //     }


    //     if ($u == null) {
    //         return $this->error('User account not found (' . $phone_number . '.)');
    //     }


    //     JWTAuth::factory()->setTTL(60 * 24 * 30 * 365);

    //     $token = auth('api')->attempt([
    //         'id' => $u->id,
    //         'password' => trim($r->password),
    //     ]);


    //     if ($token == null) {
    //         return $this->error('Wrong credentials.');
    //     }



    //     $u->token = $token;
    //     $u->remember_token = $token;

    //     return $this->success($u, 'Logged in successfully.');
    // }


    public function verify_user(Request $request)
    {
        // Get the logged-in admin
        $admin = auth('api')->user();
        if ($admin == null) {
            return $this->error('User not found.');
        }

        $loggedIn = Administrator::find($admin->id);
        if ($loggedIn == null) {
            return $this->error('User not found.');
        }
        $sacco = Sacco::find($loggedIn->sacco_id);

        if ($sacco == null) {
            return $this->error('VSLA group not found.');
        }
        $sacco_id = $sacco->id;

        // Get the input parameters
        $username = $request->username;
        $passcode = $request->passcode;
        $position_id = $request->position_id;

        // Find the user with the given parameters
        $user = User::where('sacco_id', $sacco_id)
            ->where('username', $username)
            ->where('position_id', $position_id)
            ->first();

        if (!$user) {
            return $this->error('User not found or invalid credentials.');
        }

        JWTAuth::factory()->setTTL(60 * 24 * 30 * 365);

        $token = auth('api')->attempt([
            'id' => $user->id,
            'password' => trim($request->password),
        ]);


        if ($token == null) {
            return $this->error('Wrong credentials.');
        }



        $user->token = $token;
        $user->remember_token = $token;

        // If the user is found, return success response
        return $this->success($user, 'Logged in successfully.');
    }



    public function agent_login(Request $r)
    {
        if ($r->username == null) {
            return $this->error('Username is nullable.');
        }

        if ($r->password == null) {
            return $this->error('Password is required.');
        }

        $r->username = trim($r->username);

        $u = User::where('phone_number', $r->username)->orWhere('email', $r->username)->first();

        if ($u == null) {
            // Normalize and check phone number
            $phone_number = Utils::prepare_phone_number($r->username);

            if (Utils::phone_number_is_valid($phone_number)) {
                $u = User::where('phone_number', $phone_number)->orWhere('username', $phone_number)->first();
            }
        }

        if ($u == null) {
            return $this->error('User account not found (' . $r->username . ').');
        }

        // Check if the user type corresponds to the 'agent' role
        $agentRoleId = $u->user_type;

        $agentRole = AdminRole::where('id', $agentRoleId)->where('name', 'agent')->first();

        if (!$agentRole) {
            return $this->error('You do not have permission to log in as an agent.');
        }

        JWTAuth::factory()->setTTL(60 * 24 * 30 * 365);

        $token = auth('api')->attempt([
            'id' => $u->id,
            'password' => trim($r->password),
        ]);

        if ($token == null) {
            return $this->error('Wrong credentials.');
        }

        $u->token = $token;
        $u->remember_token = $token;

        return $this->success($u, 'Logged in successfully.');
    }


    //     public function agent_login(Request $request)
    // {
    //     // Trim whitespace from input parameters
    //     $request->merge([
    //         'phone_number' => trim($request->phone_number),
    //         'password' => trim($request->password),
    //     ]);

    //     $validator = Validator::make($request->all(), [
    //         'phone_number' => 'required',
    //         'password' => 'required',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->errors()], 400);
    //     }

    //     $user = User::where('phone_number', $request->phone_number)
    //                 ->whereHas('adminRole', function ($query) {
    //                     $query->where('name', 'agent');
    //                 })
    //                 ->first();

    //     $phone_number = Utils::prepare_phone_number($request->phone_number);

    //     if (!$user) {
    //         return $this->error('User account not found (' . $phone_number . ').');
    //     }

    //     // Check the provided password against the hashed password in the database
    //     if (Hash::check($request->password, $user->password)) {
    //         Auth::login($user);

    //         // Generate JWT token
    //         $token = JWTAuth::fromUser($user);
    //         $user->setRememberToken($token);

    //         $user->save();

    //         return $this->success($user, 'Logged in successfully.');
    //     } else {
    //         return $this->error('Wrong credentials.');
    //     }
    // }




    public function new_position(Request $request)
    {
        // Check if the user is authenticated
        $admin = auth('api')->user();
        if ($admin == null) {
            $adminId = $request->input('admin_id');
            $admin = User::find($adminId);
            $loggedInAdmin = Administrator::find($admin->id);
        }
        if ($loggedInAdmin == null) {
            // Handle case where requested admin user is not found
            return $this->error('Requested admin user not found.');

            // Check if the authenticated user has the same sacco_id as the requested admin
            if ($admin->sacco_id !== $loggedInAdmin->sacco_id) {
                return $this->error('You do not have permission to perform this action.');
            }
        }

        // Continue with the logic for creating a new position in the specified sacco
        $saccoId = $request->input('sacco_id');
        $sacco = Sacco::find($saccoId);

        // Check if the Sacco model is found
        if ($sacco == null) {
            // Handle case where Sacco is not found
            return $this->error('Sacco not found.');
        }

        // Validate incoming request data
        $validatedData = $request->validate([
            'name' => 'required|string|unique:positions,name,NULL,id,sacco_id,' . $sacco->id,
        ]);

        // Create the position
        $position = new MemberPosition();

        // Assign values from the request data
        $position->sacco_id = $sacco->id;
        $position->name = $request->input('name');

        try {
            $position->save();
        } catch (\Throwable $th) {
            return $this->error('Failed to create position: ' . $th->getMessage());
        }

        return $this->success(
            $position,
            $message = "Position created successfully",
            200
        );
    }

    public function create_scheme(Request $request)
    {
        $admin = auth('api')->user();
        if ($admin == null) {
            return $this->error('User not found.');
        }

        $loggedIn = Administrator::find($admin->id);
        if ($loggedIn == null) {
            return $this->error('User not found.');
        }
        $sacco = Sacco::find($loggedIn->sacco_id);

        if ($sacco == null) {
            return $this->error('Sacco not found.');
        }
        // Create a new LoanScheme instance
        $loanScheme = new LoanScheem();

        // Assign values from the request data
        $loanScheme->sacco_id = $sacco->id;
        $loanScheme->name = $request->input('name');
        $loanScheme->description = $request->input('description');
        $loanScheme->initial_interest_type = $request->input('initial_interest_type');
        $loanScheme->initial_interest_flat_amount = $request->input('initial_interest_flat_amount');
        $loanScheme->initial_interest_percentage = $request->input('initial_interest_percentage');
        $loanScheme->bill_periodically = $request->input('bill_periodically');
        $loanScheme->billing_period = $request->input('billing_period');
        $loanScheme->periodic_interest_type = $request->input('periodic_interest_type');
        $loanScheme->periodic_interest_percentage = $request->input('periodic_interest_percentage');
        $loanScheme->periodic_interest_flat_amount = $request->input('periodic_interest_flat_amount');
        $loanScheme->min_amount = $request->input('min_amount');
        $loanScheme->max_amount = $request->input('max_amount');
        $loanScheme->min_balance = $request->input('min_balance');
        $loanScheme->max_balance = $request->input('max_balance');

        try {
            $loanScheme->save();
        } catch (\Throwable $th) {
            return $this->error('Failed to create loan scheme: ' . $th->getMessage());
        }

        return $this->success(
            $loanScheme,
            $message = "Loan scheme created successfully",
            200
        );
    }

    public function member_update(Request $request)
    {
        $admin = auth('api')->user();
        if ($admin == null) {
            return $this->error('User not found.');
        }

        $loggedIn = Administrator::find($admin->id);
        if ($loggedIn == null) {
            return $this->error('User not found.');
        }
        $sacco = Sacco::find($loggedIn->sacco_id);

        if ($sacco == null) {
            return $this->error('Sacco not found.');
        }

        if (!isset($request->task)) {
            return $this->error('Task is missing.');
        }

        $task = $request->task;

        if (($task != 'Edit') && ($task != 'Create')) {
            return $this->error('Invalid task.');
        }

        // Check if phone number is provided before validating
        if (isset($request->phone_number) && !empty($request->phone_number)) {
            // $phone_number = Utils::prepare_phone_number($request->phone_number);
            $phone_number = $request->phone_number;
            // if (!Utils::phone_number_is_valid($phone_number)) {
            //     return $this->error('Error phone number.');
            // }
        }

        $account = null;
        if ($task == 'Edit') {
            if ($request->id == null) {
                return $this->error('User id is missing.');
            }
            $acc = Administrator::find($request->id);
            if ($acc == null) {
                return $this->error('User not found.');
            }
            $old = Administrator::where('phone_number', $phone_number)
                ->where('id', '!=', $request->id)
                ->first();
            if ($old != null) {
                return $this->error('User with same phone number already exists. ' . $old->id . ' ' . $old->phone_number . ' ' . $old->first_name . ' ' . $old->last_name);
            }
        } else {

            $old = Administrator::where('phone_number', $phone_number)
                ->first();
            if ($old != null) {
                return $this->error('User with same phone number already exists.');
            }

            $acc = new Administrator();
            $acc->sacco_id = $sacco->id;
        }

        if (
            $request->first_name == null ||
            strlen($request->first_name) < 2
        ) {
            return $this->error('First name is missing.');
        }
        //validate all
        if (
            $request->last_name == null ||
            strlen($request->last_name) < 2
        ) {
            return $this->error('Last name is missing.');
        }

        //validate all
        if (
            $request->sex == null ||
            strlen($request->sex) < 2
        ) {
            return $this->error('Gender is missing.');
        }

        // if (
        //     $request->campus_id == null ||
        //     strlen($request->campus_id) < 2
        // ) {
        //     return $this->error('National ID is missing.');
        // }


        $msg = "";
        $acc->first_name = $request->first_name;
        $acc->last_name = $request->last_name;
        $acc->name = $request->first_name . ' ' . $request->last_name;
        $acc->campus_id = $request->campus_id;
        $acc->phone_number = $phone_number;
        $acc->username = $phone_number;
        $acc->sex = $request->sex;
        $acc->pwd = $request->pwd;
        $acc->position_id = $request->position_id;
        $acc->district_id = $request->district_id;
        $acc->parish_id = $request->parish_id;
        $acc->village_id = $request->village_id;
        $acc->dob = $request->dob;
        $acc->address = $request->address;
        $acc->sacco_join_status = 'Approved';

        $images = [];
        if (!empty($_FILES)) {
            $images = Utils::upload_images_2($_FILES, false);
        }
        if (!empty($images)) {
            $acc->avatar = 'images/' . $images[0];
        }

        $code = 1;

        try {

            $acc->save();
        } catch (\Throwable $th) {
            $msg = $th->getMessage();
            $code = 0;
            return $this->error($msg);
        }
        return $this->success(
            $acc,
            $message = "User account updated successfully",
            $code
        );
    }

    public function update_group(Request $request)
    {
        try {
            // Find the authenticated administrator
            $admin = auth('api')->user();
            if (!$admin) {
                return $this->error('User not authenticated.', 401);
            }

            // Find the group to update
            $group = GroupInsert::find($request->id);
            if (!$group) {
                return $this->error('Group not found.', 404);
            }

            // Update group attributes without validation
            $group->fill($request->all());
            $group->save();

            // Return success response
            return $this->success($group, 'Group updated successfully');
        } catch (\Exception $e) {
            // Handle unexpected errors
            return $this->error('Failed to update group: ' . $e->getMessage(), 500);
        }
    }

    // Function to generate unique phone number based on user's info
    private function generateUniquePhoneNumber($request)
    {
        $dob = $request->dob;
        $sex = $request->sex;
        $first_name = $request->first_name;
        $last_name = $request->last_name;

        // Extract the first two letters of first name and last name
        $first_two_first = substr($first_name, 0, 2);
        $first_two_last = substr($last_name, 0, 2);

        // Construct a unique phone number based on the provided information
        $phone_number = substr($dob, 0, 4) . substr($dob, 5, 2) . substr($dob, 8, 2) . substr($sex, 0, 1) . $first_two_first . $first_two_last;

        // You may want to add more customization or error handling here if necessary

        return $phone_number;
    }

    public function update_user(Request $request)
    {
        $admin = auth('api')->user();
        if (!$admin) {

            // Extract the admin user's ID from the request data
            $adminId = $request->input('admin_id');

            // Use the admin user's ID to find the corresponding User model
            $admin = User::find($adminId);

            // Check if the admin user is found
            if ($admin == null) {
                // Handle case where admin user is not found
                return $this->error('Admin user not found.');
            }
        }



        $loggedIn = Administrator::find($admin->id);
        if ($loggedIn == null) {
            return $this->error('Admin User not found.');
        }
        $sacco = Sacco::find($loggedIn->sacco_id);

        if ($sacco == null) {

            $saccoId = $request->input('sacco_id');

            // Use the sacco_id to find the corresponding Sacco model
            $sacco = Sacco::find($saccoId);

            // Check if the Sacco model is found
            if ($sacco == null) {
                // Handle case where Sacco is not found
                return $this->error('Sacco not found.');
            }
        }

        if (!isset($request->task)) {
            return $this->error('Task is missing.');
        }

        $task = $request->task;

        if (($task != 'Edit') && ($task != 'Create')) {
            return $this->error('Invalid task.');
        }

        // If phone number is not provided, generate a unique one based on user's info
        if (!isset($request->phone_number)) {
            $phone_number = $this->generateUniquePhoneNumber($request);
        } else {
            $phone_number = Utils::prepare_phone_number($request->phone_number);
            if (!Utils::phone_number_is_valid($phone_number)) {
                return $this->error('Error phone number.');
            }
        }

        $account = null;
        if ($task == 'Edit') {
            if ($request->id == null) {
                return $this->error('User id is missing.');
            }
            $acc = Administrator::find($request->id);
            if ($acc == null) {
                return $this->error('User not found.');
            }
            $old = Administrator::where('phone_number', $phone_number)
                ->where('id', '!=', $request->id)
                ->first();
            if ($old != null) {
                return $this->error('User with same phone number already exists. ' . $old->id . ' ' . $old->phone_number . ' ' . $old->first_name . ' ' . $old->last_name);
            }
        } else {

            $old = Administrator::where('phone_number', $phone_number)
                ->first();
            if ($old != null) {
                return $this->error('User with same phone number already exists.');
            }

            $acc = new Administrator();
            $acc->sacco_id = $sacco->id;
        }

        if (
            $request->first_name == null ||
            strlen($request->first_name) < 2
        ) {
            return $this->error('First name is missing.');
        }
        //validate all
        if (
            $request->last_name == null ||
            strlen($request->last_name) < 2
        ) {
            return $this->error('Last name is missing.');
        }

        //validate all
        if (
            $request->sex == null ||
            strlen($request->sex) < 2
        ) {
            return $this->error('Gender is missing.');
        }

        // if (
        //     $request->campus_id == null ||
        //     strlen($request->campus_id) < 2
        // ) {
        //     return $this->error('National ID is missing.');
        // }

        $msg = "";
        $acc->first_name = $request->first_name;
        $acc->last_name = $request->last_name;
        $acc->name = $request->first_name . ' ' . $request->last_name;
        $acc->campus_id = $request->campus_id;
        $acc->phone_number = $phone_number;
        $acc->username = $phone_number;
        $acc->sex = $request->sex;
        $acc->pwd = $request->pwd;
        $acc->location_lat = $request->location_lat;
        $acc->location_long = $request->location_long;
        $acc->position_id = $request->position_id;
        $acc->district_id = $request->district_id;
        $acc->parish_id = $request->parish_id;
        $acc->village_id = $request->village_id;
        $acc->dob = $request->dob;
        $acc->address = $request->address;
        $acc->sacco_join_status = 'Approved';

        $images = [];
        if (!empty($_FILES)) {
            $images = Utils::upload_images_2($_FILES, false);
        }
        if (!empty($images)) {
            $acc->avatar = 'images/' . $images[0];
        }

        $code = 1;

        try {

            $acc->save();
            $amount = abs($sacco->register_fee);

            try {
                DB::beginTransaction();
                //add balance to sacc account
                $transaction_sacco = new Transaction();
                $transaction_sacco->user_id = $admin->id;
                $transaction_sacco->source_user_id = $acc->id;
                $transaction_sacco->sacco_id = $acc->sacco_id;
                $transaction_sacco->type = 'REGESTRATION';
                $transaction_sacco->source_type = 'REGESTRATION';
                $transaction_sacco->amount = $amount;
                $transaction_sacco->description = "Registration fees of UGX " . number_format($amount) . " from {$acc->phone_number} - $acc->name.";
                try {
                    $transaction_sacco->save();
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }
                try {
                    $transaction_sacco->save();
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }

                DB::commit();
                return $this->success(null, "Registration fees of UGX " . number_format($amount) . " was successful. Your balance is now UGX " . number_format($admin->balance) . ".", 200);
            } catch (\Exception $e) {
                DB::rollback();
                // something went wrong
                return $this->error('Failed to save transaction, because ' . $e->getMessage() . '');
            }
        } catch (\Throwable $th) {
            $msg = $th->getMessage();
            $code = 0;
            return $this->error($msg);
        }
        return $this->success(null, $msg, $code);
        $msg = 'Account ' . $task . 'created successfully.';
        return $this->success($acc, $msg, $code);
    }


    public function update_admin(Request $request)
    {
        // Extract the admin user's ID from the request data
        $adminId = $request->input('admin_id');

        // Use the admin user's ID to find the corresponding User model
        $admin = User::find($adminId);

        // Check if the admin user is found
        if ($admin == null) {
            // Handle case where admin user is not found
            return $this->error('Admin user not found.');
        }

        $saccoId = $request->input('sacco_id');

        // Use the sacco_id to find the corresponding Sacco model
        $sacco = Sacco::find($saccoId);

        // Check if the Sacco model is found
        if ($sacco == null) {
            // Handle case where Sacco is not found
            return $this->error('Group not found.');
        }

        if (!isset($request->task)) {
            return $this->error('Task is missing.');
        }

        $task = $request->task;

        if (($task != 'Edit') && ($task != 'Create')) {
            return $this->error('Invalid task.');
        }

        $phone_number = Utils::prepare_phone_number($request->phone_number);
        if (!Utils::phone_number_is_valid($phone_number)) {
            return $this->error('Invalid phone number.');
        }

        $account = null;
        if ($task == 'Edit') {
            if ($request->id == null) {
                return $this->error('User id is missing.');
            }
            $acc = Administrator::find($request->id);
            if ($acc == null) {
                return $this->error('User not found.');
            }
            $old = Administrator::where('phone_number', $phone_number)
                ->where('id', '!=', $request->id)
                ->first();
            if ($old != null) {
                return $this->error('User with same phone number already exists. ' . $old->id . ' ' . $old->phone_number . ' ' . $old->first_name . ' ' . $old->last_name);
            }
        } else {

            $old = Administrator::where('phone_number', $phone_number)
                ->first();
            if ($old != null) {
                return $this->error('User with same phone number already exists.');
            }

            $acc = new Administrator();
            $acc->sacco_id = $sacco->id;
        }

        if (
            $request->first_name == null ||
            strlen($request->first_name) < 2
        ) {
            return $this->error('First name is missing.');
        }
        //validate all
        if (
            $request->last_name == null ||
            strlen($request->last_name) < 2
        ) {
            return $this->error('Last name is missing.');
        }

        //validate all
        if (
            $request->sex == null ||
            strlen($request->sex) < 2
        ) {
            return $this->error('Gender is missing.');
        }

        $msg = "";
        $acc->first_name = $request->first_name;
        $acc->last_name = $request->last_name;
        $acc->name = $request->first_name . ' ' . $request->last_name;
        $acc->campus_id = $request->campus_id;
        $acc->phone_number = $phone_number;
        $acc->username = $phone_number;
        $acc->sex = $request->sex;
        $acc->pwd = $request->pwd;
        $acc->position_id = $request->position_id;
        $acc->district_id = $request->district_id;
        $acc->parish_id = $request->parish_id;
        $acc->village_id = $request->village_id;
        $acc->dob = $request->dob;
        $acc->address = $request->address;
        $acc->sacco_join_status = 'Approved';

        $images = [];
        if (!empty($_FILES)) {
            $images = Utils::upload_images_2($_FILES, false);
        }
        if (!empty($images)) {
            $acc->avatar = 'images/' . $images[0];
        }

        $code = 1;



        // Generate a random 5-digit password
        $password = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $acc->password = password_hash($password, PASSWORD_DEFAULT);

        if (!$acc->save()) {
            return $this->error('Failed to create account. Please try again.');
        }

        // Send SMS with the generated password
        $message = "Success, your admin account has been created successfully. Use Phone number: $phone_number and Passcode: $password to login into your VSLA group";

        $resp = null;
        // Validate the phone number
        if (Utils::phone_number_is_valid($phone_number)) {
        try {
            $resp = Utils::send_sms($phone_number, $message);
        } catch (Exception $e) {
            return $this->error('Failed to send OTP because ' . $e->getMessage());
        }
            // Send SMS only if the phone number is valid
            // Utils::send_sms($phone_number, $message);
        }

        // if ($resp != 'success') {
        //     return $this->error('Failed to send OTP because ' . $resp);
        // }

        try {

            $acc->save();
            $amount = abs($sacco->register_fee);

            // Get group name based on SACCO ID in user
            // $groupName = $acc->sacco->name;

            // Send SMS notification to the newly registered user
            // $phone_number = $acc->phone_number;
            // $message = "Congradulations $acc->name 🎉🎉🥳, you have been successfully registered as a member of...";
            // Utils::send_sms($phone_number, $message);

            try {
                DB::beginTransaction();
                //add balance to sacc account
                $transaction_sacco = new Transaction();
                $transaction_sacco->user_id = $admin->id;
                $transaction_sacco->source_user_id = $acc->id;
                $transaction_sacco->sacco_id = $acc->sacco_id;
                $transaction_sacco->type = 'REGESTRATION';
                $transaction_sacco->source_type = 'REGESTRATION';
                $transaction_sacco->amount = $amount;
                $transaction_sacco->description = "Registration fees of UGX " . number_format($amount) . " from {$acc->phone_number} - $acc->name.";
                try {
                    $transaction_sacco->save();
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }
                try {
                    $transaction_sacco->save();
                } catch (\Throwable $th) {
                    DB::rollback();
                    return $this->error('Failed to save transaction, because ' . $th->getMessage() . '');
                }

                DB::commit();

                $message = "Use Phone number: $phone_number and Passcode: $password to login";

                // return $this->success(null, "Registration fees of UGX " . number_format($amount) . " was successful. Your balance is now UGX " . number_format($admin->balance) . ".", 200);
                return $this->success($message);
            } catch (\Exception $e) {
                DB::rollback();
                // something went wrong
                return $this->error('Failed to save transaction, because ' . $e->getMessage() . '');
            }
        } catch (\Throwable $th) {
            $msg = $th->getMessage();
            $code = 0;
            return $this->error($msg);
        }
        $message = "Success, your admin account has been created successfully. Use Phone number: $phone_number and Passcode: $password to login into your VSLA group";
        // $msg = 'Account ' . $task . 'ed successfully.';
        return $this->success($acc, $message, $code);
    }


    public function new_shareout(Request $request)
    {
        $admin = auth('api')->user();

        if ($admin == null) {
            return $this->error('User not found.');
        }

        $loggedIn = Administrator::find($admin->id);

        if ($loggedIn == null) {
            return $this->error('User not found.');
        }

        // if ($request->password == null) {
        //     return $this->error('Password is required.');
        // }

        // // Check if the provided password is correct
        // if (!Hash::check($request->password, $loggedIn->password)) {
        //     return $this->error('Incorrect password.');
        // }

        $sacco = Sacco::find($loggedIn->sacco_id);
        $cycle_id =  $sacco->cycle_id;
        if ($cycle_id == null) {
            return $this->error('No active cycle found.');
        }

        try {
            foreach ($request->shareouts as $shareoutData) {
                $shareout = new Shareout();
                $shareout->sacco_id = $sacco->id;
                $shareout->cycle_id = $cycle_id;
                $shareout->member_id = $shareoutData['user_id'];
                $shareout->shareout_amount = $shareoutData['savings'];
                $shareout->shareout_date = Carbon::now();
                $shareout->save();
            }

            $cycle = Cycle::find($cycle_id);
            $cycle->status = "Inactive";
            $cycle->save();


            return $this->success(null, 'Shareouts created successfully.');
        } catch (\Throwable $th) {
            return $this->error('Failed to create shareouts: ' . $th->getMessage());
        }
    }

    // public function new_shareout(Request $r)
    // {
    //     $admin = auth('api')->user();

    //     if ($admin == null) {
    //         return $this->error('User not found.');
    //     }

    //     $loggedIn = Administrator::find($admin->id);

    //     if ($loggedIn == null) {
    //         return $this->error('User not found.');
    //     }

    //     if ($r->password == null) {
    //         return $this->error('Password is required to shareout.');
    //     }

    //     // Check if the provided password is correct
    //     if (!Hash::check($r->password, $loggedIn->password)) {
    //         return $this->error('Incorrect password.');
    //     }

    //     $sacco = Sacco::find($loggedIn->sacco_id);

    //     $shareout = new Shareout();
    //     $shareout->sacco_id = $sacco->id;
    //     $shareout->cycle_id = $sacco->cycle_id;
    //     $shareout->member_id = $r->user_id;
    //     $shareout->shareout_amount = $r->savings;
    //     $shareout->shareout_date = Carbon::now();

    //     try {
    //         $shareout->save();
    //         return $this->success($shareout, 'Shareout created successfully.');
    //     } catch (\Throwable $th) {
    //         return $this->error('Failed to create shareout: ' . $th->getMessage());
    //     }
    // }


    // public function new_shareout(Request $r)
    // {
    //     $admin = auth('api')->user();
    //     if ($admin == null) {
    //         return $this->error('User not found.');
    //     }

    //     $loggedIn = Administrator::find($admin->id);
    //     if ($loggedIn == null) {
    //         return $this->error('User not found.');
    //     }

    //     $sacco = Sacco::find($loggedIn->sacco_id);

    //     $shareout = new Shareout();
    //     $shareout->sacco_id = $sacco->id;
    //     $shareout->cycle_id = $sacco->cycle_id;
    //     $shareout->member_id = $r->user_id;
    //     $shareout->shareout_amount = $r->savings;
    //     $shareout->shareout_date = Carbon::now();

    //     try {
    //         $shareout->save();
    //         return $this->success($shareout, 'Shareout created successfully.');
    //     } catch (\Throwable $th) {
    //         return $this->error('Failed to create shareout: ' . $th->getMessage());
    //     }
    // }


    // public function register(Request $r)
    // {
    //     if ($r->phone_number == null) {
    //         return $this->error('Phone number is required.');
    //     }

    //     $phone_number = Utils::prepare_phone_number(trim($r->phone_number));


    //     if (!Utils::phone_number_is_valid($phone_number)) {
    //         return $this->error('Invalid phone number. ' . $phone_number);
    //     }

    //     if ($r->password == null) {
    //         return $this->error('Password is required.');
    //     }

    //     if ($r->name == null) {
    //         return $this->error('Name is required.');
    //     }





    //     $u = Administrator::where('phone_number', $phone_number)->first();
    //     if ($u != null) {
    //         return $this->error('User with same phone number already exists.');
    //     }

    //     $u = Administrator::where('username', $phone_number)->first();
    //     if ($u != null) {
    //         return $this->error('User with same phone number already exists. (username)');
    //     }

    //     $u = Administrator::where('email', $phone_number)->first();
    //     if ($u != null) {
    //         return $this->error('User with same phone number already exists (email).');
    //     }

    //     $u = Administrator::where('reg_number', $phone_number)->first();
    //     if ($u != null) {
    //         return $this->error('User with same phone number already exists (reg_number).');
    //     }

    //     $user = new Administrator();

    //     $name = $r->name;

    //     $x = explode(' ', $name);

    //     if (
    //         isset($x[0]) &&
    //         isset($x[1])
    //     ) {
    //         $user->first_name = $x[0];
    //         $user->last_name = $x[1];
    //     } else {
    //         $user->first_name = $name;
    //     }

    //     $user->phone_number = $phone_number;
    //     $user->username = $phone_number;
    //     $user->reg_number = $phone_number;
    //     $user->country = $phone_number;
    //     $user->occupation = $phone_number;
    //     $user->profile_photo_large = '';
    //     $user->location_lat = '';
    //     $user->location_long = '';
    //     $user->facebook = '';
    //     $user->twitter = '';
    //     $user->linkedin = '';
    //     $user->website = '';
    //     $user->other_link = '';
    //     $user->cv = '';
    //     $user->language = '';
    //     $user->about = '';
    //     $user->address = '';
    //     // $user->position_id = '';
    //     $user->name = $name;
    //     $user->password = password_hash(trim($r->password), PASSWORD_DEFAULT);
    //     if (!$user->save()) {
    //         return $this->error('Failed to create account. Please try again.');
    //     }

    //     // Send SMS
    //     $message = "Your account has been created successfully. Phone number: $phone_number, Password: {$r->password}";

    //     $resp = null;
    //     try {
    //     $resp = Utils::send_sms($phone_number, $message);
    //     } catch (Exception $e) {
    //       return $this->error('Failed to send OTP  because ' . $e->getMessage() . '');
    //    }
    //    if ($resp != 'success') {
    //     return $this->error('Failed to send OTP  because ' . $resp . '');
    //    }

    //     $new_user = Administrator::find($user->id);
    //     if ($new_user == null) {
    //         return $this->error('Account created successfully but failed to log you in.');
    //     }
    //     Config::set('jwt.ttl', 60 * 24 * 30 * 365);

    //     $token = auth('api')->attempt([
    //         'username' => $phone_number,
    //         'password' => trim($r->password),
    //     ]);

    //     $new_user->token = $token;
    //     $new_user->remember_token = $token;
    //     return $this->success($new_user, 'Account created successfully.');
    // }

    public function register(Request $r)
    {
        if ($r->phone_number == null) {
            return $this->error('Phone number is required.');
        }

        $phone_number = Utils::prepare_phone_number(trim($r->phone_number));

        if (!Utils::phone_number_is_valid($phone_number)) {
            return $this->error('Register error phone number. ' . $phone_number);
        }

        if ($r->name == null) {
            return $this->error('Name is required.');
        }

        // $u = Administrator::where('phone_number', $phone_number)->first();
        // if ($u != null) {
        //     return $this->error('User with the same phone number already exists.');
        // }

        $user = new Administrator();

        $name = $r->name;
        $x = explode(' ', $name);
        if (isset($x[0]) && isset($x[1])) {
            $user->first_name = $x[0];
            $user->last_name = $x[1];
        } else {
            $user->first_name = $name;
        }

        $lat = $r->latitude;
        $lon = $r->longitude;

        // Generate 8-digit code combining phone number digits, current year, and random letters
        $code = substr($phone_number, 3, 3) . date('Y') . strtoupper(Str::random(2));
        $user->username = $code;

        $user->phone_number = $code;
        $user->reg_number = $code;
        $user->country = "Uganda";
        $user->occupation = $phone_number;
        $user->profile_photo_large = '';
        $user->location_lat = $lat;
        $user->location_long = $lon;
        $user->facebook = '';
        $user->twitter = '';
        $user->linkedin = '';
        $user->website = '';
        $user->other_link = '';
        $user->cv = '';
        $user->language = '';
        $user->about = '';
        $user->address = '';
        $user->name = $name;

        // Generate a random 5-digit password
        // $password = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $user->password = Hash::make($code);

        if (!$user->save()) {
            return $this->error('Failed to create account. Please try again.');
        }

        // Send SMS with the generated password
        $message = "Group $name been created successfully. Use $code to access your group";

        $resp = null;
        try {
            $resp = Utils::send_sms($phone_number, $message);
        } catch (Exception $e) {
            return $this->error('Failed to send OTP because ' . $e->getMessage());
        }

        // if ($resp != 'success') {
        //     return $this->error('Failed to send OTP because ' . $resp);
        // }

        $new_user = Administrator::find($user->id);
        if ($new_user == null) {
            return $this->error('Account created successfully but failed to log you in.');
        }

        // Config::set('jwt.ttl', 60 * 24 * 30 * 365);

        // $token = auth('api')->attempt([
        //     'username' => $code,
        //     'password' => $code,
        // ]);

        // $new_user->token = $token;
        // $new_user->remember_token = $token;
        $new_user->setAttribute('access_code', $code);
        return $this->success($new_user, 'Account created successfully.');
        // return $this->success($new_user, 'Account created successfully.');
    }


    public function registerGroup(Request $request)
    {
        try {
            // Validate request inputs
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'share_price' => 'nullable|numeric',
                'register_fee' => 'nullable|numeric',
                'phone_number' => 'required|string|max:20',
                'email_address' => 'nullable|email|max:255',
                'physical_address' => 'required|string|max:255',
                'establishment_date' => 'nullable|date',
                'chairperson_name' => 'nullable|string|max:255',
                'chairperson_phone_number' => 'nullable|string|max:20',
                'chairperson_email_address' => 'nullable|email|max:255',
                'mission' => 'nullable|string|max:500',
                'vision' => 'nullable|string|max:500',
                'terms' => 'nullable|string|max:500',
                'administrator_id' => 'nullable|numeric',
                'uses_shares' => 'nullable|boolean'
            ]);

            // Create a new group record in the database
            $group = GroupInsert::createGroup($validatedData);

            // Get the group ID
            $groupId = $group['group_id'];

            // Create a record in vsla_organisation_sacco with vsla_organisation_id as 2
            $saccoData = [
                'vsla_organisation_id' => 1,
                'sacco_id' => $groupId,
            ];
            $saccoRecord = VslaOrganisationSacco::createSacco($saccoData);

            if (isset($group['error'])) {
                // If an error occurred during creation, return the error response
                return response()->json(['error' => $group['error']], 400);
            }

            // Return success response
            return response()->json(['message' => 'Group registered successfully', 'data' => $group], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation error occurred
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Other unexpected errors
            return response()->json(['error' => 'Failed to register group: ' . $e->getMessage()], 500);
        }
    }

    public function registerRole(Request $request)
    {
        try {
            // Validate role request inputs
            $validatedRoleData = $request->validate([
                'user_id' => 'required|string|max:255',
                'role_id' => 'required|numeric',
            ]);
            $validatedPermissionData = $request->validate([
                'user_id' => 'required|string|max:255',
                'permission_id' => 'nullable|numeric',
            ]);

            $role = RolesInsert::createRole($validatedRoleData);

            if (isset($role['error'])) {
                // If an error occurred during role creation, return the error response
                return response()->json(['error' => $role['error']], 400);
            }

            // Create a new permission record in the database
            $permission = PermissionInsert::createPermission($validatedPermissionData);

            if (isset($permission['error'])) {
                // If an error occurred during permission creation, return the error response
                return response()->json(['error' => $permission['error']], 400);
            }

            // Return success response
            return response()->json(['message' => 'Role and permission registered successfully', 'role_data' => $role, 'permission_data' => $permission], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation error occurred
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Other unexpected errors
            return response()->json(['error' => 'Failed to register role and permission: ' . $e->getMessage()], 500);
        }
    }


    public function updateUser(Request $request, $userId)
    {
        try {

            $user = Administrator::find($userId);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Update the specified fields
            $user->sacco_id = $request->input('sacco_id');
            $user->user_type = $request->input('user_type');
            $user->sacco_join_status = $request->input('sacco_join_status');


            // Save the changes to the user
            $user->save();

            // Fetch the updated user data
            $updatedUser = Administrator::find($userId);

            return response()->json(['message' => 'User updated successfully', 'data' => $updatedUser], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update user: ' . $e->getMessage()], 500);
        }
    }
}
