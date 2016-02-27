<?php

namespace Fh\QueryBuilder;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestModel extends EloquentMapperBase implements MapperInterface
{
    use SoftDeletes;

    protected $table = 'Table';
    protected $primaryKey = 'TestId';

    public static $gboClass = '\Fh\Data\Gbo\BusinessObjects\Table';

    protected $columnMap = [
        'TestId'          => 'TestId',
        'StatusId'        => 'StatusId',
        'TemplateId'      => 'TemplateId',
        'ChildId'         => 'ChildId',
        'SponsorId'       => 'SponsorId',
        'OriginalMessage' => 'OriginalMessage',
        'EditedMessage'   => 'EditedMessage',
        'created_at'      => 'createdAt',
        'updated_at'      => 'updatedAt',
        'deleted_at'      => 'deletedAt'
    ];

    /**
     * Return field validation rules compatible with Laravel's validation classes.
     * @return array
     */
    public static function getValidatorRules()
    {
        $aRules = static::getValidatorBase();
        unset($aRules['letterid']);
        return $aRules;
    }

    /*
     * Eloquent relationship.
     * hasMany recipients
     */
    public function recipients()
    {
        return $this->hasMany('Fh\Data\Mapper\US\LetterRecipientMapper', 'TestId', 'TestId');
    }

    /*
     * Eloquent relationship.
     * belongsTo status
     */
    public function status()
    {
        return $this->belongsTo('Fh\Data\Mapper\US\LetterStatusMapper', 'StatusId', 'StatusId');
    }

    /*
     * Eloquent relationship.
     * belongsTo template
     */
    public function template()
    {
        return $this->belongsTo('Fh\Data\Mapper\US\LetterTemplateMapper', 'TemplateId', 'TemplateId');
    }

    /*
     * Eloquent relationship.
     * hasMany photos
     */
    public function photos()
    {
        return $this->hasMany('Fh\Data\Mapper\US\LetterPhotoMapper', 'TestId', 'TestId');
    }

    /*
     * Eloquent relationship.
     * belongsToMany editreasons
     */
    public function editreasons()
    {
        return $this->belongsToMany('Fh\Data\Mapper\US\LetterEditReasonMapper','LetterEditReason_Letter','TestId','EditReasonId');
    }

    /*
     * Eloquent relationship.
     * hasMany translations
     */
    public function translations() {
        return $this->hasMany('Fh\Data\Mapper\US\TranslationMapper','TestId','TranslationId');
    }

}
