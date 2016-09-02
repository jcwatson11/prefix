<?php

namespace Fh\QueryBuilder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Route;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;

class QueryBuilder {

    // Eloquent model
    protected $model;

    // The query parser member variable
    protected $parser;

    // Function to use when creating a new model so it can be mocked
    protected $modelCreationCallback;

    // Default namespace for model names found in the routeMap
    public $strModelNamespace;

    // Array of abstracted clause types that the builder supports.
    protected $builderClauses = [];

    // Sets the paging style: either 'page=' or 'limit/offset'
    public $pagingStyle;

    // Public access to builder just in case someone wants to modify it before
    // calling paginate
    public $builder;

    // Locale code for translated attributes.
    protected $locale;

    /**
     * Constructs the query builder
     * @param array   $routeToModelMap Mapping of route segment names
     *                to a Model name so that we can resolve
     *                parent-child relationships
     * @param Model   $model   Illuminate\Database\Eloquent\Model
     * @param Request $request Illuminate\Http\Request
     */
    public function __construct(RestMapperInterface $restMapper, Request $request) {
        $this->restMapper        = $restMapper; // for cloning
        $this->request           = $request; // for cloning
        $routeToModelMap         = $restMapper->getRestMap();
        $this->parser            = new QueryParser($routeToModelMap,$request);
        $this->strModelNamespace = config('fh-laravel-api-query-builder.modelNamespace');
        $this->pagingStyle       = config('fh-laravel-api-query-builder.pagingStyle');
        $this->initializeWherePrefixes();
        $this->model             = $this->resolveModel();
        $this->builder           = $this->model->newQuery();
    }

    /**
     * Sometimes we might need to completely remake the query builder.
     * @return QueryBuilder
     */
    public function remake() {
        return new static($this->restMapper,$this->request);
    }

    /**
     * Resolves the model from the routeToModelMap provided
     * in the constructor.
     * @return void
     */
    public function resolveModel() {
        $strModelName = $this->parser->getModelName();

        $strClassPath = $this->strModelNamespace . '\\' . $strModelName;
        return $this->createModel($strClassPath);
    }

    /**
     * Set up all of the various filter clauses that our API supports.
     * @return void
     */
    public function initializeWherePrefixes() {
        $qb = $this;
        // TODO:
        // Must modify array values by reference because of array_walk
        // Options: get rid of array_walk, or modify closures
        $this->builderClauses = [
            'isnull'       => new BuilderClause('isnull','whereNull')
            ,'isnotnull'   => new BuilderClause('isnotnull','whereNotNull')
            ,'orwhere'     => new BuilderClause('orwhere','orWhere','=')
            ,'where'       => new BuilderClause('where','where','=')
            ,'sortbychild'=> new OrderParentByChildBuilderClause('sortbychild')
            ,'orderby'     => new BuilderClause('orderby','orderBy')
            ,'groupby'     => new BuilderClause('groupby','groupBy')

            ,'between'     => new BuilderClause('between','whereBetween',null,$this->getValuesToArrayFunction(),false)
            ,'notinarray'  => new BuilderClause('notinarray','whereNotIn',null,$this->getValuesToArrayFunction(),false)
            ,'inarray'     => new BuilderClause('inarray','whereIn',null,$this->getValuesToArrayFunction(),false)
            ,'like'        => new BuilderClause('like','where','LIKE',function($value) {
                return "%$value%";
            },false)
            ,'orlike'      => new BuilderClause('orlike','orWhere','LIKE',function($value) {
                return "%$value%";
            },false)
            ,'greaterthan' => new BuilderClause('greaterthan','where','>')
            ,'lessthan'    => new BuilderClause('lessthan','where','<')
            ,'greaterthanorequalto' => new BuilderClause('greaterthan','where','>=')
            ,'lessthanorequalto'    => new BuilderClause('lessthan','where','<=')
        ];
    }

    /**
     * Returns a simple value modifier function for builder
     * clauses that expect array values.
     * @return array
     */
    public function getValuesToArrayFunction() {
        return function($value) {
            if(is_array($value)) return $value;
            return [$value];
        };
    }

    /**
     * Build the request parameters into a query builder.
     * @return QueryBuilder this
     */
    public function build() {

        $this->filterByParentRelation()
             ->withTrashed()
             ->onlyTrashed()
             ->includeRelations()
             ->getIfSingleRecord()
             ->setWheres()
             ->setFilters()
             ->setScopes();

        return $this;
    }

