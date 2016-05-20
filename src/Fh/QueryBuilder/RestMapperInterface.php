<?php

namespace Fh\QueryBuilder;

interface RestMapperInterface {

    /**
     * Adds a single mapping to a rest mapper so QueryBuilder
     * can resolve which model it should access when it encounters
     * a certain URI pattern.
     * @param string $strRoutePath in the format name[.relation[.subrelation]]
     * @param string $strModel     in the format ModelName[.relationname]
     * @return void
     */
    public static function addRestMapping($strRoutePath, $strModel);

    /**
     * Removes one of the mappings that was added using addRestMapping
     * by route path name.
     * @param  string $strRoutePath in the format name[.relation[.subrelation]]
     * @return void
     */
    public static function removeRestMapping($strRoutePath);

    /**
     * Returns an associative array of all rest mappings that have been
     * added so far.
     * @return array associative ['route.path' => 'Model.relation']
     */
    public static function getRestMap();

}
