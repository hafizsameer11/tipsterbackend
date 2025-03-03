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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // The user receiving the notification
            $table->string('triggered_by_username'); // Username of the user who triggered the event
            $table->string('type'); // e.g., "like", "comment", "share"
            $table->unsignedBigInteger('post_id')->nullable(); // Related post (if applicable)
            $table->text('message'); // Notification message
            $table->boolean('is_read')->default(false); // Whether the notification is read
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
