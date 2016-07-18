<?php

namespace Fh\QueryBuilder;



class RouteToModelTestMap {

    public function getRouteToModelMap() {
        RestMapper::addRestMapping('letters','TestModel');
        RestMapper::addRestMapping('letters.photos','TestModel.photos');
        RestMapper::addRestMapping('letters.photos.original','TestChildModel.original');
        return new RestMapper();
    }
}
