<?php

namespace Fareselshinawy\ElasticSearch\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


trait ElasticIndexSchemaTrait
{
    /**
     * prepare default index schema for elastic index
     *
     * @return array
     */
    public function prepareElasticIndexSchema(): array
    {
        $schema = [];
        // Get the table name for the current model
        $table = $this->getTable();
        // Query to get all columns and their details for the table
        $columns =  DB::select("
            SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = :table AND TABLE_SCHEMA = :schema", [
                'table' => $table,
                'schema' => DB::getDatabaseName() // The current database schema
            ]
        );
        // Map the columns to Elasticsearch indexable properties
        foreach ($columns as $column) {
            $elasticsearchType = $this->mapDatabaseColumnTypeToElasticType($column->DATA_TYPE);

            if ($elasticsearchType) {
                $schema[$column->COLUMN_NAME] = [
                    'type' => $elasticsearchType,
                ];
            }
        }
        return $schema;
    }

    /**
     * Map database types to Elasticsearch types
     *
     * @param [type] $columnType
     * @return string
     */
    private function mapDatabaseColumnTypeToElasticType($columnType): string
    {
        $mapping = config('elastic-search.default_schema_types',[]);
        return $mapping[$columnType] ?? null;
    }
}
