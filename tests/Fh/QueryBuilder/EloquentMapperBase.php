<?php
namespace Fh\QueryBuilder;

use Illuminate\Database\Eloquent\Model;

abstract class EloquentMapperBase extends Model {

    /**
     * Return the column mapping
     * @return [array] arraty of column => property pairs
     */
    public function getColumnMap()
    {
        return $this->columnMap;
    }

    /**
     * return the property map
     * @return [array] arraty of property => column pairs
     */
    public function getPropertyMap()
    {
        return array_flip($this->columnMap);
    }

    /**
     * Return the column name for a given property
     * @param  [string] $property
     * @return [string] column name
     */
    public function propertyToColumn($property)
    {
        $propertyMap = $this->getPropertyMap();
        if (array_key_exists($property, $propertyMap)) {
            return $propertyMap[$property];
        } else {
            return false;
        }
    }

    public function columnToProperty($column)
    {
        $columnMap = $this->getColumnMap();
        if (array_key_exists($column, $columnMap)) {
            return $columnMap[$column];
        } else {
            return false;
        }
    }

    /**
     * Added getReverseAlias for backward compatability
     * with GboRestBaseController and other GBO's
     * @param  string $property
     * @return string column name
     */
    public function getReverseAlias($property) {
        return $this->propertyToColumn($property);
    }

}
