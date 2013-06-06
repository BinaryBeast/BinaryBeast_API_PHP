<?php

/**
 * Test match model reporting and manipulations
 * @group match
 * @group model
 * @group all
 */
class BBMatchTest extends BBTest {

    /** @var BBMatch */
    protected $object;

    /**
     * Runs before each test - grab a tournament with some open untouched matches, and 
     *  save one of them to our local $object property
     */
    protected function setUp() {
        $this->set_object();
        parent::setUp();
    }
    /**
     * Set $this->object to an open match - based on $groups input it will
     *  either use the default tournament, or create one with group rounds enabled
     * 
     * @param boolean $groups
     */
    private function set_object($groups = false) {
        $this->get_tournament_with_open_matches($groups);
        $this->object = $this->tournament->open_matches[0];
    }
    /**
     * Returns a BBTeam object KNOWN not to be part of this match
     * @return BBTeam
     */
    private function get_invalid_team() {
        foreach($this->tournament->teams as $team) {
            if(!$this->object->team_in_match($team)) {
                return $team;
            }
        }
    }
    /**
     * @covers BBMatch::winner
     */
    public function test_winner() {
        $winner = $this->object->team2();
        $this->assertTrue($this->object->set_winner($winner));
        $this->assertEquals($winner, $this->object->winner());
    }
    /**
     * Tests to make sure winner() still works after reporting the match
     * @covers BBMatch::winner
     */
    public function test_winner_after_report() {
        $winner = $this->object->team2();
        $this->assertTrue($this->object->set_winner($winner));
        $this->assertEquals($winner, $this->object->winner());

        //Report it
        $this->assertSave($id = $this->object->report());

        //Assert directly
        $this->assertEquals($winner, $this->object->winner);
        $this->assertEquals($winner, $this->object->winner());

        //Assert externally - wrap $winner through BBTournament::team to make sure we have an up-to-date team object
        $external = $this->bb->match($id);
        $this->assertEquals($winner->id, $external->winner->id);
    }
    /**
     * Tests to make sure that winner() returns the right team after calling set_winner twice to change the expected value
     * @covers BBMatch::winner
     */
    public function test_winner_after_swap() {
        $team1 = $this->object->team();
        $team2 = $this->object->team2();
        //
        $this->assertTrue($this->object->set_winner($team2));
        $this->assertEquals($team2, $this->object->winner());
        //
        $this->assertTrue($this->object->set_winner($team1));
        $this->assertEquals($team1, $this->object->winner());
    }
    /**
     * @covers BBMatch::loser
     */
    public function test_loser() {
        $winner = $this->object->team2();
        $loser = $this->object->team();
        $this->assertTrue($this->object->set_winner($winner));
        $this->assertEquals($loser, $this->object->loser());
    }
    /**
     * @covers BBMatch::team
     */
    public function test_team() {
        $this->assertInstanceOf('BBTeam', $this->object->team());
    }
    /**
     * @covers BBMatch::team2
     */
    public function test_team2() {
        $this->assertInstanceOf('BBTeam', $this->object->team2());
    }
    /**
     * @covers BBMatch::opponent
     */
    public function test_opponent() {
        $this->assertInstanceOf('BBTeam', $this->object->opponent());
    }
    /**
     * @covers BBMatch::set_winner
     */
    public function test_set_winner() {
        $this->assertInstanceOf('BBTeam', $team = $this->object->team2());
        $this->assertTrue($this->object->set_winner($team));
        $this->assertEquals($team, $this->object->winner());
    }
    /**
     * @covers BBMatch::set_loser
     */
    public function test_set_loser() {
        $winner = $this->object->team2();
        $loser  = $this->object->team();
        $this->assertTrue($this->object->set_loser($loser));
        $this->assertEquals($winner, $this->object->winner());
    }
    /**
     * Tests the magic __set when trying to set the winner / loser by directly
     *  setting tourney_team_id (set_winner())
     * @covers BBMatch::__set
     */
    public function test_set_tourney_team_id() {
        $winner = $this->object->team2();
        $loser = $this->object->team();
        $this->object->tourney_team_id = $winner;
        //
        $this->assertEquals($winner, $this->object->winner());
        $this->assertEquals($loser, $this->object->loser());
    }
    /**
     * Tests the magic __set when trying to set the winner / loser by directly
     *  setting tourney_team_id (set_winner())
     * @covers BBMatch::__set
     */
    public function test_set_o_tourney_team_id() {
        $winner = $this->object->team2();
        $loser = $this->object->team();
        $this->object->o_tourney_team_id = $loser;
        //
        $this->assertEquals($winner, $this->object->winner());
        $this->assertEquals($loser, $this->object->loser());
    }
    /**
     * Test to make sure that score and o_score are correctly updated if we define
     *  them in set_winner
     * @covers BBMatch::set_winner
     */
    public function test_set_winner_scores() {
        $this->assertTrue($this->object->set_winner($this->object->team(), 15, 3));
        $this->assertEquals(15, $this->object->score);
        $this->assertEquals(3, $this->object->o_score);
    }
    /**
     * Test to make sure that score and o_score are correctly updated if we define
     *  them in set_winner multiple times
     * @covers BBMatch::set_winner
     */
    public function test_set_winner_scores_multiple_calls() {
        $this->assertTrue($this->object->set_winner($this->object->team(), 15, 3));
        $this->assertEquals(15, $this->object->score);
        $this->assertEquals(3, $this->object->o_score);
        //
        $this->assertTrue($this->object->set_winner($this->object->team2(), null, null));
        $this->assertEquals(15, $this->object->score);
        $this->assertEquals(3, $this->object->o_score);
    }
    /**
     * Test to see if the scores and o_scores properties return 
     *  the default 1 and 0 values if we don't define a winner
     */
    public function test_scores_property_without_winner() {
        $this->assertEquals(1, $this->object->score);
        $this->assertEquals(0, $this->object->o_score);
    }
    /**
     * Test to see if the scores and o_scores properties return 
     *  the number of game wins after games have been added
     */
    public function test_scores_property_with_games() {
        $this->assertTrue($this->object->set_winner($this->object->team(), 15, 3));
        $this->assertInstanceOf('BBMatchGame', $this->object->game());
        $this->assertInstanceOf('BBMatchGame', $this->object->game($this->object->loser()));
        $this->assertInstanceOf('BBMatchGame', $this->object->game());
        $this->assertEquals(2, $this->object->score);
        $this->assertEquals(1, $this->object->o_score);
    }
    /**
     * Make sure that when calling set_winner with FALSE, it flags it as a draw
     * @covers BBMatch::set_winner_draw
     */
    public function test_set_winner_draw() {
        $this->set_object(true);
        $this->assertTrue($this->object->set_winner(false));
        $this->assertTrue($this->object->is_draw());
        $this->assertTrue($this->object->draw);
    }
    /**
     * @covers BBMatch::round_format()
     */
    public function test_round_format() {
        $this->assertInstanceOf('BBRound', $this->object->round_format);
    }
    /**
     * Test team_in_match, using a valid team BBTeam object
     * @covers BBMatch::team_in_match
     */
    public function test_valid_team_object_in_match() {
        $this->assertInstanceOf('BBTeam', $this->object->team_in_match($this->object->team()));
    }
    /**
     * Test team_in_match, using a valid team id
     * @covers BBMatch::team_in_match
     */
    public function test_valid_team_object_id_match() {
        $this->assertInstanceOf('BBTeam', $this->object->team_in_match($this->object->team->id));
    }
    /**
     * Test team_in_match, using an invalid BBTeam object
     * @covers BBMatch::team_in_match
     */
    public function test_invalid_team_object_in_match() {
        $this->assertFalse($this->object->team_in_match($this->get_invalid_team()));
    }
    /**
     * Test team_in_match, using a invalid team id
     * @covers BBMatch::team_in_match
     */
    public function test_invalid_team_id_in_match() {
        $invalid_team = $this->get_invalid_team();
        $this->assertFalse($this->object->team_in_match($invalid_team->id));
    }
    /**
     * @covers BBMatch::tournament
     */
    public function test_tournament() {
        $this->assertEquals($this->tournament, $this->object->tournament());
    }
    /**
     * Tests to make sure game() returns a new BBGame model
     * @covers BBMatch::game
     */
    public function test_game() {
        $this->assertFalse($this->object->changed);
        $this->assertInstanceOf('BBMatchGame', $this->object->game());
        $this->assertTrue($this->object->changed);
    }
    /**
     * BBMatch should throw a fit when we try to create more games than the round's best_of allows
     * @covers BBMatch::game()
     */
    public function test_game_beyond_best_of() {
        $round = $this->object->round_format();
        for($x = 0; $x < $round->best_of + 5; $x++) {
            if($x < $round->best_of)    $this->assertInstanceOf('BBMatchGame', $this->object->game());
            else                        $this->assertNull($this->object->game());
        }
    }
    /**
     * Test to make sure that reset() resets all possible values within an unsaved match
     * 
     * @covers BBMatch::reset
     */
    public function test_reset() {
        $winner = $this->object->team2();
        $loser = $this->object->team();

        //Set a winner - and define both score and o_score values
        $this->assertTrue($this->object->set_winner($winner, 99, 88));
        $this->assertEquals(99, $this->object->score);
        $this->assertEquals(88, $this->object->o_score);

        //Add game details now
        $game1 = $this->object->game($loser);
        $game2 = $this->object->game($winner);
        $game3 = $this->object->game($loser);
        $this->assertArraySize($this->object->games(), 3);
        $this->assertArrayContains($this->object->games(), $game1);
        $this->assertArrayContains($this->object->games(), $game2);
        $this->assertArrayContains($this->object->games(), $game3);

        //Reset
        $this->assertTrue($this->object->reset());

        //Games should be empty now
        $this->assertArraySize($this->object->games(), 0);

        //Winner/Loser should now return null
        $this->assertNull($this->object->winner());
        $this->assertNull($this->object->loser());
        
        $this->assertTrue($this->object->is_new());
        $this->assertFalse($this->object->changed);

        //Scores should reset to 1-0
        $this->assertEquals(1, $this->object->score);
        $this->assertEquals(0, $this->object->o_score);
    }

