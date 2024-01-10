<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cycle extends Model
{
    use HasFactory;
    //boot 
    public static function boot()
    {
        parent::boot();
        self::deleting(function ($m) {
            throw new \Exception("Cannot delete Cycle");
        });
        self::creating(function ($m) {
            //only if created by status is active for a sacco
            if ($m->status == 'Active') {
                $old = Cycle::where('sacco_id', $m->sacco_id)->where('status', 'Active')->first();
                if ($old != null) {
                    throw new \Exception("Sacco already has an active cycle");
                }
            }
        });

        //updated
        self::updating(function ($m) {
            //only if created by status is active for a sacco
            if ($m->status == 'Active') {
                $old = Cycle::where('sacco_id', $m->sacco_id)->where('status', 'Active')->first();
                if ($old != null)
                    if ($old->id != $m->id) {
                        throw new \Exception("Sacco already has an active cycle");
                    }
            }
        });

        self::creating(function ($m) {

            return $m;
        });
    }
}
