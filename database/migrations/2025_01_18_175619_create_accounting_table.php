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
        Schema::create('accounting', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // Nombre de la contabilidad (C6, C5, etc.)
            $table->string('description', 100)->nullable(); // Descripción de la contabilidad
            $table->foreignId('treasury_id')->constrained('treasuries')->onDelete('cascade'); // Relación con la tesorería
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting');
    }
};