<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PIN único (4 dígitos) para anular/quitar ítems de comanda.
     * Lo configura el dueño en Restaurante > Configuración; todos los usuarios deben ingresarlo para quitar un ítem.
     */
    public function up()
    {
        Schema::table('restaurant_configurations', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurant_configurations', 'comanda_removal_pin')) {
                $table->string('comanda_removal_pin', 4)->nullable()->after('replace_template_mozo');
            }
        });
    }

    public function down()
    {
        Schema::table('restaurant_configurations', function (Blueprint $table) {
            $table->dropColumn('comanda_removal_pin');
        });
    }
};
