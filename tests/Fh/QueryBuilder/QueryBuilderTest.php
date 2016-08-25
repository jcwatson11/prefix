<?php

namespace Fh\QueryBuilder;

use Mockery as m;
use Fh\QueryBuilder\QueryBuilder;

class QueryBuilderTest extends QueryBuilderTestBase {

    public function test_it_loads_default_values_from_the_config_file() {
        // Simple non-compound URI
        $strTestUri = '/api/v1/letters';
        $qb = $this->createQueryBuilder($strTestUri);
        $this->assertEquals('Fh\QueryBuilder',$qb->strModelNamespace);
        $this->assertEquals('limit/offset',$qb->pagingStyle);
    }

    public function test_it_can_resolve_the_working_model() {
        $strTestUri = '/api/v1/letters';
        $qb = $this->createQueryBuilder($strTestUri);
        $model = $qb->getModel();
        $class = get_class($model);
        $strExpected = 'Fh\QueryBuilder\TestModel';
        $this->assertEquals($strExpected,$class);

        $strTestUri = '/api/v1/letters/23/photos';
        $qb = $this->createQueryBuilder($strTestUri);
        $model = $qb->getModel();
        $class = get_class($model);
        $strExpected = 'Fh\QueryBuilder\TestModel';
        $this->assertEquals($strExpected,$class);

        $strTestUri = '/api/v1/letters/23/photos/4/original';
        $qb = $this->createQueryBuilder($strTestUri);
        $model = $qb->getModel();
        $class = get_class($model);
        $strExpected = 'Fh\QueryBuilder\TestChildModel';
        $this->assertEquals($strExpected,$class);
    }

    public function test_it_can_resolve_a_simple_target_model() {
        $strTestUri = '/api/v1/letters';
        $qb = $this->createQueryBuilder($strTestUri);
        $model = $qb->getTargetModel();
        $class = get_class($model);
        $strExpected = 'Fh\QueryBuilder\TestModel';
        $this->assertEquals($strExpected,$class);
    }

    public function test_it_can_resolve_a_nested_target_model() {
        $strTestUri = '/api/v1/letters/23/photos';
        $qb = $this->createQueryBuilder($strTestUri);
        $model = $qb->getTargetModel();
        $class = get_class($model);
        $strExpected = 'Fh\QueryBuilder\TestChildModel';
        $this->assertEquals($strExpected,$class);

    }

    public function test_it_can_resolve_a_double_nested_target_model() {
        $strTestUri = '/api/v1/letters/23/photos/4/original';
        $qb = $this->createQueryBuilder($strTestUri);
        $model = $qb->getTargetModel();
        $class = get_class($model);
        $strExpected = 'Fh\QueryBuilder\TestChildModel';
        $this->assertEquals($strExpected,$class);
    }

    public function test_it_can_create_a_count_builder_for_a_simple_request() {
        $strTestUri = '/api/v1/letters';
        $qb = $this->createQueryBuilder($strTestUri);

        $modelInstance = $this->getMockTestModel(23);
        $mockBuilder = m::mock('stdClass')
                 ->shouldReceive('first')
                 ->andReturn($modelInstance)
                 ->getMock();
        $mockModel = m::mock('Fh\QueryBuilder\TestModel[where]')
                 ->shouldReceive('where')
                 ->with('TestId','=',23)
                 ->andReturn($mockBuilder)
                 ->getMock();

        $qb->setModel($mockModel);

        $qb->build();
        $cb = $qb->getCountBuilder();
        $sql = $cb->toSql();
        $strExpected = 'select count(*) as count from "Table" where "Table"."deleted_at" is null';
        $this->assertEquals($strExpected,$sql);
    }

