<?php

/**
 * Test country listing 
 * @group country
 * @group list
 * @group simple_model
 * @group all
 */
class BBCountryTest extends BBTest {

    /** @var BBCountry */
    protected $object;

    protected function setUp() {
        $this->object = $this->bb->country();
    }

    public function testSearch() {
        $result = $this->object->search('united');
        $this->assertListFormat($result, array('country', 'country_code', 'country_code_short' => 'string', 'country_icon'));
    }

}
