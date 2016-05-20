<?php

namespace Fh\QueryBuilder;

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
        $relationModel = $parentModel->$strRelation();
        $strParentTable = $parentModel->getTable();
        $strRelatedTable = $relationModel->getModel()->getTable();
        $strForeignKey = $relationModel->getForeignKey();
        $strOtherKey   = $relationModel->getOtherKey();

        $builder->join("$strRelatedTable AS relTable","relTable.$strOtherKey", '=', "$strParentTable.$strForeignKey")
            ->orderBy("$strRelatedTable.$strField",$value)
            ->select("$strParentTable.*");

    }

}
