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
        Schema::create('current_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('position_type_id');
            $table->unsignedBigInteger('gender_id');
            $table->string('name');
            $table->string('description');
            $table->timestamps();

            $table->foreign('position_type_id')->references('id')->on('position_types')->onDelete('cascade');
            $table->foreign('gender_id')->references('id')->on('genders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('current_positions');
    }
};
