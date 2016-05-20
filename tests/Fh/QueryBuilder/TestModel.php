<?php

namespace Fh\QueryBuilder;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use SoftDeletes;

    protected $table = 'Table';
    protected $primaryKey = 'TestId';

    public function scopeByStatus($query, $StatusId) {
        $query->where('StatusId','=',$StatusId);
    }

    public function status() {
        return $this->belongsTo('Fh\QueryBuilder\TestChildModel','TestId','TestId');
    }

    /*
     * Eloquent relationship.
     * hasMany photos
     */
    public function photos()
    {
        return $this->hasMany('Fh\QueryBuilder\TestChildModel', 'TestId', 'TestId');
    }

    /*
     * Eloquent relationship.
     * hasMany translations
     */
    public function translations() {
        return $this->hasMany('Fh\QueryBuilder\TestChildModel','TestId','TranslationId');
    }

}
