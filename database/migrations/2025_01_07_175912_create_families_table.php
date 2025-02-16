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
        Schema::create('families', function (Blueprint $table) {
            $table->id();

            // Clave foránea para relacionar con la tabla pastors
            $table->foreignId('pastor_id')->constrained('pastors')->onDelete('cascade');

            // Claves foráneas para relaciones
            $table->foreignId('relation_id')->constrained('relations')->onDelete('cascade');
            $table->foreignId('gender_id')->constrained('genders')->onDelete('cascade');
            $table->foreignId('nationality_id')->constrained('nationalities')->onDelete('cascade');
            $table->foreignId('blood_type_id')->nullable()->constrained('blood_types')->onDelete('set null');
            $table->foreignId('marital_status_id')->nullable()->constrained('marital_statuses')->onDelete('set null');
            $table->foreignId('academic_level_id')->nullable()->constrained('academic_levels')->onDelete('set null');

            // Campos de texto y únicos
            $table->string('name');
            $table->string('lastname');
            $table->string('number_cedula')->unique();
            $table->string('career')->nullable();
            $table->string('phone_mobile')->nullable();
            $table->string('phone_house')->nullable();
            $table->string('email')->unique();

            // Campos de fecha
            $table->date('birthdate');
            $table->string('birthplace')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};

