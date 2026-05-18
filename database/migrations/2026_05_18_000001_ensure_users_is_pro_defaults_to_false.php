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

        DB::table('users')->whereNull('is_pro')->update(['is_pro' => false]);
    }

    public function down(): void
    {
        // Non-destructive data fix — no rollback.
    }
};
