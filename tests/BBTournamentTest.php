<?php

require_once('lib/includes.php');

/**
 * Test tournament loading / updating
 * @group tournament
 * @group model
 * @group all
 */
class BBTournamentTest extends bb_test_case {

    /** @var BBTournament */
    protected $object;

    protected function setUp() {
        $this->get_new();
    }
    protected function tearDown() {
        if(!is_null($this->object->id)) $this->object->delete();
    }

    /**
     * Sets $object to a new tournament object, completely fresh
     */
    private function get_new() {
        $this->object = $this->bb->tournament();
    }
    /**
     * Sets $object with settings configured, but not saved() yet
     */
    private function get_new_configured() {
        $this->get_new();
        $this->object->title            = 'PHP Unit Test Touranment';
        $this->object->game_code        = 'SC2';
        $this->object->elimination      = 2;
        $this->object->max_teams        = 64;
        $this->object->type_id          = BinaryBeast::TOURNEY_TYPE_BRACKETS;
    }
    /**
     * Sets $object to a configured + saved tournament
     * @return boolean - false if save() failed
     */
    private function get_new_saved($skip_save = false) {
        $this->get_new_configured();
        if(!$skip_save) return $this->object->save() !== false;
        return true;
    }
    /**
     * Sets $object to an inactive tournament with a tournament id
     * @return boolean - false if save() failed
     */
    private function get_inactive($skip_save = false) {
        $this->get_new_configured(true);
        if(!$skip_save) return $this->object->save() !== false;
        return true;
    }
    /**
     * Sets $object to a inactive tournament with 8 active teams
     * @return boolean - false if save() failed
     */
    private function get_inactive_with_teams($skip_save = false) {
        $this->get_new_configured(true);
        for($x = 0; $x < 8; $x++) {
            $team = $this->object->team();
            $team->display_name = 'player ' . ($x + 1);
            $team->confirm();
            $team->country_code = 'USA';
            $team->network_display_name = 'in-game name ' . ($x + 1);
        }
        if(!$skip_save) return $this->object->save() !== false;
        return true;
    }
    /**
     * Sets $object to an inactive tournament with teams and rounds setup
     * @return boolean - false if save() failed
     */
    private function get_inactive_with_rounds($skip_save = false) {
        $this->get_inactive_with_teams(false);
        $maps = array('Abyssal Caverns', 614, 'Akilon Flats', 71, 'Arid Plateau', 337, 'Backwater Gulch', 225);
        foreach($this->object->rounds as &$bracket) {
            foreach($bracket as $x => &$round) {
                $round->best_of = 3;
                $round->map = isset($maps[$x]) ? $maps[$x] : null;
            }
        }
        $this->object->rounds->finals[0]->best_of = 5;
        if(!$skip_save) return $this->object->save() !== false;
        return true;
    }
    /**
     * Sets $object to an tournament with randomized brackets
     * @return boolean
     */
    private function get_active() {
        if(!$this->get_inactive_with_rounds(false)) return false;
        $this->object->start();
    }
    /**
     * Sets $object to an tournament with randomized brackets
     * @return boolean
     */
    private function get_active_groups() {
        if(!$this->get_inactive_with_rounds(true)) return false;
        $this->object->type_id = BinaryBeast::TOURNEY_TYPE_CUP;
        if(!$this->object->save()) return false;
        return $this->object->start();
    }

