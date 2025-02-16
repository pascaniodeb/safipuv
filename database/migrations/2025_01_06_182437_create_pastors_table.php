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
        Schema::create('pastors', function (Blueprint $table) {
            $table->id();

            // Relaciones con otras tablas
            $table->foreignId('region_id')->constrained()->onDelete('cascade');
            $table->foreignId('district_id')->constrained()->onDelete('cascade');
            $table->foreignId('sector_id')->constrained()->onDelete('cascade');
            $table->foreignId('state_id')->constrained()->onDelete('cascade');
            $table->foreignId('city_id')->constrained()->onDelete('cascade');
            $table->foreignId('gender_id')->constrained()->onDelete('cascade');
            $table->foreignId('nationality_id')->constrained()->onDelete('cascade');
            $table->foreignId('blood_type_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('academic_level_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('marital_status_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('housing_type_id')->nullable()->constrained()->onDelete('set null');

            // Campos de texto
            $table->string('name');
            $table->string('lastname');
            $table->string('number_cedula')->unique();
            $table->string('career')->nullable();
            $table->string('phone_mobile')->nullable();
            $table->string('phone_house')->nullable();
            $table->string('email')->unique();
            $table->string('birthplace')->nullable();
            $table->string('who_baptized')->nullable();
            $table->string('how_work')->nullable();
            $table->string('other_studies')->nullable();

            // Campos de fecha
            $table->date('birthdate');
            $table->date('baptism_date')->nullable();
            $table->date('start_date_ministry')->nullable();

            // Campos booleanos
            $table->boolean('social_security')->default(false);
            $table->boolean('housing_policy')->default(false);
            $table->boolean('other_work')->default(false);

            // Dirección
            $table->text('address')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pastors');
    }
};

