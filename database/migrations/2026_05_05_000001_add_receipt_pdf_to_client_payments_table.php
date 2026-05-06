<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReceiptPdfToClientPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('client_payments', function (Blueprint $table) {
            $table->string('receipt_pdf')->nullable()->after('reference');
        });
    }

    public function down()
    {
        Schema::table('client_payments', function (Blueprint $table) {
            $table->dropColumn('receipt_pdf');
        });
    }
}

