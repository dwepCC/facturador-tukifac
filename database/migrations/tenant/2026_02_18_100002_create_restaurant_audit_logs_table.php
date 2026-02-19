<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de auditoría para cierre de mesa, anulaciones y cambios sensibles.
     */
    public function up()
    {
        Schema::create('restaurant_audit_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('action', 64); // table_closed, orders_deleted, table_config_changed, etc.
            $table->string('entity_type', 64)->nullable(); // restaurant_tables, restaurant_item_order_statuses
            $table->unsignedInteger('entity_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->json('payload')->nullable(); // datos relevantes (table_id, sale_note_id, order_ids, etc.)
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurant_audit_logs');
    }
};
