<?php

namespace Fh\QueryBuilder;

class BuilderClause {

    // String field prefix to be strippted in order to get the field name
    protected $strPrefix;

    // String method name to call on the builder to properly set this clause
    protected $strBuilderMethodName;

    // The operator to use in the query to compare values
    protected $strOperator;

    // Function to modify values when necessary
    // for example, to wrap a text string in '%' as in LIKE comparisons
    protected $fnValueModifier;

    // Tells the value modifier not to modify each value
    // in an array if an array is given. Instead, the modifier
    // is considered global, and must act on any array elements
    // it finds for itself.
    // By default, the modifier will be applied to each value
    // in an array if an array value is provided.
    protected $bWalkModifier = true;

    /**
     * Constructor
     * @param string $strPrefix       query string field prefix to strip out
     * @param string $strMethodName   name of method to call on builder
     * @param string $strOperator     operator to use for comparison
     * @param function $fnValueModifier modifies values as necessary
     */
    public function __construct($strPrefix, $strMethodName, $strOperator = null, $fnValueModifier = null, $bWalkModifier = true) {
        $this->strPrefix            = $strPrefix;
        $this->strBuilderMethodName = $strMethodName;
        $this->strOperator          = $strOperator;
        $this->fnValueModifier      = ($fnValueModifier) ? $fnValueModifier:$this->getDefaultValueModifier();
        $this->bWalkModifier        = $bWalkModifier;
    }

    /**
     * Returns the default value modifier function, which doesn't modify anything.
     * @return Closure
     */
    public function getDefaultValueModifier() {
        return function ($value) {
            return $value;
        };
    }

    /**
     * Strips the field prefix off of the parameter name to get the
     * field name for the query.
     * @param  string $strParamName from query string
     * @return string               field name for builder
     */
    public function getFieldNameFromParameter($strParamName) {
        $value = preg_replace("/^{$this->strPrefix}|\[\]/",'',$strParamName);
        return $value;
    }

    /**
     * Modifies the value(s) sent on the query string.
     * Helpful for cases where the value needs to be altered,
     * such as wrapping a value in '%', as in LIKE '%something%'.
     * @param  mixed &$value array or string
     * @return void          modifies values by reference
     */
    public function modifyValues(&$value) {
        $fn = $this->fnValueModifier;
        if(is_array($value) && $this->bWalkModifier) {
            foreach($value AS $index => $v) {
                $value[$index] = $fn($v);
            }
            return $value;
        } else {
            // single value, not array
            return $fn($value);
        }
    }

    /**
     * Call the builder method with its proper parameters to limit
     * the builder query as instructed.
     * @param  Illuminate\Database\Eloquent\Builder $builder
     * @param  string $strParamName parameter name from the query string
     * @param  mixed $values        string or array of string
     * @return void
     */
    public function processWhere($builder,$strParamName,$values = null) {
        $strField = $this->getFieldNameFromParameter($strParamName);
        $values = $this->modifyValues($values);
        $aMethodArgs = [$strField];
        if($this->strOperator) $aMethodArgs[] = $this->strOperator;
        if($values) $aMethodArgs[] = $values;

        // If the field name has a dot, then we need to join a relation
        // and query that, not the working model.
        if($this->fieldIndicatesRelation($strField)) {
            $aParts = explode('.',$strField);
            $aMethodArgs[0] = array_pop($aParts);
            $strRelation = implode('.',$aParts);
            $fn = function($b) use ($aMethodArgs) {
                call_user_func_array([$b,$this->strBuilderMethodName], $aMethodArgs);
            };
            $builder = $builder->whereHas($strRelation,$fn);
        } else {
            call_user_func_array([$builder,$this->strBuilderMethodName], $aMethodArgs);
        }
    }

    /**
     * Returns true if the field name indicates a relation to query
     * instead of querying the working model.
     * @param  string $strField potential field name from query string
     * @return boolean
     */
    public function fieldIndicatesRelation($strField) {
        return (preg_match('/\./',$strField) != 0) ? true:false;
    }

    /**
     * NOTE: Scope is an alias for filter.
     * The API should use the filter prefix, not scope. The word 'scope'
     * is ambiguous with many other meanings, and should not be used.
     * It is included here for backward compatability because the 'scope'
     * keyword was added to the API in error.
     *
     * Unlike a standard where clause, filters and scopes do not pass in
     * the field name. So we need a slightly different process for that.
     * @param  Illuminate\Database\Eloquent\Builder $builder
     * @param  string $strParamName query string parameter name
     * @param  mixed $values string or array to be passed to the filter method
     * @return void
     */
    public function processFilter($builder,$strParamName,$values = null) {
        $values = $this->modifyValues($values);
        $aMethodArgs = [];
        if($values) $aMethodArgs[] = $values;
        call_user_func_array([$builder,$this->strBuilderMethodName], $aMethodArgs);
    }

    /**
     * NOTE: Scope is an alias for filter.
     * The API should use the filter prefix, not scope. The word 'scope'
     * is ambiguous with many other meanings, and should not be used.
     * It is included here for backward compatability because the 'scope'
     * keyword was added to the API in error.
     *
     * Unlike a standard where clause, filters and scopes do not pass in
     * the field name. So we need a slightly different process for that.
     * @param  Illuminate\Database\Eloquent\Builder $builder
     * @param  string $strParamName query string parameter name
     * @param  mixed $values string or array to be passed to the filter method
     * @return void
     */
    public function processScope($builder,$strParamName,$values = null) {
        $this->processFilter($builder,$strParamName,$values);
    }

}
