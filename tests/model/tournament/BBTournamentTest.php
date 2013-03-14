<?php

/**
 * Test tournament loading / updating
 * @group tournament
 * @group model
 * @group all
 */
class BBTournamentTest extends BBTest {

    /** @var BBTournament */
    protected $object;

    protected function setUp() {
        $this->object = &$this->tournament;
        parent::setUp();
    }

    /**
     * Test saving settings without saving any children
     * @covers BBTournament::save_settings
     */
    public function test_save_settings() {
        $this->get_tournament_new();

        $this->object->title = 'changed';
        $team = $this->object->team();
        $team1 = $this->object->team();
        $team2 = $this->object->team();

        $this->assertSave($this->object->save_settings());

        //Should still be flagged as changed, since it has changed children
        $this->assertTrue($this->object->changed);

        //Make sure none of the teams were saved
        $this->assertNull($team->id);
        $this->assertNull($team1->id);
        $this->assertNull($team2->id);

        //Now reset to clean up
        $this->object->reset();
    }

    /**
     * Test creating a new tournament 
     * @covers BBTournament::save
     */
    public function test_create() {
        $this->get_tournament_inactive(false, false, false);

        $this->assertTrue($this->object->changed);
        $this->assertTrue($this->object->is_new());

        $this->assertSave($this->object->save());

        $this->assertFalse($this->object->changed);
        $this->assertFalse($this->object->is_new());
    }

