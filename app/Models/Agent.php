<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;

class Agent extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use Notifiable;

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
        'password', 
    ];

    protected static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            try {
                Utils::send_sms($model->phone_number, "Your DigiSave agents account has been created. Download the app from https://play.google.com/store/apps/details?id=ug.digisave");
            } catch (\Throwable $th) {
                //throw $th;
            }
        });
    }

       /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

        /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        return $this->remember_token;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    // protected $appends = [
    //     'sacco_id',
    // ];

    // public function agentAllocation()
    // {
    //     return $this->hasOne(AgentAllocation::class, 'agent_id', 'id');
    // }

    // public function getSaccoIdAttribute()
    // {
    //     return optional($this->agentAllocation)->sacco_id;
    // }

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
