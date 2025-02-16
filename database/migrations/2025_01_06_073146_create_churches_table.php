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
        Schema::create('churches', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre de la iglesia
            $table->date('date_opening')->nullable(); // Fecha de apertura
            $table->string('pastor_founding')->nullable(); // Pastor fundador
            $table->string('code_church')->unique(); // Código único de la iglesia
            $table->string('type_infrastructure')->nullable(); // Tipo de infraestructura
            $table->boolean('legalized')->default(false); // Iglesia legalizada
            $table->string('legal_entity_number')->nullable(); // Número de entidad legal
            $table->string('number_rif')->nullable(); // Número de RIF
            $table->foreignId('region_id')->constrained()->onDelete('cascade'); // Región
            $table->foreignId('district_id')->constrained()->onDelete('cascade'); // Distrito
            $table->foreignId('sector_id')->constrained()->onDelete('cascade'); // Sector
            $table->foreignId('state_id')->constrained()->onDelete('cascade'); // Estado
            $table->foreignId('city_id')->constrained()->onDelete('cascade'); // Ciudad
            $table->text('address')->nullable(); // Dirección
            $table->string('pastor_current')->nullable(); // Pastor actual
            $table->string('number_cedula')->nullable(); // Cédula del pastor actual
            $table->foreignId('current_position_id')->constrained('current_positions')->onDelete('cascade'); // Posición actual
            $table->string('email')->nullable(); // Email
            $table->string('phone')->nullable(); // Teléfono
            $table->integer('adults')->default(0); // Número de adultos
            $table->integer('children')->default(0); // Número de niños
            $table->integer('baptized')->default(0); // Número de bautizados
            $table->integer('to_baptize')->default(0); // Número de personas por bautizar
            $table->integer('holy_spirit')->default(0); // Número llenos del Espíritu Santo
            $table->integer('groups_cells')->default(0); // Grupos de células
            $table->integer('centers_preaching')->default(0); // Centros de predicación
            $table->integer('members')->default(0); // Número total de miembros
            $table->foreignId('category_church_id')->constrained('category_churches')->onDelete('cascade'); // Categoría de la iglesia
            $table->boolean('directive_local')->default(false); // Directiva local (sí/no)
            $table->boolean('pastor_attach')->default(false); // Pastor adjunto (sí/no)
            $table->string('name_pastor_attach')->nullable(); // Nombre del pastor adjunto
            $table->boolean('pastor_assistant')->default(false); // Pastor asistente (sí/no)
            $table->string('name_pastor_assistant')->nullable(); // Nombre del pastor asistente
            $table->boolean('co_pastor')->default(false); // Co-pastor (sí/no)
            $table->boolean('professionals')->default(false); // Profesionales (sí/no)
            $table->string('name_professionals')->nullable(); // Nombres de profesionales
            $table->timestamps(); // Timestamps para created_at y updated_at
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('churches');
    }
};

