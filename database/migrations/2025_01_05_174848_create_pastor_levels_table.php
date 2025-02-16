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
        Schema::create('pastor_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('licence_id')->nullable(); // Relación con pastor_licences
            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('number')->nullable();
            $table->integer('anosmin')->nullable(); // Años mínimos
            $table->integer('anosmax')->nullable(); // Años máximos
            $table->timestamps();

            // Clave foránea
            $table->foreign('licence_id')->references('id')->on('pastor_licences')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pastor_levels');
    }
};

