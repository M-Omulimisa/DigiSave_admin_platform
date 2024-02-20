<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrgAllocation extends Model
{
    use HasFactory;

    protected $table = 'admin_organization';

    protected $fillable = [
        'user_id',
        'vsla_organisation_id',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function organization()
    {
        return $this->belongsTo(VslaOrganisation::class, 'vsla_organisation_id');
    }
}
