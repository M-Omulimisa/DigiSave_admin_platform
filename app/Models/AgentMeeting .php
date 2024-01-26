<?php
namespace App;

use App\Models\Sacco;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AgentMeeting extends Model
{
    protected $fillable = [
        'user_id',
        'sacco_id',
        'meeting_date',
        'meeting_time',
        'meeting_description',
    ];

    // Define relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sacco()
    {
        return $this->belongsTo(Sacco::class);
    }
}
