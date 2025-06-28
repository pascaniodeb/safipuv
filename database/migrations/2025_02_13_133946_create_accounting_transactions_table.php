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
        //Schema::create('accounting_transactions', function (Blueprint $table) {
            //$table->id();
            //$table->foreignId('accounting_id')->constrained('accountings')->onDelete('cascade'); // Relación con contabilidad
            //$table->foreignId('accounting_code_id')->constrained('accounting_codes')->onDelete('cascade'); // Relación con código contable
            //$table->foreignId('movement_id')->constrained('movements')->onDelete('cascade'); // Relación con tipo de movimiento (ingreso/egreso)
            //$table->string('currency', 3)->default('VES'); // Divisa utilizada (VES, USD, COP)
            //$table->decimal('amount', 12, 2); // Monto de la transacción
            //$table->string('description', 255)->nullable(); // Descripción de la transacción
            //$table->string('month', 7); // Formato: YYYY-MM
            //$table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Usuario que registró la transacción
            //$table->boolean('is_closed')->default(false); // Indica si la transacción está cerrada
            //$table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            //$table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            //$table->foreignId('sector_id')->nullable()->constrained()->nullOnDelete();
            //$table->string('receipt_path')->nullable(); // Ruta de la imagen del recibo o factura
            //$table->timestamps();
        //});
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //Schema::dropIfExists('accounting_transactions');
    }
};