<?php

namespace Fh\QueryBuilder;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphedToMany;
use Illuminate\Database\Eloquent\Relations\MorphedByMany;

class OrderParentByChildBuilderClause extends BuilderClause {

    // String field prefix to be strippted in order to get the field name
    protected $strPrefix;

    /**
     * Constructor
     * @param string $strPrefix       query string field prefix to strip out
     */
    public function __construct($strPrefix) {
        parent::__construct($strPrefix, 'unused');
    }

    /**
     * Call the builder method with its proper parameters to limit
     * the builder query as instructed.
     * @param  Illuminate\Database\Eloquent\Builder $builder
     * @param  string $strParamName parameter name from the query string
     * @param  mixed $values        string or array of string
     * @return void
     */
    public function processWhere($builder,$strParamName,$value = 'asc') {
        $strField = $this->getFieldNameFromParameter($strParamName);

        // The given values are child relations with their respective field names.
        if(!$this->fieldIndicatesRelation($strField)) {
            throw new QueryBuilderException("Cannot order parent by child relation without indicating the relation name and its field with dot notation: relation.fieldName");
        }

        list($strRelation,$strField) = explode('.',$strField);
        $parentModel = $builder->getModel();
        $relation = $parentModel->$strRelation();
        switch(true) {
            case $relation instanceof HasMany:
            case $relation instanceof HasOne:
            case $relation instanceof MorphOne:
            case $relation instanceof MorphMany:
                $strParentTable = $parentModel->getTable();
                $strRelatedTable = $relation->getModel()->getTable();
                $strForeignKey = $relation->getForeignKey();
                $strOtherKey   = $relation->getQualifiedParentKeyName();
                $builder->join("$strRelatedTable","$strOtherKey", '=', "$strForeignKey")
                    ->orderBy("$strRelatedTable.$strField",$value)
                    ->select("$strParentTable.*");
                break;
            case $relation instanceof MorphTo:
            case $relation instanceof BelongsTo:
                $strParentTable = $parentModel->getTable();
                $strRelatedTable = $relation->getModel()->getTable();
                $strForeignKey = $relation->getForeignKey();
                $strOtherKey   = $relation->getOtherKey();
                $builder->join("$strRelatedTable AS relTable","relTable.$strOtherKey", '=', "$strParentTable.$strForeignKey")
                    ->orderBy("relTable.$strField",$value)
                    ->select("$strParentTable.*");
                break;
            case $relation instanceof BelongsToMany:
            case $relation instanceof MorphedToMany:
            case $relation instanceof MorphedByMany:
                $strParentTable = $parentModel->getTable();
                $strFarRelationTable = $relation->getRelated()->getTable();
                $strTable = $relation->getTable();
                $strLocalKey = explode('.',$relation->getForeignKey())[1];
                $strForeignKey = explode('.',$relation->getOtherKey())[1];;
                $builder->join($strTable
                               ,"$strParentTable.$strLocalKey"
                               ,'='
                               ,"$strTable.$strLocalKey")
                        ->join($strFarRelationTable
                               ,"$strTable.$strForeignKey"
                               ,'='
                               ,"$strFarRelationTable.$strForeignKey")
                        ->orderBy("$strFarRelationTable.$strField",$value);
                break;
            default:
                throw new \Exception("Cannot sort by relation of type " . get_class($relation) . " yet. Fix this.");
        }


    }

}
