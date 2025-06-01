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
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->uuid('pdf_id');
            $table->uuid('user_id');
            $table->string('permission_type')->default('view'); // view, edit, etc.
            $table->timestamps();            $table->unique(['pdf_id', 'user_id']);
            $table->foreign('pdf_id')->references('id')->on('pdfs')->onDelete('cascade');
            $table->foreign('user_id')->references('id_profile')->on('users')->onDelete('cascade');
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
