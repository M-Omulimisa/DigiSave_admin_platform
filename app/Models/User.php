<?php

namespace App\Models;

use Dflydev\DotAccessData\Util;
use Encore\Admin\Form\Field\BelongsToMany;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as RelationsBelongsToMany;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'sacco_id', 
        'position_id'
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(MemberPosition::class);
    }

    //boot
    protected static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            try {
                Utils::send_sms($model->phone_number, "Your DigiSave account has been created. Download the app from https://play.google.com/store/apps/details?id=ug.digisave");
            } catch (\Throwable $th) {
                //throw $th;
            }
        });
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }


    public function getAvatarAttribute($avatar)
    {
        if (url()->isValidUrl($avatar)) {
            return $avatar;
        }

        $disk = config('admin.upload.disk');

        if ($avatar && array_key_exists($disk, config('filesystems.disks'))) {
            return Storage::disk(config('admin.upload.disk'))->url($avatar);
        }

        $default = config('admin.default_avatar') ?: '/assets/images/user.jpg';

        return admin_asset($default);
    }





    //getter for name
    public function getUserTextAttribute()
    {
        //merge first name and last name
        return $this->first_name . ' ' . $this->last_name;
    }

    //getter for balance
    public function getBalanceAttribute()
    {
        $sacco = Sacco::find($this->sacco_id);
        if ($sacco == null) {
            return 0;
        }
        if ($sacco->active_cycle == null) {
            return 0;
        }
        return Transaction::where('user_id', $this->id)
        ->where('cycle_id',  $sacco->active_cycle->id)
        ->sum('amount');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    //getter for name
    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    protected $appends = [
        'balance',
        'name',
        'user_text',
        'SAVING',
        'SHARE_COUNT',
        'SHARE',
        'LOAN',
        'LOAN_BALANCE',
        'LOAN_COUNT',
        'LOAN_REPAYMENT',
        'LOAN_INTEREST',
        'FEE',
        'WITHDRAWAL',
        'CYCLE_PROFIT',
        'cycle_id',
        'share_price',
        'share_out_share_price',
        'share_out_amount',
        'user_type_name',
        'register'
    ];


    // public function userType(): BelongsTo
    // {
    //     return $this->belongsTo(AdminRole::class, 'user_type');
    // }

    public function getUserTypeNameAttribute()
    {
        return $this->userType->name ?? null;
    }


public function getSHAREOUTSHAREPRICEAttribute()
{
    $sacco = Sacco::find($this->sacco_id);
    if ($sacco == null) {
        return 0;
    }
    if ($sacco->active_cycle == null) {
        return 0;
    }
    $isAdminUser = ($this->id === $sacco->administrator_id);

    if ($isAdminUser) {
        $adminBalance = $this->balance;
        $adminShareCount = $this->SHARE;

        if ($adminShareCount != 0) {
            $shareOutSharePrice = $adminBalance / $adminShareCount;
            return round($shareOutSharePrice);
        } else {
            return 0;
        }
    }

    return null; 
}

//Member share out
    public function getShareOutAmountAttribute()
{
    $sacco = Sacco::find($this->sacco_id);
    
    if ($sacco == null || $sacco->active_cycle == null) {
        return null;
    }

    $isAdminUser = ($this->id === $sacco->administrator_id);

    if (!$isAdminUser) {
        $adminUser = User::find($sacco->administrator_id);

        if ($adminUser) {
            $adminShareOutSharePrice = $adminUser->share_out_share_price;
            $userShare = $this->SHARE;

            if ($adminShareOutSharePrice !== null && $userShare !== null) {
                $shareOutAmount = round($adminShareOutSharePrice * $userShare);
                return $shareOutAmount;
            }
        }
    }

    return null;
}


    public function getCycleIdAttribute()
    {
        $sacco = Sacco::find($this->sacco_id);
        if ($sacco == null || $sacco->active_cycle == null) {
            return null; 
        }

        return $sacco->active_cycle->id;
    }

    public function getSharePriceAttribute()
    {
        $sacco = Sacco::find($this->sacco_id);

        if ($sacco == null || $sacco->active_cycle == null) {
            return null; 
        }

        $cycleId = $sacco->active_cycle->id;

        if ($sacco->cycle_id == $cycleId) {
            return $sacco->share_price;
        }

        return null;
    }

    //getter for CYCLE_PROFIT

    public function getCYCLEPROFITAttribute()
    {
        $sacco = Sacco::find($this->sacco_id);
        if ($sacco == null) {
            return 0;
        }
        if ($sacco->active_cycle == null) {
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
    //     $sacco = Sacco::find($this->sacco_id);
    //     if ($sacco == null) {
    //         return 0;
    //     }
    //     if ($sacco->active_cycle == null) {
    //         return 0;
    //     }
    //     return Transaction::where([
    //         'user_id' => $this->id,
    //         'type' => 'CYCLE_PROFIT',
    //         'cycle_id' => $sacco->active_cycle->id
    //     ])
    //         ->sum('amount');
    // }


    //getter for WITHDRAWAL
    public function getWITHDRAWALAttribute()
    {
        $sacco = Sacco::find($this->sacco_id);
        if ($sacco == null) {
            return 0;
        }
        if ($sacco->active_cycle == null) {
            return 0;
        }

        return Transaction::where([
            'user_id' => $this->id,
            'type' => 'WITHDRAWAL',
            'cycle_id' => $sacco->active_cycle->id
        ])
            ->sum('amount');
    }

    public function getFEEAttribute()
    {
        $sacco = Sacco::find($this->sacco_id);
        if ($sacco == null) {
            return 0;
        }
        if ($sacco->active_cycle == null) {
            return 0;
        }

        return Transaction::where([
            'user_id' => $this->id,
            'type' => 'FINE',
            'cycle_id' => $sacco->active_cycle->id
        ])
            ->sum('amount');
    }

    //getter for FEE
    // public function getFEEAttribute()
    // {
    //     $sacco = Sacco::find($this->sacco_id);
    //     if ($sacco == null) {
    //         return 0;
    //     }
    //     if ($sacco->active_cycle == null) {
    //         return 0;
    //     }

    //     return Transaction::where([
    //         'user_id' => $this->id,
    //         'type' => 'FINE',
    //     ])
    //         ->sum('amount');
    // }

    //getter for LOAN_INTEREST

    public function getLOANINTERESTAttribute()
    {
        $sacco = Sacco::find($this->sacco_id);
        info($sacco);
        if($sacco)
        {

          // Check if user_id in Transaction is the administrator_id in Sacco
          $isAdminUser = ($this->id === $sacco->administrator_id);
    
          // If user_id is the administrator_id, calculate totalAmount differently
         if ($isAdminUser) {
            $users = User::where([
                'sacco_id' => $this->sacco_id,

            ])->where('id', '!=', $sacco->administrator_id)
            ->pluck('id')
            ->toArray();
    
            $totalAmount = Transaction::whereIn('user_id', $users)
                ->where('type', ['LOAN_INTEREST'])
                ->where('cycle_id', $this->cycle_id)
                ->sum('amount');
        
    
            return $totalAmount;
         } else {
          return Transaction::where('type', ['LOAN_INTEREST'])
                ->where('cycle_id', $this->cycle_id)
                ->sum('amount');
        }
    }

    else {
        return 0;
    }

    }

    // public function getLOANINTERESTAttribute()
    // {
    //     $sacco = Sacco::find($this->sacco_id);
    //     if ($sacco == null) {
    //         return 0;
    //     }
    //     if ($sacco->active_cycle == null) {
    //         return 0;
    //     }

    //     return Transaction::where([
    //         'user_id' => $this->id,
    //         'type' => 'LOAN_INTEREST',
    //         'cycle_id' => $sacco->active_cycle->id
    //     ])
    //         ->sum('amount');
    // }

    //GETTER FOR LOAN_REPAYMENT
    public function getLOANREPAYMENTAttribute()
    {
        $sacco = Sacco::find($this->sacco_id);
        if ($sacco == null) {
            return 0;
        }
        if ($sacco->active_cycle == null) {
            return 0;
        }

        return Transaction::where([
            'user_id' => $this->id,
            'type' => 'LOAN_REPAYMENT',
            'cycle_id' => $sacco->active_cycle->id
        ])
            ->sum('amount');
    }

    //getter for SAVING
    public function getSAVINGAttribute()
    {
        $sacco = Sacco::find($this->sacco_id);
        if ($sacco == null) {
            return 0;
        }
        if ($sacco->active_cycle == null) {
            return 0;
        }

        return Transaction::where([
            'user_id' => $this->id,
            'type' => 'SAVING',
            'cycle_id' => $sacco->active_cycle->id
        ])
            ->sum('amount');
    }

     //getter for REGESTRATION
     public function getREGISTERAttribute()
     {
         $sacco = Sacco::find($this->sacco_id);
         if ($sacco == null) {
             return 0;
         }
         if ($sacco->active_cycle == null) {
             return 0;
         }
 
         return Transaction::where([
             'user_id' => $this->id,
             'type' => 'REGESTRATION',
             'cycle_id' => $sacco->active_cycle->id
         ])
             ->sum('amount');
     }

    //getter for LOAN
    public function getLOANAttribute()
    {
        $sacco = Sacco::find($this->sacco_id);
        if ($sacco == null) {
            return 0;
        }
        if ($sacco->active_cycle == null) {
            return 0;
        }

        return Transaction::where([
            'user_id' => $this->id,
            'type' => 'LOAN',
            'cycle_id' => $sacco->active_cycle->id
        ])
            ->sum('amount');
    }

    

    //getter for LOAN
    // public function getLOAN_BALANCEAttribute()
    // {
    //     $sacco = Sacco::find($this->sacco_id);
    //     if ($sacco == null) {
    //         return 0;
    //     }
    //     if ($sacco->active_cycle == null) {
    //         return 0;
    //     }

    //     return Transaction::where([
    //         'user_id' => $this->id,
    //         'type' => 'LOAN',
    //         'cycle_id' => $sacco->active_cycle->id
    //     ])
    //         ->sum('amount');
    // }

    //LOAN_BALANCE
    public function getLOANBALANCEAttribute()
    {
        $sacco = Sacco::find($this->sacco_id);
        if ($sacco == null) {
            return 0;
        }
        if ($sacco->active_cycle == null) {
            return 0;
        }
    
        $totalLoan = Transaction::where([
            'user_id' => $this->id,
            'type' => 'LOAN',
            'cycle_id' => $sacco->active_cycle->id
        ])->sum('amount');
    
        $totalRepayment = Transaction::where([
            'user_id' => $this->id,
            'type' => 'LOAN_REPAYMENT',
            'cycle_id' => $sacco->active_cycle->id
        ])->sum('amount');
    
        $loanBalance = - $totalLoan - $totalRepayment;
        
        return $loanBalance;
    }
    
    //LOAN_COUNT
    public function getLOANCOUNTAttribute()
    {
        $sacco = Sacco::find($this->sacco_id);
        if ($sacco == null) {
            return 0;
        }
        if ($sacco->active_cycle == null) {
            return 0;
        }

        return Transaction::where([
            'user_id' => $this->id,
            'cycle_id' => $sacco->active_cycle->id
        ])
            ->whereIn('type', ['LOAN'])
            ->count();
    }

    public function getSHARECOUNTAttribute()
{
    $activeCycle = $this->active_cycle;
    $saccoId = $this->id;
    
    if ($activeCycle == null) {
        return 0;
    }

    $savings = Transaction::where('user_id', $this->id)
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

    //getter for SHARE
    public function getSHAREAttribute()
{
    $sacco = Sacco::find($this->sacco_id);
    if ($sacco == null) {
        return 0;
    }
    if ($sacco->active_cycle == null) {
        return 0;
    }

    // Check if user_id in Transaction is the administrator_id in Sacco
    $isAdminUser = ($this->id === $sacco->administrator_id);

    // If user_id is the administrator_id, calculate totalAmount differently
    if ($isAdminUser) {
        $users = User::where([
            'sacco_id' => $this->sacco_id,
        ])->where('id', '!=', $sacco->administrator_id)
        ->pluck('id')
        ->toArray();
    
        $totalAmount = Transaction::whereIn('user_id', $users)
            ->where('type', ['SHARE'])
            ->where('cycle_id', $sacco->active_cycle->id)
            ->sum('amount');
    } else {
        $totalAmount = Transaction::where('user_id', $this->id)
            ->whereIn('type', ['SHARE'])
            ->where('cycle_id', $sacco->active_cycle->id)
            ->sum('amount');
    }
    

    $sharePrice = $this->share_price;

    if ($sharePrice != 0) {
        $shareQuantity = $totalAmount / $sharePrice;
        return $shareQuantity;
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

    //     $totalAmount = Transaction::where('user_id', $this->id)->sum('amount');
    
    //     $sharePrice = $this->share_price;
    
    //     if ($sharePrice != 0) {
    //         $shareQuantity = $totalAmount / $sharePrice;
    //         return $shareQuantity;
    //     } else {
    //         return 0;
    //     }
    // }    
}
