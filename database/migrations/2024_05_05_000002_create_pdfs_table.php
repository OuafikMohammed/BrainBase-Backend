<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pdfs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->integer('size');
            $table->string('file_path');  // Path to the file in local storage
            $table->uuid('uploaded_by');  // Changed to uuid to match users table id_profile
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('uploaded_by')
                ->references('id_profile')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pdfs');
    }
};