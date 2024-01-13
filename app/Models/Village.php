<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Village extends Model
{

    use HasFactory;

    protected $primaryKey = 'village_id';

    protected $fillable = ['village_name', 'parish_id'];

    public function parish()
    {
        return $this->belongsTo(Parish::class);
    }
}
