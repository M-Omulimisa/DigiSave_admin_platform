<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;

class OrganizationSeeder extends Seeder
{
    public function run()
    {
        Organization::create([
            'name' => 'M-Omulimisa',
            'phone_number' => '+2567059753296',
            'address' => 'Mutungo',
            'agent_id' => 2
        ]);
    }
}
