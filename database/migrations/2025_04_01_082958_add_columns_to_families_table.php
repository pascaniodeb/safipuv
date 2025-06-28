<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->string('photo_spouse')->nullable()->after('phone_house'); // coloca la columna después de una existente si lo deseas
            $table->unsignedBigInteger('position_type_id')->nullable()->after('photo_spouse');
            $table->unsignedBigInteger('current_position_id')->nullable()->after('position_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn(['photo_spouse', 'position_type_id', 'current_position_id']);
        });
    }
};