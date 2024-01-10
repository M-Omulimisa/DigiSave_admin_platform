<?php

namespace Encore\Admin\Auth\Database;

use App\Models\Transaction;
use App\Models\UserHasProgram;
use App\Models\Utils;
use Carbon\Carbon;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Class Administrator.
 *
 * @property Role[] $roles
 */
class Administrator extends Model implements AuthenticatableContract, JWTSubject
{
    use Authenticatable;
    use HasPermissions;
    use DefaultDatetimeFormat;

    //appends for balance
    protected $appends = ['balance'];
    //getter for balance
    public function getBalanceAttribute()
    {
        return Transaction::where('user_id', $this->id)->sum('amount');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    //getter for name
    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }


    protected $fillable = ['username', 'password', 'name', 'avatar', 'created_at_text'];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $connection = config('admin.database.connection') ?: config('database.default');

        $this->setConnection($connection);

        $this->setTable(config('admin.database.users_table'));

        parent::__construct($attributes);
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($m) {
            $m->phone_number = Utils::prepare_phone_number($m->phone_number);
            if (!Utils::phone_number_is_valid($m->phone_number)) {
                throw new \Exception("Invalid phone number");
            }
            //check if phone number exists
            $u = Administrator::where('phone_number', $m->phone_number)->first();
            if ($u != null)
                if ($u != null) {
                    throw new \Exception("User with same phone number already exists.");
                }
            $m->username = $m->phone_number;

            //check if username exists
            $u = Administrator::where('username', $m->username)->first();
            if ($u != null)
                if ($u != null) {
                    throw new \Exception("User with same username already exists.");
                }

            //if email is set, validate it
            if ($m->email != null) {
                $m->email = trim($m->email);
                if (!filter_var($m->email, FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception("Invalid email address");
                }
                //check if email exists
                $u = Administrator::where('email', $m->email)->first();
                if ($u != null)
                    if ($u != null)
                        if ($u->id != $m->id) {
                            throw new \Exception("User with same email address already exists.");
                        }
            }

            $n = $m->first_name . " " . $m->last_name;
            if (strlen(trim($n)) > 2) {
                $m->name = trim($n);
            }
        });
        self::updating(function ($m) {


            $m->phone_number = Utils::prepare_phone_number($m->phone_number);
            if (!Utils::phone_number_is_valid($m->phone_number)) {
                throw new \Exception("Invalid phone number");
            }
            //check if phone number exists
            $u = Administrator::where('phone_number', $m->phone_number)->first();
            if ($u != null)
                if ($u->id != $m->id) {
                    throw new \Exception("User with same phone number already exists.");
                }
            $m->username = $m->phone_number;

            //check if username exists
            $u = Administrator::where('username', $m->username)->first();
            if ($u != null)
                if ($u->id != $m->id) {
                    throw new \Exception("User with same username already exists.");
                }

            //if email is set, validate it
            if ($m->email != null) {
                $m->email = trim($m->email);
                if (!filter_var($m->email, FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception("Invalid email address");
                }
                //check if email exists
                $u = Administrator::where('email', $m->email)->first();
                if ($u != null)
                    if ($u->id != $m->id) {
                        throw new \Exception("User with same email address already exists.");
                    }
            }

            $n = $m->first_name . " " . $m->last_name;
            if (strlen(trim($n)) > 1) {
                $m->name = trim($n);
            }
        });
    }


    /**
     * Get avatar attribute.
     *
     * @param string $avatar
     *
     * @return string
     */
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


    public function programs()
    {
        return $this->hasMany(UserHasProgram::class, 'user_id');
    }

    public function program()
    {
        $p = UserHasProgram::where(['user_id' => $this->id])->first();
        if ($p == null) {
            $p = new UserHasProgram();
            $p->name = "No program";
        }
        return $p;
    }


    public function getCreatedAtTextAttribute($name)
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }


    /**
     * A user has and belongs to many roles.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        $pivotTable = config('admin.database.role_users_table');

        $relatedModel = config('admin.database.roles_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'user_id', 'role_id');
    }

    /**
     * A User has and belongs to many permissions.
     *
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        $pivotTable = config('admin.database.user_permissions_table');

        $relatedModel = config('admin.database.permissions_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'user_id', 'permission_id');
    }
}
