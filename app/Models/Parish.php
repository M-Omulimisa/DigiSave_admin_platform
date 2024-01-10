<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parish extends Model
{

    use HasFactory;

    protected $fillable = ['parish_name', 'subcounty_id'];

    public function villages()
    {
        return $this->hasMany(Village::class);
    }
}
