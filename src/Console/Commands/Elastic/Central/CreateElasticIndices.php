<?php

namespace Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Central;

use Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Central\ParentIndicesCommand;

class CreateElasticIndices extends ParentIndicesCommand
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'elastic:index-create';

    /**
     * The console command description.
     */
    protected $description = 'Creates Elastic search index for specific tenant or all tenants.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $indexes = $this->prepareIndexes();
        // perform action for all selected indexes
        $this->processIndexesOperation($indexes,function ($indexable) {
            return $this->createElasticIndex($indexable);
        });
        $this->printSuccessFulMessage();
    }
}