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
        Schema::create('offering_items', function (Blueprint $table) {
            $table->id();
        
            // Relación con el informe
            $table->foreignId('offering_report_id')->constrained()->onDelete('cascade');
        
            // Relación con el tipo de ofrenda
            $table->foreignId('offering_category_id')->constrained()->onDelete('cascade');
        
            // Montos en diferentes divisas
            $table->decimal('amount_bs', 12, 2)->default(0); // Monto en Bolívares
            $table->decimal('amount_usd', 12, 2)->default(0); // Monto en Dólares
            $table->decimal('amount_cop', 12, 2)->default(0); // Monto en Pesos
        
            // Subtotal convertido a Bs
            $table->decimal('subtotal_bs', 12, 2)->default(0);
        
            // Información de la transacción bancaria (ESPECÍFICA POR ÍTEM)
            $table->unsignedBigInteger('bank_transaction_id')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('transaction_number', 50)->nullable();
        
            // Timestamps
            $table->timestamps();
        
            // Claves foráneas
            $table->foreign('bank_transaction_id')->references('id')->on('bank_transactions')->onDelete('set null');
            $table->foreign('bank_id')->references('id')->on('banks')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offering_items');
    }
};