<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {        Schema::create('collection_shares', function (Blueprint $table) {
            $table->char('idShare', 36)->primary();
            $table->char('idCollection', 36);
            $table->char('idProfile', 36);
            $table->enum('permission', ['view', 'edit', 'admin'])->default('view');
            $table->timestamp('dateShare');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('idCollection')
                  ->references('id')
                  ->on('collections')
                  ->onDelete('cascade');

            $table->foreign('idProfile')
                  ->references('id_profile')
                  ->on('users')
                  ->onDelete('cascade');

            // Prevent duplicate shares
            $table->unique(['idCollection', 'idProfile']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('collection_shares');
    }
};
