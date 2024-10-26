# Laravel Elasticsearch Integration Package

The package provides seamless integration with Elasticsearch, supporting multi-tenant architecture and offering an intuitive query builder interface.

## Features

- Easy integration with Laravel models
- Built-in multi-tenant support using stancl/tenancy
- Fluent query builder interface
- Automated index management
- Configurable schema mappings
- Relationship syncing support
- Artisan commands for index management

## Installation

You can install the package via composer:

```bash
composer require fareselshinawy/elasticsearch
```

If you plan to use multi-tenant features:
multi tenant can be extend to other packages be making your own tenant adapter implementing TenantAdapterInterface
```bash
composer require stancl/tenancy
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Fareselshinawy\ElasticSearch\ElasticSearchServiceProvider"
```

Configure your Elasticsearch credentials in your `.env` file:

```env
ELASTIC_API_ENDPOINT=your-elasticsearch-endpoint
ELASTIC_API_KEY=your-api-key
ELASTIC_INDEX_PREFIX=optional-prefix
ELASTIC_DEFAULT_SIZE_VARIABLE=10
ELASTIC_INDEX_SYNC_CHUNK_SIZE=500
ENABLE_ELASTIC_FOR_TENANTS=true
```

## Usage

### Model Setup

To make your model searchable with Elasticsearch, add the following methods to your model:

```php
use Fareselshinawy\ElasticSearch\Traits\ElasticSearchable;

class User extends Model
{
    use ElasticSearchable;

    // Required: Define the data to be synchronized with Elasticsearch
    public function getElasticSearchSyncAbleData()
    {
        return [
            "name"  => Str::lower($this->name),
            "email" => $this->email,
            "id"    => $this->id
        ];
    }

    // Optional: Define custom index mappings
    public function getIndexableProperties()
    {
        return [
            "name"  => ['type' => 'keyword'],
            "email" => ['type' => 'keyword'],
            "id"    => ['type' => 'integer']
        ];
    }

    // Optional: Define relations to sync
    public $elasticSyncAbleRelations = [];

    // Optional: Define dependent indexes that should be updated
    public $dependentIndexesRelations = [];

    // Optional: Custom index name
    public function getElasticIndexKey()
    {
        return $this->indexPrefix() . 'users';
    }
}
```

### Query Builder Usage

The package provides a fluent interface for building Elasticsearch queries:

```php
// Basic queries
User::elasticWhere('name', 'John')
    ->elasticOrWhere('email', 'john@example.com')
    ->elasticGet();

// Wildcard searches
User::elasticWhereLike('name', 'jo')
    ->elasticWhereStartWith('email', 'john')
    ->elasticGet();

// Range queries
User::elasticRange('age', 18, 30)->elasticGet();

// Term queries
User::elasticTermMust('status', 'active')
    ->elasticTermMustNot('role', 'guest')
    ->elasticGet();

// Match queries
User::elasticWhereMatch('description', 'search text')
    ->elasticWhereMatchPhrase('title', 'exact phrase')
    ->elasticGet();

// Nested queries
User::elasticNested('orders', function($query) {
    $query->elasticWhere('status', 'completed');
})->elasticGet();

// Sorting
User::elasticSortBy('created_at', 'desc')->elasticGet();

// Pagination
User::elasticWhere('status', 'active')->elasticPaginate(15);
```

### Available Query Methods

#### Term Queries
- `elasticTerm(field, value, conditionType = 'must')`
- `elasticTermMust(field, value)`
- `elasticTermMustNot(field, value)`
- `elasticTermShould(field, value)`

#### Terms Queries (Arrays)
- `elasticTerms(field, values, conditionType = 'must')`
- `elasticTermMustIn(field, values)`
- `elasticTermMustNotIn(field, values)`
- `elasticTermShouldIn(field, values)`

#### Match Queries
- `elasticMatch(field, value, conditionType = 'must')`
- `elasticWhereMatch(field, value)`
- `elasticOrWhereMatch(field, value)`
- `elasticMatchMustNot(field, value)`

#### Wildcard Queries
- `elasticWildcard(field, value)`
- `elasticWildcardLike(field, value)`
- `elasticWildcardStartWith(field, value)`
- `elasticWildcardEndWith(field, value)`

#### Range Queries
- `elasticRange(field, from, to)`
- `greaterOrEqual(field, value)`
- `lessOrEqual(field, value)`

### Artisan Commands

The package provides several Artisan commands for index management:

#### Standard Commands
```bash
php artisan elastic:index-create        # Create index
php artisan elastic:index-delete        # Delete index
php artisan elastic:index-sync          # Sync data
php artisan elastic:index-update        # Update index
```

#### Multi-tenant Commands
```bash
php artisan elastic:tenants-index-create  # Create tenant index
php artisan elastic:tenants-index-delete  # Delete tenant index
php artisan elastic:tenants-index-sync    # Sync tenant data
php artisan elastic:tenants-index-update  # Update tenant index
```

For tenant-specific operations, use the `--tenantReference` flag:
```bash
php artisan elastic:tenants-index-sync --tenantReference=tenant1
```

## Multi-tenant Support

The package integrates with `stancl/tenancy` for multi-tenant support. Configure tenant-specific settings in the config file:

```php
'enable_elastic_for_tenants' => true,
'tenants_indexable' => [
    'user' => new App\Models\User
],
'tenant_model' => 'App\Models\Tenant',
'tenancy_facade' => 'Stancl\Tenancy\Facades\Tenancy',
```

## Security

If you discover any security-related issues, please email [faresleshinawy560@gmail.com] instead of using the issue tracker.

## Credits

- [Fares Ashraf Ibrahim Elshinawy]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
