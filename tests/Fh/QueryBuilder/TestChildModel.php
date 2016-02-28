<?php

namespace Fh\QueryBuilder;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class TestRelationModel extends Model
{
    use SoftDeletes;

    protected $table = 'ChildTable';
    protected $primaryKey = 'ChildId';

    /**
     * Scope to test scopes
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    public function scopeAppropriateForPrint($query) {
        $query->where('IncludeInPrint','=',TRUE);
    }

    /*
     * Eloquent relationship.
     * hasOne original
     */
    public function original()
    {
        return $this->hasMany('Fh\QueryBuilder\TestRelationModel', 'TestId', 'TestId');
    }

    /*
     * Eloquent relationship.
     * hasMany translations
     */
    public function translations() {
        return $this->hasMany('Fh\QueryBuilder\TestRelationModel','TestId','TranslationId');
    }

}
