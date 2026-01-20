<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCustomerNameToRestaurantItemOrderStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('restaurant_item_order_statuses', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('status_description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('restaurant_item_order_statuses', function (Blueprint $table) {
            $table->dropColumn('customer_name');
        });
    }
}
