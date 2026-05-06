<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TenantAddReceiptPdfToAccountPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('account_payments', function (Blueprint $table) {
            $table->string('receipt_pdf')->nullable()->after('reference_payment');
        });
    }

    public function down()
    {
        Schema::table('account_payments', function (Blueprint $table) {
            $table->dropColumn('receipt_pdf');
        });
    }
}

