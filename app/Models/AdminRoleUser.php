<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminRoleUser extends Model
{
    use HasFactory;
    //table admin_role_users
    protected $table = 'admin_role_users';

    //has role
    public function role()
    {
        return $this->belongsTo(AdminRole::class, 'role_id', 'id');
    } 
}
