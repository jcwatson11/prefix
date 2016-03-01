<?php

namespace Fh\QueryBuilder;

interface QueryParserInterface {

    /**
     * Returns the limit value from the query string, or the default
     * page limit if none was provided.
     * @return int page limit
     */
    public function getLimit();

    /**
     * Returns the record offset to start at for use with limit/offset
     * based paging, or 0 if none was provided.
     * @return int offset
     */
    public function getOffset();

    /**
     * Returns the current page number according to the query string,
     * or 1 if none was provided.
     * @return int current page
     */
    public function getPage();

    /**
     * Returns an array of only the segments we are interested in
     * for resolving classes and relations
     * @return array of string URI segments
     */
    public function getStrippedSegments();

    /**
     * Returns a string route name based on the segments in the URI
     * @return string route name
     */
    public function getRouteName();

    /**
     * Returns an array of primary key values in the same order
     * they were given in the URI
     * @return array of integers
     */
    public function getKeySequence();

    /**
     * Returns a Model relation name prefixed with it's Model class name
     * so we can resolve it and limit the query to the parent's
     * relation subset.
     * @return string Model relation name prefixed with the Model class name
     */
    public function getModelRelationName();

    /**
     * Returns the model name without any relations from the
     * route to model map provided during construction.
     * @return string model class name
     */
    public function getModelName();

    /**
     * Returns the name of the relation on the model that results
     * are limited to.
     * @return string relation name from parent model
     */
    public function getRelationName();

    /**
     * Answers the question of whether the requested resource path
     * indicates a parent child relationship. Returns true if it does,
     * false otherwise.
     * @return boolean
     */
    public function hasParent();

    /**
     * Returns the route name of the parent object
     * @return string route name of parent
     */
    public function getParentRouteName();

    /**
     * Returns the base name of the parent route.
     * @return string name of parent route
     */
    public function getParentRouteBaseName();

    /**
     * Resturns the ID number of the last resource in the resource URI
     * not a parent ID. This only works when the URI ends with a number.
     *
     * @return string last segment of the URI if it is a number
     */
    public function getResourceId();

    /**
     * Returns the primary key number of the parent that was requested
     * @return string id of parent
     */
    public function getParentId();

}
