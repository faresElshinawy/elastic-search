<?php

namespace Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Central;

use Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Central\ParentIndicesCommand;

class UpdateElasticIndices extends ParentIndicesCommand
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'elastic:index-update';

    /**
     * The console command description.
     */
    protected $description = 'Creates Elastic search index for specific tenant or all tenants.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // get use options for indexes
        $indexes = $this->prepareIndexes();
        // perform action for all selected indexes
        $this->processIndexesOperation($indexes,function ($indexable) {
            return $this->updateElasticIndex($indexable);
        });

        $this->printSuccessFulMessage();
    }
}