    public function test_it_can_create_a_count_builder_for_a_nested_request() {
        $strTestUri = '/api/v1/letters/23/photos';
        $qb = $this->createQueryBuilder($strTestUri);

        $modelInstance = $this->getMockTestModel(23);
        $mockBuilder = m::mock('stdClass')
                 ->shouldReceive('first')
                 ->andReturn($modelInstance)
                 ->getMock();
        $mockModel = m::mock('Fh\QueryBuilder\TestModel[where]')
                 ->shouldReceive('where')
                 ->with('TestId','=',23)
                 ->andReturn($mockBuilder)
                 ->getMock();

        $qb->setModel($mockModel);

        $qb->build();
        $cb = $qb->getCountBuilder();
        $sql = $cb->toSql();
        $strExpected = 'select count(*) as count from "ChildTable" where "ChildTable"."deleted_at" is null and "ChildTable"."TestId" = ? and "ChildTable"."TestId" is not null';
        $this->assertEquals($strExpected,$sql);
    }

    public function test_it_can_create_a_simple_sql_statement() {
        $strTestUri = '/api/v1/letters';

        $qb = $this->createQueryBuilder($strTestUri);
        $qb->build();
        $strSql = $qb->toSql();
        $strExpected = 'select * from "Table" where "Table"."deleted_at" is null';
        $this->assertEquals($strExpected,$strSql);
    }

    public function test_it_can_filter_results_by_parent_relation() {
        $strTestUri = '/api/v1/letters/23/photos';
        $qb = $this->createQueryBuilder($strTestUri);
        $modelInstance = $this->getMockTestModel(23);
        $mockBuilder = m::mock('stdClass')
                 ->shouldReceive('first')
                 ->andReturn($modelInstance)
                 ->getMock();
        $mockModel = m::mock('Fh\QueryBuilder\TestModel[where]')
                 ->shouldReceive('where')
                 ->with('TestId','=',23)
                 ->andReturn($mockBuilder)
                 ->getMock();

        $qb->setModel($mockModel);

        $qb->filterByParentRelation();
        $strSql = $qb->toSql();
        $strExpected = 'select * from "ChildTable" where "ChildTable"."deleted_at" is null and "ChildTable"."TestId" = ? and "ChildTable"."TestId" is not null';
        $this->assertEquals($strExpected,$strSql);
        $aBindings = $qb->getBindings();
        $aExpectedBindings = [23];
        $this->assertEquals($aExpectedBindings,$aBindings);
    }

    public function test_it_can_include_relations_using_with() {
        // Single where
        $strTestUri = '/api/v1/letters?with[]=photos';

        $qb = $this->createQueryBuilder($strTestUri);
        $qb->includeRelations();
        $eagerLoads = $qb->getBuilder()->getEagerLoads();
        $this->assertEquals(['photos'],array_keys($eagerLoads));
        $strSql = $qb->toSql();
        $strExpected = 'select * from "Table" where "Table"."deleted_at" is null';
        $this->assertEquals($strExpected,$strSql);

        // Multiple wheres
        $strTestUri = '/api/v1/letters?with[]=photos&with[]=status';

        $qb = $this->createQueryBuilder($strTestUri);
        $qb->includeRelations();
        $eagerLoads = $qb->getBuilder()->getEagerLoads();
        $this->assertEquals(['photos','status'],array_keys($eagerLoads));
        $strSql = $qb->toSql();
        $strExpected = 'select * from "Table" where "Table"."deleted_at" is null';
        $this->assertEquals($strExpected,$strSql);
    }

