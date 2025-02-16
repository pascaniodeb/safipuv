<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOfferingReportTreasuryDistributionsTable extends Migration
{
    public function up()
    {
        Schema::create('offering_report_treasury_distributions', function (Blueprint $table) {
            $table->id();

            // Relación con el informe
            $table->unsignedBigInteger('offering_report_id');
            $table->foreign('offering_report_id', 'ofr_tres_ofr_id_fk') // Nombre corto para la clave foránea
                  ->references('id')
                  ->on('offering_reports')
                  ->onDelete('cascade');

            // Relación con la tesorería
            $table->unsignedBigInteger('treasury_id');
            $table->foreign('treasury_id', 'ofr_tres_tres_id_fk') // Nombre corto para la clave foránea
                  ->references('id')
                  ->on('treasuries')
                  ->onDelete('cascade');

            // Monto distribuido
            $table->decimal('amount_bs', 12, 2)->default(0);

            // Estado de revisión (pendiente, aprobado, rechazado)
            $table->enum('status', ['pendiente', 'aprobado', 'devuelto'])->default('pendiente');

            // Timestamps
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('offering_report_treasury_distributions');
    }
}