<?php

namespace Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Tenants;

use App\Models\User;
use App\Models\Tenant;
use Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Tenants\ParentTenantIndicesCommand;

class SyncTenantElasticIndices extends ParentTenantIndicesCommand
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'elastic:tenants-index-sync {--tenantReference=}';

    /**
     * The console command description.
     */
    protected $description = 'Syncs data of all elastic searchable models with elastic search cloud for specific or all tenants.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Parent::handle();
        $this->performTenantOperation(function ($indexable){
            return $this->syncIndexable($indexable);
        },'Sync');
    }
}
