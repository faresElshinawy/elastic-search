<?php

namespace Fareselshinawy\ElasticSearch\Traits;

use Illuminate\Pagination\LengthAwarePaginator;

trait ElasticQueryBuilderTrait
{
    private array $elasticQuery = ['bool' => ['must' => [], 'must_not' => [], 'should' => []]];
    private array $sortOptions = ['sort' => []];
    private int $totalElasticSearchResult;
    private int $queryStringIndexKey;
    private array $selectedFields = [];
    protected int $size = 10;
    protected int $page = 1;
    private $elasticQueryFilters = [];

    /**
     * Execute the constructed query
     *
     * @param integer $size
     * @param integer $page
     * @return void
     */
    public function executeElasticQuery(int $size = 0,int $page = 0)
    {
        $client     = $this->initializeElasticClient();
        $size       = $size == 0 ? config('elastic-search.default_size') : $size;
        $page       = $page == 0 ? request(config('elastic-search.default_page_variable'),1) : $page;
        $indexKey   = $this->getElasticIndexKey();

        // Execute elastic search query
        $response = $client->search(array_merge([
            'track_total_hits'  => true,
            'index' => $indexKey,
            'from'  => max(0,$size * ($page - 1)),
            'size'  => $size,
            'body' => [
                'query' => $this->elasticQuery,
                'sort'  => $this->sortOptions['sort'],
                '_source' => $this->selectedFields
            ]
        ],$this->elasticQueryFilters));

        $this->size = $size;
        $this->page = $page;

        return $this->handleElasticResponse($response);
    }

    /**
     * Filter terms query
     *
     * @param [type] $query
     * @param array $filters
     * @return self
     */
    public function scopeElasticQueryFilters($query,array $filters): self
    {
        $this->elasticQueryFilters = $filters;
        return $this;
    }

    /**
     * Range filter like data , price..etc
     *
     * @param [type] $query
     * @param [type] $field
     * @param [type] $from
     * @param [type] $to
     * @return self
     */
    public function scopeElasticRange($query, $field, $from, $to): self
    {
        // Add the range condition to the elastic query
        if (!is_null($from) || !is_null($to)) {
            $range = [];
            if (!is_null($from)) {
                $range['gte'] = $from; // Greater than or equal to
            }
            if (!is_null($to)) {
                $range['lte'] = $to; // Less than or equal to
            }

            $this->elasticQuery['bool']['must'][] = ['range' => [$field => $range]];
        }

        return $this;
    }

    /**
     * greater than or equal value
     *
     * @param [type] $field
     * @param [type] $value
     * @return self
     */
    public function scopeElasticGreaterOrEqual($query,$field, $value): self
    {
        $this->elasticQuery['bool']['must'][] = [
            'range' => [$field => ['gte' => $value]],
        ];
        return $this;
    }

    /**
     * less than or equal value
     *
     * @param [type] $field
     * @param [type] $value
     * @return self
     */
    public function scopeElasticLessOrEqual($query,$field, $value): self
    {
        $this->elasticQuery['bool']['must'][] = [
            'range' => [$field => ['lte' => $value]],
        ];
        return $this;
    }

    /**
     * Start term queries
     *
     * @param [type] $query
     * @param string $field
     * @param mixed $value
     * @param string $conditionType
     * @param [type] $subConditionType
     * @return self
     */
    public function scopeElasticTerm($query,string $field,mixed $value,$conditionType = 'must',$subConditionType = null): self
    {
        $term = empty($subConditionType) ? ['term' => [$field => $value]] : ['bool' => [$subConditionType => [['term' => [$field => $value]]]]];
        $this->elasticQuery['bool'][$conditionType][] = $term;
        return $this;
    }

    /**
     * elastic term must query
     *
     * @param [type] $query
     * @param string $field
     * @param string|integer|float|boolean $value
     * @return self
     */
    public function scopeElasticTermMust($query,string $field,string|int|float|bool $value): self
    {
        return $this->elasticTerm($field, $value);
    }

    /**
     * elastic term filter query
     *
     * @param [type] $query
     * @param string $field
     * @param string|integer|float|boolean $value
     * @return self
     */
    public function scopeElasticFilterTerm($query,string $field,string|int|float|bool $value): self
    {
        return $this->elasticTerm($field, $value,'filter');
    }

