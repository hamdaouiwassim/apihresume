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
        $adminUser = User::firstOrNew(
            ['email' => 'admin@hresume.com']
        );

        // If admin exists but email is not verified, verify it
        if ($adminUser->exists && is_null($adminUser->email_verified_at)) {
            $adminUser->email_verified_at = now();
            $adminUser->save();
        }

        // Set or update admin user attributes
        $adminUser->name = 'Platform Admin';
        $adminUser->password = Hash::make('Qwerty123456@&');
        $adminUser->is_admin = true;
        $adminUser->is_recruiter = false;
        
        // Only set email_verified_at if it's null (for new accounts)
        if (is_null($adminUser->email_verified_at)) {
            $adminUser->email_verified_at = now();
        }
        
        $adminUser->save();

        Admin::updateOrCreate(
            ['user_id' => $adminUser->id],
            ['role' => 'super_admin']
        );

        // call other seeders here
        $this->call(TemplatesData::class);
    }
}
