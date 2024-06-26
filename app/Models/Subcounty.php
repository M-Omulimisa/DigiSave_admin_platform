<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcounty extends Model
{
    use HasFactory;

    protected $fillable = ['sub_county', 'district_id'];

    public function parishes()
    {
        return $this->hasMany(Parish::class);
    }

    public function district()
{
    return $this->belongsTo(District::class, 'district_id', 'id');
}

    /**
     * Retrieves all subcounties for a specific district.
     *
     * @param int $districtId The ID of the district.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByDistrictId($districtId)
    {
        return self::where('district_id', $districtId)->get();
    }
}
