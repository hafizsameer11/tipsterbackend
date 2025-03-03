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
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscriber_id'); // The user who is subscribing
            $table->unsignedBigInteger('subscribed_to_id'); // The user being subscribed to
            $table->timestamps();

            $table->foreign('subscriber_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('subscribed_to_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['subscriber_id', 'subscribed_to_id']); // Ensure unique subscription
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
