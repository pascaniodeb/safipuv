<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pastor_ministries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pastor_id')->constrained('pastors')->onDelete('cascade');
            $table->string('code_pastor')->unique();
            $table->date('start_date_ministry')->nullable();
            $table->foreignId('pastor_income_id')->nullable()->constrained('pastor_incomes')->onDelete('set null');
            $table->foreignId('pastor_type_id')->nullable()->constrained('pastor_types')->onDelete('set null');
            $table->boolean('active')->default(true);
            $table->foreignId('church_id')->nullable()->constrained('churches')->onDelete('set null');
            $table->string('code_church')->nullable();
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
            $table->foreignId('district_id')->nullable()->constrained('districts')->onDelete('set null');
            $table->foreignId('sector_id')->nullable()->constrained('sectors')->onDelete('set null');
            $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('set null');
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('set null');
            $table->string('address')->nullable();
            $table->boolean('abisop')->default(false);
            $table->boolean('iblc')->default(false);
            $table->foreignId('course_type_id')->nullable()->constrained('course_types')->onDelete('set null');
            $table->foreignId('pastor_licence_id')->nullable()->constrained('pastor_licences')->onDelete('set null');
            $table->foreignId('pastor_level_id')->nullable()->constrained('pastor_levels')->onDelete('set null');
            $table->foreignId('position_type_id')->nullable()->constrained('position_types')->onDelete('set null');
            $table->foreignId('current_position_id')->nullable()->constrained('current_positions')->onDelete('set null');
            $table->boolean('appointment')->default(false);
            $table->year('promotion_year')->nullable();
            $table->string('promotion_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pastor_ministries');
    }
};

