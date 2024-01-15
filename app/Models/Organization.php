<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'phone_number', 'address', 'unique_code'];

    // Generate a unique four-digit code for a new organization
    public static function boot()
    {
        parent::boot();

        static::creating(function ($organization) {
            do {
                $code = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            } while (self::where('unique_code', $code)->exists());

            $organization->unique_code = $code;
        });
    }    

}
