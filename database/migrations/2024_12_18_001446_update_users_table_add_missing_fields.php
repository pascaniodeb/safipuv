<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Nuevos campos
            $table->string('lastname')->after('name');
            $table->string('username')->unique()->after('lastname');
            $table->unsignedBigInteger('role_id')->nullable()->after('username');
            $table->unsignedBigInteger('region_id')->nullable()->after('role_id');
            $table->unsignedBigInteger('district_id')->nullable()->after('region_id');
            $table->unsignedBigInteger('sector_id')->nullable()->after('district_id');
            $table->unsignedBigInteger('nationality_id')->nullable()->after('sector_id');
            $table->boolean('active')->default(false)->after('nationality_id');
            $table->softDeletes()->after('active'); // Soft deletes

            // Agregar claves foráneas
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('set null');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('set null');
            $table->foreign('sector_id')->references('id')->on('sectors')->onDelete('set null');
            $table->foreign('nationality_id')->references('id')->on('nationalities')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar claves foráneas
            $table->dropForeign(['role_id']);
            $table->dropForeign(['region_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['sector_id']);
            $table->dropForeign(['nationality_id']);

            // Eliminar columnas
            $table->dropColumn([
                'lastname',
                'username',
                'role_id',
                'region_id',
                'district_id',
                'sector_id',
                'nationality_id',
                'active',
                'deleted_at',
            ]);
        });
    }
};