    /**
     * elastic term filter must not query
     *
     * @param [type] $query
     * @param string $field
     * @param string|integer|float|boolean $value
     * @return self
     */
    public function scopeElasticFilterMustNotTerm($query,string $field,string|int|float|bool $value): self
    {
        return $this->elasticTerm($field, $value,'filter','must_not');
    }

    /**
     * elastic term filter should query
     *
     * @param [type] $query
     * @param string $field
     * @param string|integer|float|boolean $value
     * @return self
     */
    public function scopeElasticFilterShouldTerm($query,string $field,string|int|float|bool $value): self
    {
        return $this->elasticTerm($field, $value,'filter','should');
    }

    /**
     * elastic term must not query
     *
     * @param [type] $query
     * @param string $field
     * @param string|integer|float|boolean $value
     * @return self
     */
    public function scopeElasticTermMustNot($query,string $field,string|int|float|bool $value): self
    {
        return $this->elasticTerm($field, $value,'must_not');
    }

    /**
     * elastic term should query
     *
     * @param [type] $query
     * @param string $field
     * @param string|integer|float|boolean $value
     * @return self
     */
    public function scopeElasticTermShould($query,string $field,string|int|float|bool $value): self
    {
        return $this->elasticTerm($field, $value,'should');
    }
    // End term queries

    // Start terms queries
    /**
     * elastic terms query
     *
     * @param [type] $query
     * @param string $field
     * @param array $values
     * @param string $conditionType
     * @param string|null|null $subConditionType
     * @return self
     */
    public function scopeElasticTerms($query,string $field, array $values,string $conditionType = 'must',string|null $subConditionType = null): self
    {
        $terms = empty($subConditionType) ?  ['terms' => [$field => $values]] : ['bool' => [$subConditionType => [['terms' => [$field => $values]]]]];
        $this->elasticQuery['bool'][$conditionType][] = $terms;
        return $this;
    }

    /**
     * elastic terms must query
     *
     * @param [type] $query
     * @param string $field
     * @param array $values
     * @return self
     */
    public function scopeElasticTermMustIn($query,string $field, array $values): self
    {
        return $this->elasticTerms($field,$values);
    }

    /**
     * elastic terms must not query
     *
     * @param [type] $query
     * @param string $field
     * @param array $values
     * @return self
     */
    public function scopeElasticTermMustNotIn($query,string $field, array $values): self
    {
        return $this->elasticTerms($field,$values,'must_not');
    }

    /**
     * elastics terms should query
     *
     * @param [type] $query
     * @param string $field
     * @param array $values
     * @return self
     */
    public function scopeElasticTermShouldIn($query,string $field, array $values): self
    {
        return $this->elasticTerms($field,$values,'should');
    }

    /**
     * elastic terms filter query
     *
     * @param [type] $query
     * @param string $field
     * @param array $values
     * @return self
     */
    public function scopeElasticFilterTermIn($query,string $field, array $values): self
    {
        return $this->elasticTerms($field,$values,'filter');
    }

    /**
     * elastic terms must not query
     *
     * @param [type] $query
     * @param string $field
     * @param array $values
     * @return self
     */
    public function scopeElasticFilterMustNotTermIn($query,string $field, array $values): self
    {
        return $this->elasticTerms($field,$values,'filter','must_not');
    }

    /**
     * elastic terms should query
     *
     * @param [type] $query
     * @param string $field
     * @param array $values
     * @return self
     */
    public function scopeElasticFilterShouldTermIn($query,string $field, array $values): self
    {
        return $this->elasticTerms($field,$values,'filter','should');
    }

    /**
     * general method to append a query string based on field(s) and query
     *
     * @param array $fields
     * @param string $query
     * @param string $operator
     * @param string $conditionType
     * @return self
     */
    private function appendElasticQueryString(array $fields, string $query, string $operator = 'AND', string $conditionType = 'must'): self
    {
        // Check if the specific condition type key exists, initialize if not
        if (empty($this->elasticQuery['bool'][$conditionType])) {
            $this->elasticQuery['bool'][$conditionType] = [];
        }

        // If it does not exist, initialize it
        if (empty($this->queryStringIndexKey)) {
            $this->elasticQuery['bool'][$conditionType][] = [
                'query_string' => [
                    'query' => '',
                    'fields' => []
                ]
            ];
            $this->queryStringIndexKey = count($this->elasticQuery['bool'][$conditionType]) - 1; // Last index
        }

        // Get a reference to the current query string
        $currentQueryString = &$this->elasticQuery['bool'][$conditionType][$this->queryStringIndexKey]['query_string'];

        // Append the new query to the existing query string with the specified operator
        if (!empty($currentQueryString['query'])) {
            $currentQueryString['query'] .= " {$operator} {$query}";
        } else {
            $currentQueryString['query'] = $query;
        }

        // Merge fields into the existing fields array without duplicates
        $currentQueryString['fields'] = array_unique(array_merge($currentQueryString['fields'], $fields));

        return $this;
    }

