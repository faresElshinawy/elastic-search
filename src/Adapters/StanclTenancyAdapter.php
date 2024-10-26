<?php

namespace Fareselshinawy\ElasticSearch\Adapters;

use Fareselshinawy\ElasticSearch\Adapters\Interfaces\TenantAdapterInterface;

class StanclTenancyAdapter implements TenantAdapterInterface
{
    private $tenantModel;
    private $tenancyFacade;

    public function __construct()
    {
        $tenantModel    = config('elastic-search.tenant_model');
        $tenancyFacade  = config('elastic-search.tenancy_facade');
        $this->tenantModel   = new $tenantModel;
        $this->tenancyFacade  = new $tenancyFacade;
    }

    /**
     * initialize tenant by reference
     *
     * @param [type] $reference
     * @return boolean
     */
    public function initializeTenantByReference($reference): bool
    {
        $tenant = $this->tenantModel::where(config('elastic-search.tenant_model_command_reference_field','id'),$reference)->first();
        if(!$tenant){return false;}
        $this->initializeTenant($tenant);
        return true;
    }

    /**
     * initialize given tenant
     *
     * @param [type] $tenant
     * @return void
     */
    public function initializeTenant($tenant): void
    {
        $this->tenancyFacade::initialize($tenant);
    }

    /**
     * get tenant model
     *
     * @return object
     */
    public function getTenantModel(): object
    {
        return $this->tenantModel;
    }
}
