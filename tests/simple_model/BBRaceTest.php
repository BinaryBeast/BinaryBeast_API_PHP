<?php

/**
 * Test map listing 
 * @group race
 * @group list
 * @group simple_model
 * @group all
 */
class BBRaceTest extends BBTest {

    /** @var BBRace */
    protected $object;

    protected function setUp() {
        $this->object = $this->bb->race();
    }

    public function testSearch() {
        $result = $this->object->game_search('sc2', 'r');
        $this->assertListFormat($result, array('race', 'race_id'));
    }
    public function testList() {
        $this->assertListFormat($this->object->game_list('sc2'), array('race', 'race_id'));
    }

}
