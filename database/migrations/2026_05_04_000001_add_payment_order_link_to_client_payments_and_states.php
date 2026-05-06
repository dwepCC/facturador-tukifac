<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPaymentOrderLinkToClientPaymentsAndStates extends Migration
{
    public function up()
    {
        if (Schema::hasTable('client_payments')) {
            Schema::table('client_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('client_payments', 'payment_order_id')) {
                    $table->unsignedInteger('payment_order_id')->nullable()->after('client_id');
                    $table->index('payment_order_id');
                }
                if (!Schema::hasColumn('client_payments', 'status')) {
                    $table->unsignedTinyInteger('status')->default(0)->after('state');
                    $table->index('status');
                }
            });

            Schema::table('client_payments', function (Blueprint $table) {
                if (Schema::hasColumn('client_payments', 'payment_order_id')) {
                    $table->foreign('payment_order_id')->references('id')->on('payment_orders')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('payment_order_states')) {
            DB::table('payment_order_states')->updateOrInsert(['id' => 5], ['name' => 'En verificación']);
            DB::table('payment_order_states')->updateOrInsert(['id' => 6], ['name' => 'Rechazado']);
        }
    }

    public function down()
    {
        if (Schema::hasTable('client_payments')) {
            Schema::table('client_payments', function (Blueprint $table) {
                if (Schema::hasColumn('client_payments', 'payment_order_id')) {
                    $table->dropForeign(['payment_order_id']);
                    $table->dropIndex(['payment_order_id']);
                    $table->dropColumn('payment_order_id');
                }
                if (Schema::hasColumn('client_payments', 'status')) {
                    $table->dropIndex(['status']);
                    $table->dropColumn('status');
                }
            });
        }

        if (Schema::hasTable('payment_order_states')) {
            DB::table('payment_order_states')->whereIn('id', [5, 6])->delete();
        }
    }
}