    /**
     * Build the request parameters into a query builder.
     * @return QueryBuilder this
     */
    public function buildForCount() {

        $this->filterByParentRelation()
             ->withTrashed()
             ->onlyTrashed()
             ->includeRelations()
             ->getIfSingleRecord()
             ->setWheres(true)
             ->setFilters()
             ->setScopes();

        return $this;
    }

    /**
     * Returns a builder clone that has been modified to select count(*)
     * instead of returning actual results.
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function getCountBuilder() {
        $counter = clone $this->builder->getQuery();
        $counter->select(new Expression('count(*) as count'));
        return $counter;
    }

    /**
     * Returns the results of the count query, which is a single
     * record with a count property.
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getCount() {
        $counter = $this->getCountBuilder();
        return $counter->first()->count;
    }

    /**
     * Analyzes the URI segments to determine a nested relationship
     * and restricts access only to members of the parent.
     * @return QueryBuilder this
     */
    public function filterByParentRelation() {
        if(!$this->parser->hasParent()) return $this;
        $strPrimaryKey = $this->model->getKeyName();
        $strKeyValue   = $this->parser->getParentId();
        $strRelationName = $this->parser->getRelationName();
        $record = $this->model->where($strPrimaryKey,'=',intval($strKeyValue))->first();
        $this->builder = $record->$strRelationName();
        $this->model = $this->builder->getModel();
        return $this;
    }

    public function getIfSingleRecord() {
        $id = $this->parser->getResourceId();
        if(is_null($id)) return $this;
        $strPrimaryKey = $this->builder->getModel()->getKeyName();
        $this->builder->where($strPrimaryKey,'=',intval($id));
        return $this;
    }

    /**
     * Tells the builder to also load relations on the resulting model.
     * @return QueryBuilder this
     */
    public function includeRelations() {
        if($with = $this->parser->request->get('with')) {
            $this->builder->with($with);
        }
        return $this;
    }

    /**
     * When withTrashed=1 is on the query string,
     * soft-deleted models will be included.
     * @return QueryBuilder this
     */
    public function withTrashed() {
        if('1' == $this->parser->request->get('withTrashed')) {
            $this->builder->withTrashed();
        }
        return $this;
    }

    /**
     * When onlyTrashed=1 is on the query string, only
     * soft-deleted models will be returned.
     * @return QueryBuilder this
     */
    public function onlyTrashed() {
        if('1' == $this->parser->request->get('onlyTrashed')) {
            $this->builder->onlyTrashed();
        }
        return $this;
    }

    /**
     * Walks the input array and adds all appropriate clauses
     * to the builder using the BuilderClause for each.
     * @return QueryBuilder this
     */
    public function setWheres($bForCount = false) {
        $input = $this->parser->fixedInput;

        $fn = $this->getWhereProcessor($bForCount);
        array_walk($input, $fn);
        return $this;
    }

    /**
     * Returns a function for use in setWheres array_walk
     * @return Closure function to add a where clause.
     */
    public function getWhereProcessor($bForCount = false) {
        return function($value, $parameterName) use ($bForCount) {
            foreach($this->builderClauses AS $prefix => $clause) {
                if(preg_match("/^$prefix/",$parameterName) > 0) {
                    if($bForCount && in_array($prefix,['orderby','sortbychild'])) continue;
                    $clause->processWhere($this->builder,$parameterName,$value,$this->getLocale());
                }
            }
        };
    }

    /**
     * Ueses array_walk to loop through all of the filters set on this
     * request and add filter calls to the builder.
     * @return QueryBuilder this
     */
    public function setFilters() {
        $input = $this->parser->request->all();

        $fn = $this->getFilterProcessor();
        array_walk($input, $fn);
        return $this;
    }

