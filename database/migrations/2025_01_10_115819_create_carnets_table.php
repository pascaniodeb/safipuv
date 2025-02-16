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
        Schema::create('carnets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pastor_id')->constrained('pastors')->onDelete('cascade'); // Relación con pastores
            $table->foreignId('pastor_licence_id')->nullable()->constrained('pastor_licences')->onDelete('set null'); // Relación con licencias pastorales
            $table->foreignId('pastor_type_id')->nullable()->constrained('pastor_types')->onDelete('set null'); // Relación con tipos de pastor
            $table->boolean('is_active')->default(true); // Activo o no
            $table->string('file_path')->nullable(); // Ruta del archivo generado
            $table->string('generated_by')->nullable(); // Quién generó el carnet
            $table->json('custom_data')->nullable(); // Datos personalizados adicionales
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carnets');
    }
};
