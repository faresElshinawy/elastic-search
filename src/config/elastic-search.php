<?php

return [
    /*
        to use elastic search you need to set needed credentials api end point and api key
    */
    'api_end_point' => env('ELASTIC_API_ENDPOINT'),
    'api_key'       => env('ELASTIC_API_KEY'),

    /*
        default_schema_types is where you can specify the default types to be set for model fields in case you did not set getIndexableProperties() for model index fields
    */
    'default_schema_types' => [
        // String types
        'string'        => 'keyword',
        'char'          => 'keyword',
        'varchar'       => 'keyword',
        'text'          => 'text',
        'mediumtext'    => 'text',
        'longtext'      => 'text',
        'json'          => 'text',
        'jsonb'         => 'text',

        // Numeric types
        'integer'  => 'integer',
        'bigint'   => 'long',
        'smallint' => 'short',
        'tinyint'  => 'byte',
        'float'    => 'float',
        'double'   => 'double',
        'decimal'  => 'scaled_float',

        // Date & time types
        'date'     => 'date',
        'datetime' => 'date',
        'timestamp'=> 'date',
        'time'     => 'date',

        // Boolean type
        'boolean'  => 'boolean',

        // Binary types
        'blob'     => 'binary',
        'binary'   => 'binary',

        // Miscellaneous
        'enum'     => 'keyword',
        'uuid'     => 'keyword',
    ],

    /*
        below settings is for specify the variable sent with request for page number and size (item per request/page) and you can set default value
    */
    'default_page_variable'  => env('ELASTIC_DEFAULT_PAGE_VARIABLE',1),
    // note you can make helper function for getting length from request and pass it here
    'default_size'         => env('ELASTIC_DEFAULT_SIZE_VARIABLE ',10),

    /*
        to set chunk size for index data sync commands you are free to change index_sync_chunk_size value to what you prefer
    */
    'index_sync_chunk_size'  => env('ELASTIC_INDEX_SYNC_CHUNK_SIZE',500),

    /*
        you can set any prefix you want for elastic index from default_index_prefix but notice that if you are dealing with tenants,
        tenants domain will be set after the prefix so if prefix is live and we are on tenant x so prefix will be live_x...etc
    */
    'default_index_prefix'  => env('ELASTIC_INDEX_PREFIX',''),

    /*
        in central_indexable you can add the models you want to be able to make index and use elastic search for,
        set the name you want as key and class as value
    */
    'central_indexable' => [
        'user'          => new App\Models\User
    ],

    /*
        elastic search by default has max window size set to 10000
        so users can't request records if (size + from) is above 10000
        here you can control set it to be dynamic for set to specific value
        if you set enable_dynamic_window_size to true it will set the max window size for index based on total items exists on model table from database by querying count
        when index is created or updated
    */
    'max_result_size'               =>  env('ELASTIC_MAX_RESULT_SIZE',10000),
    'enable_dynamic_result_size'    => true,

    /*
        set enable_trashed_record_sync to true will enable syncing for soft deleted records
    */
    'enable_trashed_record_sync'  => false,

    // -------------------- TENANTS SPECIFIC --------------------

    /*
        to use elastic index tenants command make sure you have installed stancl/tenancy package and set enable_elastic_for_tenants to true
    */
    'enable_elastic_for_tenants' => env('ENABLE_ELASTIC_FOR_TENANTS',true),

    /*
        because you may use diff indexable models for tenants so tenants indexes is set in tenants_indexable option
    */
    'tenants_indexable'    => [
        'user'          => new App\Models\User
    ],

    /*
        to run elastic tenant commands you need to set tenant model and tenancy model which is provided by stancl/tenancy package
    */
    'tenant_model'      => 'App\Models\Tenant',
    'tenancy_facade'    => 'Stancl\Tenancy\Facades\Tenancy',

    /*
        when using tenants command for elastic this value will be used to specify which field we are matching --tenantReference value with
    */
    'tenants_command_reference_field' => 'id',

    /*
        adapter is the way package use to interact with tenants, in case you are not using stancl/tenancy package you are free to
        implement TenantAdapterInterface for your custom adapter and pass replace it with default stancl/tenancy adapter
    */
    'tenant_adapter'      => "Fareselshinawy\ElasticSearch\Adapters\StanclTenancyAdapter",
];
