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
    {        Schema::create('shares', function (Blueprint $table) {            $table->id();            $table->char('pdf_id', 36)->nullable();
            $table->unsignedBigInteger('collection_id')->nullable();
            $table->uuid('user_id');
            $table->enum('permissions', ['view', 'edit'])->default('view');
            $table->timestamps();

            $table->foreign('pdf_id')
                  ->references('id')
                  ->on('pdfs')
                  ->onDelete('cascade');

            $table->foreign('collection_id')
                  ->references('id')
                  ->on('collections')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id_profile')
                  ->on('users')
                  ->onDelete('cascade');
                    // Allow either pdf_id or collection_id to be null, but not both
            $table->unique(['pdf_id', 'user_id']);
            $table->unique(['collection_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