    /**
     * Tests the value of score and o_score on a minimal match report
     */
    public function test_default_scores() {
        $this->object->round_format->best_of = 3;
        $this->assertSave($this->object->round_format->save());

        $this->assertTrue( $this->object->set_winner($this->object->team()) );

        //Assert before
        $this->assertEquals(1, $this->object->score);
        $this->assertEquals(0, $this->object->o_score);

        $this->assertID($id = $this->object->report() );

        //Assert after
        $this->assertEquals(1, $this->object->score);
        $this->assertEquals(0, $this->object->o_score);

        //Asset externally
        $this->AssertMatchValueExternally($id, 'score', 1);
        $this->AssertMatchValueExternally($id, 'o_score', 0);
    }

    /**
     * Test the accuracy of toggle_team() with a valid BBTeam object
     * @covers BBMatch::toggle_team()
     */
    public function test_toggle_team_valid_object() {
        $team1          = $this->object->team();
        $team2          = $this->object->opponent();
        //
        $this->assertEquals($team2, $this->object->toggle_team($team1));
        $this->assertEquals($team1, $this->object->toggle_team($team2));
    }
    /**
     * Test the accuracy of toggle_team() with a valid team id
     * @covers BBMatch::toggle_team()
     */
    public function test_toggle_team_valid_id() {
        $team1          = $this->object->team();
        $team2          = $this->object->opponent();
        //
        $this->assertEquals($team2, $this->object->toggle_team($team1->id));
        $this->assertEquals($team1, $this->object->toggle_team($team2->id));
    }
    /**
     * Test the accuracy of toggle_team() with an invalid BBTeam object
     * @covers BBMatch::toggle_team()
     */
    public function test_toggle_team_invalid_object() {
        $this->assertNull($this->object->toggle_team($this->get_invalid_team()));
    }
    /**
     * Test the accuracy of toggle_team() with an invalid team id
     * @covers BBMatch::toggle_team()
     */
    public function test_toggle_team_invalid_id() {
        $invalid = $this->get_invalid_team();
        $this->assertNull($this->object->toggle_team($invalid->id));
    }
    /**
     * @covers BBMatch::set_draw
     */
    public function test_set_draw() {
        $this->set_object(true);

        //set_draw
        $this->assertTrue($this->object->set_draw());
        $this->assertTrue($this->object->draw);

        //Team returns should now be false
        $this->assertFalse($this->object->winner());
        $this->assertFalse($this->object->loser());
    }
    /**
     * @covers BBMatch::set_draw
     */
    public function test_set_draw_after_reset() {
        $this->set_object(true);

        //set_draw
        $this->assertTrue($this->object->set_draw());
        $this->assertFalse($this->object->loser());

        //Reset should reset draw to false
        $this->object->reset();
        $this->assertFalse($this->object->is_draw());
        $this->assertTrue($this->object->set_draw());
        $this->assertTrue($this->object->is_draw());
    }
    /**
     * Test game() after calling set_draw on the match, to make sure that new games
     *  are inheriting the default draw value - unless a winner is defined
     * @covers BBMatch::set_draw
     * @covers BBMatch::game
     * @group match_game
     */
    public function test_set_draw_inherited_by_games() {
        $this->set_object(true);

        //set_draw
        $this->assertTrue($this->object->set_draw());

        //Now test to make sure that new games are also considered draws
        $game = $this->object->game();
        $this->assertFalse($game->winner());

        //New games with winner specific should not be considered draws
        $game1 = $this->object->game($this->object->team());
        $this->assertFalse($game1->is_draw());
        $this->assertEquals($this->object->team(), $game1->winner());
    }
    /**
     * Test set_draw in a tournament without groups (draws only work in group rounds)
     */
    public function test_set_draw_brackets() {
        $this->assertFalse($this->object->set_draw());
        $this->assertFalse($this->object->set_winner(false));
        $this->assertFalse($this->object->set_winner(null));
    }
    /**
     * Test team_in_match to make sure it returns a team, or false when appropriate
     */
    public function test_team_in_match() {
        $invalid_team = $this->get_invalid_team();
        $valid_team   = &$this->object->team();

        //Invalid - by BBTeam, and by id integer
        $this->assertFalse($this->object->team_in_match($invalid_team));
        $this->assertFalse($this->object->team_in_match($invalid_team->id));

        //Valid team - passing in the model and id
        $this->assertEquals($valid_team, $this->object->team_in_match($valid_team));
        $this->assertEquals($valid_team, $this->object->team_in_match($valid_team->id));
    }

