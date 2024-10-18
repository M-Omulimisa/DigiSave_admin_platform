<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPermission extends Model
{
    // Define the table name
    protected $table = 'admin_permissions';

    // Define the fillable fields to allow mass assignment
    protected $fillable = [
        'name',
        'slug',
        'http_method',
        'http_path',
    ];

    /**
     * Define the relationship with AdminRolePermission.
     * A permission may be assigned to many roles through the `admin_role_permissions` table.
     */
    public function roles()
    {
        return $this->belongsToMany(AdminRole::class, 'admin_role_permissions', 'permission_id', 'role_id');
    }

    // You can add more methods or relationships as needed
}
