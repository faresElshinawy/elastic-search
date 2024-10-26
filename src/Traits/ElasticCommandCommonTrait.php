<?php

namespace Fareselshinawy\ElasticSearch\Traits;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Fareselshinawy\ElasticSearch\Traits\ElasticClientTrait;



trait ElasticCommandCommonTrait
{
    use ElasticClientTrait;

    protected $operationStatus = true;
    /**
     * get config key for indexable model
     *
     * @return string
     */
    // get indexable configuration key for indexable connection tenant/central like config('central_indexable.*')
    public function indexableConfigKey(): string
    {
        return 'central_indexable';
    }

    /**
     * prepare indexable models based on user selection
     *
     * @param string $confirmationMessage
     * @return array|null
     */
    // get and prepare user options for indexes to apply action to
    public function prepareIndexes(string $confirmationMessage = 'Are you sure you want to perform this index action for all options? yes or no'): array|null
    {
        $indexes = [];
        $indexable = $this->getIndexable();
        $indexableKeys = array_keys($indexable);

        $index = $this->displayOptions($indexableKeys);

        if ($index == 'all') {
            if(!$this->requestActionConfirmation($confirmationMessage))
            {
                return null;
            }

            array_pop($indexable);

            $indexes = $indexable;
        } elseif (in_array($index,$indexableKeys)) {
            $indexes[] = $indexable[$index];
        }

        return $indexes;
    }

    /**
     * display user option for elastic indexable models
     *
     * @param array $options
     * @return string
     */
    public function displayOptions(array $options = []): string
    {
        $index = $this->choice(
            'Select a indexable to create an index for select number between 0 to ' . max(0,count($options) - 1),
            $options,
            null,
            null,
            false
        );
        return $index;
    }

    /**
     * get user option for available indexable model exists in config dynamic based on command type tenant/central connection
     *
     * @return array
     */
    public function getIndexable(): array
    {
        return array_merge(config('elastic-search.' . $this->indexableConfigKey(),[]),[
            'all' => 'all'
        ]);
    }

    /**
     * create new elastic index for specific model
     *
     * @param Model $indexable
     * @return void
     */
    // create elastic search index for indexable class
    public function createElasticIndex(Model $indexable): void
    {
        try{
                // Initialize the elastic client
                $client = $this->initializeElasticClient();

                // Get the index key for the model
                $indexKey = $indexable->getElasticIndexKey();

                $params = [
                    'index'                 => $indexable->getElasticIndexKey(),
                    'max_result_window'     => $indexable->getIndexMaxResultSize(),
                    'body' => [
                        'mappings' => [
                            'properties' => $indexable->getIndexableProperties()
                        ]
                    ]
                ];

                $settingsParams = [
                    'index' => $indexKey,
                    'body' => [
                        'index' => [
                            'max_result_window' => $indexable->getIndexMaxResultSize(),
                        ],
                    ],
                ];

                $client->indices()->create($params);
                $client->indices()->putSettings($settingsParams);
                $this->info($indexable::class . ' elastic index created successfully');
        }
        catch(Exception $e)
        {
            $this->operationStatus = false;
            $this->error($indexable::class . ' elastic index failed to create');
            $this->error($e->getMessage());
        }
    }

    /**
     * update index information for specific index model like adding new fields
     *
     * @param Model $indexable
     * @return void
     */
    public function updateElasticIndex(Model $indexable): void
    {
        try {
            // Initialize the elastic client
            $client = $this->initializeElasticClient();

            // Get the index key for the model
            $indexKey = $indexable->getElasticIndexKey();

            // Update index mappings
            $params = [
                'index'             => $indexKey,
                'max_result_window'   => $indexable->getIndexMaxResultSize(),
                'body' => [
                    'properties' => $indexable->getIndexableProperties(),
                ]
            ];

            $settingsParams = [
                'index' => $indexKey,
                'body' => [
                    'index' => [
                        'max_result_window' => $indexable->getIndexMaxResultSize(),
                    ],
                ],
            ];

            // Check if index exists, then update mappings
            if ($client->indices()->exists(['index' => $indexKey])) {
                $client->indices()->putMapping($params);
                $client->indices()->putSettings($settingsParams);
                $this->info($indexable::class . ' elastic index updated successfully');
            } else {
                $this->error($indexable::class . ' elastic index does not exist.');
            }

        } catch (Exception $e) {
            $this->operationStatus = false;
            $this->error('Failed to update elastic index for ' . $indexable::class);
            $this->error($e->getMessage());
        }
    }

