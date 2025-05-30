<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionsTable extends Migration
{
    public function up()
    {        Schema::create('collections', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->char('created_by', 36); // User who created the collection
            $table->boolean('is_favorite_collection')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('created_by')
                  ->references('id_profile')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('collections');
    }
}