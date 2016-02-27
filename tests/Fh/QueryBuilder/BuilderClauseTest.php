<?php

namespace Fh\QueryBuilder;

use Mockery as m;
use Fh\QueryBuilder\QueryBuilder;
use Fh\QueryBuilder\BuilderClause;
use Fh\Data\Mapper\US\LetterMapper;

class BuilderClauseTest extends QueryBuilderTestBase {

    public function test_it_can_provide_a_default_value_modifier() {
        $w = new BuilderClause('where','where','=');

        $fn = $w->getDefaultValueModifier();
        $this->assertIsClosure($fn);
    }

    public function test_it_can_properly_strip_the_prefix_out_of_a_field_name() {
        $w = new BuilderClause('inarray','whereIn');

        $strParameter = 'inarrayFirstName';
        $strExpected = 'FirstName';
        $strActual   = $w->getFieldNameFromParameter($strParameter);
        $this->assertEquals($strExpected,$strActual);
    }

    public function test_it_can_modify_an_array_of_values() {
        $w = new BuilderClause('like','where','LIKE',function(&$value) {
            $value = "%$value%";
        });

        $aValues = ['this here','is','a','test'];
        $aExpected = ['%this here%','%is%','%a%','%test%'];
        $w->modifyValues($aValues);
        $this->assertEquals($aExpected,$aValues);
    }

    public function test_it_can_modify_a_single_value() {
        $w = new BuilderClause('like','where','LIKE',function(&$value) {
            $value = "%$value%";
        });

        $strValue = 'This is a test';
        $strExpected = '%This is a test%';
        $w->modifyValues($strValue);
        $this->assertEquals($strExpected,$strValue);
    }

    public function test_it_can_instruct_the_builder_with_an_isnull() {
        $w = new BuilderClause('isnull','whereNull');

        $letter = new LetterMapper();
        $builder = $letter->newQuery();
        $w->processWhere($builder,'isnullFirstName');
        $strSql = $builder->toSql();
        $strExpected = 'select * from `Letter` where `Letter`.`deleted_at` is null and `FirstName` is null';
        $this->assertEquals($strExpected,$strSql);
    }

    public function test_it_can_instruct_the_builder_with_an_isnotnull() {
        $w = new BuilderClause('isnotnull','whereNotNull');

        $letter = new LetterMapper();
        $builder = $letter->newQuery();
        $w->processWhere($builder,'isnotnullFirstName');
        $strSql = $builder->toSql();
        $strExpected = 'select * from `Letter` where `Letter`.`deleted_at` is null and `FirstName` is not null';
        $this->assertEquals($strExpected,$strSql);
    }

