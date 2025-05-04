<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('vaults')) {
            throw new \Exception('The vaults table must exist before creating the elements table.');
        }

        Schema::create('elements', function (Blueprint $table) {
            $table->uuid('id_element')->primary();
            $table->string('name');
            $table->enum('element_type', ['FILE', 'FOLDER']);
            $table->text('content_html')->nullable();
            $table->uuid('id_vault');
            $table->uuid('id_parent')->nullable();
            $table->timestamp('last_edited')->nullable();
            $table->json('tags')->nullable();
            $table->json('versions')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('id_vault')
                  ->references('id_vault')
                  ->on('vaults')
                  ->onDelete('cascade');
                  
            $table->foreign('id_parent')
                  ->references('id_element')
                  ->on('elements')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('elements');
    }
};