    /**
     * general method to build a query string based on field(s) and value(s)
     *
     * @param array|string $field
     * @param array|string $value
     * @param string $operator
     * @param string $conditionType
     * @return self
     */
    private function buildElasticQueryString(array|string $field, array|string $value, string $operator, string $conditionType = 'must'): self
    {
        // Initialize the query string
        $queryString = '';

        // Ensure $value is formatted correctly if it's an array
        $valueQuery = is_array($value) ? implode(' ', $value) : $value;

        // Handling array of fields
        if (is_array($field)) {
            // Generate individual queries for each field
            $queries = collect($field)->map(fn($f) => "({$f}:{$valueQuery})");
            // Join the field queries with the operator
            $queryString = $queries->implode(" {$operator} ");
            $fields = $field;
        } else {
            // Single field
            $queryString = "{$field}:{$valueQuery}";
            $fields = [$field];
        }

        // Append the built query string to the elastic query with the specified condition type
        $this->appendElasticQueryString($fields, $queryString, $operator, $conditionType);

        return $this;
    }

    /**
     * elasticWhere method for AND queries (default operator)
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticWhere($query,string $field,string $value): self
    {
        return $this->buildElasticQueryString($field, $value, 'AND', 'must');
    }

    /**
     * elasticOrWhere method for OR queries
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticOrWhere($query,string $field,string $value): self
    {
        return $this->buildElasticQueryString($field, $value, 'OR', 'should');
    }

    /**
     * query string starting with value%
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticWhereStartWith($query,string $field,string $value): self
    {
        return $this->buildElasticQueryString($field, "{$value}*", 'AND', 'must');
    }

    /**
     * query string or where start with value%
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticOrWhereStartWith($query,string $field,string $value): self
    {
        return $this->buildElasticQueryString($field, "{$value}*", 'OR', 'should');
    }

    /**
     * query string ending with %value
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticWhereEndWith($query,string $field,string $value): self
    {
        return $this->buildElasticQueryString($field, "*{$value}", 'AND', 'must');
    }

    /**
     * query string or where end with %value
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticOrWhereEndWith($query,string $field,string $value): self
    {
        return $this->buildElasticQueryString($field, "*{$value}", 'OR', 'should');
    }

    /**
     * query string like wildcard: *value* similar to (like %value% for mysql)
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticWhereLike($query,string $field,string $value): self
    {
        return $this->buildElasticQueryString($field, "*{$value}*", 'AND', 'must');
    }

    /**
     * query string or where like wildcard: *value* similar to (like %value% for mysql)
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticOrWhereLike($query,string $field,string $value): self
    {
        return $this->buildElasticQueryString($field, "*{$value}*", 'OR', 'should');
    }

    //Start wildcard queries
    /**
     * set wildcard query
     *
     * @param [type] $query
     * @param [type] $field
     * @param [type] $value
     * @return self
     */
    public function scopeElasticWildcard($query,$field, $value): self
    {
        $this->elasticQuery['bool']['must'][] = ['wildcard' => [$field => $value]];
        return $this;
    }

