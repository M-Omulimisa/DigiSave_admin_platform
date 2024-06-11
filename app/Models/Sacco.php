<?php

namespace App\Models;

use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Sacco extends Model
{
    use HasFactory;

    public static function boot()
    {
        parent::boot();
        self::deleting(function ($m) {
            // throw new \Exception("Cannot delete Sacco");
        });
        self::created(function ($m) {
            $u = User::find($m->administrator_id);
            if ($u == null) {
                throw new \Exception("Sacco Administrator not found");
            }

            // Set the user's sacco_id to the newly created SACCO's ID
            $u->sacco_id = $m->id;
            $u->user_type = 'Admin';
            $u->status = 'Active';
            $u->sacco_join_status = 'Approved';

            // Save the user changes
            $u->save();

            // Establish a relationship with organization having ID 2
            $organization = VslaOrganisation::find(1);
            if ($organization) {
                $m->vslaOrganisation()->associate($organization);
                // You can also use $m->vsla_organisation_id = $organization->id; if needed

                // Save the changes
                $m->save();
            } else {
                throw new \Exception("Organization with ID 2 not found");
            }
        });


        //updated
        self::updated(function ($m) {
            $u = User::find($m->administrator_id);
            if ($u == null) {
                throw new \Exception("Sacco Administrator not found");
            }
            $u->sacco_id = $m->id;
            $u->user_type = 'Admin';
            $u->status = 'Active';
            $u->sacco_join_status = 'Approved';
            $u->save();
        });

        self::creating(function ($m) {

            return $m;
        });



        self::updating(function ($m) {

            $u = User::find($m->administrator_id);
            if ($u == null) {
                throw new \Exception("Sacco Administrator not found");
            }
            $u->sacco_id = $m->id;
            $u->save();

            return $m;
        });
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    //balance
    public function getBalanceAttribute()
    {
        $cycle = Cycle::where('sacco_id', $this->id)->where('status', 'Active')->first();
        if ($cycle == null) {
            return 0;
        }
        return Transaction::where([
            'user_id' => $this->administrator_id,
            'cycle_id' => $cycle->id
        ])->sum('amount');
    }

    //active cycle
    public function getActiveCycleAttribute()
    {
        return Cycle::where('sacco_id', $this->id)->where('status', 'Active')->first();
    }

    //appends
    protected $appends = [
        'balance',
        'active_cycle',
        'cycle_text',
        'SAVING',
        'SHARE',
        'LOAN',
        'SHARE_COUNT',
        'LOAN_COUNT',
        'LOAN_REPAYMENT',
        'LOAN_INTEREST',
        'FEE',
        'WITHDRAWAL',
        'FINE',
        'CYCLE_PROFIT',
        'cycle_id',
    ];

    //getter for cycle_text
    public function getCycleTextAttribute()
    {
        $cycle = Cycle::where('sacco_id', $this->id)->where('status', 'Active')->first();
        if ($cycle == null) {
            return "No active cycle";
        }
        return $cycle->name;
    }

    //getter for cycle_id
    public function getCycleIdAttribute()
    {
        $cycle = Cycle::where('sacco_id', $this->id)->where('status', 'Active')->first();
        if ($cycle == null) {
            return null;
        }
        return $cycle->id;
    }


    public function getCYCLEPROFITAttribute()
    {
        $activeCycle = $this->active_cycle;
        if ($activeCycle == null) {
            return 0;
        }

        $balance = $this->balance;
        $shareQuantity = $this->SHARE;

        if ($shareQuantity != 0) {
            $cycleProfit = $balance / $shareQuantity;
            return $cycleProfit;
        } else {
            return 0;
        }
    }


    // public function getCYCLEPROFITAttribute()
    // {
    //     $admin = Sacco::find($this->administrator_id);
    //     if ($admin == null) {
    //         return 0;
    //     }
    //     if ($this->active_cycle == null) {
    //         return 0;
    //     }
    //     //calculate the total profit
    //     $total_profit = 0;
    // }

    public function getWITHDRAWALAttribute()
    {
        $admin = Sacco::find($this->administrator_id);
        if ($admin == null) {
            return 0;
        }
        if ($this->active_cycle == null) {
            return 0;
        }
        return Transaction::where([
            'user_id' => $admin->id,
            'type' => 'WITHDRAWAL',
            'cycle_id' => $this->active_cycle->id
        ])
            ->sum('amount');
    }

    public function getLOANINTERESTAttribute()
    {
        $admin = Sacco::find($this->administrator_id);
        if ($admin == null) {
            return 0;
        }
        if ($this->active_cycle == null) {
            return 0;
        }
        return Transaction::where([
            'sacco_id' => $this->id,
            'type' => 'LOAN_INTEREST',
            'cycle_id' => $this->active_cycle->id
        ])
            ->sum('amount');
    }

    public function getFINEAttribute()
    {
        $saccoId = $this->id;
        return Transaction::where([
        'sacco_id'=>$saccoId,
        'type' => 'FINE',
    ])
        ->sum('amount');
    }

    // public function getFINEAttribute()
    // {
    //     $admin = Sacco::find($this->administrator_id);
    //     if ($admin == null) {
    //         return 0;
    //     }

    //     return Transaction::where([
    //         'user_id' => $admin->id,
    //         'type' => 'SHARE',
    //     ])
    //         ->sum('amount');
    // }

    public function getSAVINGAttribute()
    {
        $admin = Sacco::find($this->administrator_id);
        if ($admin == null) {
            return 0;
        }
        if ($this->active_cycle == null) {
            return 0;
        }
        return Transaction::where([
            'user_id' => $admin->id,
            'type' => 'SAVING',
            'cycle_id' => $this->active_cycle->id
        ])
            ->sum('amount');
    }

    public function getFEEAttribute()
    {
        $admin = Sacco::find($this->administrator_id);
        if ($admin == null) {
            return 0;
        }
        if ($this->active_cycle == null) {
            return 0;
        }
        return Transaction::where([
            'user_id' => $admin->id,
            'type' => 'FEE',
            'cycle_id' => $this->active_cycle->id
        ])
            ->sum('amount');
    }

    public function getLOANREPAYMENTAttribute()
    {
        $admin = Sacco::find($this->administrator_id);
        if ($admin == null) {
            return 0;
        }
        if ($this->active_cycle == null) {
            return 0;
        }
        return Transaction::where([
            'user_id' => $admin->id,
            'type' => 'LOAN_REPAYMENT',
            'cycle_id' => $this->active_cycle->id
        ])
            ->sum('amount');
    }

    // public function getSHAREAttribute()
    // {
    //     $admin = Sacco::find($this->administrator_id);
    //     $active_cycle = Sacco::find($this->cycle_id);
    //     if ($admin == null) {
    //         return 0;
    //     }
    //     if ($this->active_cycle == null) {
    //         return 0;
    //     }
    //     return Transaction::where([
    //         'user_id' => $admin->id,
    //         'cycle_id' => $this->$active_cycle->id
    //     ])
    //         ->sum('amount');
    // }

    public function getSHAREAttribute()
{
    $saccoId = $this->id;
    $totalTransactionAmount = $this-> balance;
    $sharePrice = $this-> share_price;

    if ($sharePrice != 0) {
        $shares = $totalTransactionAmount / $sharePrice;
        return $shares;
    } else {
        return 0;
    }
}

public function getSHARECOUNTAttribute()
{
    $activeCycle = $this->active_cycle;
    $saccoId = $this->id;

    if ($activeCycle == null) {
        return 0;
    }

    $savings = Transaction::where('sacco_id', $saccoId)
                          ->whereIn('type', ['SHARE', 'SAVING'])
                          ->sum('amount');

    $sharePrice = $this->share_price;

    if ($sharePrice != 0) {
        $shares = $savings / $sharePrice;
        return $shares;
    } else {
        return 0;
    }
}

    // public function getSHAREAttribute()
    // {
    //     $sacco = Sacco::find($this->sacco_id);
    //     if ($sacco == null) {
    //         return 0;
    //     }
    //     if ($sacco->active_cycle == null) {
    //         return 0;
    //     }

    //     $totalAmount = Transaction::where('sacco_id', $this->id)->sum('amount');

    //     $sharePrice = $this->share_price;

    //     if ($sharePrice != 0) {
    //         $shareQuantity = $totalAmount / $sharePrice;
    //         return $shareQuantity;
    //     } else {
    //         return 0;
    //     }
    // }

    public function getLOANAttribute()
{
    $admin = Sacco::find($this->administrator_id);
    $active_cycle = Sacco::find($this->cycle_id);

           if ($admin == null) {
            return 0;
        }
        if ($this->active_cycle == null) {
            return 0;
        }

    return Transaction::where([
        'sacco_id' => $this->id,
        'type' => 'LOAN',
        'cycle_id' => $active_cycle->id  // Corrected syntax
    ])->sum('amount');
}


    // public function getLOANAttribute()
    // {
    //     $admin = Sacco::find($this->administrator_id);
    //     $active_cycle = Sacco::find($this->cycle_id);

    //     if ($admin == null) {
    //         return 0;
    //     }
    //     if ($this->active_cycle == null) {
    //         return 0;
    //     }
    //     return Transaction::where([
    //         'sacco_id' => $this->id,
    //         'type' => 'LOAN',
    //         'cycle_id' => $this->$active_cycle->id
    //     ])
    //         ->sum('amount');
    // }

    // public function getSHARECOUNTAttribute()
    // {
    //     $admin = Sacco::find($this->administrator_id);
    //     $active_cycle = Sacco::find($this->cycle_id);

    //     if ($admin == null) {
    //         return 0;
    //     }
    //     if ($this->active_cycle == null) {
    //         return 0;
    //     }
    //     return ShareRecord::where([
    //         'sacco_id' => $this->id,
    //         'cycle_id' => $this->$active_cycle->id
    //     ])
    //         ->sum('number_of_shares');
    // }

    public function getLOANCOUNTAttribute()
{
    try {
        $admin = Sacco::find($this->administrator_id);
        $activeCycle = $this->active_cycle;

        if ($admin == null || $activeCycle == null) {
            return 0;
        }

        return Loan::where([
            'sacco_id' => $this->id,
            'cycle_id' => $activeCycle->id
        ])->count();
    } catch (\Exception $e) {
        // Log the error for debugging purposes
        Log::error('Error in getLOANCOUNTAttribute: ' . $e->getMessage());
        // Return 0 or handle the error based on your application's requirements
        return 0;
    }
}

}