    /**
     * @covers BBTournament::generate_player_password
     */
    public function test_generate_player_password() {
        $this->get_tournament_new();

        //Should be the default null value
        $this->assertNull($this->object->player_password);

        //Generate a random password!
        $this->object->generate_player_password();

        //Should now be a string, of exactly 13 characters (from unique id)
        $this->assertTrue(strlen($this->object->player_password) == 13);
    }
    /**
     * @covers BBTournament::save_teams
     */
    public function test_save_teams() {
        $this->get_tournament_inactive();
        $this->assertFalse($this->object->changed);

        for($x = 0; $x < 8; $x++) {
            $team = $this->object->team();
            $team->display_name = 'player ' . ($x + 1);
            $team->confirm();
            $team->country_code = 'USA';
            $team->network_display_name = 'in-game name ' . ($x + 1);
        }

        $this->assertTrue($this->object->changed);
        $this->assertSave($this->object->save());
        $this->assertFalse($this->object->changed);

        //We should now have exactly 8 confirmed teams in the tournament
        $this->assertArraySize($this->object->teams(), 8);
        $this->assertArraySize($this->object->confirmed_teams(), 8);
        foreach($this->object->teams as $team) {
            $this->assertID($team->id);
            $this->assertFalse($team->changed);
        }
    }
    /**
     * Test adding a single team to an active tournament, through BBTeam::save
     * @covers BBTeam::save
     */
    public function test_add_team_confirmed() {
        $this->get_tournament_inactive();

        $team = $this->object->team();
        $team->display_name = 'new player';
        $team->confirm();
        $team->country_code = 'USA';
        $team->network_display_name = 'in-game name';

        $this->assertSave($team->save());
    }
    /**
     * Test trying to saving a team in a new tournament (should not let us)
     * @covers BBTeam::save
     * @group team
     */
    public function test_add_team_new() {
        $this->get_tournament_new();

        $team = $this->object->team();
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
        $this->get_tournament_with_open_matches();

        $this->assertFalse($this->object->team());
    }
    /**
     * Testing loading a list of teams from an active tournament
     */
    public function test_teams() {
        $this->get_tournament_ready();
        $this->assertNotNull($this->object->teams());
        foreach($this->object->teams() as $team) $this->assertInstanceOf('BBTeam', $team);
    }
    /**
     * @covers BBTournament::confirmed_teams
     * @todo   Implement testConfirmed_teams().
     */
    public function test_confirmed_teams() {
        $this->get_tournament_ready();

        //Add an unconfirmed team, to make sure it's not included
        $unconfirmed = $this->object->team();
        $unconfirmed->unconfirm();

        $this->assertArraySize($this->object->teams, 9);
        $this->assertArraySize($this->object->confirmed_teams(), 8);

        //Clean up
        $unconfirmed->delete();
    }
    /**
     * @covers BBTournament::save_rounds
     */
    public function test_add_rounds() {
        $this->get_tournament_inactive();
        $this->assertFalse($this->object->changed);

        $maps = array('Abyssal Caverns', 614, 'Akilon Flats', 71, 'Arid Plateau', 337, 'Backwater Gulch', 225);
        foreach($this->object->rounds as &$bracket) {
            foreach($bracket as $x => &$round) {
                $round->best_of = 3;
                $round->map = isset($maps[$x]) ? $maps[$x] : null;
            }
        }
        $this->assertSave($this->object->save_rounds());

        $this->assertFalse($this->object->changed);
    }
    /**
     * Test the ability to load round info from an active tournament
     * @covers BBTournament::rounds
     */
    public function test_rounds() {
        $this->get_tournament_inactive();

        $this->assertObjectFormat($this->object->rounds(), array('winners' => 'array', 'bronze' => 'array'));
        $this->assertObjectFormat($this->object->rounds->winners[0], array('best_of', 'wins_needed'));
    }
    /**
     * Test to make sure reset() does everything it needs to 
     *  include resetting children, and delete new children
     */
    public function test_reset() {
        $this->get_tournament_new();

        for($x = 0; $x < 8; $x++) $this->object->team();
        $orphan_team = $this->object->team();
        $null_team = &$this->object->team();
        $this->assertArraySize($this->object->teams, 10);

        $this->assertTrue($this->object->changed);
        $this->assertTrue($this->object->is_new());
        //
        $this->object->reset();
        //

        $this->assertArraySize($this->object->teams, 0);
        $this->assertTrue($orphan_team->is_orphan());
        $this->assertNull($null_team);
        $this->assertFalse($this->object->changed);
    }
    /**
     * Testing enabling player confirmations
     * @covers BBTournament::enable_player_confirmations
     */
    public function test_enable_player_confirmations() {
        $this->get_tournament_inactive();
        $this->assertTrue($this->object->enable_player_confirmations());
        $this->assertEquals('Confirmation', $this->object->status);
    }
    /**
     * Test disabling player confirmations
     * @covers BBTournament::disable_player_confirmations
     */
    public function test_disable_player_confirmations() {
        $this->get_tournament_inactive();
        $this->assertTrue($this->object->disable_player_confirmations());
        $this->assertEquals('Building', $this->object->status);
    }
    /**
     * @covers BBTournament::remove_child
     */
    public function test_remove_child() {
        $this->get_tournament_inactive();
        $team = &$this->object->team();
        $this->assertTrue(in_array($team, $this->object->teams()));
        $this->assertTrue($this->object->remove_child($team));
        //
        $this->assertFalse(in_array($team, $this->object->teams()));
        $this->assertTrue(sizeof($this->object->teams()) == 0);
    }
    /**
     * Tests removing a child with $preserve disabled, and checking to make sure
     *  that any references are set to null
     * @covers BBTournament::remove_child
     * @group bbmodel
     */
    public function test_remove_child_unpreserved() {
        $this->get_tournament_inactive();
        $team = &$this->object->team();
        $this->assertTrue(in_array($team, $this->object->teams()));
        $this->assertTrue($this->object->remove_child($team, null, false));
        //
        $this->assertNull($team);
    }
    /**
     * Test deleting teams to make sure they are removed from the tournament correctly afterwards
     */
    public function test_remove_teams() {
        $this->get_tournament_inactive();

        //Add teams that will be saved before removal
        for($x = 0; $x < 8; $x++) $this->object->team->confirm();

        //Should now have 8 teams with ids
        $this->assertSave($this->object->save());
        $this->assertArraySize($this->object->teams(), 8);
        foreach($this->object->teams as $team) {
            $this->assertID($team->id);
        }

        //Add teams that won't have ids when removed
        for($x = 0; $x < 8; $x++) $this->object->team->ban();

        $this->assertArraySize($this->object->teams(), 16);
        $this->assertArraySize($this->object->confirmed_teams(), 8);
        $this->assertArraySize($this->object->banned_teams(), 8);

        //Deleted!!!
        foreach($this->object->teams as $team) {
            $team->delete();
        }

        $this->assertArraySize($this->object->teams(), 0);
        $this->assertArraySize($this->object->confirmed_teams(), 0);
        $this->assertArraySize($this->object->banned_teams(), 0);

        //Check externally
        $this->AssertTournamentValueExternally($this->object, 'teams', array());
    }
    /**
     * Test starting brackets with random seeding
     * @covers BBTournament::start
     */
    public function test_start_random() {
        $this->get_tournament_ready();
        $this->assertTrue($this->object->start());
        $this->assertEquals('Active', $this->object->status);
        $this->assertChildrenArray($this->object->teams(), 'BBTeam');
        $teams = $this->object->teams();
        $this->assertTrue(is_array($teams), 'BBTournament::teams() did not return an array');
        $this->assertEquals(0, $teams[0]->position);
    }
    /**
     * Test starting group rounds with random seeding
     */
    public function test_start_groups() {
        $this->get_tournament_ready(true);
        $this->assertTrue($this->object->start());
        $this->assertEquals('Active-Groups', $this->object->status);
    }
    /**
     * Test re-opening brackets to confirmation
     */
    public function test_reopen_elimination() {
        $this->get_tournament_with_open_matches();
        $this->assertEquals('Active', $this->object->status, 'object created from get_active is not Active');
        $result = $this->object->reopen();
        $this->assertTrue($result);
        $this->assertEquals('Confirmation', $this->object->status);
    }
    /**
     * Test re-opening brackets to confirmation
     */
    public function test_reopen_groups() {
        $this->get_tournament_with_open_matches(true);
        $this->assertEquals('Active-Groups', $this->object->status, 'object created from get_active is not get_active_groups, it\'s ' . $this->object->Status);
        $this->assertTrue($this->object->reopen());
        $this->assertEquals('Confirmation', $this->object->status);
    }
    /**
     * Test re-opening brackets to confirmation
     */
    public function test_reopen_brackets_to_groups() {
        $this->get_tournament_with_open_matches(true);
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
        $this->get_tournament_ready();
        //Simply start manually, alphabetical (default order when loading teams of non-active tournaments)
        $this->assertTrue($this->object->start('manual', $this->object->teams));
        $this->assertEquals('Active', $this->object->status);
        $this->assertEquals(0, $this->object->teams[0]->position);
    }
    /**
     * Test starting brackets using "balanced" seeding
     */
    public function test_start_balanced() {
        $this->get_tournament_ready();
        $this->assertTrue($this->object->start('balanced', $this->object->teams));
        $this->assertEquals('Active', $this->object->status);
        $this->assertEquals(0, $this->object->teams[0]->position);
    }
    /**
     * Test starting brackets using "sports" seeding
     */
    public function test_start_sports() {
        $this->get_tournament_ready();
        $this->assertTrue($this->object->start('balanced', $this->object->teams));
        $this->assertEquals('Active', $this->object->status);
        $this->assertEquals(0, $this->object->teams[0]->position);
    }
    /**
     * Test starting brackets with pending changes - it SHOULD fail
     */
    public function test_start_unsaved() {
        $this->get_tournament_ready();
        $this->object->title = 'unsaved!!!';
        $this->assertFalse($this->object->start());
        $this->object->reset();
        $team = $this->object->team();
        $team->display_name = 'changed!!';
        $this->assertFalse($this->object->start());
        $this->object->reset();
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
        $this->get_tournament_with_open_matches();
        $matches = $this->object->open_matches();
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
        $this->get_tournament_inactive();
        $this->assertTrue($this->object->delete());
        $this->assertNull($this->object->id);
        $this->assertNull($this->object->title);
    }
    /**
     * test loading a list of tournaments created by logged in user
     * @group list
     */
    public function test_list_my() {
        $this->get_tournament_new();
        $list = $this->object->list_my();
        $this->assertListFormat($list, array('tourney_id', 'title', 'status', 'date_start', 'game_code', 'game', 'team_mode', 'max_teams'));
    }
    /**
     * test loading a list of popular tournaments
     * @group list
     */
    public function test_list_popular() {
        $this->get_tournament_new();
        $list = $this->object->list_popular();
        $this->assertListFormat($list, array('tourney_id', 'title', 'status', 'date_start', 'game_code', 'game', 'team_mode', 'max_teams'));
    }

}
