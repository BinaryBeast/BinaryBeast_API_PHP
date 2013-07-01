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
     * Make sure that we are not allowed to change the type_id after the tournament has started
     */
    public function test_update_type_id_active() {
        $this->get_tournament_with_open_matches();
        $this->assertEquals(BinaryBeast::TOURNEY_TYPE_BRACKETS, $this->object->type_id);
        $this->object->type_id = BinaryBeast::TOURNEY_TYPE_CUP;
        $this->assertEquals(BinaryBeast::TOURNEY_TYPE_BRACKETS, $this->object->type_id);
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

        $this->assertNull($this->object->team());
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
     * Test first to make sure teams() by default doesn't return freewins,
     *  and that it DOES if we specifically ask for them.
     */
    public function test_teams_freewins() {
        $this->get_tournament_ready();

        //Add a few teams to make it uneven
        for($x = 0; $x < 3; $x++) {
            $team = $this->object->team();
            $team->display_name = 'Last minute participant!!';
            $team->confirm();
        }
        $this->object->save();

        //We should have 8 BBTeams
        $this->assertChildrenArray($this->object->teams(), 'BBTeam');
        $this->assertArraySize($this->object->teams(), 11);

        //Start the tournament!
        $this->assertSave($this->object->start());

        //Teams() should return the original 11
        $this->assertArraySize($this->object->teams(), 11);

        //It should return 16 if we set $freewins true
        $this->assertArraySize($this->object->teams(false, null, true), 16);

        //Freewins should return 5
        $this->assertChildrenArray($this->object->freewins(), 'BBTeam');
        $this->assertArraySize($this->object->freewins(), 5);

        //Confirmed should return 8 and 16 with/without freewins
        $this->assertChildrenArray($this->object->confirmed_teams(false, false), 'BBTeam');
        $this->assertChildrenArray($this->object->confirmed_teams(false, true), 'BBTeam');
        $this->assertArraySize($this->object->confirmed_teams(), 11);
        $this->assertArraySize($this->object->confirmed_teams(false, true), 16);

        //Should all be labeled freewin
        foreach($this->object->freewins as $freewin) {
            $this->assertEquals('FreeWin', $freewin->display_name);
        }
    }
    /**
     * Test teams() and request ID values only
     * @covers BBTournament::teams()
     */
    public function test_teams_ids() {
        $this->get_tournament_ready();
        $this->assertArraySize($this->object->teams(), 8);
        $this->assertArraySize($ids = $this->object->teams(true), 8);
        foreach($ids as $id) {
            $this->assertTrue(is_numeric($id));
        }
    }
    /**
     * @covers BBTournament::confirmed_teams
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
    public function test_match_valid_teams() {
        $this->get_tournament_with_open_matches();

        $match = $this->object->open_matches[0];

        $this->assertEquals($match, $this->object->match($match->team(), $match->team2(), $match->bracket));
    }
    /**
     * Test loading a match by providing the two teams that are in it
     */
    public function test_match_invalid_teams() {
        $this->get_tournament_with_open_matches();

        $match = $this->object->open_matches[0];

        //Get an invalid team
        foreach($this->object->teams as &$team) {
            if(!$match->team_in_match($team)) break;
        }

        $this->assertNotNull($match, $this->object->match($match->team(), $team, $match->bracket));
    }
    /**
     * Test loading a match by providing the match id
     * @covers BBTournament::match
     */
    public function test_match_id_after_report() {
        $this->get_tournament_with_open_matches();

        $match = $this->object->open_matches[0];
        $match->set_winner($match->team());
        $this->assertSave($match->report());

        $fresh_match = $this->object->match($match->id);
        $this->assertEquals($match->id, $fresh_match->id);
    }
    /**
     * Test loading a match by providing the match id
     * @covers BBTournament::match
     */
    public function test_match_after_report() {
        $this->get_tournament_with_open_matches();

        $match = $this->object->open_matches[0];
        $match->set_winner($match->team());
        $this->assertSave($match->report());

        $fresh_match = $this->object->match($match);
        $this->assertEquals($match->id, $fresh_match->id);
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
     */
    public function test_save_matches_with_report() {
        $this->get_tournament_with_open_matches();

        $match1 = $this->object->open_matches[0];
        $match2 = $this->object->open_matches[2];
        $match3 = $this->object->open_matches[3];

        $match1->set_winner($match1->team2());
        $match2->set_winner($match2->team());

        $this->assertSave($this->object->save_matches());
        
        //First 3 matches should have ids, match 4 should NOT have one
        $this->assertID($match1->id);
        $this->assertID($match2->id);
        $this->assertNull($match3->id);
    }
    /**
     * @covers BBTournament::save_matches
     */
    public function test_save_matches_without_report() {
        $this->get_tournament_with_open_matches();

        $match1 = $this->object->open_matches[0];
        $match2 = $this->object->open_matches[2];
        $match3 = $this->object->open_matches[3];
        
        //report the first match - leave 2 and 3 alone
        $match1->set_winner($match1->team2());
        $this->assertSave($match1->report());

        //update the notes for first 2 matches
        $match1->notes = 'updated notes for match 1';
        $match2->notes = 'updated notes for match 2';

        //Now set the winners for the second 2 matches - but they should never be reported
        $match2->set_winner($match2->team());
        $match3->set_winner($match2->team());

        //Update matches without reporting
        $this->assertSave($this->object->save_matches(false));
        
        //Match 1 should have an id, but not the second 2
        $this->assertID($match1->id);
        $this->assertNull($match2->id);
        $this->assertNull($match3->id);
        
        //Externally verify notes updated on match 1
        $this->AssertMatchValueExternally($match1, 'notes', 'updated notes for match 1');
    }
    /**
     * Test team()'s ability to return the object of an existing team
     */
    public function test_team_object() {
        $this->get_tournament_ready();
        
        $team = $this->object->teams[0];
        
        $this->assertEquals($team, $this->object->team($team));
    }
    /**
     * Test team()'s ability to return the object of an existing team
     */
    public function test_team_id() {
        $this->get_tournament_ready();
        
        $team = $this->object->teams[0];
        
        $this->assertEquals($team, $this->object->team($team->id));
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

    /**
     * Tests open_match by providing a match object
     * @covers BBTournament::open_match()
     */
    public function test_open_match_valid_match_object() {
        $this->get_tournament_with_open_matches();

        $match = $this->object->open_matches[0];

        $this->assertEquals($match, $this->object->open_match($match));
    }
    /**
     * Tests open_match by providing a match object
     * @covers BBTournament::open_match()
     */
    public function test_open_match_valid_team_pair() {
        $this->get_tournament_with_open_matches();

        $match = $this->object->open_matches[0];
        $team1 = $match->team();
        $team2 = $match->opponent();

        $this->assertEquals($match, $this->object->open_match($team1, $team2));
    }
    /**
     * Tests open_match by providing a match object
     * @covers BBTournament::open_match()
     */
    public function test_open_match_invalid_team_pair() {
        $this->get_tournament_with_open_matches();

        $match = $this->object->open_matches[0];
        $team1 = $match->team();
        $invalid_team = null;
        foreach($this->object->teams() as $team) {
            if(!$match->team_in_match($team)) {
                $invalid_team = $team;
                break;
            }
        }

        $this->assertNull($this->object->open_match($team1, $invalid_team));
    }

    /**
     * Test bracket "drawing" handling
     * @covers BBTournament::brackets()
     */
    public function test_brackets() {
        $this->get_tournament_with_open_matches();

        //GOGOGO!
        $this->assertTrue(is_object($brackets = $this->object->brackets()));

        //Should have a 'winners' array
        $this->assertObjectHasAttribute('winners', $brackets);

        //Based on the 8-man bracket size, we know there should be 4 rounds (including the psuedo "display" round)
        $this->assertArraySize($brackets->winners, 4);

		//Every match in first round should have a team and oppponent
		foreach($brackets->winners[0] as $match) {
            $this->assertInstanceOf('BBTeam', $match->team);
            $this->assertInstanceOf('BBTeam', $match->opponent);
            $this->assertInstanceOf('BBMatch', $match->match);
		}
    }

    /**
     * Testing group rounds "drawing" handling
     * @covers BBTournament::groups()
     */
    public function test_groups() {
        $this->get_tournament_with_open_matches(true);

        $this->assertTrue(is_object($groups = $this->tournament->groups()));
        
        /*
         * Default test settings 2 groups, 8 teams total (4 teams per group)
         * 
         * Therefore we should have an array for group a and group b, 3 rounds each
         */
        $this->assertArraySize($groups->a, 3);
        $this->assertArraySize($groups->b, 3);

        //Every single match returned should have a team, opponent, and match
        foreach($groups as $rounds) {
            foreach($rounds as $matches) {
                foreach($matches as $match) {
                    $this->assertInstanceOf('BBTeam', $match->team);
                    $this->assertInstanceOf('BBTeam', $match->opponent);
                    $this->assertInstanceOf('BBMatch', $match->match);
                }
            }
        }
    }

    /**
     * Test reporting wins, by fetching open_match objects from a bracket draw
     */
    public function test_report_from_brackets() {
        $this->get_tournament_with_open_matches();

        $this->assertTrue(is_object($brackets = $this->object->brackets()));

        //By default, we won't have any freewins - so just grab the first match in the WB
        $this->assertInstanceOf('BBMatch', $match = $brackets->winners[0][0]->match);

        //Grab winner / loser objects, verify they're matching up with value sin the bracket draw
        $this->assertInstanceOf('BBTeam', $winner = $match->team());
        $this->assertInstanceOf('BBTeam', $loser = $match->team2());
        $this->assertEquals($brackets->winners[0][0]->team, $winner);
        $this->assertEquals($brackets->winners[0][0]->opponent, $loser);

        //Report the match
        $this->assertTrue($match->set_winner($winner));

        //GOGOGO!
        $this->assertSave($match->report());

        //The original match object should have updated itself
        $this->assertEquals($match->id, $brackets->winners[0][0]->match->id);
    }

    /**
     * Test reporting wins, by fetching open_match objects from a group-rounds draw
     */
    public function test_report_from_groups() {
        $this->get_tournament_with_open_matches(true);

        $this->assertTrue(is_object($groups = $this->object->groups()));

        //Grab the first match in group A
        $this->assertInstanceOf('BBMatch', $match = $groups->a[0][0]->match);

        //Grab winner / loser objects, verify they're matching up with values in the draw
        $this->assertInstanceOf('BBTeam', $winner = $match->team());
        $this->assertInstanceOf('BBTeam', $loser = $match->team2());
        $this->assertEquals($groups->a[0][0]->team, $winner);
        $this->assertEquals($groups->a[0][0]->opponent, $loser);

        //Report the match
        $this->assertTrue($match->set_winner($winner));

        //GOGOGO!
        $this->assertSave($match->report());

        //The original match object should have updated itself
        $this->assertEquals($match->id, $groups->a[0][0]->match->id);
    }

    /**
     * A pseudo test that is used to clean up any stray test tournaments
     *
     * It deletes all tournaments with titles that contain the phrase "API Demo", or "Unit Test"
     *
     * @group cleanup_tournament_tests
     */
    public function test_clean_test_tournaments() {
        $count = 0;

        foreach($this->bb->tournament->list_my('API Demo') as $tournament) {
            $tournament->delete();
            ++$count;
        }
        foreach($this->bb->tournament->list_my('Unit Test') as $tournament) {
            $tournament->delete();
            ++$count;
        }

        var_dump(['Tournaments Cleaned' => $count]);
    }

    /**
     * Test non-streaming played matches
     *
     * @covers BBTournament::played_matches
     */
    public function test_played_matches() {
        //Tournament with freewin matches
        $this->get_tournament_ready();

        //Add players so it's no longer an even power of 2
        for($x = 0; $x < 4; $x++) {
            $team = $this->tournament->team();
            $team->confirm();

            $team->display_name = 'Extra player ' . ($x + 1);
        }

        //Insert new players and start
        $this->assertSave($this->tournament->save());
        $this->assertSave($this->tournament->start());

        //Should be empty
        $this->assertArraySize($this->tournament->played_matches(), 0);

        //Should have a few if we request freewins
        $this->assertTrue( count($this->tournament->played_matches(true)) > 0);

        //Report a win
        $match = $this->tournament->open_matches[0];
        $match->set_winner($match->team());
        $this->assertSave( $match->report() );

        //Now played should should have 1
        $this->assertArraySize($this->tournament->played_matches(), 1);

        //Should be 0 for streaming
        $this->assertArraySize($this->tournament->played_matches(false, true), 0);
    }
}