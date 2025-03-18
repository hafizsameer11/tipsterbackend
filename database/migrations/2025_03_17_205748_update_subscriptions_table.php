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
        // Schema::table('subscriptions', function (Blueprint $table) {
        //     // $table->string('google_product_id')->nullable()->after('package_id');
        //     $table->string('purchase_token')->nulklable()->after('google_product_id');
        //     $table->timestamp('expires_at')->nullable()->after('renewal_date');
        // });

        // Schema::table('users', function (Blueprint $table) {
        //     $table->boolean('vip_status')->default(false)->change(); // Update VIP status to a boolean field
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['google_product_id', 'purchase_token', 'expires_at']);
        });

        // Schema::table('users', function (Blueprint $table) {
        //     $table->string('vip_status')->default('not_active')->change(); // Revert VIP status
        // });
    }
};