    public function test_it_can_instruct_the_builder_with_an_orwhere() {
        $w = new BuilderClause('orwhere','orWhere','=');

        $letter = new LetterMapper();
        $builder = $letter->newQuery();
        $w->processWhere($builder,'orwhereFirstName','Jon');
        $strSql = $builder->toSql();
        $strExpected = 'select * from `Letter` where `Letter`.`deleted_at` is null or `FirstName` = ?';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $builder->getBindings();
        $aExpected = ['Jon'];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_instruct_the_builder_with_a_where() {
        $w = new BuilderClause('where','where','=');

        $letter = new LetterMapper();
        $builder = $letter->newQuery();
        $w->processWhere($builder,'whereFirstName','Jon');
        $strSql = $builder->toSql();
        $strExpected = 'select * from `Letter` where `Letter`.`deleted_at` is null and `FirstName` = ?';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $builder->getBindings();
        $aExpected = ['Jon'];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_instruct_the_builder_with_an_orderby() {
        $w = new BuilderClause('orderby','orderBy');

        $letter = new LetterMapper();
        $builder = $letter->newQuery();
        $w->processWhere($builder,'orderbyFirstName');
        $strSql = $builder->toSql();
        $strExpected = 'select * from `Letter` where `Letter`.`deleted_at` is null order by `FirstName` asc';
        $this->assertEquals($strExpected,$strSql);
    }

    public function test_it_can_instruct_the_builder_with_a_groupby() {
        $w = new BuilderClause('groupby','groupBy');

        $letter = new LetterMapper();
        $builder = $letter->newQuery();
        $w->processWhere($builder,'groupbyFirstName');
        $strSql = $builder->toSql();
        $strExpected = 'select * from `Letter` where `Letter`.`deleted_at` is null group by `FirstName`';
        $this->assertEquals($strExpected,$strSql);
    }

    public function test_it_can_instruct_the_builder_with_a_between() {
        $w = new BuilderClause('between','whereBetween');

        $letter = new LetterMapper();
        $builder = $letter->newQuery();
        $w->processWhere($builder,'betweenLetterId',[5,7]);
        $strSql = $builder->toSql();
        $strExpected = 'select * from `Letter` where `Letter`.`deleted_at` is null and `LetterId` between ? and ?';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $builder->getBindings();
        $aExpected = [5,7];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_instruct_the_builder_with_a_notinarray() {
        $w = new BuilderClause('notinarray','whereNotIn');

        $letter = new LetterMapper();
        $builder = $letter->newQuery();
        $w->processWhere($builder,'notinarrayLetterId',[5,7]);
        $strSql = $builder->toSql();
        $strExpected = 'select * from `Letter` where `Letter`.`deleted_at` is null and `LetterId` not in (?, ?)';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $builder->getBindings();
        $aExpected = [5,7];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_instruct_the_builder_with_a_inarray() {
        $w = new BuilderClause('inarray','whereIn');

        $letter = new LetterMapper();
        $builder = $letter->newQuery();
        $w->processWhere($builder,'inarrayLetterId',[5,7]);
        $strSql = $builder->toSql();
        $strExpected = 'select * from `Letter` where `Letter`.`deleted_at` is null and `LetterId` in (?, ?)';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $builder->getBindings();
        $aExpected = [5,7];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_instruct_the_builder_with_a_like() {
        $w = new BuilderClause('like','where','LIKE',function(&$value) {
            $value = "%$value%";
        });

        $letter = new LetterMapper();
        $builder = $letter->newQuery();
        $w->processWhere($builder,'likeFirstName','Jon');
        $strSql = $builder->toSql();
        $strExpected = 'select * from `Letter` where `Letter`.`deleted_at` is null and `FirstName` LIKE ?';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $builder->getBindings();
        $aExpected = ['%Jon%'];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_instruct_the_builder_with_an_orlike() {
        $w = new BuilderClause('orlike','orWhere','LIKE',function(&$value) {
            $value = "%$value%";
        });

        $letter = new LetterMapper();
        $builder = $letter->newQuery();
        $w->processWhere($builder,'orlikeFirstName','Jon');
        $strSql = $builder->toSql();
        $strExpected = 'select * from `Letter` where `Letter`.`deleted_at` is null or `FirstName` LIKE ?';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $builder->getBindings();
        $aExpected = ['%Jon%'];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_instruct_the_builder_with_a_greaterthan() {
        $w = new BuilderClause('greaterthan','where','>=');

        $letter = new LetterMapper();
        $builder = $letter->newQuery();
        $w->processWhere($builder,'greaterthanLetterId',9);
        $strSql = $builder->toSql();
        $strExpected = 'select * from `Letter` where `Letter`.`deleted_at` is null and `LetterId` >= ?';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $builder->getBindings();
        $aExpected = [9];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_instruct_the_builder_with_a_lessthan() {
        $w = new BuilderClause('lessthan','where','<=');

        $letter = new LetterMapper();
        $builder = $letter->newQuery();
        $w->processWhere($builder,'lessthanLetterId',9);
        $strSql = $builder->toSql();
        $strExpected = 'select * from `Letter` where `Letter`.`deleted_at` is null and `LetterId` <= ?';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $builder->getBindings();
        $aExpected = [9];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_instruct_the_builder_with_a_filter() {
        $w = new BuilderClause('filter','byStatus');

        $letter = new LetterMapper();
        $builder = $letter->newQuery();
        $w->processFilter($builder,'filterByStatus',1);
        $strSql = $builder->toSql();
        $strExpected = 'select * from `Letter` where `Letter`.`deleted_at` is null and `StatusId` = ?';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $builder->getBindings();
        $aExpected = [1];
        $this->assertEquals($aExpected,$aBindings);
    }

    public function test_it_can_instruct_the_builder_with_a_scope() {
        $w = new BuilderClause('scope','byStatus');

        $letter = new LetterMapper();
        $builder = $letter->newQuery();
        $w->processFilter($builder,'scopeByStatus',1);
        $strSql = $builder->toSql();
        $strExpected = 'select * from `Letter` where `Letter`.`deleted_at` is null and `StatusId` = ?';
        $this->assertEquals($strExpected,$strSql);

        $aBindings = $builder->getBindings();
        $aExpected = [1];
        $this->assertEquals($aExpected,$aBindings);
    }

}