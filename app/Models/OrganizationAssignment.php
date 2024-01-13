<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;

class OrganizationAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'sacco_id'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function sacco()
    {
        return $this->belongsTo(Sacco::class);
    }
}