    public function test_it_can_set_wheres() {
        $strTestUri = '/api/v1/letters?betweenLetterId[]=4&betweenLetterId[]=8&whereFirstName=Jon';

        $qb = $this->createQueryBuilder($strTestUri);
        $qb->setWheres();
        $strSql = $qb->toSql();
        $strExpected = 'select * from "Table" where "Table"."deleted_at" is null and "LetterId" between ? and ? and "FirstName" = ?';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $qb->getBindings();
        $aExpected = [4,8,'Jon'];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_decode_special_characters_in_query_string_values() {
        $strTestUri = '/api/v1/letters?likeFullName=Jon+Watson&whereDescription=This%20is%20a%20test.';
        $qb = $this->createQueryBuilder($strTestUri);
        $qb->setWheres();
        $strSql = $qb->toSql();
        $strExpected = 'select * from "Table" where "Table"."deleted_at" is null and "FullName" LIKE ? and "Description" = ?';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $qb->getBindings();
        $aExpected = ['%Jon Watson%','This is a test.'];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_get_a_record_count_for_all_records() {
        $strTestUri = '/api/v1/letters?likeFirstName=Jon';

        $qb = $this->createQueryBuilder($strTestUri);
        $qb->setWheres();
        $countBuilder = $qb->getCountBuilder();

        $strSql = $countBuilder->toSql();
        $strExpected = 'select count(*) as count from "Table" where "Table"."deleted_at" is null and "FirstName" LIKE ?';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $qb->getBindings();
        $aExpected = ['%Jon%'];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_return_a_single_result_by_id() {
        // Single record without parent
        $strTestUri = '/api/v1/letters/23';
        $qb = $this->createQueryBuilder($strTestUri);

        $qb->build();

        $strSql = $qb->toSql();
        $strExpected = 'select * from "Table" where "Table"."deleted_at" is null and "TestId" = ?';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $qb->getBindings();
        $aExpected = [23];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_return_a_single_result_by_id_when_indicating_a_nested_relationship() {
        $strTestUri = '/api/v1/letters/23/photos/4';
        $qb = $this->createQueryBuilder($strTestUri);
        $modelInstance = $this->getMockTestModel(23);
        $mockBuilder = m::mock('stdClass')
                 ->shouldReceive('first')
                 ->andReturn($modelInstance)
                 ->getMock();
        $mockModel = m::mock('Fh\QueryBuilder\TestModel[where]')
                 ->shouldReceive('where')
                 ->with('TestId','=',23)
                 ->andReturn($mockBuilder)
                 ->getMock();

        $qb->setModel($mockModel);

        $qb->build();
        $strSql = $qb->toSql();
        $strExpected = 'select * from "ChildTable" where "ChildTable"."deleted_at" is null and "ChildTable"."TestId" = ? and "ChildTable"."TestId" is not null and "ChildId" = ?';
        $this->assertEquals($strExpected,$strSql);
        $aBindings = $qb->getBindings();
        $aExpectedBindings = [23,4];
        $this->assertEquals($aExpectedBindings,$aBindings);
    }

    public function test_it_can_build_all_things_at_once() {
        $strTestUri = '/api/v1/letters/23/photos?with[]=translations&with[]=original&isnullCaption&isnotnullOriginalId&likeFirstName=Jon&filterAppropriateForPrint&lessthanTestId=2';
        $qb = $this->createQueryBuilder($strTestUri);
        $modelInstance = $this->getMockTestModel(23);
        $mockBuilder = m::mock('stdClass')
                 ->shouldReceive('first')
                 ->andReturn($modelInstance)
                 ->getMock();
        $mockModel = m::mock('Fh\QueryBuilder\TestModel[where]')
                 ->shouldReceive('where')
                 ->with('TestId','=',23)
                 ->andReturn($mockBuilder)
                 ->getMock();

        $qb->setModel($mockModel);
        $qb->build();

        // Verify eager loaded relations requested by with[]
        $eagerLoads = $qb->getBuilder()->getEagerLoads();
        $this->assertEquals(['translations','original'],array_keys($eagerLoads));

        // Verify SQL output
        $strSql = $qb->toSql();
        $strExpected = 'select * from "ChildTable" where "ChildTable"."deleted_at" is null and "ChildTable"."TestId" = ? and "ChildTable"."TestId" is not null and "Caption" is null and "OriginalId" is not null and "FirstName" LIKE ? and "TestId" < ? and "IncludeInPrint" = ?';
        $this->assertEquals($strExpected,$strSql);

        // Verify bindings
        $aBindings = $qb->getBindings();
        $aExpectedBindings = [
             23
            ,'%Jon%'
            ,'2'
            ,true
        ];
        $this->assertEquals($aExpectedBindings,$aBindings);
    }

    public function test_it_can_paginate_results_using_limit_offset_default_settings() {
        $strTestUri = '/api/v1/letters';
        $qb = $this->createQueryBuilder($strTestUri);
        $thirdMockBuilder = m::mock('stdClass')
                 ->shouldReceive('get')
                 ->andReturn('')
                 ->getMock();
        $secondMockBuilder = m::mock('stdClass')
                 ->shouldReceive('limit')
                 ->with(10)
                 ->andReturn($thirdMockBuilder)
                 ->getMock();
        $mockBuilder = m::mock('stdClass')
                 ->shouldReceive('skip')
                 ->with(0)
                 ->andReturn($secondMockBuilder)
                 ->getMock();

        $qb->setBuilder($mockBuilder);
        $qb->paginate();
    }

    public function test_it_can_paginate_results_using_limit_offset_with_parameters() {
        $strTestUri = '/api/v1/letters?limit=20&offset=40';
        $qb = $this->createQueryBuilder($strTestUri);
        $thirdMockBuilder = m::mock('stdClass')
                 ->shouldReceive('get')
                 ->andReturn('')
                 ->getMock();
        $secondMockBuilder = m::mock('stdClass')
                 ->shouldReceive('limit')
                 ->with(20)
                 ->andReturn($thirdMockBuilder)
                 ->getMock();
        $mockBuilder = m::mock('stdClass')
                 ->shouldReceive('skip')
                 ->with(40)
                 ->andReturn($secondMockBuilder)
                 ->getMock();

        $qb->setBuilder($mockBuilder);
        $qb->paginate();
    }

    public function test_it_can_paginate_results_using_page_default_settings() {
        $strTestUri = '/api/v1/letters';
        $qb = $this->createQueryBuilder($strTestUri);
        $qb->setPagingStyle('page=');
        $mockBuilder = m::mock('stdClass')
                 ->shouldReceive('paginate')
                 ->with(10,null,null,1)
                 ->andReturn('')
                 ->getMock();

        $qb->setBuilder($mockBuilder);
        $qb->paginate();
    }

    public function test_it_can_paginate_results_using_page_with_parameters() {
        $strTestUri = '/api/v1/letters?page=2&limit=40';
        $qb = $this->createQueryBuilder($strTestUri);
        $qb->setPagingStyle('page=');
        $mockBuilder = m::mock('stdClass')
                 ->shouldReceive('paginate')
                 ->with(40,null,null,2)
                 ->andReturn('')
                 ->getMock();

        $qb->setBuilder($mockBuilder);
        $qb->paginate();
    }

    public function test_it_can_set_a_where_clause_on_a_relation() {
        $strTestUri = '/api/v1/letters?likephotos.FirstName=Jon';

        $qb = $this->createQueryBuilder($strTestUri);
        $qb->setWheres();

        $strSql = $qb->toSql();
        $strExpected = 'select * from "Table" where "Table"."deleted_at" is null and (select count(*) from "ChildTable" where "ChildTable"."TestId" = "Table"."TestId" and "FirstName" LIKE ? and "ChildTable"."deleted_at" is null) >= 1';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $qb->getBindings();
        $aExpected = ['%Jon%'];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_set_a_where_clause_on_a_grandchild_relation() {
        $strTestUri = '/api/v1/letters?likephotos.letter.FirstName=Jon';

        $qb = $this->createQueryBuilder($strTestUri);
        $qb->setWheres();

        $strSql = $qb->toSql();
        $strExpected = 'select * from "Table" where "Table"."deleted_at" is null and (select count(*) from "ChildTable" where "ChildTable"."TestId" = "Table"."TestId" and (select count(*) from "Table" where "ChildTable"."TestId" = "Table"."TestId" and "FirstName" LIKE ? and "Table"."deleted_at" is null) >= 1 and "ChildTable"."deleted_at" is null) >= 1';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $qb->getBindings();
        $aExpected = ['%Jon%'];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_get_records_where_value_inarray() {
        $strTestUri = '/api/v1/letters?inarrayLetterId[]=1&inarrayLetterId[]=2';

        $qb = $this->createQueryBuilder($strTestUri);
        $qb->setWheres();

        $strSql = $qb->getBuilder()->toSql();
        $strExpected = 'select * from "Table" where "Table"."deleted_at" is null and "LetterId" in (?, ?)';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $qb->getBindings();
        $aExpected = [1,2];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_get_records_where_value_inarray_with_escaped_query_string() {
        $strTestUri = '/api/v1/letters?inarrayLetterId%5B%5D=1&inarrayLetterId%5B%5D=2';

        $qb = $this->createQueryBuilder($strTestUri);
        $qb->setWheres();

        $strSql = $qb->getBuilder()->toSql();
        $strExpected = 'select * from "Table" where "Table"."deleted_at" is null and "LetterId" in (?, ?)';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $qb->getBindings();
        $aExpected = [1,2];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_sortbychild_descending() {
        $strTestUri = '/api/v1/letters?sortbychildstatus.ChildId';

        $qb = $this->createQueryBuilder($strTestUri);
        $qb->setWheres();

        $strSql = $qb->getBuilder()->toSql();
        $strExpected = 'select "Table".* from "Table" inner join "ChildTable" on "ChildTable"."TestId" = "Table"."TestId" where "Table"."deleted_at" is null order by "ChildTable"."ChildId" desc';
        $this->assertEquals($strExpected,$strSql);
    }

    public function test_it_can_sortbychild_ascending() {
        $strTestUri = '/api/v1/letters?sortbychildstatus.ChildId=asc';

        $qb = $this->createQueryBuilder($strTestUri);
        $qb->setWheres();

        $strSql = $qb->getBuilder()->toSql();
        $strExpected = 'select "Table".* from "Table" inner join "ChildTable" on "ChildTable"."TestId" = "Table"."TestId" where "Table"."deleted_at" is null order by "ChildTable"."ChildId" asc';
        $this->assertEquals($strExpected,$strSql);
    }

    public function test_it_can_sortbygrandchild_descending() {
        $strTestUri = '/api/v1/letters?sortbychildphotos.letter.ChildId';

        $qb = $this->createQueryBuilder($strTestUri);
        $qb->setWheres();

        $strSql = $qb->getBuilder()->toSql();
        $strExpected = 'select "Table".* from "Table" inner join "ChildTable" on "Table"."TestId" = "ChildTable"."TestId" inner join "Table" on "Table"."TestId" = "ChildTable"."TestId" where "Table"."deleted_at" is null order by "Table"."ChildId" desc';
        $this->assertEquals($strExpected,$strSql);
    }

    public function test_it_can_sortbygrandchild_ascending() {
        $strTestUri = '/api/v1/letters?sortbychildphotos.letter.ChildId=asc';

        $qb = $this->createQueryBuilder($strTestUri);
        $qb->setWheres();

        $strSql = $qb->getBuilder()->toSql();
        $strExpected = 'select "Table".* from "Table" inner join "ChildTable" on "Table"."TestId" = "ChildTable"."TestId" inner join "Table" on "Table"."TestId" = "ChildTable"."TestId" where "Table"."deleted_at" is null order by "Table"."ChildId" asc';
        $this->assertEquals($strExpected,$strSql);
    }

    public function test_it_can_include_trashed_items() {
        $strTestUri = '/api/v1/letters?withTrashed=1';

        $qb = $this->createQueryBuilder($strTestUri);
        $qb->build();

        $strSql = $qb->getBuilder()->toSql();
        $strExpected = 'select * from "Table"';
        $this->assertEquals($strExpected,$strSql);
    }

    public function test_it_can_get_only_trashed_items() {
        $strTestUri = '/api/v1/letters?onlyTrashed=1';

        $qb = $this->createQueryBuilder($strTestUri);
        $qb->build();

        $strSql = $qb->getBuilder()->toSql();
        $strExpected = 'select * from "Table" where "Table"."deleted_at" is not null';
        $this->assertEquals($strExpected,$strSql);
    }

}
