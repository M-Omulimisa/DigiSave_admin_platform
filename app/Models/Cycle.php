<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cycle extends Model
{
    use HasFactory;

    // Define the relationship with transactions
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Boot method to apply the cascading delete
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($cycle) {
            // Delete all related transactions
            $cycle->transactions()->delete();
        });

        self::creating(function ($m) {
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
                if ($old != null && $old->id != $m->id) {
                    throw new \Exception("Sacco already has an active cycle");
                }
            }
        });
    }
}
