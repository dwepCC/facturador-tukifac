<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPerformanceIndexesToDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('documents', function (Blueprint $table) {
            // Índice compuesto para filtros comunes (fecha, tipo documento, estado)
            if (!$this->indexExists('documents', 'idx_date_type_state')) {
                $table->index(['date_of_issue', 'document_type_id', 'state_type_id'], 'idx_date_type_state');
            }
            
            // Índice para búsqueda por cliente y fecha
            if (!$this->indexExists('documents', 'idx_customer_date')) {
                $table->index(['customer_id', 'date_of_issue'], 'idx_customer_date');
            }
            
            // Índice para búsqueda por estado y fecha
            if (!$this->indexExists('documents', 'idx_state_date')) {
                $table->index(['state_type_id', 'date_of_issue'], 'idx_state_date');
            }
            
            // Índice compuesto para series/number (búsquedas exactas)
            if (!$this->indexExists('documents', 'idx_series_number')) {
                $table->index(['series', 'number'], 'idx_series_number');
            }
            
            // Índice para currency_type_id y document_type_id (usado en recordsTotal)
            if (!$this->indexExists('documents', 'idx_currency_doc_type')) {
                $table->index(['currency_type_id', 'document_type_id'], 'idx_currency_doc_type');
            }
            
            // Índice para user_id (filtro whereTypeUser)
            if (!$this->indexExists('documents', 'idx_user_id')) {
                $table->index('user_id', 'idx_user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('idx_date_type_state');
            $table->dropIndex('idx_customer_date');
            $table->dropIndex('idx_state_date');
            $table->dropIndex('idx_series_number');
            $table->dropIndex('idx_currency_doc_type');
            $table->dropIndex('idx_user_id');
        });
    }
    
    /**
     * Verificar si un índice existe
     */
    private function indexExists($table, $indexName)
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = $connection->select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$databaseName, $table, $indexName]
        );
        
        return $result[0]->count > 0;
    }
}

