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
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('path');
            $table->timestamps();
            
            $table->foreign('message_id')->references('id')->on('messages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};