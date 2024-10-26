<?php

namespace Fareselshinawy\ElasticSearch\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;

trait HasElasticSearch
{
    use ElasticClientTrait,ElasticIndexSchemaTrait,ElasticQueryBuilderTrait;

    /**
     * sync latest changes for current record to elastic index we have
     *
     * @return void
     */
    public function syncIndexableRecord(): void
    {
        $this->loadMissing($model->elasticSyncAbleRelations ?? []);
        $indexKey  = $this->getElasticIndexKey();
        try{
            $client    = $this->initializeElasticClient();

            // check if class use soft delete and if current record is trashed  and if sync trashed records is not enabled
            if (in_array(SoftDeletes::class, class_uses($this)) && $this->trashed() && !config('elastic-search.enable_trashed_record_sync')) {
                return;
            }

            $params   = [
                'index' => $indexKey,
                'id'    => $this->id,
                'body'  => $this->getElasticSearchSyncAbleData()
            ];

            $client->index($params);
        }catch(\Exception $e){
            Log::error("failed to sync record with id " . $this->id . ' data to elastic search index for ' . $indexKey . "\n" . $e->getMessage());
        }
    }
    
    /**
     * sync latest changes made to current model to the indexes that depend on like orders index has user name if user name changed we need the updates to reflect relations like orders
     *
     * @return void
     */
    public function syncDependentIndexesRelations()
    {
        $model = $this;

        if(!count($model->dependentIndexesRelations ?? []))
        {
            return;
        }

        $dependentIndexesRelations = $model->dependentIndexesRelations;

        $dependentIndexes          = array_keys($dependentIndexesRelations);

        $model->loadMissing($dependentIndexes);

        // initialize elastic client
        $client    = $model->initializeElasticClient();

        foreach($dependentIndexesRelations as $dependentRelation => $dependentRelationClass)
        {
            // get effected relation ids
            $effectedRelationIds = $model->{$dependentRelation}()->get()->pluck('id');
            // get relation class
            $indexable = new $dependentRelationClass;
            // get index key
            $indexKey  = $indexable->getElasticIndexKey();
            $chunkSize = 3000;
            // prepare query for index updates
            $indexableCollection = $indexable::query()->with($indexable->elasticSyncAbleRelations ?? [])->whereIn('id', $effectedRelationIds);

            if(in_array(SoftDeletes::class, class_uses($indexable::class)))
            {
                if(config('elastic-search.enable_trashed_record_sync'))
                {
                    $indexableCollection = $indexableCollection->withTrashed();
                }else{
                    $indexableCollection = $indexableCollection->whereNull('deleted_at');
                }
            }
            // process index changes
            $indexableCollection->chunk($chunkSize, function ($collection) use($client,$indexKey) {
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

                $client->bulk($bulkParams);
            });
        }
    }

    /**
     * set index schema for elastic index
     *
     * @return array
     */
    public function getIndexableProperties(): array
    {
        return $this->prepareElasticIndexSchema();
    }

    /**
     * get elastic index key
     *
     * @return string
     */
    public function getElasticIndexKey(): string
    {
        return $this->indexPrefix() . $this->getTable();
    }

    /**
     * get index prefix from config
     *
     * @return string
     */
    public function indexPrefix(): string
    {
        return config('elastic-search.default_index_prefix');
    }

    /**
     * get index max window size for requests
     *
     * @return integer
     */
    public function getIndexMaxResultSize(): int
    {
        if(config('elastic-search.enable_dynamic_result_size',false))
        {
            return get_class($this)::count();
        }
        return config('elastic-search.max_result_size',10000);
    }
}
