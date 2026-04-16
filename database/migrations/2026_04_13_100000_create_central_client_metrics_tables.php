<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $c = 'system';

        if (!Schema::connection($c)->hasTable('tenant_metrics_current')) {
        Schema::connection($c)->create('tenant_metrics_current', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->primary();
            $table->unsignedInteger('total_users')->default(0);
            $table->unsignedInteger('total_establishments')->default(0);
            $table->string('soap_type_id', 2)->nullable();

            $table->unsignedInteger('total_documents')->default(0);
            $table->unsignedInteger('total_documents_pse')->default(0);
            $table->unsignedInteger('current_month_documents')->default(0);
            $table->unsignedInteger('total_sales_notes')->default(0);

            $table->unsignedInteger('pending_regularize_shipping')->default(0);
            $table->unsignedInteger('pending_not_sent')->default(0);
            $table->unsignedInteger('pending_to_be_canceled')->default(0);
            $table->unsignedInteger('pending_rejected')->default(0);
            $table->unsignedInteger('pending_observed')->default(0);

            $table->decimal('monthly_sales_total_cached', 18, 2)->default(0);
            $table->timestamp('metrics_last_synced_at')->nullable();

            $table->timestamps();
        });
        }

        if (!Schema::connection($c)->hasTable('client_central_documents')) {
        Schema::connection($c)->create('client_central_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('tenant_document_id');
            $table->date('date_of_issue');
            $table->string('document_type_id', 2);
            $table->string('state_type_id', 2);
            $table->boolean('regularize_shipping')->default(false);
            $table->boolean('send_to_pse')->default(false);
            $table->string('currency_type_id', 3)->default('PEN');
            $table->decimal('exchange_rate_sale', 14, 4)->default(1);
            $table->decimal('total', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['client_id', 'tenant_document_id'], 'ccd_client_tenant_doc_uid');
            $table->index(['client_id', 'date_of_issue'], 'ccd_client_date_idx');
            $table->index(['client_id', 'state_type_id'], 'ccd_client_state_idx');
        });
        }

        if (!Schema::connection($c)->hasTable('client_central_sale_notes')) {
        Schema::connection($c)->create('client_central_sale_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('tenant_sale_note_id');
            $table->date('date_of_issue');
            $table->string('state_type_id', 2);
            $table->boolean('changed')->default(false);
            $table->string('currency_type_id', 3)->default('PEN');
            $table->decimal('exchange_rate_sale', 14, 4)->default(1);
            $table->decimal('total', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['client_id', 'tenant_sale_note_id'], 'ccsn_client_tenant_sn_uid');
            $table->index(['client_id', 'date_of_issue'], 'ccsn_client_date_idx');
        });
        }

        if (!Schema::connection($c)->hasTable('tenant_metric_history')) {
        Schema::connection($c)->create('tenant_metric_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('client_id');
            $table->string('metric_type', 64);
            $table->string('event_type', 32);
            $table->decimal('value', 18, 4)->nullable();
            $table->string('reference_type', 64)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->dateTime('event_date');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'event_date'], 'tmh_client_event_date_idx');
        });
        }
    }

    public function down(): void
    {
        $c = 'system';
        Schema::connection($c)->dropIfExists('tenant_metric_history');
        Schema::connection($c)->dropIfExists('client_central_sale_notes');
        Schema::connection($c)->dropIfExists('client_central_documents');
        Schema::connection($c)->dropIfExists('tenant_metrics_current');
    }
};
