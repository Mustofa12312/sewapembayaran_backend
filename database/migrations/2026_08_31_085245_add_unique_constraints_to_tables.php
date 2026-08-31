<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unique('order_number');
            $table->unique('secure_token');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('referral_code');
        });

        Schema::table('license_keys', function (Blueprint $table) {
            $table->unique('license_key');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unique('midtrans_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['order_number']);
            $table->dropUnique(['secure_token']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['referral_code']);
        });

        Schema::table('license_keys', function (Blueprint $table) {
            $table->dropUnique(['license_key']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['midtrans_transaction_id']);
        });
    }
};
