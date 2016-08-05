<?php

namespace Fh\QueryBuilder;

use Mockery as m;
use Fh\QueryBuilder\QueryBuilder;
use Fh\QueryBuilder\BuilderClause;
use Fh\Data\Mapper\US\LetterMapper;

class OrderParentByChildBuilderClauseTest extends QueryBuilderTestBase {

    public function test_it_can_instruct_the_builder_with_parent_order_by_child() {
        $w = new OrderParentByChildBuilderClause('parentorderbychild');

        $letter = new TestModel();
        $builder = $letter->newQuery();
        $w->processWhere($builder,'parentorderbychildstatus.Date');
        $strSql = $builder->toSql();
        $strExpected = 'select "Table".* from "Table" inner join "ChildTable" on "ChildTable"."TestId" = "Table"."TestId" where "Table"."deleted_at" is null order by "ChildTable"."Date" asc';
        $this->assertEquals($strExpected,$strSql);

        $builder = $letter->newQuery();
        $w->processWhere($builder,'parentorderbychildstatus.Date', 'desc');
        $strSql = $builder->toSql();
        $strExpected = 'select "Table".* from "Table" inner join "ChildTable" on "ChildTable"."TestId" = "Table"."TestId" where "Table"."deleted_at" is null order by "ChildTable"."Date" desc';
        $this->assertEquals($strExpected,$strSql);
    }

}
