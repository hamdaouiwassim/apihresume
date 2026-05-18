<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_pro')) {
            return;
        }

        DB::table('users')
            ->where('is_pro', true)
            ->whereNull('email_verified_at')
            ->update(['is_pro' => false]);
    }

    public function down(): void
    {
        // Cannot restore prior Pro flags for unverified users.
    }
};
