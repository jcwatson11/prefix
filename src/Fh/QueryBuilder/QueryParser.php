<?php

namespace Fh\QueryBuilder;

use Illuminate\Http\Request;

class QueryParser {

    // Illuminate\Http\Request object
    public $request;

    // array of route => Model.relation pairs
    protected $routeMap;

    // Base URI to strip out to begin processing
    protected $strUriBase = '/api/v1/';

    /**
     * QueryParser constructor
     * @param array   $routeToModelMap array of route.name => Model.relation pairs
     * @param Illuminate\Http\Request $request
     */
    public function __construct(array $routeToModelMap, Request $request) {
        $this->routeMap = $routeToModelMap;
        $this->request = $request;
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

    public function getParentRouteBaseName() {
        $strParentRouteName = $this->getParentRouteName();
        $aSegments = explode('.',$strParentRouteName);
        return array_pop($aSegments);
    }

    public function getParentId() {
        $strRouteName = $this->getRouteName();
        $aRouteNames = explode('.',$strRouteName);
        $aKeys = $this->getKeySequence();
        if(count($aRouteNames) < 2) {
            throw new \Exception("QueryBuilder Internal Error: Requested ParentId when no parent was requested.");
        }
        return $aKeys[count($aRouteNames) - 2];
    }

}