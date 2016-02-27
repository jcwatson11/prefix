<?php

namespace Fh\QueryBuilder;

class RouteToModelTestMap {

    public function getRouteToModelMap() {
        return [
            // route                      MapperName.relationname
             'letters'                 => "LetterMapper"
            ,'letters.status'          => "LetterMapper.status"
            ,'letters.template'        => "LetterMapper.template"
            ,'letters.photos'          => "LetterMapper.photos"
            ,'letters.photos.original' => "LetterPhotoMapper.original"
            ,'letters.child'           => "LetterMapper.child"
            ,'letters.sponsor'         => "LetterMapper.sponsor"
            ,'letters.editreasons'     => "LetterMapper.editreasons"
            ,'letters.recipient'       => "LetterMapper.recipients"
            ,'letters.recipient.child' => "LetterRecipientMapper.child"
            // Direct access not through a child relation
            ,'letterstatuses'          => "LetterStatusMapper"
            ,'lettertemplates'         => "LetterTemplateMapper"
        ];
    }
}