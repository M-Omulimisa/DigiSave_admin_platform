<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupInsert extends Model
{
    use HasFactory;
    protected $table = 'saccos';

    protected $fillable = [
        'name',
        'share_price',
        'register_fee',
        'uses_shares',
        'phone_number',
        'email_address',
        'physical_address',
        'establishment_date',
        'registration_number',
        'chairperson_name',
        'chairperson_phone_number',
        'chairperson_email_address',
        'administrator_id',
        'mission',
        'vision',
        'about',
        'terms',
        'logo',
    ];

    public static function createGroup($data)
    {
        // Check for existing group with the same name and phone number
        // $existingGroup = static::where([
        //     'name' => $data['name'],
        //     'phone_number' => $data['phone_number'],
        // ])->first();

        // if ($existingGroup) {
        //     // If a group with the same name and phone number exists, return an error
        //     return ['error' => 'Group with the same name and phone number already exists'];
        // }

        // Create a new group record in the database
        $newGroup = static::create($data);

        // Return the entire data along with the ID for the newly created group
        return ['group_id' => $newGroup->id, 'group_data' => $newGroup];
    }
}
