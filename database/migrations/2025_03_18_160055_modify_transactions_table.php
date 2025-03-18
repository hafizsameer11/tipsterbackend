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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreignId('subscription_id')->nullable()->change();
            $table->string('google_product_id')->nullable()->change();
            $table->string('order_id')->nullable()->change(); // Remove unique constraint
            $table->string('purchase_token')->nullable()->change(); // Remove unique constraint
            $table->decimal('amount', 10, 2)->nullable()->change();
            $table->string('currency')->nullable()->default('USD')->change();
            $table->timestamp('transaction_date')->nullable()->change();
            $table->text('response')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->nullable()->default('completed')->change();
        });

        // Remove unique constraints from order_id and purchase_token
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['order_id']);
            $table->dropUnique(['purchase_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
