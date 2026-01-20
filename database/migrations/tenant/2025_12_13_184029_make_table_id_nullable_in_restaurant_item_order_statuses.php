<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakeTableIdNullableInRestaurantItemOrderStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Use raw SQL to avoid doctrine/dbal dependency requirement for changing columns
        DB::statement("ALTER TABLE restaurant_item_order_statuses MODIFY COLUMN table_id INT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Reverting to NOT NULL might fail if there are null values, so we generally accept it staying nullable or handle data fix first.
        // For safety, we won't force it back to NOT NULL in down() to avoid data loss/errors during rollback of a batch.
        // DB::statement("ALTER TABLE restaurant_item_order_statuses MODIFY COLUMN table_id INT NOT NULL");
    }
}
