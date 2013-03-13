<?php

/**
 * Test map listing 
 * @group map
 * @group list
 * @group simple_model
 * @group all
 */
class BBMapTest extends BBTest {

    /** @var BBMap */
    protected $object;

    protected function setUp() {
        $this->object = $this->bb->map();
    }

    public function testSearch() {
        $result = $this->object->game_search('sc2', 'plat');
        $this->assertListFormat($result, array('map', 'map_id'));
    }
    public function testList() {
        $this->assertListFormat($this->object->game_list('sc2'), array('map', 'map_id'));
    }

}