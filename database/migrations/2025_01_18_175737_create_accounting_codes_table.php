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
        Schema::create('accounting_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_id')->constrained('accountings')->onDelete('cascade'); // Relación con contabilidad
            $table->foreignId('role_id')->nullable(); // Relación opcional con roles
            $table->foreignId('movement_id')->nullable(); // Relación opcional con movimientos contables
            $table->string('code', 8)->nullable(); // Código contable
            $table->string('description', 50)->nullable(); // Descripción del código
            $table->boolean('active')->default(true); // Estado del código
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_codes');
    }
};