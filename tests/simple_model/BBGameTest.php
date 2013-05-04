<?php

/**
 * Test game listing / searching
 * @group game
 * @group list
 * @group simple_model
 * @group all
 */
class BBGameTest extends BBTest {

    /** @var BBGame */
    protected $object;

    protected function setUp() {
        $this->object = $this->bb->game();
    }

    /**
     */
    public function test_list_top() {
        $result = $this->object->list_top();
        $this->assertListFormat($result, array('game', 'game_code', 'parent_id', 'network_id', 'genre', 'genre_id', 'genre_abbreviation', 'race_label'));
    }

    /**
     */
    public function test_search() {
        $result = $this->object->search('star');
        $this->assertListFormat($result, array('game', 'game_code', 'parent_id', 'network_id', 'genre', 'genre_id', 'genre_abbreviation', 'race_label'));
        $this->assertListContains($result, 'game_code', 'HotS');
        $this->assertListContains($result, 'game_code', 'SC2');
        $this->assertListContains($result, 'game_code', 'BW');
    }

}
