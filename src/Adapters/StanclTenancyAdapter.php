<?php

namespace Fareselshinawy\ElasticSearch\Adapters;

use Exception;
use Fareselshinawy\ElasticSearch\Adapters\Interfaces\TenantAdapterInterface;

class StanclTenancyAdapter implements TenantAdapterInterface
{
    private $tenantModel;
    private $tenancyFacade;

    public function __construct()
    {
        $tenantModel    = config('elastic-search.tenant_model');
        $tenancyFacade  = config('elastic-search.tenancy_facade');

        if(!$this->packageInstalled())
        {
            return;
        }

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
        $this->ensurePackageIsInstalled();
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
        $this->ensurePackageIsInstalled();
        $this->tenancyFacade::initialize($tenant);
    }

    /**
     * get tenant model
     *
     * @return object
     */
    public function getTenantModel(): object
    {
        $this->ensurePackageIsInstalled();
        return $this->tenantModel;
    }

    /**
     * check if stancl/tenancy package is install to use the adapter
     *
     * @return boolean
     */
    public function packageInstalled(): bool
    {
        $installedPackages = json_decode(file_get_contents(base_path('composer.json')), true);
        if (isset($installedPackages['require']['fareselshinawy/elastic-search'])) {
            return true;
        } else {
            return false;
        }
    }

    private function ensurePackageIsInstalled(): void
    {
        if (!$this->packageInstalled()) {
            throw new Exception('Please make sure to install and setup stancl/tenancy package to be able to use this command.');
        }
    }
}
