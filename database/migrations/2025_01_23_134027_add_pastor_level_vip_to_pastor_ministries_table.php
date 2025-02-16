<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPastorLevelVipToPastorMinistriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pastor_ministries', function (Blueprint $table) {
            $table->foreignId('pastor_level_vip_id')
                  ->nullable()
                  ->constrained('pastor_levels') // Asegúrate de que la tabla `pastor_levels` existe
                  ->onDelete('set null'); // Si se elimina un nivel, deja el campo como NULL
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pastor_ministries', function (Blueprint $table) {
            $table->dropForeign(['pastor_level_vip_id']);
            $table->dropColumn('pastor_level_vip_id');
        });
    }
}