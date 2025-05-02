<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVaultsTable extends Migration
{
    public function up()
    {
        Schema::create('vaults', function (Blueprint $table) {
            $table->uuid('id_vault')->primary(); // Unique identifier for the vault
            $table->string('name'); // Name of the vault
            $table->text('description')->nullable(); // Description of the vault
            $table->uuid('id_profile'); // Foreign key to the user profile
            $table->timestamps(); // Created_at and updated_at timestamps
            $table->foreign('id_profile')->references('id_profile')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vaults');
    }
}