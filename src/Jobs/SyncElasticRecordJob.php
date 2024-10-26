<?php

namespace Fareselshinawy\ElasticSearch\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Database\Eloquent\SoftDeletes;

class SyncElasticRecordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly object $model){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $model = $this->model;
        $model->syncIndexableRecord();
        $model->syncDependentIndexesRelations();
    }
}
