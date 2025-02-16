<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Relación con usuarios
            
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('cascade'); // Relación con regiones
            $table->foreignId('district_id')->nullable()->constrained('districts')->onDelete('cascade'); // Relación con distritos
            $table->foreignId('sector_id')->nullable()->constrained('sectors')->onDelete('cascade'); // Relación con sectores
            $table->foreignId('bank_id')->constrained('banks')->onDelete('cascade'); // Relación con bancos
            $table->foreignId('bank_transaction_id')->constrained('bank_transactions')->onDelete('cascade'); // Relación con transacciones
            $table->foreignId('bank_account_type_id')->nullable()->default(0)->constrained('bank_account_types')->onDelete('cascade'); // Relación con tipos de cuenta
            $table->string('username_id', 100)->nullable(); // Campo para username del usuario
            $table->string('email', 150)->nullable(); // Campo para email del usuario
            $table->string('tax_id', 10)->nullable()->unique(); // RIF
            $table->string('business_name', 150)->nullable(); // Razón social
            $table->string('account_number', 20)->nullable()->unique(); // Número de cuenta bancaria
            $table->string('mobile_payment_number', 10)->nullable()->unique(); // Número de pago móvil
            $table->boolean('active')->default(true); // Estado activo
            $table->timestamps();
        });
        
    }

    public function down()
    {
        Schema::dropIfExists('bank_accounts');
    }
}