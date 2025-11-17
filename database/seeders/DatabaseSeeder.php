<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ensure we have a default admin account
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@hresume.com'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make('Qwerty123456@&'),
                'is_admin' => true,
                'is_recruiter' => false,
            ]
        );

        Admin::updateOrCreate(
            ['user_id' => $adminUser->id],
            ['role' => 'super_admin']
        );

        // call other seeders here
        $this->call(TemplatesData::class);
    }
}
