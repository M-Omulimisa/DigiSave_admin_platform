<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;

class VslaOrganisationSacco extends Model
{
    use HasFactory;

    protected $table = 'vsla_organisation_sacco';

    protected $fillable = [
        'vsla_organisation_id',
        'sacco_id',
    ];

    // Define the relationships
    public function vslaOrganisation()
    {
        return $this->belongsTo(VslaOrganisation::class);
    }

    public function sacco()
    {
        return $this->belongsTo(Sacco::class);
    }

    public function organization()
    {
        return $this->belongsTo(VslaOrganisation::class, 'vsla_organisation_id');
    }

    public static function createSacco($data)
    {
        // Check if the record already exists
        $existingRecord = static::where([
            'vsla_organisation_id' => $data['vsla_organisation_id'],
            'sacco_id' => $data['sacco_id'],
        ])->first();

        if ($existingRecord) {
            // If the record already exists, return an error
            return ['error' => 'Record already exists'];
        }

        // Create a new record in the database
        $newRecord = static::create($data);

        // Return the ID and data for the newly created record
        return ['record_id' => $newRecord->id, 'record_data' => $newRecord];
    }
}
