<?php

namespace Fh\QueryBuilder;

use Mockery as m;
use Fh\QueryBuilder\QueryBuilder;

class QueryBuilderTest extends QueryBuilderTestBase {

    public function test_it_can_create_a_simple_sql_statement() {
        $strTestUri = '/api/v1/letters';

        $queryBuilder = $this->createQueryBuilder($strTestUri);
        $queryBuilder->build();
        $strSql = $queryBuilder->toSql();
        $strExpected = 'select * from `Table` where `Table`.`deleted_at` is null';
        $this->assertEquals($strExpected,$strSql);
    }

    public function test_it_can_filter_results_by_parent_relation() {
        $strTestUri = '/api/v1/letters/23/photos';
        $queryBuilder = $this->createQueryBuilder($strTestUri);
        $queryBuilder->setModelCreationCallback(function($strClassPath) {
            $letter = $this->getMockLetter(23);
            $mockBuilder = m::mock('stdClass')
                     ->shouldReceive('first')
                     ->andReturn($letter)
                     ->getMock();
            return m::mock("{$strClassPath}[where]")
                     ->shouldReceive('where')
                     ->with('LetterId','=',23)
                     ->andReturn($mockBuilder)
                     ->getMock();
        });

        $queryBuilder->filterByParentRelation();
        $strSql = $queryBuilder->toSql();
        $strExpected = 'select * from `LetterPhoto` where `LetterPhoto`.`deleted_at` is null and `LetterPhoto`.`LetterId` = ? and `LetterPhoto`.`LetterId` is not null';
        $this->assertEquals($strExpected,$strSql);
        $aBindings = $queryBuilder->getBindings();
        $aExpectedBindings = [23];
        $this->assertEquals($aExpectedBindings,$aBindings);
    }

    public function test_it_can_include_relations_using_with() {
        // Single where
        $strTestUri = '/api/v1/letters?with[]=photos';

        $queryBuilder = $this->createQueryBuilder($strTestUri);
        $queryBuilder->includeRelations();
        $eagerLoads = $queryBuilder->getBuilder()->getEagerLoads();
        $this->assertEquals(['photos'],array_keys($eagerLoads));
        $strSql = $queryBuilder->toSql();
        $strExpected = 'select * from `Table` where `Table`.`deleted_at` is null';
        $this->assertEquals($strExpected,$strSql);

        // Multiple wheres
        $strTestUri = '/api/v1/letters?with[]=photos&with[]=status';

        $queryBuilder = $this->createQueryBuilder($strTestUri);
        $queryBuilder->includeRelations();
        $eagerLoads = $queryBuilder->getBuilder()->getEagerLoads();
        $this->assertEquals(['photos','status'],array_keys($eagerLoads));
        $strSql = $queryBuilder->toSql();
        $strExpected = 'select * from `Table` where `Table`.`deleted_at` is null';
        $this->assertEquals($strExpected,$strSql);
    }

    public function test_it_can_set_wheres() {
        $strTestUri = '/api/v1/letters?betweenLetterId[]=4&betweenLetterId[]=8&whereFirstName=Jon';

        $queryBuilder = $this->createQueryBuilder($strTestUri);
        $queryBuilder->setWheres();
        $strSql = $queryBuilder->toSql();
        $strExpected = 'select * from `Table` where `Table`.`deleted_at` is null and `LetterId` between ? and ? and `FirstName` = ?';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $queryBuilder->getBindings();
        $aExpected = [4,8,'Jon'];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_include_translations_when_locale_is_set() {
        $strTestUri = '/api/v1/letters?locale=en';

        $queryBuilder = $this->createQueryBuilder($strTestUri);
        $queryBuilder->setTranslations();
        $eagerLoads = $queryBuilder->getBuilder()->getEagerLoads();
        $this->assertEquals(['translations'],array_keys($eagerLoads));

        $strSql = $queryBuilder->toSql();
        $strExpected = 'select * from `Table` where `Table`.`deleted_at` is null';
        $this->assertEquals($strExpected,$strSql);
    }

    public function test_it_can_get_a_record_count_for_all_records() {
        $strTestUri = '/api/v1/letters?likeFirstName=Jon';

        $queryBuilder = $this->createQueryBuilder($strTestUri);
        $queryBuilder->setWheres();
        $countBuilder = $queryBuilder->getCountBuilder();

        $strSql = $queryBuilder->toSql();
        $strExpected = 'select * from `Table` where `Table`.`deleted_at` is null and `FirstName` LIKE ?';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $queryBuilder->getBindings();
        $aExpected = ['%Jon%'];
        $this->assertEquals($aExpected,$aBindings);
    }
}
