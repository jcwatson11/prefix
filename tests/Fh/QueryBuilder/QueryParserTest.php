<?php

namespace Fh\QueryBuilder;

use Mockery as m;
use Fh\QueryBuilder\QueryBuilder;

class QueryParserTest extends QueryBuilderTestBase {

    public function test_it_loads_default_values_from_the_config_file() {
        // Simple non-compound URI
        $strTestUri = '/api/v1/letters';
        $queryParser = $this->createQueryParser($strTestUri);
        $this->assertEquals('/api/v1/',$queryParser->strUriBase);
    }

    public function test_it_can_get_stripped_segments() {
        // Simple non-compound URI
        $strTestUri = '/api/v1/letters';
        $queryParser = $this->createQueryParser($strTestUri);
        $aSegments = $queryParser->getStrippedSegments();
        $aExpected = ['letters'];
        $this->assertEquals($aExpected,$aSegments);

        // Compound URI
        $strTestUri = '/api/v1/letters/1/photos';
        $queryParser = $this->createQueryParser($strTestUri);
        $aSegments = $queryParser->getStrippedSegments();
        $aExpected = ['letters','1','photos'];
        $this->assertEquals($aExpected,$aSegments);
    }

    public function test_it_can_turn_segments_into_route_formatted_strings() {
        // Simple non-compound URI
        $strTestUri = '/api/v1/letters/';
        $queryParser = $this->createQueryParser($strTestUri);
        $strFormatted = $queryParser->getRouteName();
        $strExpected = 'letters';
        $this->assertEquals($strExpected,$strFormatted);

        // Compound URI
        $strTestUri = '/api/v1/letters/1/photos';
        $queryParser = $this->createQueryParser($strTestUri);
        $strFormatted = $queryParser->getRouteName();
        $strExpected = 'letters.photos';
        $this->assertEquals($strExpected,$strFormatted);
    }

    public function test_it_can_return_the_proper_sequence_of_primary_key_values_from_a_URI() {
        // Simple non-compound URI
        $strTestUri = '/api/v1/letters/';
        $queryParser = $this->createQueryParser($strTestUri);
        $aFormatted = $queryParser->getKeySequence();
        $aExpected = [];
        $this->assertEquals($aExpected,$aFormatted);

        // Compound URI
        $strTestUri = '/api/v1/letters/10/photos/2';
        $queryParser = $this->createQueryParser($strTestUri);
        $aFormatted = $queryParser->getKeySequence();
        $aExpected = [10,2];
        $this->assertEquals($aExpected,$aFormatted);
    }

    public function test_it_can_resolve_a_parent_relations_model_route_name() {
        // Simple non-compound URI
        $strTestUri = '/api/v1/letters/';
        $queryParser = $this->createQueryParser($strTestUri);
        $strRelation = $queryParser->getParentRouteName();
        $expected = false;
        $this->assertEquals($expected,$strRelation);

        // Compound URI
        $strTestUri = '/api/v1/letters/10/photos';
        $queryParser = $this->createQueryParser($strTestUri);
        $strRelation = $queryParser->getParentRouteName();
        $expected = 'letters';
        $this->assertEquals($expected,$strRelation);

        // Double-Compound URI
        $strTestUri = '/api/v1/letters/10/photos/4/original';
        $queryParser = $this->createQueryParser($strTestUri);
        $strRelation = $queryParser->getParentRouteName();
        $expected = 'letters.photos';
        $this->assertEquals($expected,$strRelation);
    }

    public function test_it_can_resolve_a_parent_relations_model_class_name() {
        // Simple non-compound URI
        $strTestUri = '/api/v1/letters/';
        $queryParser = $this->createQueryParser($strTestUri);
        $strRelation = $queryParser->getModelName();
        $expected = 'TestModel';
        $this->assertEquals($expected,$strRelation);

        // Compound URI
        $strTestUri = '/api/v1/letters/10/photos';
        $queryParser = $this->createQueryParser($strTestUri);
        $strRelation = $queryParser->getModelName();
        $expected = 'TestModel';
        $this->assertEquals($expected,$strRelation);

        // Double-Compound URI
        $strTestUri = '/api/v1/letters/10/photos/4/original';
        $queryParser = $this->createQueryParser($strTestUri);
        $strRelation = $queryParser->getModelName();
        $expected = 'TestChildModel';
        $this->assertEquals($expected,$strRelation);
    }

    public function test_it_can_resolve_a_relations_name() {
        // Simple non-compound URI
        $strTestUri = '/api/v1/letters/';
        $queryParser = $this->createQueryParser($strTestUri);
        $strRelation = $queryParser->getRelationName();
        $expected = false;
        $this->assertEquals($expected,$strRelation);

        // Compound URI
        $strTestUri = '/api/v1/letters/10/photos';
        $queryParser = $this->createQueryParser($strTestUri);
        $strRelation = $queryParser->getRelationName();
        $expected = 'photos';
        $this->assertEquals($expected,$strRelation);

        // Double-Compound URI
        $strTestUri = '/api/v1/letters/10/photos/4/original';
        $queryParser = $this->createQueryParser($strTestUri);
        $strRelation = $queryParser->getRelationName();
        $expected = 'original';
        $this->assertEquals($expected,$strRelation);
    }

