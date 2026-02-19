<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Usuario que creó la comanda (auditoría).
     */
    public function up()
    {
        Schema::table('restaurant_item_order_statuses', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurant_item_order_statuses', 'user_id')) {
                $table->unsignedInteger('user_id')->nullable()->after('customer_name');
            }
        });
    }

    public function down()
    {
        Schema::table('restaurant_item_order_statuses', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
