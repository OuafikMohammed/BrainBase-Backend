<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionPdfTable extends Migration
{
    public function up()
    {
        Schema::create('collection_pdf', function (Blueprint $table) {
            $table->uuid('collection_id');
            $table->uuid('pdf_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('collection_id')
                  ->references('id')
                  ->on('collections')
                  ->onDelete('cascade');

            $table->foreign('pdf_id')
                  ->references('id')
                  ->on('pdfs')
                  ->onDelete('cascade');

            // Composite Primary Key
            $table->primary(['collection_id', 'pdf_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('collection_pdf');
    }
}