<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parish extends Model
{
    use HasFactory;

    protected $primaryKey = 'parish_id';

    protected $fillable = ['parish_name', 'subcounty_id'];

    public function villages()
    {
        return $this->hasMany(Village::class);
    }

    public function subcounty()
    {
        return $this->belongsTo(Subcounty::class, 'subcounty_id');
    }
}
