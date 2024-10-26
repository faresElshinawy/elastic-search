<?php

namespace Fareselshinawy\ElasticSearch\Traits;

use Elastic\Elasticsearch\ClientBuilder;

trait ElasticClientTrait
{
    /**
     * initialize elastic client to interact with elastic search to manage indexes
     *
     * @return object
     */
    public function initializeElasticClient(): object
    {
        $client = ClientBuilder::create()
            ->setHosts([config('elastic-search.api_end_point')])
            ->setApiKey(config('elastic-search.api_key'))
            ->build();
        return $client;
    }
}
