<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('users')) {
            throw new \Exception('The users table must exist before creating the vaults table.');
        }

        Schema::create('vaults', function (Blueprint $table) {
            $table->uuid('id_vault')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('id_profile');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('id_profile')
                  ->references('id_profile')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vaults');
    }
};