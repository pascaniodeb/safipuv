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
        Schema::create('pastor_licences', function (Blueprint $table) {
            $table->id(); // Llave primaria
            $table->string('name')->unique(); // Nombre único de la licencia
            $table->text('description')->nullable(); // Descripción opcional
            $table->timestamps(); // Timestamps para created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pastor_licences');
    }
};
