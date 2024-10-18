<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DistrictAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'district_id',
    ];

    /**
     * Get the district that is allocated to the agent.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'id');
    }

    /**
     * Get the agent (user) that is allocated to the district.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');  // Updated to reference the 'users' table
    }
}
