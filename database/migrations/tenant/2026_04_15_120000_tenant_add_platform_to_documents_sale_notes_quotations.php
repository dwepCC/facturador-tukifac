<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TenantAddPlatformToDocumentsSaleNotesQuotations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('platform')->default('web');
        });

        Schema::table('sale_notes', function (Blueprint $table) {
            $table->string('platform')->default('web');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->string('platform')->default('web');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('platform');
        });

        Schema::table('sale_notes', function (Blueprint $table) {
            $table->dropColumn('platform');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('platform');
        });
    }
}
