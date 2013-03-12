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
    protected $tournament;

    protected function setUp() {
        $this->get_new();
    }
    protected function tearDown() {
        if(!is_null($this->tournament->id)) $this->tournament->delete();
    }

    /**
     * Sets $object to a new tournament object, completely fresh
     */
    private function get_new() {
        $this->tournament = $this->bb->tournament();
    }
    /**
     * Sets $object with settings configured, but not saved() yet
     */
    private function get_new_configured() {
        $this->get_new();
        $this->tournament->title            = 'PHP Unit Test Touranment';
        $this->tournament->game_code        = 'SC2';
        $this->tournament->elimination      = 2;
        $this->tournament->max_teams        = 64;
        $this->tournament->type_id          = BinaryBeast::TOURNEY_TYPE_BRACKETS;
    }
    /**
     * Sets $object to an inactive tournament with a tournament id
     * @return boolean - false if save() failed
     */
    private function get_inactive($skip_save = false) {
        $this->get_new_configured(true);
        if(!$skip_save) return $this->tournament->save() !== false;
        return true;
    }
    /**
     * Sets $object to a inactive tournament with 8 active teams
     * @return boolean - false if save() failed
     */
    private function get_inactive_with_teams($skip_save = false) {
        $this->get_new_configured(true);
        for($x = 0; $x < 8; $x++) {
            $team = $this->tournament->team();
            $team->display_name = 'player ' . ($x + 1);
            $team->confirm();
            $team->country_code = 'USA';
            $team->network_display_name = 'in-game name ' . ($x + 1);
        }
        if(!$skip_save) return $this->tournament->save() !== false;
        return true;
    }
    /**
     * Sets $object to an inactive tournament with teams and rounds setup
     * @return boolean - false if save() failed
     */
    private function get_inactive_with_rounds($skip_save = false) {
        $this->get_inactive_with_teams(false);
        $maps = array('Abyssal Caverns', 614, 'Akilon Flats', 71, 'Arid Plateau', 337, 'Backwater Gulch', 225);
        foreach($this->tournament->rounds as &$bracket) {
            foreach($bracket as $x => &$round) {
                $round->best_of = 3;
                $round->map = isset($maps[$x]) ? $maps[$x] : null;
            }
        }
        $this->tournament->rounds->finals[0]->best_of = 5;
        if(!$skip_save) return $this->tournament->save() !== false;
        return true;
    }
    /**
     * Sets $object to an tournament with randomized brackets
     * @return boolean
     */
    private function get_active() {
        if(!$this->get_inactive_with_rounds(false)) return false;
        $this->tournament->start();
    }
    /**
     * Sets $object to an tournament with randomized brackets
     * @return boolean
     */
    private function get_active_groups() {
        if(!$this->get_inactive_with_rounds(true)) return false;
        $this->tournament->type_id = BinaryBeast::TOURNEY_TYPE_CUP;
        if(!$this->tournament->save()) return false;
        return $this->tournament->start();
    }

    public function test_get_object() {
        $this->assertInstanceOf('BBTournament', $this->tournament);
    }
    public function test_update_before_save() {
        $this->get_new();
        $this->tournament->title = 'updated';
        $this->assertEquals('updated', $this->tournament->title);
        $this->assertNull($this->tournament->id);
    }
    public function test_setup() {
        $this->get_new_configured();
        $this->assertEquals('PHP Unit Test Touranment', $this->tournament->title);
        $this->assertEquals('SC2', $this->tournament->game_code);
        $this->assertEquals(2, $this->tournament->elimination);
        $this->assertEquals(64, $this->tournament->max_teams);
    }
    /**
     * Test saving settings without saving any children
     * @covers BBTournament::save_settings
     */
    public function test_save_settings() {
        $this->get_new();
        $this->tournament->title = 'changed';
        $team = $this->tournament->team();
        $team1 = $this->tournament->team();
        $team2 = $this->tournament->team();
        $this->assertTourneyID($this->tournament->save_settings());
        //Should still be flagged as changed, since it has changed children
        $this->assertTrue($this->tournament->changed);
        //Make sure none of the teams were saved
        $this->assertNull($team->id);
        $this->assertNull($team1->id);
        $this->assertNull($team2->id);
        //Now do a full save and make sure the teams get ids
        $this->assertTourneyID($result = $this->tournament->save());
        $this->assertFalse($this->tournament->changed);
        $this->assertNotNull($team->id);
        $this->assertNotNull($team1->id);
        $this->assertNotNull($team2->id);
    }
    /**
     * @covers BBTournament::save() when tourney_id is null
     */
    public function test_create() {
        $this->get_new_configured();
        $this->assertSave($this->tournament->save());
    }
    /**
     * @covers BBTournament::generate_player_password
     * @todo   Implement testGenerate_player_password().
     */
    public function test_generate_player_password() {
        //Should be the default null value
        $this->assertNull($this->tournament->player_password);

        //Generate a random password!
        $this->tournament->generate_player_password();

        //Should now be a string, of exactly 13 characters (from unique id)
        $this->assertTrue(strlen($this->tournament->player_password) == 13);
    }
    /**
     * Testing adding teams to an active tournament
     */
    public function test_add_teams() {
        $this->get_inactive();
        for($x = 0; $x < 8; $x++) {
            $team = $this->tournament->team();
            $team->display_name = 'player ' . ($x + 1);
            $team->confirm();
            $team->country_code = 'USA';
            $team->network_display_name = 'in-game name ' . ($x + 1);
        }
        $result = $this->tournament->save();
        //Failing because the team is saving a really weird value for status, and binarybeast is not validating it correctly
        $this->assertTrue($result != false);
        $this->assertStringStartsWith('x', $result);
    }
    /**
     * @covers BBTournament::save_teams
     * @todo   Implement testSave_teams().
     */
    public function test_save_teams() {
        $this->assertTrue(false, 'implement this test');
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
            $team = $this->tournament->team();
            $team->display_name = 'player ' . ($x + 1);
            $team->confirm();
            $team->country_code = 'USA';
            $team->network_display_name = 'in-game name ' . ($x + 1);
        }
        $result = $this->tournament->save();
        //Failing because the team is saving a really weird value for status, and binarybeast is not validating it correctly
        $this->assertTrue($result != false);
        $this->assertStringStartsWith('x', $result);
    }
    /**
     * Test adding a single team to an active tournament
     */
    public function test_add_team_confirmed() {
        $this->get_inactive();
        $team = $this->tournament->team();
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
        $team = $this->tournament->team();
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
        $team = $this->tournament->team();
        $team->display_name = 'new player';
        $team->ban();
        $team->country_code = 'USA';
        $team->network_display_name = 'in-game name';
        $this->assertTrue(is_int($team->save()));
    }
    /**
     * Test trying to save a tournament in a new tournament (should not let us)
     */
    public function test_add_team_new() {
        $this->get_inactive(true);
        $team = $this->tournament->team();
        $team->display_name = 'new player';
        $team->ban();
        $team->country_code = 'USA';
        $team->network_display_name = 'in-game name';
        //Should not allow us, tournament hasn't been saved
        $this->assertFalse($team->save());
    }
    /**
     * Test trying to add a new team to an active touranment
     */
    public function test_add_team_active() {
        $this->get_active();
        $this->assertFalse($this->tournament->team());
    }
    /**
     * Testing loading a list of teams from an active tournament
     */
    public function test_teams() {
        $this->get_inactive_with_teams();
        $teams = $this->tournament->teams();
        $this->assertTrue(is_array($teams), 'BBTournament::teams() did not return an array');
        foreach($teams as $team) $this->assertInstanceOf('BBTeam', $team);
    }
    /**
     * @covers BBTournament::confirmed_teams
     * @todo   Implement testConfirmed_teams().
     */
    public function test_confirmed_teams() {
        $this->assertTrue(false, 'implement this test');
    }
    /**
     * @covers BBTournament::confirmed_team_ids
     * @todo   Implement testConfirmed_team_ids().
     */
    public function testConfirmed_team_ids() {
        $this->assertTrue(false, 'implement this test');
    }
    /**
     * Test the ability to add a batch of rounds and upgrade them all at once
     * @covers BBTournament::save_rounds
     */
    public function test_add_rounds() {
        $this->get_inactive_with_teams();
        $maps = array('Abyssal Caverns', 614, 'Akilon Flats', 71, 'Arid Plateau', 337, 'Backwater Gulch', 225);
        $this->assertInstanceOf('stdClass', $this->tournament->rounds());
        foreach($this->tournament->rounds as &$bracket) {
            foreach($bracket as $x => &$round) {
                $round->best_of = 3;
                $round->map = isset($maps[$x]) ? $maps[$x] : null;
            }
        }
        $this->assertNotFalseOrNull($this->tournament->save());
        $round = &$this->tournament->rounds->finals[0];
        $round->best_of = 5;
        $this->assertTrue($round->save());
        $this->assertTrue(false, 'implement through save_rounds too');
    }
    /**
     * Test the ability to load round info from an active tournament
     */
    public function test_rounds() {
        $this->get_inactive_with_rounds();
        $this->assertTrue(is_object($this->tournament->rounds()));
        $this->assertTrue(is_array($this->tournament->rounds->winners));
        $this->assertTrue(sizeof($this->tournament->rounds->winners) > 0);
        $this->assertObjectFormat($this->tournament->rounds->winners[0], array('best_of', 'wins_needed'));
    }
    /**
     * Test to make sure reset() does everything it needs to 
     *  include resetting children, and delete new children
     */
    public function test_reset() {
        $this->get_new();
        $this->tournament->title = 'updated';
        $this->assertEquals('updated', $this->tournament->title, 'test does not retain result from prior test');
        $this->tournament->reset();
        $this->assertFalse($this->tournament->changed);
        $this->assertEquals($this->tournament->default_value('title'), $this->tournament->title);
        //Add teams - team0 will become null since we save a ref - team1 we test to make sure it becomes an orphan correctly
        $team0 = &$this->tournament->team();
        $team1 = $this->tournament->team();
        //
        $this->assertArrayContains($this->tournament->teams(), $team0);
        $this->assertArrayContains($this->tournament->teams(), $team1);
        $this->tournament->reset();
        $this->assertNull($team0);
        $this->assertTrue(sizeof($this->tournament->teams()) == 0);
        $this->assertFalse($this->tournament->changed);
        //Orphaned team
        $team1->dislay_name = 'updated';
        $this->assertNotEquals('updated', $team1->display_name);
        $this->assertFalse($team1->confirm());
        $this->assertFalse($team1->delete());
    }
    /**
     * Testing enabling player confirmations
     */
    public function test_enable_player_confirmations() {
        $this->get_inactive();
        $this->assertTrue($this->tournament->enable_player_confirmations());
        $this->assertEquals('Confirmation', $this->tournament->status, 'Tournament status should equal "Confirmation", "' . $this->tournament->status . '" was found');
    }
    /**
     * Test disabling player confirmations
     */
    public function test_disable_player_confirmations() {
        $this->get_inactive();
        $this->assertTrue($this->tournament->disable_player_confirmations());
        $this->assertEquals('Building', $this->tournament->status, 'Tournament status should equal "Building", "' . $this->tournament->status . '" was found');
    }
    /**
     * @covers BBTournament::remove_child
     */
    public function test_remove_child() {
        $this->get_inactive();
        $team = &$this->tournament->team();
        $this->assertTrue(in_array($team, $this->tournament->teams()));
        $this->assertTrue($this->tournament->remove_child($team));
        //
        $this->assertFalse(in_array($team, $this->tournament->teams()));
        $this->assertTrue(sizeof($this->tournament->teams()) == 0);
    }
    /**
     * Test deleting teams to make sure they are removed from the tournament correctly afterwards
     */
    public function test_remove_teams() {
        $this->assertTrue(false, 'implement this test');
    }
    /**
     * Test starting brackets with random seeding
     */
    public function test_start_random() {
        $this->get_inactive_with_rounds();
        $this->assertTrue($this->tournament->start());
        $this->assertEquals('Active', $this->tournament->status);
        $teams = $this->tournament->teams();
        $this->assertTrue(is_array($teams), 'BBTournament::teams() did not return an array');
        $this->assertEquals(0, $teams[0]->position);
    }
    /**
     * Test starting group rounds with random seeding
     */
    public function test_start_groups() {
        $this->get_inactive_with_rounds(true);
        $this->tournament->type_id = 1;
        $this->tournament->save();
        $this->assertTrue($this->tournament->start());
    }
    /**
     * Test re-opening brackets to confirmation
     */
    public function test_reopen_elimination() {
        $this->get_active();
        $this->assertEquals('Active', $this->tournament->status, 'object created from get_active is not Active');
        $result = $this->tournament->reopen();
        $this->assertTrue($result);
        $this->assertEquals('Confirmation', $this->tournament->status);
    }
    /**
     * Test re-opening brackets to confirmation
     */
    public function test_reopen_groups() {
        $result = $this->get_active_groups();
        $this->assertEquals('Active-Groups', $this->tournament->status, 'object created from get_active is not get_active_groups, it\'s ' . $this->tournament->Status);
        $result = $this->tournament->reopen();
        $this->assertTrue($result);
        $this->assertEquals('Confirmation', $this->tournament->status);
    }
    /**
     * Test re-opening brackets to confirmation
     */
    public function test_reopen_brackets_to_groups() {
        $this->get_active_groups();
        $this->assertEquals('Active-Groups', $this->tournament->status, 'object created from get_active is not Active');
        $this->assertTrue($this->tournament->start());
        $this->assertEquals('Active-Brackets', $this->tournament->status);
        $this->assertTrue($this->tournament->reopen());
        $this->assertEquals('Active-Groups', $this->tournament->status);
    }
    /**
     * Test re-openining brackets to groups
     */
    public function test_start_manual() {
        $this->get_inactive_with_teams();
        //Simply start manually, alphabetical (default order when loading teams of non-active tournaments)
        $this->assertTrue($this->tournament->start('manual', $this->tournament->teams));
        $this->assertEquals('Active', $this->tournament->status);
        $this->assertEquals(0, $this->tournament->teams[0]->position);
    }
    /**
     * Test starting brackets using "balanced" seeding
     */
    public function test_start_balanced() {
        $this->get_inactive_with_teams();
        $this->assertTrue($this->tournament->start('balanced', $this->tournament->teams));
        $this->assertEquals('Active', $this->tournament->status);
        $this->assertEquals(0, $this->tournament->teams[0]->position);
    }
    /**
     * Test starting brackets using "sports" seeding
     */
    public function test_start_sports() {
        $this->get_inactive_with_teams();
        $this->assertTrue($this->tournament->start('balanced', $this->tournament->teams));
        $this->assertEquals('Active', $this->tournament->status);
        $this->assertEquals(0, $this->tournament->teams[0]->position);
    }
    /**
     * Test starting brackets with pending changes - it SHOULD fail
     */
    public function test_start_unsaved() {
        $this->get_inactive_with_teams(true);
        $this->tournament->title = 'unsaved!!!';
        $this->assertFalse($this->tournament->start());
        $this->tournament->reset();
        $team = $this->tournament->team();
        $team->display_name = 'changed!!';
        $this->assertFalse($this->tournament->start());
        $this->tournament->reset();
    }
    /**
     * Test loading a match by providing the two teams that are in it
     */
    public function test_match_teams() {
        //Test an open match, and an existing match
        $this->assertTrue(false, 'implement this');
    }
    /**
     * Test loading a match by providing the match id
     */
    public function test_match_id() {
        //Todo test a valid match, a valid match from another touranment, and an invalid match
        $this->assertTrue(false, 'implement this');
    }
    /**
     * Test loading a list of unplayed matches
     */
    public function test_list_open_matches() {
        $this->get_active();
        $matches = $this->tournament->open_matches();
        $this->assertTrue(is_array($matches), 'BBTournament::open_matches() did not return an array)');
        foreach($matches as $match) $this->assertInstanceOf('BBMatch', $match);
    }
    /**
     * @covers BBTournament::save_matches
     * @todo   Implement testSave_matches().
     */
    public function test_save_matches() {
        $this->assertTrue(false, 'Please implement this test');
    }
    /**
     * Test team()'s ability to return the object of an existing team
     * @todo build this
     */
    public function test_team() {
        //Test valid team, invalid team, valid team from another tournament, by both directly providing a BBTeam and by providing a tourney_team_id
        $this->assertTrue(false, 'Please implement this');
    }
    /**
     * Test the ability to delete a tournament
     */
    public function test_delete() {
        $this->get_inactive();
        $this->assertTrue($this->tournament->delete());
        $this->assertNull($this->tournament->id);
        $this->assertNull($this->tournament->title);
    }
    /**
     * test loading a list of tournaments created by logged in user
     * @group list
     */
    public function test_list_my() {
        $list = $this->tournament->list_my();
        $this->assertListFormat($list, array('tourney_id', 'title', 'status', 'date_start', 'game_code', 'game', 'team_mode', 'max_teams'));
    }
    /**
     * test loading a list of popular tournaments
     * @group list
     */
    public function test_list_popular() {
        $list = $this->tournament->list_popular();
        $this->assertListFormat($list, array('tourney_id', 'title', 'status', 'date_start', 'game_code', 'game', 'team_mode', 'max_teams'));
    }

}
