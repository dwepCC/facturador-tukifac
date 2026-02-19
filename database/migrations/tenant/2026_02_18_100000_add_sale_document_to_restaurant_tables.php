<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vincula la mesa con la venta generada (Nota de venta o Factura).
     * Requerido para permitir cerrar mesa con productos sin pérdida económica.
     */
    public function up()
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurant_tables', 'sale_note_id')) {
                $table->unsignedInteger('sale_note_id')->nullable()->after('is_paid');
            }
            if (!Schema::hasColumn('restaurant_tables', 'document_id')) {
                $table->unsignedInteger('document_id')->nullable()->after('sale_note_id');
            }
        });
    }

    public function down()
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->dropColumn(['sale_note_id', 'document_id']);
        });
    }
};
