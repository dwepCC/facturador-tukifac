<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TenantAddAutoSendDocumentToPseToCompanies extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'auto_send_document_to_pse')) {
                $table->boolean('auto_send_document_to_pse')
                    ->default(false)
                    ->after('send_document_to_pse');
            }
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'auto_send_document_to_pse')) {
                $table->dropColumn('auto_send_document_to_pse');
            }
        });
    }
}