    /**
     * set %like% wildcard query
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticWildcardLike($query,string $field,string $value): self
    {
        return $this->elasticWildcard($field,"*$value*");
    }

    /**
     * set wildcard start with value% query
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticWildcardStartWith($query,string $field,string $value): self
    {
        return $this->elasticWildcard($field,"$value*");
    }

    /**
     * set wildcard end with %value query
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticWildcardEndWith($query,string $field,string $value): self
    {
        return $this->elasticWildcard($field,"*$value");
    }
    // End wildcard queries

    // Start string queries

    /**
     * query string not starting with value%
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticWhereNotStartWith($query,string $field,string $value): self
    {
        // Build the query using the must_not clause directly
        return $this->buildElasticQueryString($field, "{$value}*", 'AND', 'must_not');
    }

    /**
     * query string or not where starting with value%
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticOrWhereNotStartWith($query,string $field,string $value): self
    {
        return $this->buildElasticQueryString($field, "{$value}*", 'OR','must_not');
    }

    /**
     * query string not ending with %value
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticWhereNotEndWith($query,string $field,string $value): self
    {
        return $this->buildElasticQueryString($field, "*{$value}", 'AND', 'must_not');
    }

    /**
     * query string or not where ending with %value
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticOrWhereNotEndWith($query,string $field,string $value): self
    {
        return $this->buildElasticQueryString($field, "*{$value}", 'OR','must_not');
    }

    /**
     * query string not like wildcard: *value* similar to (not like %value% for mysql)
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticWhereNotLike($query,string $field,string $value): self
    {
        return $this->buildElasticQueryString($field, "*{$value}*", 'AND', 'must_not');
    }

    /**
     * query string or not where like wildcard: *value* similar to (not like %value% for mysql)
     *
     * @param [type] $query
     * @param string $field
     * @param string $value
     * @return self
     */
    public function scopeElasticOrWhereNotLike($query,string $field,string $value): self
    {
        return $this->buildElasticQueryString($field, "*{$value}*", 'OR','must_not');
    }

    // End string queries

    // Start match queries

    /**
     * set match query
     *
     * @param [type] $query
     * @param string $field
     * @param string|integer|float|boolean $value
     * @param string $conditionType
     * @return self
     */
    public function scopeElasticMatch($query,string $field,string|int|float|bool $value,$conditionType = 'must'): self
    {
        $this->elasticQuery['bool'][$conditionType][] = ['match' => [$field => $value]];
        return $this;
    }

    /**
     * set match phrase query
     *
     * @param [type] $query
     * @param string $field
     * @param string|integer|float|boolean $value
     * @param string $conditionType
     * @return self
     */
    public function scopeElasticMatchPhrase($query,string $field,string|int|float|bool $value,$conditionType = 'must'): self
    {
        $this->elasticQuery['bool'][$conditionType][] = ['match_phrase' => [$field => $value]];
        return $this;
    }

    /**
     * set must match query
     *
     * @param [type] $query
     * @param string $field
     * @param string|integer|float|boolean $value
     * @return self
     */
    public function scopeElasticWhereMatch($query,string $field,string|int|float|bool $value): self
    {
        return$this->elasticMatch($field, $value);
    }

    /**
     * set should match query
     *
     * @param [type] $query
     * @param string $field
     * @param string|integer|float|boolean $value
     * @return self
     */
    public function scopeElasticOrWhereMatch($query,string $field,string|int|float|bool $value): self
    {
        return$this->elasticMatch($field, $value,'should');
    }

    /**
     * must not match query
     *
     * @param [type] $query
     * @param string $field
     * @param string|integer|float|boolean $value
     * @return self
     */
    public function scopeElasticMatchMustNot($query,string $field,string|int|float|bool $value): self
    {
        return$this->elasticMatch($field, $value,'must_not');
    }

    /**
     * must match phrase query
     *
     * @param [type] $query
     * @param string $field
     * @param string|integer|float|boolean $value
     * @return self
     */
    public function scopeElasticWhereMatchPhrase($query,string $field,string|int|float|bool $value): self
    {
        return$this->elasticMatchPhrase($field, $value,'must');
    }

    /**
     * or Where Match Phrase query
     *
     * @param [type] $query
     * @param string $field
     * @param string|integer|float|boolean $value
     * @return self
     */
    public function scopeElasticOrWhereMatchPhrase($query,string $field,string|int|float|bool $value): self
    {
        return$this->elasticMatchPhrase($field, $value,'should');
    }

    /**
     * must not match phrase query
     *
     * @param [type] $query
     * @param string $field
     * @param string|integer|float|boolean $value
     * @return self
     */
    public function scopeElasticMatchPhraseMustNot($query,string $field,string|int|float|bool $value): self
    {
        return$this->elasticMatchPhrase($field, $value,'must_not');
    }

