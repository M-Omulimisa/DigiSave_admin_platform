<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CropProtocol extends Model
{
    use HasFactory;

    protected $fillable = [
        'crop_id',
        'step',
        'name',
        'is_before_planting',
        'is_activity_required',
        'days_before_planting',
        'days_after_planting',
        'acceptable_timeline',
        'value',
        'details',
    ];
}
