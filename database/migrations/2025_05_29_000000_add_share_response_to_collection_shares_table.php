<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShareResponseToCollectionSharesTable extends Migration
{
    public function up()
    {
        Schema::table('collection_shares', function (Blueprint $table) {
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('collection_shares', function (Blueprint $table) {
            $table->dropColumn('accepted_at');
            $table->dropColumn('rejected_at');
        });
    }
}
