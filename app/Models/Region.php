<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Get the sub-regions for this region
     */
    public function subRegions()
    {
        return $this->hasMany(SubRegion::class);
    }

    /**
     * Get all districts that belong to this region through sub-regions
     */
    public function districts()
    {
        return $this->hasManyThrough(District::class, SubRegion::class);
    }
}
