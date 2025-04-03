<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubRegion extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'region_id'];

    /**
     * Get the region that this sub-region belongs to
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the districts for this sub-region
     */
    public function districts()
    {
        return $this->hasMany(District::class);
    }
}
