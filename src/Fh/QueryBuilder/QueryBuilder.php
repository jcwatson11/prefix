<?php

namespace Fh\QueryBuilder;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Route;
use Illuminate\Database\Connection;

class QueryBuilder {

    // Eloquent model
    protected $model;

    // Function to use when creating a new model so it can be mocked
    protected $modelCreationCallback;

    // Default namespace for model names found in the routeMap
    protected $strModelNamespace = 'Fh\Data\Mapper\US';

    protected $builderClauses = [];

    /**
     * Constructs the query builder
     * @param array   $routeToModelMap Mapping of route segment names
     *                to a Model name so that we can resolve
     *                parent-child relationships
     * @param Model   $model   Illuminate\Database\Eloquent\Model
     * @param Request $request Illuminate\Http\Request
     */
    public function __construct(array $routeToModelMap, Model $model, Request $request) {
        $this->parser = new QueryParser($routeToModelMap,$request);
        $this->model = $model;
        $this->builder = $this->model->newQuery();
        // This is necessary so models can be mocked.
        $this->setModelCreationCallback(
            $this->getDefaultModelCreationCallback()
        );
        $this->initializeWherePrefixes();
    }

    /**
     * Set up all of the various filter clauses that our API supports.
     * @return void
     */
    public function initializeWherePrefixes() {
        $this->builderClauses = [
            'isnull'       => new BuilderClause('isnull','whereNull')
            ,'isnotnull'   => new BuilderClause('isnotnull','whereNotNull')
            ,'orwhere'     => new BuilderClause('orwhere','orWhere','=')
            ,'where'       => new BuilderClause('where','where','=')
            ,'orderby'     => new BuilderClause('orderby','orderBy')
            ,'groupby'     => new BuilderClause('groupby','groupBy')

            ,'between'     => new BuilderClause('between','whereBetween')
            ,'notinarray'  => new BuilderClause('notinarray','whereNotIn')
            ,'inarray'     => new BuilderClause('inarray','whereIn')
            ,'like'        => new BuilderClause('like','where','LIKE',function(&$value) {
                $value = "%$value%";
            })
            ,'orlike'      => new BuilderClause('orlike','orWhere','LIKE',function(&$value) {
                $value = "%$value%";
            })
            ,'greaterthan' => new BuilderClause('greaterthan','where','>=')
            ,'lessthan'    => new BuilderClause('lessthan','where','<=')
        ];
    }

    /**
     * Build the request parameters into a query builder.
     * @return QueryBuilder this
     */
    public function build() {

/*
        // Restrict access to child objects by natural relation.
        $this->filterByParentRelation();

        // Set with relations
        $this->setRelations();

        // Process where clauses, filters and scopes.
        $this->setWheres();
        $this->setFilters();
        $this->setScopes();

        // Get translations
        $this->setTranslations();
*/

        return $this;
    }

    /**
     * Returns a builder clone that has been modified to select count(*)
     * instead of returning actual results.
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function getCountBuilder() {
        $counter = clone $this->builder->getQuery();
        $counter->select($counter->getConnection()->raw('count(*) as count'));
        return $counter;
    }

    /**
     * Returns the results of the count query, which is a single
     * record with a count property.
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getCount() {
        $counter = $this->getCountBuilder();
        return $count->first()->count;
    }

    /**
     * Analyzes the URI segments to determine a nested relationship
     * and restricts access only to members of the parent.
     * @return QueryBuilder this
     */
    public function filterByParentRelation() {
        $aParts = explode('.',$this->parser->getModelRelationName());

        // No need to limit by parent relation if there is no parent.
        if(count($aParts) == 1) return $this;

        list($strModelName, $strRelationName) = $aParts;
        $strClassPath = $this->strModelNamespace . '\\' . $strModelName;
        $model = $this->createModel($strClassPath);
        $strPrimaryKey = $model->getKeyName();
        $strKeyValue   = $this->parser->getParentId();
        $builder = $model->where($strPrimaryKey,'=',intval($strKeyValue))->first();
        $this->builder = $builder->$strRelationName();
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
     * Walks the input array and adds all appropriate clauses
     * to the builder using the BuilderClause for each.
     * @return QueryBuilder this
     */
    public function setWheres() {
        $input = $this->parser->request->all();

        $fn = $this->getWhereProcessor();
        array_walk($input, $fn);
        return $this;
    }

    /**
     * Returns a function for use in setWheres array_walk
     * @return Closure function to add a where clause.
     */
    public function getWhereProcessor() {
        return function($value, $parameterName) {
            foreach($this->builderClauses AS $prefix => $clause) {
                if(preg_match("/^$prefix/",$parameterName) > 0) {
                    $clause->processWhere($this->builder,$parameterName,$value);
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
     * Returns a function for use in setFilters array_walk
     * @return Closure function to add a filter clause.
     */
    public function getFilterProcessor() {
        return function($value, $parameterName) {
            if(preg_match("/^filter/",$parameterName) > 0) {
                $method = preg_replace('/^filter/','',$parameterName);
                $method = lcfirst($method);
                $clause = new BuilderClause('filter',$method);
                $clause->processWhere($this->builder,$parameterName,$value);
            }
        };
    }

    /**
     * Load translations if the locale is set.
     * @return QueryBuilder this
     */
    public function setTranslations() {
        $locale = $this->parser->request->get('locale');

        if($locale) {
            $this->builder->with('translations');
        }
        return $this;
    }

    /**
     * Creates a new Model through a callback function so it can be mocked.
     * @param  string $strClassPath class path to Model
     * @return Illuminate\Database\Eloquent\Model
     */
    public function createModel($strClassPath) {
        $fn = $this->modelCreationCallback;
        return $fn($strClassPath);
    }

    /**
     * Sets the model creator callback function.
     * @param function $function
     */
    public function setModelCreationCallback($function) {
        $this->modelCreationCallback = $function;
    }

    /**
     * Returns the default model creation callback.
     * @return function
     */
    public function getDefaultModelCreationCallback() {
        return function($strClassPath) {
            return new $strClassPath();
        };
    }

    /**
     * Executes the query and returns
     * a result set.
     * @param  int $iPerPage
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function paginate($iPerPage = null) {
        return $this->builder->paginate($iPerPage);
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
     * Setter for the total record count property
     * @param int $count
     */
    public function setCount($count) {
        $this->count = $count;
    }

}
