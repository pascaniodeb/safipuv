<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTreasuryTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('treasury_transactions', function (Blueprint $table) {
            $table->id();
            
            // Relación con la transacción de ofrendas
            $table->unsignedBigInteger('offering_transaction_id');
            
            // Relación con la tesorería
            $table->unsignedBigInteger('treasury_id');

            // Detalles de la transacción
            $table->decimal('amount', 12, 2); // Monto distribuido
            $table->decimal('percentage', 5, 2); // Porcentaje aplicado
            $table->string('month', 7); // Mes correspondiente, formato: YYYY-MM
            $table->string('remarks')->nullable(); // Observaciones o comentarios (opcional)

            // Subdivisión (opcional)
            $table->string('subdivision_name')->nullable(); // Nombre de la subdivisión, si aplica

            // Fechas de creación/actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_transactions');
    }
}