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
        Schema::create('offering_transactions', function (Blueprint $table) {
            $table->id();

            // Relación con el tipo de ofrenda
            $table->unsignedBigInteger('offering_id');
            $table->foreign('offering_id')->references('id')->on('offerings')->onDelete('cascade');

            // Monto ingresado en diferentes divisas
            $table->decimal('amount_bs', 12, 2)->default(0); // Monto en Bolívares
            $table->decimal('amount_usd', 12, 2)->default(0); // Monto en Dólares
            $table->decimal('amount_cop', 12, 2)->default(0); // Monto en Pesos

            // Subtotal para cada ofrenda en Bs (incluye conversiones)
            $table->decimal('subtotal_bs', 12, 2)->default(0); // Subtotal de la ofrenda actual convertido a Bs

            // Relación con el pastor que está registrando la ofrenda
            $table->unsignedBigInteger('pastor_id')->nullable();
            $table->foreign('pastor_id')->references('id')->on('users')->onDelete('cascade');

            // Mes al que corresponde el registro
            $table->string('month', 7)->nullable(); // Formato: YYYY-MM (ejemplo: 2025-01)

            // Tasa de cambio registrada en el momento
            $table->decimal('usd_rate', 12, 6)->nullable(); // Tasa de cambio de USD a Bs
            $table->decimal('cop_rate', 12, 6)->nullable(); // Tasa de cambio de COP a Bs

            // Totales calculados
            $table->decimal('total_bs', 12, 2)->default(0); // Total global en Bolívares
            $table->decimal('total_usd_to_bs', 12, 2)->default(0); // Total USD convertido a Bs
            $table->decimal('total_cop_to_bs', 12, 2)->default(0); // Total COP convertido a Bs
            $table->decimal('grand_total_bs', 12, 2)->default(0); // Gran total en Bs

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offering_transactions', function (Blueprint $table) {
            // Eliminar claves foráneas explícitamente para facilitar el truncado
            $table->dropForeign(['offering_id']);
            $table->dropForeign(['pastor_id']);
        });

        Schema::dropIfExists('offering_transactions');
    }
};