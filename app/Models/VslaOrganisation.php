<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VslaOrganisation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_number',
        'email',
        'unique_code',
        'logo'
    ];

    /**
     * Generate a unique code for the organisation.
     *
     * @return string
     */
    public static function generateUniqueCode()
    {
        do {
            $code = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::where('unique_code', $code)->exists());

        return $code;
    }

    /**
     * Boot function to generate unique code before saving the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($organisation) {
            $organisation->unique_code = self::generateUniqueCode();
        });
    }

    public function vslaOrganisationSaccos()
    {
        return $this->hasMany(VslaOrganisationSacco::class, 'vsla_organisation_id');
    }
}
