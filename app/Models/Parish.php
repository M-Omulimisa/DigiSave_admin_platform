<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parish extends Model
{
    use HasFactory;

    protected $primaryKey = 'parish_id';

    protected $fillable = ['parish_name', 'subcounty_id'];

    public function villages()
    {
        return $this->hasMany(Village::class, 'parish_id', 'parish_id');
    }

    public function subcounty()
    {
        return $this->belongsTo(Subcounty::class, 'subcounty_id');
    }
    /**
 * Retrieves all parishes for a specific subcounty.
 *
 * @param int $subcountyId The ID of the subcounty.
 * @return \Illuminate\Database\Eloquent\Collection
 */
public static function getBySubcountyId($subcountyId)
{
    return self::where('subcounty_id', $subcountyId)->get();
}

}
