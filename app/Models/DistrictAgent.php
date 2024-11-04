<?php

namespace App\Models;

use App\Mail\ResetPasswordMail;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Mail;

class DistrictAgent extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use Notifiable;

    // Use the 'users' table for this model
    protected $table = 'users';

    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'date_of_birth',
        'gender',
        'national_id',
        'district_id',
        'password',
    ];

    protected static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            try {
                \App\Models\AdminRoleUser::create([
                    'role_id' => 6,  // District agent role ID
                    'user_id' => $model->id,
                ]);
                // Custom SMS notification for DistrictAgent
                Utils::send_sms($model->phone_number, "Your DigiSave District Agent account has been created. Download the app from https://play.google.com/store/apps/details?id=ug.digisave");
            } catch (\Throwable $th) {
                // Handle potential errors silently
            }
        });
    }

    /**
     * Define the relationship to AdminRoleUser model.
     */
    public function adminRoleUsers()
    {
        return $this->hasMany(AdminRoleUser::class, 'user_id');
    }

    protected $appends = [
        'token',
        'district_data'
    ];

    public static function registerDefaultAgents()
{
    $agents = [
        [
            'first_name' => 'Wrancis',
            'last_name' => 'Awesigye',
            'district' => 'MBARARA',
            'phone_number' => '0775463766',
            'email' => 'francis.twesigye@rippleeffect767.org',
        ],
        // [
        //     'first_name' => 'David',
        //     'last_name' => 'Ssegwanyi',
        //     'district' => 'MBARARA',
        //     'phone_number' => '0774677185',
        //     'email' => 'david.ssegwanyi@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Oscar',
        //     'last_name' => 'Atwine',
        //     'district' => 'BUSHENYI',
        //     'phone_number' => '0772822433',
        //     'email' => 'oscar.atwine@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Derrick',
        //     'last_name' => 'Amuku',
        //     'district' => 'IBANDA',
        //     'phone_number' => '0702328400',
        //     'email' => 'derrick.amuku@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'James',
        //     'last_name' => 'Muhumuza',
        //     'district' => 'BUSHENYI',
        //     'phone_number' => '0778522185',
        //     'email' => 'james.muhumuza@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Brian',
        //     'last_name' => 'Niwabasa',
        //     'district' => 'MITOOMA',
        //     'phone_number' => '0784499721',
        //     'email' => 'brian.niwabaasa@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Peter',
        //     'last_name' => 'Yiiki',
        //     'district' => 'KAMWENGE',
        //     'phone_number' => '0751933535',
        //     'email' => 'peter.yikii@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Parkrasio',
        //     'last_name' => 'Butesi',
        //     'district' => 'MBARARA',
        //     'phone_number' => '0777640564',
        //     'email' => 'parkrasio.tumusiime@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Yubu',
        //     'last_name' => 'Ahomugyisha',
        //     'district' => 'BUSHENYI',
        //     'phone_number' => '0777891699',
        //     'email' => 'yubu.ahomugyisha@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'John',
        //     'last_name' => 'Kakulu',
        //     'district' => 'NTUGAMO',
        //     'phone_number' => '0779602622',
        //     'email' => 'john.kakulu@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Jacob',
        //     'last_name' => 'Mugarura',
        //     'district' => 'MITOOMA',
        //     'phone_number' => '0785611528',
        //     'email' => 'jacob.mugarura@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Obed',
        //     'last_name' => 'Mugumya',
        //     'district' => 'MBARARA',
        //     'phone_number' => '0784635661',
        //     'email' => 'obed.mugumya@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Dennis',
        //     'last_name' => 'Mwesigwa',
        //     'district' => 'RUBIRIZI',
        //     'phone_number' => '0783866867',
        //     'email' => 'dennis.mwesiga@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Clement',
        //     'last_name' => 'Nuwamanya',
        //     'district' => 'IBANDA',
        //     'phone_number' => '0786132323',
        //     'email' => 'clement.nuwamanya@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Longino',
        //     'last_name' => 'Orimanya',
        //     'district' => 'ISINGIRO',
        //     'phone_number' => '0785957776',
        //     'email' => 'orimanya.longino@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Jack',
        //     'last_name' => 'Turihihi',
        //     'district' => 'KAMWENGE',
        //     'phone_number' => '0760004424',
        //     'email' => 'jack.turihihi@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Juditah',
        //     'last_name' => 'Aine',
        //     'district' => 'BUSHENYI',
        //     'phone_number' => '0778321047',
        //     'email' => 'juditah.aine@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Francis',
        //     'last_name' => 'Ariganyira',
        //     'district' => 'KAMWENGE',
        //     'phone_number' => '0779081040',
        //     'email' => 'francis.ariganyira@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Deusdedit',
        //     'last_name' => 'Arinetwe',
        //     'district' => 'MBARARA',
        //     'phone_number' => '0784834053',
        //     'email' => 'deusdedit.arinetwe@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Clovis',
        //     'last_name' => 'Basisa',
        //     'district' => 'MITOOMA',
        //     'phone_number' => '0787253696',
        //     'email' => 'clovis.basisa@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Gorreti',
        //     'last_name' => 'Asiimwe',
        //     'district' => 'MITOOMA',
        //     'phone_number' => '0784007800',
        //     'email' => 'gorreti.asiimwe@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Sarah',
        //     'last_name' => 'Batamuriza',
        //     'district' => 'RUBIRIZI',
        //     'phone_number' => '0783152050',
        //     'email' => 'sarah.batamuriza@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Emmanuel',
        //     'last_name' => 'Byarugaba',
        //     'district' => 'NTUGAMO',
        //     'phone_number' => '0782561410',
        //     'email' => 'emmanuel.byarugabu@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Glory',
        //     'last_name' => 'Kemigisha',
        //     'district' => 'ISINGIRO',
        //     'phone_number' => '0785230442',
        //     'email' => 'glory.kemigisha@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Lazarus',
        //     'last_name' => 'Okurut',
        //     'district' => 'SHEEMA',
        //     'phone_number' => '0758608468',
        //     'email' => 'lazarous.okurut@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Moses',
        //     'last_name' => 'Magemu',
        //     'district' => 'IBANDA',
        //     'phone_number' => '0773764007',
        //     'email' => 'moses.megemu@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Suzan Mirriam',
        //     'last_name' => 'Akullu',
        //     'district' => 'ISINGIRO',
        //     'phone_number' => '0702207109',
        //     'email' => 'susan.Akullu@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Samuel',
        //     'last_name' => 'Kivumbye Sajjabi',
        //     'district' => 'SHEEMA',
        //     'phone_number' => '0781436868',
        //     'email' => 'samuel.kivumbye@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Claire',
        //     'last_name' => 'Nsubuga Namutebi',
        //     'district' => 'SHEEMA',
        //     'phone_number' => '0707424881',
        //     'email' => 'claire.nsubuga@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Christine',
        //     'last_name' => 'Atuhaire',
        //     'district' => 'MBARARA',
        //     'phone_number' => '0787099559',
        //     'email' => 'Christine.Atuhaire@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Blessings',
        //     'last_name' => 'Davis',
        //     'district' => 'RUBIRIZI',
        //     'phone_number' => '0706326386',
        //     'email' => 'Davis.Blessing@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Freedom',
        //     'last_name' => 'Buhoora',
        //     'district' => 'BUSHENYI',
        //     'phone_number' => '0700905667',
        //     'email' => 'Freedom.Buhoora@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Antony',
        //     'last_name' => 'Tumusiime',
        //     'district' => 'KAMWENGE',
        //     'phone_number' => '0701511263',
        //     'email' => 'Anthony.Tumusiime@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Mable',
        //     'last_name' => 'Katusiime',
        //     'district' => 'SHEEMA',
        //     'phone_number' => '0777122072',
        //     'email' => 'Mable.Katusiime@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Ambrose',
        //     'last_name' => 'Arinda',
        //     'district' => 'NTUGAMO',
        //     'phone_number' => '0786443753',
        //     'email' => 'ambrose.arinda@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Ambrose',
        //     'last_name' => 'Ariho',
        //     'district' => 'MBARARA',
        //     'phone_number' => '0773568001',
        //     'email' => 'ambrose.ariho@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Akram',
        //     'last_name' => 'Ssuuna',
        //     'district' => 'BUSHENYI',
        //     'phone_number' => '0751151251',
        //     'email' => 'akram.ssuuna@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Alexander',
        //     'last_name' => 'Hakizimana',
        //     'district' => 'NTUGAMO',
        //     'phone_number' => '0705432069',
        //     'email' => 'alexander.hakizimana@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Richard',
        //     'last_name' => 'Mugisha',
        //     'district' => 'MBARARA',
        //     'phone_number' => '0700630324',
        //     'email' => 'richard.mugisha@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Andrew',
        //     'last_name' => 'Birikomawa',
        //     'district' => 'ISINGIRO',
        //     'phone_number' => '0705412067',
        //     'email' => 'andrew.birikomawa@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Restatuta',
        //     'last_name' => 'Arineitwe',
        //     'district' => 'MBARARA',
        //     'phone_number' => '0775225904',
        //     'email' => 'restatuta.arineitwe@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Winnie',
        //     'last_name' => 'Kyohangirwe',
        //     'district' => 'MITOOMA',
        //     'phone_number' => '0779425784',
        //     'email' => 'winnie.kyohangirwe@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Jasenti',
        //     'last_name' => 'Muhwezi',
        //     'district' => 'MITOOMA',
        //     'phone_number' => '0777875515',
        //     'email' => 'jasenti.muhwezi@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Nicholas',
        //     'last_name' => 'Nyanzi',
        //     'district' => 'IBANDA',
        //     'phone_number' => '0701472514',
        //     'email' => 'nicholas.nyanzi@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Prossy',
        //     'last_name' => 'Tumuhimbise',
        //     'district' => 'KAMWENGE',
        //     'phone_number' => '0784893437',
        //     'email' => 'prossy.tumuhimbise@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Patrick',
        //     'last_name' => 'Turyahabwe',
        //     'district' => 'SHEEMA',
        //     'phone_number' => '0778463391',
        //     'email' => 'patrick.turyahabwe@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Hildah',
        //     'last_name' => 'Twikirize',
        //     'district' => 'NTUGAMO',
        //     'phone_number' => '0750868526',
        //     'email' => 'hilda.twikirize@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Gerald',
        //     'last_name' => 'Asiimwe',
        //     'district' => 'BUSHENYI',
        //     'phone_number' => '0773367274',
        //     'email' => 'gerald.asiimwe@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Miriam',
        //     'last_name' => 'Atuheire',
        //     'district' => 'MITOOMA',
        //     'phone_number' => '0750643953',
        //     'email' => 'miriam.atuheire@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Christine',
        //     'last_name' => 'Nansubuga',
        //     'district' => 'MBARARA',
        //     'phone_number' => '0771858309',
        //     'email' => 'christine.nansubuga@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Phionah',
        //     'last_name' => 'Kampeire',
        //     'district' => 'IBANDA',
        //     'phone_number' => '0753006592',
        //     'email' => 'phionah.kampeire@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Doreen',
        //     'last_name' => 'Kasande',
        //     'district' => 'SHEEMA',
        //     'phone_number' => '0785304259',
        //     'email' => 'doreen.kasande@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Privah',
        //     'last_name' => 'Katusiime',
        //     'district' => 'NTUGAMO',
        //     'phone_number' => '0787009038',
        //     'email' => 'privah.katusiime@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Ronald',
        //     'last_name' => 'Shumbusho',
        //     'district' => 'ISINGIRO',
        //     'phone_number' => '0781546846',
        //     'email' => 'ronald.shumbusho@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Noah',
        //     'last_name' => 'Kibalama',
        //     'district' => 'NTUGAMO',
        //     'phone_number' => '0778787821',
        //     'email' => 'noah.kibalama@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Adellah',
        //     'last_name' => 'Ninsiima',
        //     'district' => 'MBARARA',
        //     'phone_number' => '0781791330',
        //     'email' => 'adella.ninsiima@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Vicent',
        //     'last_name' => 'Sseguya',
        //     'district' => 'MBARARA',
        //     'phone_number' => '0773709077',
        //     'email' => 'vicent.sseguya@rippleeffect.org',
        // ],
        // [
        //     'first_name' => 'Penina',
        //     'last_name' => 'Katendeke',
        //     'district' => 'KAMWENGE',
        //     'phone_number' => '0786984030',
        //     'email' => 'penina.katendeke@rippleeffect.org',
        // ],
    ];

    foreach ($agents as $agentData) {
        // Check if the agent already exists based on email or phone number
        $existingAgent = self::where('email', $agentData['email'])
            ->orWhere('phone_number', $agentData['phone_number'])
            ->first();

        if (!$existingAgent) {
            // Find or create the district by name
            $district = District::firstOrCreate(
                ['name' => $agentData['district']],
                ['created_at' => now(), 'updated_at' => now()] // Add additional defaults if needed
            );

            // Generate a unique, random password
            $generatedPassword = Str::random(10);

            // Create the District Agent
            $districtAgent = self::create([
                'first_name' => $agentData['first_name'],
                'last_name' => $agentData['last_name'],
                'phone_number' => $agentData['phone_number'],
                'email' => $agentData['email'],
                'district_id' => $district->id,
                'password' => Hash::make($generatedPassword), // Store the hashed password
            ]);

            // Assign role in AdminRoleUser table
            DB::table('admin_role_users')->insert([
                'user_id' => $districtAgent->id,
                'role_id' => 6, // District Agent role ID
            ]);

            // Send SMS notification with the generated password
            Utils::send_sms($districtAgent->phone_number, "Your DigiSave District Agent account has been created. Your password is: $generatedPassword");

            $platformLink = "https://digisave.m-omulimisa.com/";

                $email_info = [
                    "first_name" => $districtAgent->first_name,
                    "last_name" => $districtAgent->last_name,
                    "phone_number" => $districtAgent->phone_number,
                    "password" => $generatedPassword,
                    "platformLink" => $platformLink,
                    "org" => "DigiSave",
                    "email" => $districtAgent->email
                ];
                // Send email if the identifier is an email
                try {
                    Mail::to($districtAgent->email)->send(new ResetPasswordMail($email_info, 'emails.admin-mail')); // Specify the new view for password reset
                } catch (Exception $e) {
                    echo "Failed to send email to {$districtAgent->email}: " . $e->getMessage();
                }
        }
    }
}

    /**
     * Get the custom token attribute.
     *
     * @return string
     */
    public function getTokenAttribute()
    {
        return $this->remember_token;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        return $this->remember_token;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    /**
     * Get the district that the agent belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'id');
    }

    /**
 * Get Sacco data for the district agent.
 *
 * @return mixed
 */
public function getDistrictDataAttribute()
{
    // Ensure that the districtAllocations relationship is not null
    if ($this->districtAllocations && $this->districtAllocations->isNotEmpty()) {
        // Retrieve allocated districts using the updated relationship
        $allocatedDistricts = $this->districtAllocations->pluck('district_id');

        // Retrieve District data based on allocated districts
        return District::whereIn('id', $allocatedDistricts)->get();
    }

    // If no allocations exist, return an empty collection
    return collect([]);
}

}
