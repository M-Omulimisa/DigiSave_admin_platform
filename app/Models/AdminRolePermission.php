<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRolePermission extends Model
{
    // Define the table name
    protected $table = 'admin_role_permissions';

    // Define the fillable fields to allow mass assignment
    protected $fillable = [
        'role_id',
        'permission_id',
        'created_at',
        'updated_at'
    ];

    /**
     * Define the relationship with AdminRole.
     */
    public function role()
    {
        return $this->belongsTo(AdminRole::class, 'role_id');
    }

    /**
     * Define the relationship with AdminPermission.
     */
    public function permission()
    {
        return $this->belongsTo(AdminPermission::class, 'permission_id');
    }

    // Timestamps are enabled by default, so there's no need to set this unless manually managing them.
}
