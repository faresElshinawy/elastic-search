<?php

namespace Fareselshinawy\ElasticSearch\Adapters\Interfaces;

interface TenantAdapterInterface
{
    /**
     * function to initialize tenant by reference value it can be id/name/domain can be specified throw tenants_command_reference_field
     *
     * @param [type] $reference
     * @return boolean
     */
    public function initializeTenantByReference($reference): bool;

    /**
     * initialize tenant
     *
     * @param [type] $tenant
     * @return void
     */
    public function initializeTenant($tenant): void;

    /**
     * get tenant model
     *
     * @return object
     */
    public function getTenantModel(): object;
}
