<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('offering_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('treasury_id')->constrained()->onDelete('cascade'); // Tesorería Sectorial
        
            // Mes al que corresponde el registro
            $table->string('month', 7)->nullable(); // Formato: YYYY-MM (ejemplo: 2025-01)
        
            // 📌 Número de Orden (Autogenerado)
            $table->string('number_report')->unique();
        
            // Información del pastor que realiza la ofrenda
            $table->unsignedBigInteger('pastor_id');
            $table->unsignedBigInteger('pastor_type_id')->nullable();
        
            // Relación con la iglesia
            $table->unsignedBigInteger('church_id')->nullable();
        
            // Ubicación dentro de la estructura organizativa
            $table->unsignedBigInteger('region_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->unsignedBigInteger('sector_id')->nullable();
        
            // Información del usuario tesorero y contralor
            $table->unsignedBigInteger('user_id')->nullable();
        
            // Tasa de cambio registrada en el momento (GLOBALES PARA EL INFORME)
            $table->decimal('usd_rate', 12, 6)->nullable();
            $table->decimal('cop_rate', 12, 6)->nullable();

            // Totales calculados
            $table->decimal('total_bs', 12, 2)->default(0); // Total global en Bolívares
            $table->decimal('total_usd', 12, 2)->default(0); // Total USD convertido a Bs
            $table->decimal('total_cop', 12, 2)->default(0); // Total COP convertido a Bs
            $table->decimal('total_usd_to_bs', 12, 2)->default(0); // Total USD convertido a Bs
            $table->decimal('total_cop_to_bs', 12, 2)->default(0); // Total COP convertido a Bs
            $table->decimal('grand_total_bs', 12, 2)->default(0); // Gran total en Bs
        
            $table->enum('status', ['pendiente', 'aprobado'])->default('pendiente');

            // Observaciones
            $table->text('remarks')->nullable();
        
            // Timestamps
            $table->timestamps();
        
            // Claves foráneas
            $table->foreign('pastor_id')->references('id')->on('pastors')->onDelete('cascade');
            $table->foreign('pastor_type_id')->references('id')->on('pastor_types')->onDelete('set null');
            $table->foreign('church_id')->references('id')->on('churches')->onDelete('set null');
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('set null');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('set null');
            $table->foreign('sector_id')->references('id')->on('sectors')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
        
    }

    public function down()
    {
        Schema::dropIfExists('offering_reports');
    }
};