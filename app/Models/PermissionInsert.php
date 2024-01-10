<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionInsert extends Model
{
    use HasFactory;
    protected $table = 'admin_user_permissions';

    protected $fillable = [
        'user_id',
        'permission_id',
    ];

    public static function createPermission($data)
    {
        // Create a new group record in the database
        $newPermission = static::create($data);

        // Return the entire data along with the ID for the newly created group
        return ['role_data' => $newPermission];
    }
}
