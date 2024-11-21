<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\VslaOrganisation;
use App\Models\Sacco;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'vsla_organisation_id',
        'name',
        'description',
        'start_date',
        'end_date',
    ];

    // A project belongs to an organization
    public function organisation()
    {
        return $this->belongsTo(VslaOrganisation::class, 'vsla_organisation_id');
    }

    // A project can have many saccos (groups)
    public function saccos()
    {
        return $this->belongsToMany(Sacco::class, 'project_sacco', 'project_id', 'sacco_id');
    }
}

