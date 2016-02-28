<?php
return [
    /**
     * This is the route prefix that your API might use
     * before starting the route to any single resource.
     */
    'baseUri' => '/api/v1/',

    // Default page limit while doing pagination.
    'defaultLimit' => 10,

    /**
     * Model namespace
     *
     * This is the namespace that your models will be prefixed with
     * when they are found in the route to model mapping that you
     * provide to the QueryBuilder when you instantiate it.
     *
     * You can leave this blank, and simply provide the full class path
     * of your models directly in the mapping if you want to.
     */
    'modelNamespace' => 'Fh\QueryBuilder',

    /**
     * Paging Style
     * Two different types of paging styles are supported:
     * 'page=' and 'limit/offset'
     *
     * The 'page=' style is like laravel's default paging
     * style, except that you can control the name of the paging
     * parameter. This is helpful for backward compatability with
     * older API signatures.
     *
     * The 'limit/offset' style is just that. Instead of specifying
     * a page number and a number of results per page, you specify
     * a limit and an offset, much like what MySQL developers are
     * familiar with in a SQL query.
     */
    'pagingStyle' => 'limit/offset',

    /**
     * Name of offset/limit/page parameters used as described
     * above 'pagingStyle'.
     */
    'offsetParameterName' => 'offset',
    'limitParameterName'  => 'limit',
    'pageParameterName'   => 'page'
];