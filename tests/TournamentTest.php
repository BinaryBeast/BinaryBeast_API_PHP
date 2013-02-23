<?php

require_once('lib/includes.php');

/**
 * Test tournament loading / updating
 * @group tournament
 * @group model
 * @group all
 */
class TournamentTest extends bb_test_case {

    /**
     * @var BBTournament
     */
    protected $object;
    protected static $static_object;

    protected function setUp() {
        if(!is_null(self::$static_object)) $this->object = &self::$static_object;
        else {
            self::$static_object = $this->bb->tournament();
            $this->object = &self::$static_object;
        }
    }
    public function test_get_object() {
        $this->assertInstanceOf('BBTournament', $this->object);
    }
    public function test_update_before_save() {
        $this->object->title = 'updated';
        $this->assertEquals('updated', $this->object->title);
        $this->assertNull($this->object->id);
    }
    public function test_reset() {
        $this->assertEquals('updated', $this->object->title, 'test does not retain result from prior test');
        $this->object->reset();
        $this->assertEquals($this->object->default_value('title'), $this->object->title);
    }
    public function test_setup() {
        $this->object->title            = 'PHP Unit Test Touranment';
        $this->object->game_code        = 'SC2';
        $this->object->elimination      = 1;
        $this->object->max_teams        = 64;
        $this->assertEquals('PHP Unit Test Touranment', $this->object->title);
        $this->assertEquals('SC2', $this->object->game_code);
        $this->assertEquals(1, $this->object->elimination);
        $this->assertEquals(64, $this->object->max_teams);
    }
    public function test_create() {
        $result = $this->object->save();
        $this->assertNotEquals(false, $result);
        $this->assertStringStartsWith('x', $result);
    }
    public function add_teams() {
        for($x = 0; $x < 8; $x++) {
            $team = $this->object->team();
            $team->display_name = 'player ' . ($x + 1);
            $team->confirm();
            $team->country_code = 'USA';
            $team->network_display_name = 'in-game name ' . ($x + 1);
        }
        $this->assertTrue($this->object->save());
    }
    public function test_teams() {
        $this->assertTrue(is_array($this->object->teams()));
        $this->assertTrue(sizeof($this->object->teams()) == 8);
        foreach($this->object->teams as $team) $this->assertTrue(!is_null($team->id));
    }
    public function test_add_rounds() {
        $maps = array('Abyssal Caverns', 614, 'Akilon Flats', 71, 'Arid Plateau', 337, 'Backwater Gulch', 225);
        foreach($this->object->rounds as &$bracket) {
            foreach($bracket as $x => &$round) {
                $round->best_of = 3;
                $round->map = $maps[$x];
            }
        }
        $this->assertTrue($this->object->save());
        $round = &$this->object->rounds->finals[0];
        $round->best_of = 5;
        $this->assertTrue($round->save());
    }

    
}
