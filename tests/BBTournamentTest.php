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
        $this->object->type_id          = BinaryBeast::TOURNEY_TYPE_BRACKETS;
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
    public function test_add_teams() {
        return;
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
    public function test_rounds() {
        $this->assertTrue(is_object($this->object->rounds()));
        $this->assertTrue(is_array($this->object->rounds->winners));
        $this->assertTrue(sizeof($this->object->rounds->winners) > 0);
        $this->assertObjectFormat($this->rounds->winners[0], array('best_of', 'wins_needed'));
    }
    public function enable_player_confirmations() {
        $this->assertTrue($this->object->enable_player_confirmations());
        $this->assertEquals('Confirmation', $this->object->status, 'Tournament status should equal "Confirmation", "' . $this->object->status . '" was found');
    }
    public function disable_player_confirmations() {
        $this->assertTrue($this->object->disable_player_confirmations());
        $this->assertEquals('Building', $this->object->status, 'Tournament status should equal "Building", "' . $this->object->status . '" was found');
    }
    public function test_start_random() {
        $this->assertTrue($this->object->start());
        $this->assertEquals('Active', $this->object->status);
        $this->assertAttributeEquals(0, 'position', $this->object->teams[0]);
    }
    public function test_reopen() {
        $this->assertTrue($this->object->reopen());
        $this->assertEquals('Confirmation', $this->object->status);
    }
    public function test_start_manual() {
        //Simply start manually, alphabetical (default order when loading teams of non-active tournaments)
        $this->assertTrue($this->object->start('manual', $this->object->teams));
        $this->assertEquals('Active', $this->object->status);
        $this->assertAttributeEquals(0, 'position', $this->object->teams[0]);
    }
    public function test_start_balanced() {
        $this->object->reopen();
        $this->assertTrue($this->object->start('balanced', $this->object->teams));
        $this->assertEquals('Active', $this->object->status);
        $this->assertAttributeEquals(0, 'position', $this->object->teams[0]);
    }
    public function test_start_sports() {
        $this->object->reopen();
        $this->assertTrue($this->object->start('balanced', $this->object->teams));
        $this->assertEquals('Active', $this->object->status);
        $this->assertAttributeEquals(0, 'position', $this->object->teams[0]);
    }
    public function test_delete() {
        $this->assertTrue($this->object->delete());
        $this->assertNull($this->object->id);
        $this->assertNull($this->object->title);
    }

    public function test_list_my() {
        $list = $this->object->list_my();
        $this->assertListFormat($list, array('tourney_id', 'title', 'status', 'date_start', 'game_code', 'game', 'team_mode', 'max_teams'));
    }
    public function test_list_popular() {
        $list = $this->object->list_popular();
        $this->assertListFormat($list, array('tourney_id', 'title', 'status', 'date_start', 'game_code', 'game', 'team_mode', 'max_teams'));
    }
}