    public function test_it_can_resolve_a_model_relation_name_from_a_url() {
        // non-existant URI
        $strTestUri = '/api/v1/';
        $queryParser = $this->createQueryParser($strTestUri);
        $this->setExpectedException('Exception');
        $strRelation = $queryParser->getModelRelationName();
        $expected = false;
        $this->assertEquals($expected,$strRelation);

        // Single non-compound URI
        $strTestUri = '/api/v1/letters';
        $queryParser = $this->createQueryParser($strTestUri);
        $strRelation = $queryParser->getModelRelationName();
        $expected = 'LetterMapper';
        $this->assertEquals($expected,$strRelation);

        // Compound URI
        $strTestUri = '/api/v1/letters/23/photos';
        $queryParser = $this->createQueryParser($strTestUri);
        $strRelation = $queryParser->getModelRelationName();
        $expected = 'LetterMapper.photos';
        $this->assertEquals($expected,$strRelation);

        // Double-Compound URI
        $strTestUri = '/api/v1/letters/23/photos/4/original';
        $queryParser = $this->createQueryParser($strTestUri);
        $strRelation = $queryParser->getModelRelationName();
        $expected = 'LetterPhotoMapper.original';
        $this->assertEquals($expected,$strRelation);
    }

    public function test_it_can_find_the_parent_route_basename() {
        // Double-Compound URI
        $strTestUri = '/api/v1/letters/23/photos/4/original';
        $queryParser = $this->createQueryParser($strTestUri);
        $strRelation = $queryParser->getParentRouteBaseName();
        $expected = 'photos';
        $this->assertEquals($expected,$strRelation);
    }

    public function test_it_can_find_the_parent_relations_id_in_the_URI() {
        // Simple non-compound URI
        $strTestUri = '/api/v1/letters/23';
        $queryParser = $this->createQueryParser($strTestUri);
        $this->setExpectedException('Exception');
        $strRelation = $queryParser->getParentId();
        $expected = '';
        $this->assertEquals($expected,$strRelation);

        // Compound URI
        $strTestUri = '/api/v1/letters/23/photos/4';
        $queryParser = $this->createQueryParser($strTestUri);
        $strRelation = $queryParser->getParentId();
        $expected = '23';
        $this->assertEquals($expected,$strRelation);

        // Compound URI
        $strTestUri = '/api/v1/letters/14/photos';
        $queryParser = $this->createQueryParser($strTestUri);
        $strRelation = $queryParser->getParentId();
        $expected = '14';
        $this->assertEquals($expected,$strRelation);

        // Double-Compound URI
        $strTestUri = '/api/v1/letters/23/photos/4/original';
        $queryParser = $this->createQueryParser($strTestUri);
        $strRelation = $queryParser->getParentId();
        $expected = '4';
        $this->assertEquals($expected,$strRelation);
    }

    public function test_it_can_find_the_resource_id_when_a_single_resource_is_requested() {
        // No single resource requested
        $strTestUri = '/api/v1/letters/23/photos';
        $queryParser = $this->createQueryParser($strTestUri);
        $this->setExpectedException('Exception');
        $strId = $queryParser->getResourceId();
        $this->assertNull($strRelation);

        // Simple non-compound URI
        $strTestUri = '/api/v1/letters/23';
        $queryParser = $this->createQueryParser($strTestUri);
        $this->setExpectedException('Exception');
        $strId = $queryParser->getResourceId();
        $expected = '23';
        $this->assertEquals($expected,$strRelation);

        // Double-Compound URI
        $strTestUri = '/api/v1/letters/23/photos/4';
        $queryParser = $this->createQueryParser($strTestUri);
        $strId = $queryParser->getResourceId();
        $expected = '4';
        $this->assertEquals($expected,$strRelation);
    }

    public function test_it_can_fix_phps_auto_replacement_of_special_characters_in_key_names() {
        $strTestUri = '/api/v1/letters?likephoto.Caption=Testing&likecontacts.FirstName=Jon';
        $queryParser = $this->createQueryParser($strTestUri);

        // First, we verify that PHP does, in fact, replace dot characters
        // with underscores, which it does. If PHP changes in the future,
        // this is no longer a valid test.
        $aPHPInputs = $queryParser->request->all();
        $aExpectedInputs = [
            'likephoto_Caption' => 'Testing'
            ,'likecontacts_FirstName' => 'Jon'
        ];
        $this->assertEquals($aExpectedInputs,$aPHPInputs);

        // Next, we verify that our query parser has the ability to fix those
        // inputs by getting the original query string and parsing it.
        $strQueryString = $queryParser->getOriginalQueryString();
        $strExpected = 'likecontacts.FirstName=Jon&likephoto.Caption=Testing';
        $this->assertEquals($strExpected, $strQueryString);

        $fixed = $queryParser->fixInput($strQueryString);
        $aExpected = [
            'likephoto.Caption'       => 'Testing'
            ,'likecontacts.FirstName' => 'Jon'
        ];
        $this->assertEquals($aExpected,$fixed);
    }

    public function test_it_makes_fixed_inputs_available_during_construction() {
        $strTestUri = '/api/v1/letters?likephoto.Caption=Testing&likecontacts.FirstName=Jon';
        $queryParser = $this->createQueryParser($strTestUri);
        $aExpected = [
            'likephoto.Caption'       => 'Testing'
            ,'likecontacts.FirstName' => 'Jon'
        ];
        $this->assertEquals($aExpected,$queryParser->fixedInput);
    }

    public function test_it_can_resolve_a_relation_name_from_a_compound_field_name() {
        $strTestUri = '/api/v1/letters?likephoto.Caption=Testing';
        $queryParser = $this->createQueryParser($strTestUri);
        $this->setExpectedException('Exception');
        $strId = $queryParser->getResourceId();
        $expected = '23';
        $this->assertEquals($expected,$strRelation);
    }

}
