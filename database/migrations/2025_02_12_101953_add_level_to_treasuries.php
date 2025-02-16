<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('treasuries', function (Blueprint $table) {
            $table->string('level')->after('name'); // sectorial, distrital, regional, nacional
        });
    }

    public function down()
    {
        Schema::table('treasuries', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }

};