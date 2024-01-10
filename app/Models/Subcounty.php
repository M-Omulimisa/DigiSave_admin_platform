<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcounty extends Model
{
    use HasFactory;

    protected $fillable = ['subcounty_name', 'district_id'];

    public function parishes()
    {
        return $this->hasMany(Parish::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }
}