    /**
     * NOTE: Scope is an alias for filter.
     * The API should use the filter prefix, not scope. The word 'scope'
     * is ambiguous with many other meanings, and should not be used.
     * It is included here for backward compatability because the 'scope'
     * keyword was added to the API in error.
     *
     * Returns a function for use in setFilters array_walk
     * @return Closure function to add a filter clause.
     */
    public function getFilterProcessor($prefix = 'filter') {
        return function($value, $parameterName) use ($prefix) {
            if(preg_match("/^$prefix/",$parameterName) > 0) {
                $method = preg_replace("/^$prefix/",'',$parameterName);
                $method = lcfirst($method);
                $clause = new BuilderClause($prefix,$method);
                $clause->processWhere($this->builder,$parameterName,$value,$this->getLocale());
            }
        };
    }

    /**
     * Ueses array_walk to loop through all of the filters set on this
     * request and add filter calls to the builder.
     * @return QueryBuilder this
     */
    public function setScopes() {
        $input = $this->parser->request->all();

        $fn = $this->getFilterProcessor('scope');
        array_walk($input, $fn);
        return $this;
    }

    /**
     * Creates a new Model through a callback function so it can be mocked.
     * @param  string $strClassPath class path to Model
     * @return Illuminate\Database\Eloquent\Model
     */
    public function createModel($strClassPath) {
        return new $strClassPath();
    }

    /**
     * Returns the Eloquent model object instance that was
     * used to create a new query.
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getModel() {
        return $this->model;
    }

    /**
     * Returns the target model of the collection that will actually
     * be returned. If the current route is a nested parent/child
     * then the child model will be returned, not the working model
     * that the builder uses for every day use.
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getTargetModel() {
        if($this->parser->hasParent()) {
            $strRelationName = $this->parser->getRelationName();
            if(method_exists($this->getModel(),strtolower($strRelationName))) {
                return $this->getModel()->$strRelationName()->getModel();
            } else return $this->getModel();
        } else {
            return $this->getModel();
        }
    }

    /**
     * Allows you to set the model so it can be mocked.
     * @param Illuminate\Database\Eloquent\Model $model
     */
    public function setModel($model) {
        $this->model = $model;
    }

    /**
     * Return the SQL statement that has been
     * built so far.
     * @return string SQL statement
     */
    public function toSql() {
        return $this->builder->toSql();
    }

    /**
     * Returns the builder's bindings so we can inspect them for testing.
     * @return array bindings
     */
    public function getBindings() {
        return $this->builder->getBindings();
    }

    /**
     * Return the Eloquent Builder object so we can inspect it for testing.
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function getBuilder() {
        return $this->builder;
    }

    /**
     * Sets the builder object so it can be mocked
     * @param Illuminate\Database\Eloquent\Builder $builder
     */
    public function setBuilder($builder) {
        $this->builder = $builder;
    }

    /**
     * Returns the URI query parser
     * @return Fh\QueryBuilder\QueryParserInterface
     */
    public function getParser() {
        return $this->parser;
    }

    /**
     * If you want to set a new parser of your own, do that here.
     * @param Fh\QueryBuilder\QueryParserInterface $parser
     */
    public function setParser($parser) {
        $this->parser = $parser;
    }

    /**
     * Sets the paging style so it can be mocked
     * @param string $style either 'page=' or 'limit/offset'
     */
    public function setPagingStyle($style) {
        $this->pagingStyle = $style;
    }

    /**
     * Gets the paging style
     * @return string $style either 'page=' or 'limit/offset'
     */
    public function getPagingStyle() {
        return $this->pagingStyle;
    }

    /**
     * Setter for the total record count property
     * @param int $count
     */
    public function setCount($count) {
        $this->count = $count;
    }

    /**
     * Return results via pagination.
     * Supports any style of pagination you want, including:
     * - laravel default ?page=1 from query string
     * - limit / offset pagination using ?limit=10&offset=30
     * - custom query string names for any of the above parameters
     * @param  int $limit
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function paginate($limit = null) {
        if(!$limit)
            $limit = $this->parser->getLimit();
        $page = $this->parser->getPage();
        // Page by paging style
        if($this->pagingStyle == 'page=') {
            return $this->builder->paginate($limit,null,null,$page);
        } else {
            // using limit / offset for paging
            $start = $this->parser->getOffset();
            return $this->builder->skip($start)->limit($limit)->get();
        }
    }

    /**
     * Setter for locale
     * @param string $locale code
     */
    public function setLocale($locale) {
        $this->locale = $locale;
    }

    /**
     * Getter for locale
     * @return string locale code
     */
    public function getLocale() {
        return $this->locale;
    }

}
