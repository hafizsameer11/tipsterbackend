<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['image_1', 'image_2', 'image_3', 'image_4']); // Remove old columns
            $table->json('images')->nullable(); // Store multiple images as JSON
        });
    }

    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('image_1')->nullable();
            $table->string('image_2')->nullable();
            $table->string('image_3')->nullable();
            $table->string('image_4')->nullable();
            $table->dropColumn('images');
        });
    }
};