    public function test_get_object() {
        $this->assertInstanceOf('BBTournament', $this->object);
    }
    public function test_update_before_save() {
        $this->get_new();
        $this->object->title = 'updated';
        $this->assertEquals('updated', $this->object->title);
        $this->assertNull($this->object->id);
    }
    public function test_reset() {
        $this->get_new();
        $this->object->title = 'updated';
        $this->assertEquals('updated', $this->object->title, 'test does not retain result from prior test');
        $this->object->reset();
        $this->assertEquals($this->object->default_value('title'), $this->object->title);
    }
    public function test_setup() {
        $this->get_new_configured();
        $this->assertEquals('PHP Unit Test Touranment', $this->object->title);
        $this->assertEquals('SC2', $this->object->game_code);
        $this->assertEquals(2, $this->object->elimination);
        $this->assertEquals(64, $this->object->max_teams);
    }
    public function test_create() {
        $this->get_new_configured();
        $result = $this->object->save();
        $this->assertNotEquals(false, $result);
        $this->assertStringStartsWith('x', $result);
    }
    /**
     * Testing adding teams to an active tournament
     */
    public function test_add_teams() {
        $this->get_inactive();
        for($x = 0; $x < 8; $x++) {
            $team = $this->object->team();
            $team->display_name = 'player ' . ($x + 1);
            $team->confirm();
            $team->country_code = 'USA';
            $team->network_display_name = 'in-game name ' . ($x + 1);
        }
        $result = $this->object->save();
        //Failing because the team is saving a really weird value for status, and binarybeast is not validating it correctly
        $this->assertTrue($result != false);
        $this->assertStringStartsWith('x', $result);
    }
    /**
     * Testing adding teams + saving in
     *  a tournament that hasn't been saved yet
     * 
     * Saves through tournament, not by team
     */
    public function test_add_teams_inactive() {
        $this->get_inactive(true);
        for($x = 0; $x < 8; $x++) {
            $team = $this->object->team();
            $team->display_name = 'player ' . ($x + 1);
            $team->confirm();
            $team->country_code = 'USA';
            $team->network_display_name = 'in-game name ' . ($x + 1);
        }
        $result = $this->object->save();
        //Failing because the team is saving a really weird value for status, and binarybeast is not validating it correctly
        $this->assertTrue($result != false);
        $this->assertStringStartsWith('x', $result);
    }
    /**
     * Test adding a single team to an active tournament
     */
    public function test_add_team_confirmed() {
        $this->get_inactive();
        $team = $this->object->team();
        $team->display_name = 'new player';
        $team->confirm();
        $team->country_code = 'USA';
        $team->network_display_name = 'in-game name';
        $result = $team->save();
        $this->assertTrue(is_int($result));
    }
    /**
     * Test adding a single team to an active tournament
     */
    public function test_add_team_unconfirmed() {
        $this->get_inactive();
        $team = $this->object->team();
        $team->display_name = 'new player';
        $team->unconfirm();
        $team->country_code = 'USA';
        $team->network_display_name = 'in-game name';
        $this->assertTrue(is_int($team->save()));
    }
    /**
     * Test adding a single banned team to an active tournament
     */
    public function test_add_team_banned() {
        $this->get_inactive();
        $team = $this->object->team();
        $team->display_name = 'new player';
        $team->ban();
        $team->country_code = 'USA';
        $team->network_display_name = 'in-game name';
        $this->assertTrue(is_int($team->save()));
    }
    /**
     * Test adding a single team to an active tournament
     */
    public function test_add_team_inactive() {
        $this->get_inactive(true);
        $team = $this->object->team();
        $team->display_name = 'new player';
        $team->ban();
        $team->country_code = 'USA';
        $team->network_display_name = 'in-game name';
        //Should not allow us, tournament hasn't been saved
        $this->assertFalse($team->save());
    }
    /**
     * Testing loading a list of teams from an active tournament
     */
    public function test_teams() {
        $this->get_inactive_with_teams();
        $teams = $this->object->teams();
        $this->assertTrue(is_array($teams), 'BBTournament::teams() did not return an array');
        foreach($teams as $team) $this->assertInstanceOf('BBTeam', $team);
    }
    /**
     * Test the ability to add a batch of rounds and upgrade them all at once
     */
    public function test_add_rounds() {
        $this->get_inactive_with_teams();
        $maps = array('Abyssal Caverns', 614, 'Akilon Flats', 71, 'Arid Plateau', 337, 'Backwater Gulch', 225);
        $this->assertInstanceOf('stdClass', $this->object->rounds());
        foreach($this->object->rounds as &$bracket) {
            foreach($bracket as $x => &$round) {
                $round->best_of = 3;
                $round->map = isset($maps[$x]) ? $maps[$x] : null;
            }
        }
        $this->assertNotFalseOrNull($this->object->save());
        $round = &$this->object->rounds->finals[0];
        $round->best_of = 5;
        $this->assertNotFalseOrNull($round->save());
    }
    /**
     * Test the ability to load round info from an active tournament
     */
    public function test_rounds() {
        $this->get_inactive_with_rounds();
        $this->assertTrue(is_object($this->object->rounds()));
        $this->assertTrue(is_array($this->object->rounds->winners));
        $this->assertTrue(sizeof($this->object->rounds->winners) > 0);
        $this->assertObjectFormat($this->object->rounds->winners[0], array('best_of', 'wins_needed'));
    }
    /**
     * Testing enabling player confirmations
     */
    public function enable_player_confirmations() {
        $this->get_inactive();
        $this->assertTrue($this->object->enable_player_confirmations());
        $this->assertEquals('Confirmation', $this->object->status, 'Tournament status should equal "Confirmation", "' . $this->object->status . '" was found');
    }
    /**
     * Test disabling player confirmations
     */
    public function disable_player_confirmations() {
        $this->get_inactive();
        $this->assertTrue($this->object->disable_player_confirmations());
        $this->assertEquals('Building', $this->object->status, 'Tournament status should equal "Building", "' . $this->object->status . '" was found');
    }
    /**
     * Test starting brackets with random seeding
     */
    public function test_start_random() {
        $this->get_inactive_with_rounds();
        $this->assertTrue($this->object->start());
        $this->assertEquals('Active', $this->object->status);
        $teams = $this->object->teams();
        $this->assertTrue(is_array($teams), 'BBTournament::teams() did not return an array');
        $this->assertEquals(0, $teams[0]->position);
    }
    /**
     * Test starting group rounds with random seeding
     */
    public function test_start_groups() {
        $this->get_inactive_with_rounds(true);
        $this->object->type_id = 1;
        $this->object->save();
        $this->assertTrue($this->object->start());
    }
    /**
     * Test re-opening brackets to confirmation
     */
    public function test_reopen_elimination() {
        $this->get_active();
        $this->assertEquals('Active', $this->object->status, 'object created from get_active is not Active');
        $result = $this->object->reopen();
        $this->assertTrue($result);
        $this->assertEquals('Confirmation', $this->object->status);
    }
    /**
     * Test re-opening brackets to confirmation
     */
    public function test_reopen_groups() {
        $result = $this->get_active_groups();
        $this->assertEquals('Active-Groups', $this->object->status, 'object created from get_active is not get_active_groups, it\'s ' . $this->object->Status);
        $result = $this->object->reopen();
        $this->assertTrue($result);
        $this->assertEquals('Confirmation', $this->object->status);
    }
    /**
     * Test re-opening brackets to confirmation
     */
    public function test_reopen_brackets_to_groups() {
        $this->get_active_groups();
        $this->assertEquals('Active-Groups', $this->object->status, 'object created from get_active is not Active');
        $this->assertTrue($this->object->start());
        $this->assertEquals('Active-Brackets', $this->object->status);
        $this->assertTrue($this->object->reopen());
        $this->assertEquals('Active-Groups', $this->object->status);
    }
    /**
     * Test re-openining brackets to groups
     */
    public function test_start_manual() {
        $this->get_inactive();
        //Simply start manually, alphabetical (default order when loading teams of non-active tournaments)
        $this->assertTrue($this->object->start('manual', $this->object->teams));
        $this->assertEquals('Active', $this->object->status);
        $this->assertEquals(0, $this->object->teams[0]->position);
    }
    /**
     * Test starting brackets using "balanced" seeding
     */
    public function test_start_balanced() {
        $this->get_inactive();
        $this->assertTrue($this->object->start('balanced', $this->object->teams));
        $this->assertEquals('Active', $this->object->status);
        $this->assertEquals(0, $this->object->teams[0]->position);
    }
    /**
     * Test starting brackets using "sports" seeding
     */
    public function test_start_sports() {
        $this->get_inactive();
        $this->assertTrue($this->object->start('balanced', $this->object->teams));
        $this->assertEquals('Active', $this->object->status);
        $this->assertEquals(0, $this->object->teams[0]->position);
    }
    /**
     * Test starting brackets with pending changes - it SHOULD fail
     */
    public function test_start_unsaved() {
        $this->get_inactive_with_teams(true);
        $this->object->title = 'unsaved!!!';
        $this->assertFalse($this->object->start());
        $this->object->reset();
        $team = $this->object->team();
        $team->display_name = 'changed!!';
        $this->assertFalse($this->object->start());
        $this->object->reset();
    }
    /**
     * Test loading a list of unplayed matches
     */
    public function test_list_open_matches() {
        $this->get_active();
        $matches = $this->object->open_matches();
        $this->assertTrue(is_array($matches), 'BBTournament::open_matches() did not return an array)');
        foreach($matches as $match) $this->assertInstanceOf('BBMatch', $match);
    }
    /**
     * Test team()'s ability to return the object of an existing team
     * @todo build this
     */
    public function test_team() {
        $this->assertTrue(false, 'Please implement this');
    }
    /**
     * Test the ability to delete a tournament
     */
    public function test_delete() {
        $this->get_inactive();
        $this->assertTrue($this->object->delete());
        $this->assertNull($this->object->id);
        $this->assertNull($this->object->title);
    }
    /**
     * test loading a list of tournaments created by logged in user
     * @group list
     */
    public function test_list_my() {
        $list = $this->object->list_my();
        $this->assertListFormat($list, array('tourney_id', 'title', 'status', 'date_start', 'game_code', 'game', 'team_mode', 'max_teams'));
    }
    /**
     * test loading a list of popular tournaments
     * @group list
     */
    public function test_list_popular() {
        $list = $this->object->list_popular();
        $this->assertListFormat($list, array('tourney_id', 'title', 'status', 'date_start', 'game_code', 'game', 'team_mode', 'max_teams'));
    }
}
