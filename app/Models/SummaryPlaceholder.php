<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SummaryPlaceholder extends Model
{
    // Disable timestamps for this mock model
    public $timestamps = false;

    // Set a dummy table name to avoid table lookups
    protected $table = 'summaries_placeholder';

    // Override the newQuery method to prevent database queries
    public function newQuery(): Builder
    {
        return parent::newQuery()->whereRaw('1 = 0'); // Always returns an empty result
    }

    // Override the getKeyName method
    public function getKeyName()
    {
        return 'id'; // or any arbitrary value; it won't be used
    }
}
