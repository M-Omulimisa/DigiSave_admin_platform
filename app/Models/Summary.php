<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\User;

class Summary extends Model
{
    // Disable the default table association
    protected $table = null;

    public static function adminCount()
    {
        // Count of all users whose user_type is Admin
        return User::where('user_type', 'Admin')->count();
    }

    public static function nonAdminCount()
    {
        // Count of all users whose user_type is not Admin
        return User::where('user_type', '!=', 'Admin')->count();
    }

    public static function maleCount()
    {
        // Count of all users whose sex is Male
        return User::where('sex', 'Male')->count();
    }

    public static function femaleCount()
    {
        // Count of all users whose sex is Female
        return User::where('sex', 'Female')->count();
    }

    public static function pwdCount()
    {
        // Count of all users with pwd status true
        return User::where('pwd', true)->count();
    }

    public static function youthCount()
    {
        // Count of users who are youth (age < 30)
        return User::where('dob', '>', Carbon::now()->subYears(30))->count();
    }
}
