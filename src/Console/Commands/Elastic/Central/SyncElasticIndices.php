<?php

namespace Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Central;

use Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Central\ParentIndicesCommand;

class SyncElasticIndices extends ParentIndicesCommand
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'elastic:index-sync';

    /**
     * The console command description.
     */
    protected $description = 'Syncs data of all elastic searchable models with elastic search cloud for specific or all tenants.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // get use options for indexes
        $indexes = $this->prepareIndexes();
        // perform action for all selected indexes
        $this->processIndexesOperation($indexes,function ($indexable) {
            return $this->syncIndexable($indexable);
        });
        $this->printSuccessFulMessage();
    }
}
