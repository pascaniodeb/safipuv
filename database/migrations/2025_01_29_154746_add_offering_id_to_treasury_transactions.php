<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('treasury_transactions', function (Blueprint $table) {
            // Agregar la columna sin clave foránea inicialmente
            $table->unsignedBigInteger('offering_id')->nullable()->after('treasury_id');
        });

        // Asignar valores por defecto a `offering_id` (evitar errores)
        DB::statement("UPDATE treasury_transactions SET offering_id = (SELECT offering_id FROM offering_transactions WHERE offering_transactions.id = treasury_transactions.offering_transaction_id LIMIT 1)");

        // Ahora que los datos están listos, podemos añadir la clave foránea
        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->foreign('offering_id')->references('id')->on('offerings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->dropForeign(['offering_id']);
            $table->dropColumn('offering_id');
        });
    }
};