    /**
     * Test to make sure save_games returns false if 
     *  trying to call it before saving the match itself
     */
    public function test_save_games_inactive() {
        $game = $this->object->game();
        $this->assertFalse($this->object->save_games());
    }

    /**
     * Test the accuracy of test_get_game_wins
     */
    public function test_get_game_wins() {
        $winner = &$this->object->opponent();
        $loser  = &$this->object->team();
        
        //Winner should start with 0 wins, and it shoudn't change after defining a game without a winner
        $this->assertEquals(0, $this->object->get_game_wins($winner));
        $this->assertInstanceOf('BBMatchGame', $game1 = $this->object->game());
        $this->assertEquals(0, $this->object->get_game_wins($winner));

        //Now all new games will define the $match winner as game winner by default
        $this->object->set_winner($winner);
        $this->assertInstanceOf('BBMatchGame', $game2 = $this->object->game());
        $this->assertInstanceOf('BBMatchGame', $game3 = $this->object->game($loser));

        //Verify the game::winner() returns correctly
        $this->assertNull($game1->winner());
        $this->assertEquals($winner, $game2->winner());
        $this->assertEquals($loser, $game3->winner());

        //Now the main test - get_game_wins
        $this->assertEquals(1, $this->object->get_game_wins($winner));
        $this->assertEquals(1, $this->object->get_game_wins($loser));

        //Changing a game's winner should change the result
        $game1->set_winner($loser);
        $this->assertEquals(1, $this->object->get_game_wins($winner));
        $this->assertEquals(2, $this->object->get_game_wins($loser));
        //
        $this->object->games[2]->set_winner($winner);
        $this->assertEquals(2, $this->object->get_game_wins($winner));
        $this->assertEquals(1, $this->object->get_game_wins($loser));        
        
    }
    /**
     * Test validate_games, which will check to see if you 
     *  gave the match winner enough game wins, based on the round's best_of setting
     */
    public function test_validate_games() {
        //Determine winner
        $winner = $this->object->team();
        $this->assertTrue($this->object->set_winner($winner));
        $loser = $this->object->loser();

        //Make sure it's BO3 - so we can also test strict mode
        $this->object->round_format->best_of = 3;
        $this->assertEquals(2, $this->object->round_format->wins_needed);

        //Should not validate yet - but at least it works even without any games set
        $this->assertFalse($this->object->validate_winner_games());
        $this->assertFalse($this->object->validate_winner_games(true));

        //Give the loser the first win - 0:1, still shouldn't validate
        $game1 = $this->object->game($loser);
        $this->assertFalse($this->object->validate_winner_games());
        $this->assertFalse($this->object->validate_winner_games(true));

        //Give $winner a win - 1:1, still shouldn't validate
        $game2 = $this->object->game();
        $this->assertFalse($this->object->validate_winner_games(true));

        //Give the $winner the first win - 2:1, it should now validate, even in strict mode
        $game3 = $this->object->game();
        $this->assertTrue($this->object->validate_winner_games());
        $this->assertTrue($this->object->validate_winner_games(true));

        //Now give $winner all 3 game wins - 3:0 should validate unless we enable $strict
        $this->assertTrue($game1->set_winner($winner));
        $this->assertTrue($this->object->validate_winner_games());
        $this->assertFalse($this->object->validate_winner_games(true));
    }

