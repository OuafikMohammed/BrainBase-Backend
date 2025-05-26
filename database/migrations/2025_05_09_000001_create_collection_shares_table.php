<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('collection_shares', function (Blueprint $table) {            $table->id();
            $table->unsignedBigInteger('collection_id');
            $table->uuid('user_id');
            $table->string('role')->default('viewer'); // viewer, editor, admin
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('collection_id')
                  ->references('id')
                  ->on('collections')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id_profile')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('created_by')
                  ->references('id_profile')
                  ->on('users')
                  ->onDelete('cascade');
            
            // Prevent duplicate shares
            $table->unique(['collection_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('collection_shares');
    }
};
