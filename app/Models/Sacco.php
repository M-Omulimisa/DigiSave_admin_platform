<?php

namespace App\Models;

use Encore\Admin\Facades\Admin;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Sacco extends Model
{
    use HasFactory;


    // Define the relationship with Cycle if necessary
    public function cycles()
    {
        return $this->hasMany(Cycle::class, 'sacco_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }


     // Calculate the number of loans for male users
//      public function getLoansToMalesAttribute()
// {
//     return Transaction::where('type', 'LOAN')
//         ->whereHas('user', function ($query) {
//             $query->where('sex', 'male');
//         })
//         ->where('source_user_id', $this->id)
//         ->count();
// }

// public function getLoanSumForMalesAttribute()
// {
//     return Transaction::where('type', 'LOAN')
//         ->whereHas('user', function ($query) {
//             $query->where('sex', 'male');
//         })
//         ->where('source_user_id', $this->id)
//         ->sum('amount');
// }

// public function getLoansToFemalesAttribute()
// {
//     return Transaction::where('type', 'LOAN')
//         ->whereHas('user', function ($query) {
//             $query->where('sex', 'female');
//         })
//         ->where('source_user_id', $this->id)
//         ->count();
// }

// public function getLoanSumForFemalesAttribute()
// {
//     return Transaction::where('type', 'LOAN')
//         ->whereHas('user', function ($query) {
//             $query->where('sex', 'female');
//         })
//         ->where('source_user_id', $this->id)
//         ->sum('amount');
// }

// public function getLoansToYouthAttribute()
// {
//     return Transaction::where('type', 'LOAN')
//         ->whereHas('user', function ($query) {
//             $query->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 30');
//         })
//         ->where('source_user_id', $this->id) // Ensure the transaction source is the current user
//         ->count();
// }

// public function getLoanSumForYouthAttribute()
// {
//     return Transaction::where('type', 'LOAN')
//         ->whereHas('user', function ($query) {
//             $query->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 30');
//         })
//         ->where('source_user_id', $this->id) // Ensure the transaction source is the current user
//         ->sum('amount'); // Calculate the sum of the loan amounts
// }


// public function getTotalLoansAttribute()
// {
//     return $this->transactions()
//         ->where('type', 'LOAN')
//         ->whereHas('user', function ($query) {
//             $query->where('user_type', 'admin'); // Filter for users with user_type 'admin'
//         })
//         ->count();
// }


// public function getTotalPrincipalAttribute()
// {
//     return $this->transactions()
//         ->where('type', 'LOAN')
//         ->whereHas('user', function ($query) {
//             $query->where('user_type', 'admin'); // Filter for users with user_type 'admin'
//         })
//         ->sum('amount');
// }


// public function getTotalInterestAttribute()
// {
//     return $this->transactions()
//         ->where('type', 'LOAN_INTEREST')
//         ->whereHas('user', function ($query) {
//             $query->where('user_type', 'admin'); // Filter for users with user_type 'admin'
//         })
//         ->sum('amount');
// }


    public static function boot()
    {
        parent::boot();

        self::deleting(function ($model) {
            $model->status = 'deleted';
            $model->save();
            return false; // Prevent actual deletion
        });

        self::created(function ($m) {
            $u = User::find($m->administrator_id);
            if ($u == null) {
                throw new \Exception("Sacco Administrator not found");
            }

            $u->sacco_id = $m->id;
            $u->user_type = 'Admin';
            $u->status = 'Active';
            $u->sacco_join_status = 'Approved';
            $u->save();

            // Create a record in the pivot table
            VslaOrganisationSacco::create([
                'vsla_organisation_id' => 1, // Assuming the organization ID is 1
                'sacco_id' => $m->id
            ]);

            // Automatically create positions
            $positions = ['Chairperson', 'Secretary', 'Treasurer'];
            foreach ($positions as $position) {
                MemberPosition::create([
                    'name' => $position,
                    'sacco_id' => $m->id
                ]);
            }
        });

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
    }

    // public static function boot()
    // {
    //     parent::boot();
    //     self::deleting(function ($m) {
    //         //delete cycles that belong to this group
    //         Cycle::where([
    //             'sacco_id' => $m->id
    //         ])->delete();
    //         Transaction::where([
    //             'sacco_id' => $m->id
    //         ])->delete();
    //     });
    //     self::created(function ($m) {
    //         $u = User::find($m->administrator_id);
    //         if ($u == null) {
    //             throw new \Exception("Sacco Administrator not found");
    //         }

    //         // Set the user's sacco_id to the newly created SACCO's ID
    //         $u->sacco_id = $m->id;
    //         $u->user_type = 'Admin';
    //         $u->status = 'Active';
    //         $u->sacco_join_status = 'Approved';

    //         // Save the user changes
    //         $u->save();

    //         // Establish a relationship with organization having ID 2
    //         $organization = VslaOrganisation::find(1);
    //         if ($organization) {
    //             $m->vslaOrganisation()->associate($organization);
    //             // You can also use $m->vsla_organisation_id = $organization->id; if needed

    //             // Save the changes
    //             $m->save();
    //         } else {
    //             throw new \Exception("Organization with ID 2 not found");
    //         }
    //     });


    //     //updated
    //     self::updated(function ($m) {
    //         $u = User::find($m->administrator_id);
    //         if ($u == null) {
    //             throw new \Exception("Sacco Administrator not found");
    //         }
    //         $u->sacco_id = $m->id;
    //         $u->user_type = 'Admin';
    //         $u->status = 'Active';
    //         $u->sacco_join_status = 'Approved';
    //         $u->save();
    //     });

    //     self::creating(function ($m) {

    //         return $m;
    //     });



    //     self::updating(function ($m) {

    //         $u = User::find($m->administrator_id);
    //         if ($u == null) {
    //             throw new \Exception("Sacco Administrator not found");
    //         }
    //         $u->sacco_id = $m->id;
    //         $u->save();

    //         return $m;
    //     });
    // }

       // Define the inverse relationship with the LoanScheem model
       public function loanSchemes()
       {
           return $this->hasMany(LoanScheem::class);
       }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function vslaOrganisationSaccos()
    {
        return $this->hasMany(VslaOrganisationSacco::class, 'sacco_id');
    }

    public function vslaOrganisations()
    {
        return $this->belongsToMany(VslaOrganisation::class, 'vsla_organisation_saccos', 'sacco_id', 'vsla_organisation_id');
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

    protected $fillable = [
        'name',
        'phone_number',
        'email_address',
        'physical_address',
        'share_price',
        'created_at',
        'administrator_id',
        'logo',
    ];

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
            'sacco_id' => $saccoId,
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



    public function getSHAREAttribute()
    {
        $saccoId = $this->id;
        $totalTransactionAmount = $this->balance;
        $sharePrice = $this->share_price;

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
        $active_cycle = $this->active_cycle;

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
