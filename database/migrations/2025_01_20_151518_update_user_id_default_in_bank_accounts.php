<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserIdDefaultInBankAccounts extends Migration
{
    public function up()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->default(1)->change(); // Cambiar el valor predeterminado
        });
    }

    public function down()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change(); // Revertir el cambio
        });
    }
}