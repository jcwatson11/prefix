<?php

namespace Fh\QueryBuilder;

use Illuminate\Http\Request;

class QueryParser {

    // Illuminate\Http\Request object
    public $request;

    // array of route => Model.relation pairs
    protected $routeMap;

    // Base URI to strip out to begin processing
    public $strUriBase;

    // Array of query string inputs whose special characters
    // have been restored after PHP turned them into underscores.
    public $fixedInput;

    /**
     * QueryParser constructor
     * @param array   $routeToModelMap array of route.name => Model.relation pairs
     * @param Illuminate\Http\Request $request
     */
    public function __construct(array $routeToModelMap, Request $request) {
        $this->routeMap   = $routeToModelMap;
        $this->request    = $request;
        $this->fixedInput = $this->fixInput($request->server->get('QUERY_STRING'));
        $this->strUriBase = config('fh-laravel-api-query-builder.baseUri');
    }

    /**
     * Returns the limit value from the query string, or the default
     * page limit if none was provided.
     * @return int page limit
     */
    public function getLimit() {
        $name  = config('fh-laravel-api-query-builder.limitParameterName');
        $limit = $this->request->get($name);
        if(!$limit) {
            $limit = config('fh-laravel-api-query-builder.defaultLimit');
        }
        return $limit;
    }

    /**
     * Returns the record offset to start at for use with limit/offset
     * based paging, or 0 if none was provided.
     * @return int offset
     */
    public function getOffset() {
        $name   = config('fh-laravel-api-query-builder.offsetParameterName');
        $offset = $this->request->get($name);
        if(!$offset) {
            $offset = 0;
        }
        return $offset;
    }

    /**
     * Returns the current page number according to the query string,
     * or 1 if none was provided.
     * @return int current page
     */
    public function getPage() {
        $name  = config('fh-laravel-api-query-builder.pageParameterName');
        $page  = $this->request->get($name);
        if(!$page) {
            $page = 1;
        }
        return $page;
    }

    /**
     * Returns an array of only the segments we are interested in
     * for resolving classes and relations
     * @return array of string URI segments
     */
    public function getStrippedSegments() {
        $aSegments = $this->request->segments();
        $aBase     = explode('/',trim($this->strUriBase,'/'));
        return array_values(array_diff($aSegments,$aBase));
    }

    /**
     * Returns a string route name based on the segments in the URI
     * @return string route name
     */
    public function getRouteName() {
        $aSegments = $this->getStrippedSegments();
        $aFiltered = array_filter($aSegments
        , function($strSegment) {
            return !is_numeric($strSegment);
        });
        return implode('.',$aFiltered);
    }

    /**
     * Returns an array of primary key values in the same order
     * they were given in the URI
     * @return array of integers
     */
    public function getKeySequence() {
        $aSegments = $this->getStrippedSegments();
        $aFiltered = array_filter($aSegments
        , function($strSegment) {
            return is_numeric($strSegment);
        });
        return array_values($aFiltered);
    }

    /**
     * Returns a Model relation name prefixed with it's Model class name
     * so we can resolve it and limit the query to the parent's
     * relation subset.
     * @return string Model relation name prefixed with the Model class name
     */
    public function getModelRelationName() {
        $strRouteName = $this->getRouteName();
        if(!in_array($strRouteName,array_keys($this->routeMap))) {
            throw new \Exception("Route name '$strRouteName' was not provided to the API QueryBuider.");
        }
        return $this->routeMap[$strRouteName];
    }

    /**
     * Returns the model name without any relations from the
     * route to model map provided during construction.
     * @return string model class name
     */
    public function getModelName() {
        $strModelRelationName = $this->getModelRelationName();
        $aParts = explode('.',$strModelRelationName);
        return $aParts[0];
    }

    /**
     * Returns the name of the relation on the model that results
     * are limited to.
     * @return string relation name from parent model
     */
    public function getRelationName() {
        $strModelRelationName = $this->getModelRelationName();
        $aParts = explode('.',$strModelRelationName);
        if(count($aParts) < 2) return false;
        return $aParts[1];
    }

    /**
     * Answers the question of whether the requested resource path
     * indicates a parent child relationship. Returns true if it does,
     * false otherwise.
     * @return boolean
     */
    public function hasParent() {
        return ($this->getParentRouteName()) ? true:false;
    }

    /**
     * Returns the route name of the parent object
     * @return string route name of parent
     */
    public function getParentRouteName() {
        $strRouteName = $this->getRouteName();
        $aNames = explode('.',$strRouteName);
        if(count($aNames) < 2) return false;
        array_pop($aNames);
        return implode('.',$aNames);
    }

    /**
     * Returns the base name of the parent route.
     * @return string name of parent route
     */
    public function getParentRouteBaseName() {
        $strParentRouteName = $this->getParentRouteName();
        $aSegments = explode('.',$strParentRouteName);
        return array_pop($aSegments);
    }

    /**
     * Resturns the ID number of the last resource in the resource URI
     * not a parent ID. This only works when the URI ends with a number.
     *
     * @return string last segment of the URI if it is a number
     */
    public function getResourceId() {
        $aSegments = $this->getStrippedSegments();
        $last = array_pop($aSegments);
        return (is_numeric($last)) ? $last:null;
    }

    /**
     * Returns the primary key number of the parent that was requested
     * @return string id of parent
     */
    public function getParentId() {
        $strRouteName = $this->getRouteName();
        $aRouteNames = explode('.',$strRouteName);
        $aKeys = $this->getKeySequence();
        if(count($aRouteNames) < 2) {
            throw new \Exception("QueryBuilder Internal Error: Requested ParentId when no parent was requested.");
        }
        return $aKeys[count($aRouteNames) - 2];
    }

    public function getOriginalQueryString() {
        return $this->request->getQueryString();
    }

    /**
     * Fixes input field names by encoding each key name before
     * running str_parse on them. This prevents PHP from
     * turning dot (.) and other characters into underscores.
     * See: http://tinyurl.com/po632un
     * @param  string $input from $_SERVER['QUERY_STRING']
     * @return array  of fixed inputs with special characters in their key names
     */
    public function fixInput($strInput) {
        $strInput = preg_replace('/%5B/','[',$strInput);
        $strInput = preg_replace('/%5D/',']',$strInput);
        $strInput = preg_replace_callback(
            '/(^|(?<=&))[^=[&]+/',
            function($key) { return bin2hex(urldecode($key[0])); },
            $strInput
        );
        $output = [];
        parse_str($strInput, $output);
        $ret = array_combine(array_map('hex2bin', array_keys($output)), $output);
        return $ret;
    }

}
