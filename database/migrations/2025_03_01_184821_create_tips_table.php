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
        Schema::create('tips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('betting_company_id');
            $table->foreign('betting_company_id')->references('id')->on('betting_companies')->onDelete('cascade');
            $table->string('codes')->nullable();
            $table->string('ods')->nullable();
            $table->string('status')->default('pending');
            $table->string('result')->default('running');
            $table->string('match_date')->nullable();
            $table->string('betting_category')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tips');
    }
};
