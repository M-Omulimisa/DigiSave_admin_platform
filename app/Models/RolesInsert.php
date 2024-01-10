<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolesInsert extends Model
{
    use HasFactory;
    protected $table = 'admin_role_users';

    protected $fillable = [
        'role_id',
        'user_id',
    ];

    public static function createRole($data)
    {
        // Create a new group record in the database
        $newRole = static::create($data);

        // Return the entire data along with the ID for the newly created group
        return ['role_data' => $newRole];
    }
}