    /**
     * Test trying to call report() without defining a winner
     */
    public function test_report_without_winner() {
        $this->assertFalse($this->object->report());
    }

    /**
     * Tests to make sure that it won't let us report the match if the round has unsaved changes
     */
    public function test_report_with_unsaved_round() {
        //Easy enough to trigger a change - just increment the best_of
        $this->object->round_format->best_of += 3;

        //Make sure the match has a winner - to guarantee it would otherwise report successfully
        $this->object->set_winner($this->object->team());

        $this->assertFalse($this->object->report());
        
        //Clean up
        $this->object->round_format->reset();
    }
    /**
     * Report a match in elimination brackets
     * @covers BBMatch::report()
     * @covers BBMatch::game()
     * @covers BBMatch::team()
     * @covers BBMatch::opponent()
     * @covers BBMatch::set_winner()
     */
    public function test_report_brackets() {
        $this->assertInstanceOf('BBTeam', $winner = &$this->object->opponent());
        $this->assertInstanceOf('BBTeam', $loser = &$this->object->team());
        $this->assertTrue($this->object->set_winner($winner));

        //Setup the games
        $this->assertInstanceOf('BBMatchGame', $game1 = $this->object->game($loser));
        $this->assertInstanceOf('BBMatchGame', $game2 = $this->object->game($winner));
        $this->assertInstanceOf('BBMatchGame', $game3 = $this->object->game($winner));

        //Report and assert!
        $this->assertSave($this->object->report());
        $this->assertFalse($this->object->changed);

        //Make sure each game got an ID too
        foreach($this->object->games as &$game) {
            $this->assertID($game->id);
            $this->assertFalse($game->changed);
        }
    }
    /**
     * Test to make sure that after reporting a match, it is removed
     *  from the tournament's open_matches array
     */
    public function test_report_removes_from_open_matches() {
        //Should be in there to start
        $this->assertArrayContains($this->tournament->open_matches(), $this->object);

        //Gogo report!
        $this->assertTrue( $this->object->set_winner($this->object->team()) );
        $this->assertSave($this->object->report());

        //After reporting, it should be removed from open_matches, but make sure it wasn't set to NULL first
        $this->assertNotNull($this->object);

        //After reporting, it should have been removed
        $this->assertArrayNotContains($this->tournament->open_matches(), $this->object);
    }
    /**
     * @covers BBMatch::is_draw
     */
    public function test_is_draw() {
        //We need a match from group rounds
        $this->set_object(true);
        //
        $this->assertFalse($this->object->is_draw());
        $this->assertTrue($this->object->set_draw());
        $this->assertTrue($this->object->is_draw());

        //Create a generic match with custom data
        $match = $this->bb->match((object)array('draw' => 1));
        $this->assertTrue($match->is_draw());
    }
    /**
     * Test reporting multiple matches through BBTournament::save()
     * @group tournament
     */
    public function test_report_brackets_batch() {
        //Keep requesting new tournaments until we get one with at least 2 open matches
        while( sizeof($this->tournament->open_matches()) < 2 ) {
            $this->get_tournament_with_open_matches();
        }

        $this->assertInstanceOf('BBMatch', $match1 = $this->tournament->open_matches[0]);
        $this->assertInstanceOf('BBMatch', $match2 = $this->tournament->open_matches[1]);

        //Make sure best_of is 3 for both matches
        $match1->round_format->best_of = 3;
        $match2->round_format->best_of = 3;
        $this->assertTrue($match1->tournament->save_rounds());

        //Define winners
        $this->assertTrue($match1->set_winner($match1->team2()));
        $this->assertTrue($match2->set_winner($match2->team()));

        //Match 1 Game details
        $this->assertInstanceOf('BBMatchGame', $match1->game());
        $this->assertInstanceOf('BBMatchGame', $match1->game($match1->loser()));
        $this->assertInstanceOf('BBMatchGame', $match1->game());
        //
        $this->assertInstanceOf('BBMatchGame', $match2->game($match2->loser()));
        $this->assertInstanceOf('BBMatchGame', $match2->game());
        $this->assertInstanceOf('BBMatchGame', $match2->game());

        //GOGOGO!
        $this->assertSave($this->tournament->save());

        //Both matches should have match ids now
        $this->assertID($match1->id);
        $this->assertID($match2->id);

        //Matches should not have changes flagged, and should no longer be considered new
        $this->assertFalse($match1->changed);
        $this->assertFalse($match2->changed);
        $this->assertFalse($match1->is_new());
        $this->assertFalse($match2->is_new());

        //All games within matches should have ids too
        foreach($match1->games as &$game) $this->assertID($game->id);
        foreach($match2->games as &$game) $this->assertID($game->id);
    }
    /**
     * Report a match in elimination brackets - in strict mode (winner has to have exactly enough game wins)
     */
    public function test_report_brackets_strict() {
        $this->object->round_format->best_of = 3;
        $this->assertTrue($this->object->round_format->save());
        //
        $this->assertInstanceOf('BBTeam', $winner = &$this->object->opponent());
        $this->assertInstanceOf('BBTeam', $loser = &$this->object->team());
        $this->assertTrue($this->object->set_winner($winner));

        //Start out by giving the loser too many wins
        $this->assertInstanceOf('BBMatchGame', $this->object->game($winner));
        $this->assertInstanceOf('BBMatchGame', $this->object->game($loser));
        $this->assertInstanceOf('BBMatchGame', $this->object->game($loser));
        $this->assertFalse($this->object->report(true));

        //Now give the winner too many wins
        $this->assertTrue($this->object->games[0]->set_winner($winner));
        $this->assertTrue($this->object->games[1]->set_winner($winner));
        $this->assertTrue($this->object->games[2]->set_winner($winner));
        $this->assertFalse($this->object->report(true));

        //Now make it just-right
        $this->assertTrue($this->object->games[0]->set_winner($winner));
        $this->assertTrue($this->object->games[1]->set_winner($loser));
        $this->assertTrue($this->object->games[2]->set_winner($winner));
        $this->assertSave($this->object->report(true));
    }
    /**
     * Make sure that deleted BBMatchGames are removed from the match and update
     *  the get_game_wins results correctly
     *
     * @group match_game
     */
    public function test_delete_game_plus_report_strict() {
        $this->assertTrue($this->object->set_winner($this->object->team()));
        $winner = &$this->object->winner();

        //Make sure it's BO3
        $this->object->round_format->best_of = 3;
        $this->assertTrue($this->object->round_format->save());

        //Add 3 games - match winner wins all 3 - invalid if reporting strict
        $this->assertInstanceOf('BBMatchGame', $this->object->game($winner));
        $this->assertInstanceOf('BBMatchGame', $this->object->game($winner));
        $this->assertInstanceOf('BBMatchGame', $this->object->game($winner));
        $this->assertTrue(sizeof($this->object->games()) == 3);
        $this->assertFalse($this->object->report(true));

        //Now delete the 3rd game, make sure it's removed from $games array
        $this->assertInstanceOf('BBMatchGame', $game = &$this->object->games[2]);
        $this->assertTrue($game->delete());
        $this->assertNull($game);
        $this->assertTrue(sizeof($this->object->games()) == 2);

        //Should work now that we have 2 games
        $this->assertSave($this->object->report(true));
    }
    /**
     * Test trying to call report() on a draw match
     */
    public function test_report_draw() {
        //First, get a match from group rounds
        $this->set_object(true);

        //Set the match as a draw
        $this->assertTrue($this->object->set_draw());
        //
        $this->assertInstanceOf('BBTeam', $team1 = $this->object->team());
        $this->assertInstanceOf('BBTeam', $team2 = $this->object->opponent());

        //Game details
        $this->assertInstanceOf('BBMatchGame', $game1 = $this->object->game());
        $this->assertInstanceOf('BBMatchGame', $game2 = $this->object->game($this->object->team()));
        $this->assertInstanceOf('BBMatchGame', $game3 = $this->object->game($this->object->team2()));
        
        //Match + game1 should be draws
        $this->assertTrue($this->object->is_draw());
        $this->assertTrue($game1->is_draw());

        //GOGOGO!
        $this->assertSave($id = $this->object->report());
        $this->assertTrue($this->object->is_draw());

        //Double check directly from the API to make sure that it is flagged as a draw remotely
        $this->AssertMatchValueExternally($this->object, 'draw', true);
    }
    /**
     * Test updating games in an existing match
     *
     * @covers BBGame::save_games()
     */
    public function test_save_games() {
        $this->assertTrue($this->object->set_winner($this->object->team()));
        $this->assertSave($this->object->report());

        //Reported - try adding game details after reporting using save_games
        $this->assertInstanceOf('BBMatchGame', $game1 = $this->object->game());
        $this->assertInstanceOf('BBMatchGame', $game2 = $this->object->game($this->object->loser()));
        $this->assertTrue($this->object->save_games());

        //Make sure they have ids
        $this->assertID($game1->id);
        $this->assertID($game2->id);

        //Add one more game, this time use match::save to submit
        $this->assertInstanceOf('BBMatchGame', $game3 = $this->object->game());
        $this->assertSave($this->object->save());
        $this->assertID($game3->id);
    }
    /**
     * Test trying to call report() on an already reported match
     */
    public function test_double_report() {
        $this->assertTrue($this->object->is_new());
        $this->assertTrue($this->object->set_winner($this->object->team()));

        $this->assertSave($this->object->report());

        //Already reported - it should throw a fit if I try again
        $this->assertFalse($this->object->report());
    }

