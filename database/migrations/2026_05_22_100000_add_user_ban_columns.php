<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('banned_at')->nullable()->after('email_verified_at');
            $table->timestamp('banned_until')->nullable()->after('banned_at');
            $table->boolean('banned_permanently')->default(false)->after('banned_until');
            $table->text('ban_reason')->nullable()->after('banned_permanently');
            $table->foreignId('banned_by_user_id')->nullable()->after('ban_reason')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('banned_by_user_id');
            $table->dropColumn(['banned_at', 'banned_until', 'banned_permanently', 'ban_reason']);
        });
    }
};
