<?php

namespace Fh\QueryBuilder;

class RouteToModelTestMap {

    public function getRouteToModelMap() {
        return [
            // route                      MapperName.relationname
             'letters'                 => "TestModel"
            ,'letters.photos'          => "TestModel.photos"
            ,'letters.photos.original' => "TestChildModel.original"
        ];
    }
}