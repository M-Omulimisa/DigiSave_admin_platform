<?php

namespace Database\Seeders;

use App\Models\VslaOrganisation;
use Illuminate\Database\Seeder;

class VslaOrganisationSeeder extends Seeder
{
    public function run()
    {
        VslaOrganisation::create([
            'name' => 'International Institute of Rural Reconstruction (IIRR)',
            'phone_number' => '0414664495',
            'email' => 'ug.office@iirr.org',
            'logo' => 'images/iirr_logo.png'
        ]);
    }
}
