<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'sub_region_id'];

    /**
     * Get the sub-region that this district belongs to
     */
    public function subRegion()
    {
        return $this->belongsTo(SubRegion::class);
    }

    /**
     * Get the region that this district belongs to (through sub-region)
     */
    public function region()
    {
        return $this->belongsTo(Region::class)->through('subRegion');
    }

    /**
     * Get all users in this district
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
