<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $allowedTemplates = ['Classic', 'Executive Split'];

        DB::table('templates')
            ->whereNotIn('name', $allowedTemplates)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: previously deleted templates cannot be restored automatically
    }
};

