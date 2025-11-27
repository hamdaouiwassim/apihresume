<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Console\Command;

class MakeUserAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if user with email hamdaouiwassim@gmail.com exists and make them admin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = 'hamdaouiwassim@gmail.com';
        
        $this->info("Checking for user with email: {$email}");
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found.");
            return Command::FAILURE;
        }
        
        $this->info("User found: {$user->name} (ID: {$user->id})");
        
        // Set is_admin to true
        if (!$user->is_admin) {
            $user->is_admin = true;
            $user->save();
            $this->info("✓ Set is_admin to true");
        } else {
            $this->info("✓ User already has is_admin set to true");
        }
        
        // Create Admin record if it doesn't exist
        if (!$user->admin) {
            Admin::create([
                'user_id' => $user->id,
                'role' => 'admin'
            ]);
            $this->info("✓ Created Admin record");
        } else {
            $this->info("✓ Admin record already exists");
        }
        
        $this->info("\n✅ User {$user->name} ({$email}) is now an admin!");
        
        return Command::SUCCESS;
    }
}
