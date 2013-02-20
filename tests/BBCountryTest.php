<?php

require_once('lib/includes.php');

/**
 * Test country listing 
 */
class BBCountryTest extends bb_test_case {

    /**
     * @var BBCountry
     */
    protected $object;

    protected function setUp() {
        $this->object = $this->bb->country();
    }

    public function testSearch() {
        $result = $this->object->search('united');
        $this->assertListFormat($result, array('country', 'country_code', 'country_code_short' => 'string', 'country_icon'));
    }

}
