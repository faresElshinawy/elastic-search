<?php

namespace Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Tenants;

use Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Tenants\ParentTenantIndicesCommand;

class CreateTenantElasticIndices extends ParentTenantIndicesCommand
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'elastic:tenants-index-create {--tenantReference=}';

    /**
     * The console command description.
     */
    protected $description = 'Creates Elastic search index for specific tenant or all tenants.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Parent::handle();
        $this->performTenantOperation(function ($indexable){
            return $this->createElasticIndex($indexable);
        },'Create');
    }
}
