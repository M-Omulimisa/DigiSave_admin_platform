<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;

class Agent extends Model
{
    protected $fillable = [
        'full_name',
        'phone_number',
        'email',
        'date_of_birth',
        'gender',
        'national_id',
        'district_id',
        'subcounty_id',
        'parish_id',
        'village_id',
    ];

    // Define relationships if needed
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function subcounty()
    {
        return $this->belongsTo(Subcounty::class);
    }

    public function parish()
{
    return $this->belongsTo(Parish::class, 'parish_id', 'parish_id'); 
}

    public function village()
    {
        return $this->belongsTo(Village::class,  'village_id', 'village_id');
    }
}
