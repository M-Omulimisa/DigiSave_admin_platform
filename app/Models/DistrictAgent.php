<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class DistrictAgent extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use Notifiable;

    // Use the 'users' table for this model
    protected $table = 'users';

    protected $fillable = [
        'full_name',
        'phone_number',
        'email',
        'date_of_birth',
        'gender',
        'national_id',
        'district_id',
        'password',
    ];

    protected static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            try {
                \App\Models\AdminRoleUser::create([
                    'role_id' => 6,  // District agent role ID
                    'user_id' => $model->id,
                ]);
                // Custom SMS notification for DistrictAgent
                Utils::send_sms($model->phone_number, "Your DigiSave District Agent account has been created. Download the app from https://play.google.com/store/apps/details?id=ug.digisave");
            } catch (\Throwable $th) {
                // Handle potential errors silently
            }
        });
    }

    /**
     * Define the relationship to AdminRoleUser model.
     */
    public function adminRoleUsers()
    {
        return $this->hasMany(AdminRoleUser::class, 'user_id');
    }

    protected $appends = [
        'token',
        'district_data'
    ];

    /**
     * Get the custom token attribute.
     *
     * @return string
     */
    public function getTokenAttribute()
    {
        return $this->remember_token;
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

    /**
     * Get the district that the agent belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'id');
    }

    /**
 * Get Sacco data for the district agent.
 *
 * @return mixed
 */
public function getDistrictDataAttribute()
{
    // Ensure that the districtAllocations relationship is not null
    if ($this->districtAllocations && $this->districtAllocations->isNotEmpty()) {
        // Retrieve allocated districts using the updated relationship
        $allocatedDistricts = $this->districtAllocations->pluck('district_id');

        // Retrieve District data based on allocated districts
        return District::whereIn('id', $allocatedDistricts)->get();
    }

    // If no allocations exist, return an empty collection
    return collect([]);
}

}
