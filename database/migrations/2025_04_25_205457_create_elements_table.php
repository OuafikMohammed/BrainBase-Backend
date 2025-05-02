<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateElementsTable extends Migration
{
    public function up()
    {
        Schema::create('elements', function (Blueprint $table) {
            $table->uuid('id_element')->primary(); // Unique identifier for the element
            $table->string('name'); // Name of the element
            $table->enum('element_type', ['FOLDER', 'FILE']); // Type of the element
            $table->uuid('id_vault'); // Foreign key to the vault
            $table->uuid('id_parent')->nullable(); // Self-referencing foreign key for hierarchy
            $table->text('content_html')->nullable(); // Content of the element (for FILE type)
            $table->integer('position')->default(0); // Position in the hierarchy
            $table->timestamps(); // Created_at and updated_at timestamps
            $table->foreign('id_vault')->references('id_vault')->on('vaults')->onDelete('cascade');
            $table->foreign('id_parent')->references('id_element')->on('elements')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('elements');
    }
}
