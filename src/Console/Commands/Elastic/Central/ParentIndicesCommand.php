<?php

namespace Fareselshinawy\ElasticSearch\Console\Commands\Elastic\Central;

use Illuminate\Console\Command;
use Fareselshinawy\ElasticSearch\Traits\ElasticCommandCommonTrait;

class ParentIndicesCommand extends Command
{
    use ElasticCommandCommonTrait;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'elastic:';


    public function printSuccessFulMessage()
    {
        if($this->operationStatus)
        {
            $this->info('Done ;)');
        }
    }
}
