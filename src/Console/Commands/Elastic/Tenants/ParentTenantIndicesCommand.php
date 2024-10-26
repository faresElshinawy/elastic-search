<?php

namespace Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Tenants;

use App\Models\Tenant;
use Exception;
use Fareselshinawy\ElasticSearch\Adapters\StanclTenancyAdapter;
use Illuminate\Console\Command;
use Stancl\Tenancy\Facades\Tenancy;
use Fareselshinawy\ElasticSearch\Traits\ElasticTenantCommandTrait;

class ParentTenantIndicesCommand extends Command
{
    use ElasticTenantCommandTrait;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'elastic-index:tenants';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $tenantAdapter         = config('elastic-search.tenant_adapter');
        $this->tenantAdapter   = new $tenantAdapter;
    }

        /**
     * Execute the console command.
     */
    public function handle()
    {
        // making sure that elastic search package is installed
        if(!class_exists('Elastic\Elasticsearch\ClientBuilder',true))
        {
            throw new Exception('Please make sure to install and setup elasticsearch/elasticsearch package to be able to use this command.');
        }
    }

    // this function is made to avoid replicate common function calls between tenant commands
    public function performTenantOperation($operationCallback,$operationType)
    {
        $tenantReference = $this->option('tenantReference');
        // if tenant selected then make specific index for it else create for all tenants
        if ($tenantReference) {
            // perform multi tenant action
            $this->performSingleTenantIndexesAction($tenantReference,function ($indexes) use($operationCallback,) {
                // perform action for all selected indexes
                $this->processIndexesOperation($indexes,function ($indexable) use($operationCallback) {
                    return $operationCallback($indexable);
                });
            });
        } else {
            // perform multi tenant action
            $this->performMultiTenantIndexesAction(function ($indexes)  use($operationCallback) {
                // perform action for all selected indexes
                $this->processIndexesOperation($indexes,function ($indexable) use($operationCallback) {
                    return  $operationCallback($indexable);
                });
            },$operationType);
        }
        $this->printSuccessFulMessage();
    }

    public function printSuccessFulMessage()
    {
        if($this->operationStatus)
        {
            $this->info('Done ;)');
        }
    }
}
