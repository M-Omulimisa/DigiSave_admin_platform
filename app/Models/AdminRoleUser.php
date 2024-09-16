<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRoleUser extends Model
{
    // Define the table name
    protected $table = 'admin_role_users';

    // Define the fillable fields to allow mass assignment
    protected $fillable = [
        'role_id',
        'user_id',
        'created_at',
        'updated_at'
    ];

    // Define any relationships here (assuming there are role and user models)
    
    /**
     * Get the role associated with the AdminRoleUser
     */
    public function role()
    {
        return $this->belongsTo(AdminRole::class, 'role_id');
    }

    /**
     * Get the user associated with the AdminRoleUser
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // If you need to manage timestamps manually, enable them
    public $timestamps = true;
}

