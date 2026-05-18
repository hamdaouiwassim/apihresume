<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('github_import_token')->nullable()->after('linkedin_refresh_token');
            $table->string('github_import_login', 255)->nullable()->after('github_import_token');
            $table->timestamp('github_import_connected_at')->nullable()->after('github_import_login');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['github_import_token', 'github_import_login', 'github_import_connected_at']);
        });
    }
};