    /**
     * match all documents in the index, match all works similar to eloquent get() or all() it gets all documents exists in index
     *
     * @return array
     */
    public function scopeElasticMatchAll(): array
    {
        // Set the match_all query
        $this->elasticQuery['bool']['must'] = [
            'match_all' => new \stdClass(), // Using an empty object for clarity
        ];

        // Return the built query as an array
        return $this->elasticQuery;
    }
     // End match queries


    /**
     * add nested query
     *
     * @param [type] $query
     * @param string $path
     * @param callable $callback
     * @return self
     */
    public function scopeElasticNested($query, string $path, callable $callback): self
    {
        // Initialize the nested query structure
        $nestedQuery = [
            'nested' => [
                'path' => $path,
                'query' => ['bool' => ['must' => [], 'must_not' => [], 'should' => []]]
            ]
        ];

        // Use a fresh instance of the trait for the nested query context
        $nestedContext = new static();

        // Call the callback to modify the nested query (it allows adding further queries inside the nested query)
        $callback($nestedContext);

        // Merge the built queries from the nested context into the nested query
        $nestedQuery['nested']['query']['bool'] = array_merge($nestedQuery['nested']['query']['bool'], $nestedContext->elasticQuery['bool']);

        // Add the nested query to the main Elasticsearch query
        $this->elasticQuery['bool']['must'][] = $nestedQuery;

        return $this;
    }

    /**
     * handle elastic response result response
     *
     * @param [type] $response
     * @return void
     */
    public function handleElasticResponse($response)
    {
        $results = [];
        if(isset($response['hits']))
        {
            $this->totalElasticSearchResult = $response['hits']['total']['value'];
            $results = $response['hits'];
        }
        return $results;
    }

    /**
     * select specific fields to be returned from elastic
     *
     * @param [type] $query
     * @param array $fields
     * @return self
     */
    public function scopeElasticSelect($query, array $fields = []): self
    {
        if (!empty($fields)) {
            $this->selectedFields = $fields;
        }
        return $this;
    }

    /**
     * because elastic does not return all values when you request query it has limit 10000 record so we are paginating with total result we got from elastic search index
     *
     * @param [type] $query
     * @param integer $size
     * @param integer $page
     * @return void
     */
    public function scopeElasticPaginate($query,int $size = 0,int $page = 0)
    {
        $elasticResult = $this->executeElasticQuery($size,$page);

        $totalResult = $this->totalElasticSearchResult;
        $perPage     = $this->size;
        $page        = $this->page;

        $query = $this->applyElasticResultOnQuery($query,$elasticResult);

        return new LengthAwarePaginator(
            $query->get(),
            $totalResult,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()] // Pagination links
        );
    }

    /**
     * get all result
     *
     * @param [type] $query
     * @param integer $size
     * @return void
     */
    public function scopeElasticGet($query,int $size = 9999)
    {
        $elasticResult = $this->executeElasticQuery($size);

        $query = $this->applyElasticResultOnQuery($query,$elasticResult);

        return $query->get();
    }

    /**
     * get all result from elastic direct
     *
     * @param [type] $query
     * @param integer $size
     * @return void
     */
    public function scopeElasticGetRaw($query,int $size = 9999)
    {
        $elasticResult = $this->executeElasticQuery($size);
        return $elasticResult;
    }

    /**
     * apply result from elastic to our query by set condition to get data by ids
     *
     * @param [type] $query
     * @param [type] $elasticResult
     * @return void
     */
    private function applyElasticResultOnQuery($query,$elasticResult)
    {
        $ids = array_column($elasticResult['hits'] ?? [],'_id');
        $query->whereIn('id',$ids);
        return $query;
    }

    /**
     * apply sort to elastic
     *
     * @param [type] $query
     * @param string $field
     * @param string $order
     * @return self
     */
    public function scopeElasticSortBy($query,string $field,string $order = 'asc'): self
    {
        $this->sortOptions['sort'][] = [$field => ['order' => strtolower($order)]];
        return $this;
    }

    /**
     * get elastic query to Array
     *
     * @param  $query
     * @return array
     */
    public function scopeElasticQueryToArray($query): array
    {
        return $this->elasticQuery;
    }

    /**
     * get elastic query as json
     *
     * @param  $query
     * @return string
     */
    public function scopeElasticQueryToJson($query): string
    {
        return json_encode($this->elasticQuery);
    }
}
