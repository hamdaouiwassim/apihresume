<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TemplatesData extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        Template::create(['name' => 'Classic']);
        Template::create(['name' => 'Modern']);
        Template::create(['name' => 'Creative']);
    }
}
