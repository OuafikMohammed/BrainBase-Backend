<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePdfsTable extends Migration
{
    public function up()
    {
        Schema::create('pdfs', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID as primary key
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('file_path');
            $table->bigInteger('size');
            $table->uuid('uploaded_by'); // Foreign key to users.id_profile
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
}