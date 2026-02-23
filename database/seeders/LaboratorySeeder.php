<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Laboratory;


class LaboratorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Laboratory::create([
            'name' => 'Chemistry Lab',
            'location' => 'Building B, Room 202',
            'description' => 'Laboratory for chemical experiments.',
        ]);
    }
}
