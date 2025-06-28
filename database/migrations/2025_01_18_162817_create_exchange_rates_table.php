<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        //Schema::create('exchange_rates', function (Blueprint $table) {
            //$table->id();

            //$table->unsignedBigInteger('sector_id')->nullable()->index()
                //->comment('Sector responsable de la tasa (puede ser NULL si es global)');
            //$table->string('month', 7)->nullable()->index()
                //->comment('Mes en formato YYYY-MM');
            
            //$table->string('currency', 3)->index()
                //->comment('Moneda: USD, COP');
            //$table->decimal('rate_to_bs', 15, 6)
                //->comment('Tasa de conversión a Bolívares');
            //$table->string('operation', 1)->index()
                //->comment('Tipo de operación: C = Compra, V = Venta');

            //$table->timestamps();

            //$table->unique(['sector_id', 'month', 'currency', 'operation'], 'unique_rate_per_context');
        //});
    }

    public function down(): void
    {
        //Schema::dropIfExists('exchange_rates');
    }
};