<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateRegionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create regions table
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Create sub-regions table with foreign key to regions
        Schema::create('sub_regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('region_id');
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
            $table->timestamps();
        });

        // Create districts table with foreign key to sub-regions
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('sub_region_id');
            $table->foreign('sub_region_id')->references('id')->on('sub_regions')->onDelete('cascade');
            $table->timestamps();
        });

        // Seed the data
        $this->seedData();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('districts');
        Schema::dropIfExists('sub_regions');
        Schema::dropIfExists('regions');
    }

    /**
     * Seed the regions, sub-regions, and districts data
     */
    private function seedData()
    {
        // Insert regions
        $regions = [
            ['name' => 'Central'],
            ['name' => 'Eastern'],
            ['name' => 'Northern'],
            ['name' => 'Western']
        ];

        foreach ($regions as $region) {
            DB::table('regions')->insert([
                'name' => $region['name'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Insert sub-regions and districts
        $regionsData = [
            'Central' => [
                'Buganda' => [
                    'Kampala', 'Wakiso', 'Mukono', 'Luwero', 'Nakasongola', 'Nakaseke', 'Kayunga',
                    'Buikwe', 'Buvuma', 'Mpigi', 'Butambala', 'Gomba', 'Mubende', 'Mityana',
                    'Kassanda', 'Kyankwanzi', 'Kiboga', 'Sembabule'
                ]
            ],
            'Eastern' => [
                'Busoga' => [
                    'Jinja', 'Iganga', 'Kamuli', 'Mayuge', 'Bugiri', 'Namutumba', 'Buysende',
                    'Luuka', 'Kaliro', 'Bugweri'
                ],
                'Bukedi' => [
                    'Tororo', 'Butaleja', 'Busia', 'Pallisa', 'Kibuku', 'Budaka'
                ],
                'Elgon' => [
                    'Mbale', 'Manafwa', 'Bududa', 'Sironko', 'Bulambuli', 'Namisindwa',
                    'Kapchorwa', 'Kween', 'Bukwo'
                ],
                'Teso' => [
                    'Soroti', 'Kumi', 'Ngora', 'Serere', 'Amuria', 'Katakwi', 'Bukesdea',
                    'Kaberamaido', 'Kalaki'
                ]
            ],
            'Northern' => [
                'Acholi' => [
                    'Gulu', 'Kitgum', 'Pader', 'Agago', 'Amuru', 'Nwoya', 'Omoro', 'Lamwo'
                ],
                'Lango' => [
                    'Lira', 'Oyam', 'Kole', 'Alebtong', 'Otuke', 'Amolatar', 'Apac', 'Kwania', 'Dokolo'
                ],
                'West Nile' => [
                    'Arua', 'Nebbi', 'Zombo', 'Maracha', 'Koboko', 'Yumbe', 'Moyo', 'Adjumani',
                    'Pakwach', 'Madi-Okollo', 'Obongi', 'Terego'
                ],
                'Karamoja' => [
                    'Moroto', 'Kotido', 'Kaabong', 'Nakapiripirit', 'Amudat', 'Napak', 'Abim', 'Nabilatuk', 'Karenga'
                ]
            ],
            'Western' => [
                'Bunyoro' => [
                    'Hoima', 'Masindi', 'Kiryandongo', 'Buliisa', 'Kikuube', 'Kagadi', 'Kakumiro'
                ],
                'Tooro' => [
                    'Fort Portal', 'Kabarole', 'Kyenjojo', 'Kyegegwa', 'Ntoroko', 'Kamwenge', 'Bundibugyp'
                ],
                'Ankole' => [
                    'Mbarara', 'Ibanda', 'Isingiro', 'Kiruhura', 'Buhweju', 'Bushenyi', 'Mitooma',
                    'Sheema', 'Ntungamo', 'Rubirizi', 'Rwampara'
                ],
                'Kigezi' => [
                    'Kabale', 'Kisoro', 'Rukungiri', 'Kanungu', 'Rubanda'
                ]
            ]
        ];

        foreach ($regionsData as $regionName => $subRegions) {
            $regionId = DB::table('regions')->where('name', $regionName)->value('id');

            foreach ($subRegions as $subRegionName => $districts) {
                // Insert sub-region
                $subRegionId = DB::table('sub_regions')->insertGetId([
                    'name' => $subRegionName,
                    'region_id' => $regionId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Insert districts for this sub-region
                foreach ($districts as $districtName) {
                    DB::table('districts')->insert([
                        'name' => $districtName,
                        'sub_region_id' => $subRegionId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
    }
}