    /**
     * delete elastic search index for specific index model
     *
     * @param Model $indexable
     * @return void
     */
    // delete elastic search index for indexable class
    public function deleteElasticIndex(Model $indexable): void
    {
        $client     = $this->initializeElasticClient();
        $indexKey   = $indexable->getElasticIndexKey();
        $params = ['index' => $indexKey];
        try {
            $client->indices()->delete($params);
            $this->info('Deleting elastic index ' . $indexKey . ' for ' . $indexable::class);
        } catch (\Exception $e) {
            $this->operationStatus = false;
            $this->error('Error deleting ' . 'Elastic index ' . $indexKey . ' for ' . $indexable::class);
        }
    }

    /**
     * function for sync index data to elastic search index for specific model
     *
     * @param Model $indexable
     * @return void
     */
    // sync elastic search index for indexable class
    public function syncIndexable(Model $indexable): void
    {
        // get indexable index key
        $indexKey  = $indexable->getElasticIndexKey();
        try {
            // initialize elastic client
            $client    = $this->initializeElasticClient();
            // get index chunk size
            $chunkSize = config('elastic-search.index_sync_chunk_size');
            // prepare the query loading relation
            $indexableCollection = $indexable::query()->with($indexable->elasticSyncAbleRelations ?? []);
            // check if class has soft delete
            if(in_array(SoftDeletes::class, class_uses($indexable::class)))
            {
                if(config('elastic-search.enable_trashed_record_sync'))
                {
                    $indexableCollection = $indexableCollection->withTrashed();
                }else{
                    $indexableCollection = $indexableCollection->whereNull('deleted_at');
                }
            }

            // cloning the query to get total items count for sync analysis
            $itemsToSync = $indexableCollection->clone()->count();
            // chunk records and prepare request payload
            $indexableCollection->chunk($chunkSize, function ($collection) use($client,$indexable,$indexKey,$chunkSize,&$itemsToSync) {
                $bulkParams = ['body' => []];

                foreach ($collection as $collectionItem) {
                    $bulkParams['body'][] = [
                        'index' => [
                            '_index' => $indexKey,
                            '_id'    => $collectionItem->id,
                        ]
                    ];

                    $bulkParams['body'][] = $collectionItem->getElasticSearchSyncAbleData();
                }

                if (empty($bulkParams['body'])) {
                    $this->info('Elastic index ' . $indexKey . ' data is up to date for ' . $indexable::class);
                }

                $response = $client->bulk($bulkParams);

                if ($response->getStatusCode() !== 200) {
                    Log::error('Bulk sync failed with status code: ' . $response->getStatusCode());
                    $this->operationStatus = false;
                    return;
                }

                if (isset($response['errors']) && $response['errors']) {
                    Log::error('Failed to sync index data for ' . $indexKey,$response['items']);
                    $this->error('Failed to sync index data for ' . $indexKey . ' check laravel log to see the issue');
                    $this->operationStatus = false;
                    return;
                }

                $itemSyncedCount = $chunkSize > $itemsToSync ?  $itemsToSync : $chunkSize;

                $itemsToSync -= $chunkSize;

                $this->info('Sync elastic index ' . $indexKey . ' data for ' . $indexable::class . "({$itemSyncedCount} done)(" . max(0,$itemsToSync) . " to go)");
            });

        } catch (Exception $e) {
            $this->info('Failed to sync elastic index ' . $indexKey . ' data for ' . $indexable::class);
            Log::error($e);
        }
    }

    /**
     * request user confirmation function making sure each important step is confirmed by user before starting any process
     *
     * @param string $message
     * @return boolean
     */
    // request user confirmation before specific action
    public function requestActionConfirmation(string $message): bool
    {
        $confirmation = $this->ask($message);

        if ($confirmation !== 'yes') {
            $this->info('Operation cancelled.');
            $this->operationStatus = false;
            return false;
        }
        return true;
    }

    /**
     * process index operation for all selected indexes user/products..etc
     *
     * @param array $indexes
     * @param callable $function
     * @return void
     */
    // run indexes function or user indexes selection
    protected function processIndexesOperation(array $indexes = [],callable $function): void
    {
        foreach ($indexes as $indexable) {
            $function($indexable);
        }
    }
}
