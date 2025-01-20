<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class GroupInsert extends Model
{
    use HasFactory;

    protected $table = 'saccos';

    protected $fillable = [
        'name',
        'share_price',
        'register_fee',
        'uses_shares',
        'district',
        'subcounty',
        'parish',
        'village',
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

    // public static function createGroup($data)
    // {
    //     // Use DB transaction to ensure data integrity
    //     return DB::transaction(function () use ($data) {
    //         // Create a new group record in the database
    //         $newGroup = static::create($data);

    //         // Create default positions for the group
    //         $positions = [
    //             ['name' => 'Chairperson', 'sacco_id' => $newGroup->id],
    //             ['name' => 'Secretary', 'sacco_id' => $newGroup->id],
    //             ['name' => 'Treasurer', 'sacco_id' => $newGroup->id],
    //             ['name' => 'Member', 'sacco_id' => $newGroup->id],
    //         ];

    //         // Insert positions into the database
    //         foreach ($positions as $position) {
    //             MemberPosition::create($position);
    //         }

    //         // Return the entire data along with the ID for the newly created group
    //         return ['group_id' => $newGroup->id, 'group_data' => $newGroup];
    //     });
    // }

    public static function createGroup($data)
{
    try {
        return DB::transaction(function () use ($data) {
            // Create the group
            $newGroup = static::create($data);

            // Create default positions
            $positions = [
                ['name' => 'Chairperson', 'sacco_id' => $newGroup->id],
                ['name' => 'Secretary', 'sacco_id' => $newGroup->id],
                ['name' => 'Treasurer', 'sacco_id' => $newGroup->id],
                ['name' => 'Member', 'sacco_id' => $newGroup->id],
            ];

            // Create all positions
            foreach ($positions as $position) {
                MemberPosition::create($position);
            }

            // After successful group creation and positions setup
            if ($newGroup && isset($data['user_id'])) {
                try {
                    // Create agent-group relationship
                    AgentGroup::assignGroupToAgent($data['user_id'], $newGroup->id);
                } catch (\Exception $e) {
                    \Log::error('Failed to create agent-group relationship: ' . $e->getMessage());
                    // Even if relationship creation fails, we still want to return the group
                }
            }

            return ['group_id' => $newGroup->id, 'group_data' => $newGroup];
        });
    } catch (\Exception $e) {
        \Log::error('Failed to create group: ' . $e->getMessage());
        throw $e;
    }
}
}
