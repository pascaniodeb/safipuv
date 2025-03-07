<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique(); // Permite manejar distintos tipos de secuencias en el futuro
            $table->integer('last_number')->unsigned()->default(0); // Último número generado
            $table->timestamps();
        });

        // Insertar un registro inicial para el número de reportes
        DB::table('report_sequences')->insert([
            'type' => 'report',
            'last_number' => 0, // Se inicia en 0, el primer reporte será 1
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('report_sequences');
    }
};