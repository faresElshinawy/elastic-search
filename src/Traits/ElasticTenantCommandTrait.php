<?php

namespace Fareselshinawy\ElasticSearch\Traits;

use App\Models\Tenant;
use Stancl\Tenancy\Facades\Tenancy;
use Fareselshinawy\ElasticSearch\Traits\ElasticClientTrait;

trait ElasticTenantCommandTrait
{
    use ElasticCommandCommonTrait,ElasticClientTrait;
    public $tenantAdapter;
    /**
     * get config key for tenant indexable models
     *
     * @return string
     */
    // get indexable configuration key for indexable connection tenant/central like config('central_indexable.*')
    public function indexableConfigKey(): string
    {
        return 'tenants_indexable';
    }

    /**
     * perform indexes actions like create / delete / update by using functions throw callback argument
     *
     * @param string $tenantReference
     * @param callable $function
     * @param string $confirmationMessage
     * @return void
     */
    // perform action for single tenant
    public function performSingleTenantIndexesAction(string $tenantReference,callable $function,string $confirmationMessage = 'Are you sure you want to sync index data for all options? yes or no'): void
    {
        if(!$this->tenantAdapter->initializeTenantByReference($tenantReference))
        {
            $this->error('Tenant with reference ' . $tenantReference . ' not found.');
            return;
        }
        $this->info('Tenant with reference ' . $tenantReference . ' initialized.');
        $indexes = $this->prepareIndexes($confirmationMessage);
        if(empty($indexes)){return;}
        $function($indexes);
    }

    /**
     * Undocumented function
     *
     * @param callable $function
     * @param string $action
     * @param string $confirmationMessage
     * @return void
     */
    // perform actions for each tenant
    public function performMultiTenantIndexesAction(callable $function,string $action = 'action',string $confirmationMessage = 'Are you sure you want to sync index data for all options? yes or no'): void
    {
        // get user confirmation to apply changes to all tenants
        if(!$this->requestActionConfirmation('Are you sure you want to sync index data for each tenant if no please re run the command and specify the tenant by using --tenantReference=something flag? yes or no'))
        {
            return;
        }

        // get indexes that we will apply action for
        $indexes = $this->prepareIndexes($confirmationMessage);

        $this->info('Running for all tenants...');

        if(empty($indexes))
        {
            return;
        }

        // process action for all tenants
        $this->tenantAdapter->getTenantModel()->query()->cursor()->each(function ($tenant) use($indexes,$function,$action) {
            $tenantReference = $tenant->{config('elastic-search.tenants_command_reference_field')};
            $this->info($action . ' tenant ' . $tenantReference . ' elastic index data: Processing');
            $this->tenantAdapter->initializeTenant($tenant);// initialize tenant to perform action on tenant database
            $function($indexes);// call action function like sync,delete,create index
            $this->info('--------------------------------------------------');
        });
    }
}
