<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('paddle_customer_id')->nullable()->after('stripe_subscription_id');
            $table->string('paddle_subscription_id')->nullable()->after('paddle_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['paddle_customer_id', 'paddle_subscription_id']);
        });
    }
};
