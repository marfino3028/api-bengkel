<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_gateway')->default('manual')->after('payment_method');
            $table->text('snap_token')->nullable()->after('payment_gateway');
            $table->string('payment_reference')->nullable()->after('snap_token');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('payment_gateway')->default('manual')->after('payment_method');
            $table->text('snap_token')->nullable()->after('payment_gateway');
            $table->string('payment_reference')->nullable()->after('snap_token');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_gateway', 'snap_token', 'payment_reference']);
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['payment_gateway', 'snap_token', 'payment_reference']);
        });
    }
};
