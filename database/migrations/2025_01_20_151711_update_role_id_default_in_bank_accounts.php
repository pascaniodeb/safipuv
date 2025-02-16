<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRoleIdDefaultInBankAccounts extends Migration
{
    public function up()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->default(null)->change(); // Permitir nulo inicialmente
        });
    }

    public function down()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable(false)->change(); // Revertir el cambio
        });
    }
}