    /**
     * Test unreport() on a match that was part of a tournament
     *  phase that is no longer active - group rounds, in a tournament
     *  in active-brackets
     */
    public function test_unreport_invalid_status() {
        $this->set_object(true);

        //match should be in group rounds
        $this->assertEquals(0, $this->object->bracket);

        //Start the brackets!
        $this->assertTrue($this->tournament->start());

        //Our object should not have changed - should still see itself as in group rounds
        $this->assertInstanceOf('BBMatch', $this->object);
        $this->assertEquals(0, $this->object->bracket);
        $this->assertEquals($this->object->bracket, $this->object->round_format->bracket);

        //The match should no longer be part of the tournament, and that the match we have in $match hasn't changed
        $this->assertArrayNotContains($this->tournament->open_matches(), $this->object);
        $this->assertNull($this->tournament->match($this->object));

        //Try reporting now that the brackets have started - it shouldn't let us
        $this->assertFalse($this->object->report());
    }
    /**
     * Test unreport()
     */
    public function test_unreport() {
        $winner = $this->object->team();
        $loser = $this->object->team2();
        $this->assertTrue($this->object->set_winner($winner));
        //
        $game1 = $this->object->game($loser);
        $game2 = $this->object->game();
        $game3 = $this->object->game();
        //save and make sure all games now have ids
        $this->assertSave($this->object->report());
        $this->assertID($game1->id);
        $this->assertID($game2->id);
        $this->assertID($game3->id);
        //
        $this->assertTrue($this->object->unreport());
        //all ids should now be null
        $this->assertNull($this->object->id);
        $this->assertNull($game1->id);
        $this->assertNull($game2->id);
        $this->assertNull($game3->id);
    }
    /**
     * Test unreport() on a match that is not allowed
     *  to be unreported - because either team has 
     *  reported other wins since
     */
    public function test_unreport_bracket_invalid() {
        $this->assertTrue( $this->object->set_winner($this->object->team()) );
        $this->assertSave($this->object->report());

        //Game team() another win, but we may have to report other matches until he has an opponent
        while(is_null($this->object->winner->match())) {
            $this->assertInstanceOf('BBMatch', $match = $this->object->tournament->open_matches[0]);
            $this->assertTrue($match->set_winner($match->team2()));
            $this->assertSave($match->report());
        }

        //should have an open match now - report it
        $this->assertInstanceOf('BBMatch', $match = $this->object->winner->match());
        $this->assertTrue( $match->set_winner($this->object->winner()) );
        $this->assertSave($match->report());

        //now that winner() has had reported beyond $this->object, we should no longer be allowed to unreport it
        $this->assertFalse($this->object->unreport());
    }
}

?>