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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('creator_id');
            $table->unsignedBigInteger('sector_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->unsignedBigInteger('region_id')->nullable();
            $table->enum('status', ['active', 'closed', 'archived'])->default('active');
            $table->timestamps();
            
            $table->foreign('creator_id')->references('id')->on('users');
            $table->index(['sector_id', 'district_id', 'region_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};