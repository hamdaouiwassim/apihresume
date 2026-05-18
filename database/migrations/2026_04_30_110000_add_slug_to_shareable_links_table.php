<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shareable_links', function (Blueprint $table) {
            $table->string('slug', 80)->nullable()->unique()->after('token');
        });
    }

    public function down(): void
    {
        Schema::table('shareable_links', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
