<?php

namespace Fareselshinawy\ElasticSearch\Providers;

use Illuminate\Support\ServiceProvider;
use Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Central\SyncElasticIndices;
use Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Central\CreateElasticIndices;
use Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Central\DeleteElasticIndices;
use Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Central\UpdateElasticIndices;
use Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Tenants\SyncTenantElasticIndices;
use Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Tenants\CreateTenantElasticIndices;
use Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Tenants\DeleteTenantElasticIndices;
use Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Tenants\UpdateTenantElasticIndices;


class ElasticSearchServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/elastic-search.php' => config_path('elastic-search.php'),
        ], 'elastic-search-config');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/elastic-search.php', 'elastic-search'
        );

        $this->commands($this->preparePackageCommands());
    }

    public function preparePackageCommands()
    {
        $commands = [
            CreateElasticIndices::class,
            UpdateElasticIndices::class,
            DeleteElasticIndices::class,
            SyncElasticIndices::class,
        ];

        // if tenancy is set load its command for elastic search
        if(config('elastic-search.enable_elastic_for_tenants'))
        {
            $commands = array_merge($commands,[
                CreateTenantElasticIndices::class,
                UpdateTenantElasticIndices::class,
                DeleteTenantElasticIndices::class,
                SyncTenantElasticIndices::class
            ]);
        }

        return $commands;
    }
}
