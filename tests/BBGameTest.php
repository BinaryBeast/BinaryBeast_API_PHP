<?php

require_once('lib/includes.php');

/**
 * Test game listing / searching
 * @group game
 * @group list
 * @group simple_model
 * @group all
 */
class BBGameTest extends bb_test_case {

    /**
     * @var BBGame
     */
    protected $object;

    protected function setUp() {
        $this->object = $this->bb->game();
    }

    public function test_list_top() {
        $result = $this->object->list_top();
        $this->assertListFormat($result, array('game', 'game_code', 'game_style', 'race_label'));
    }
    
    public function test_search() {
        $result = $this->object->search('star');
        $this->assertListFormat($result, array('game', 'game_code', 'game_style', 'race_label'));
        $this->assertListContains($result, 'game_code', 'HotS');
        $this->assertListContains($result, 'game_code', 'SC2');
        $this->assertListContains($result, 'game_code', 'BW');
    }

}
