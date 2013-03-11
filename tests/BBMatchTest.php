<?php

require_once('lib/includes.php');

/**
 * Test match model reporting and manipulations
 * @group match
 * @group model
 * @group all
 */
class BBMatchTest extends bb_test_case {

    /** @var BBMatch */
    protected $object;
    /** @var BBTournament */
    protected static $tournament;
    /** @var BBTournament */
    protected static $tournament_with_groups;

    /**
     * Store an array of all tournaments created, so that we can delete them after
     *  all tests are complete
     * @var BBTournament[]
     */
    protected static $tournaments = array();

    /**
     * Attempt to delete any tournaments we've created
     */
    function __destruct() {
        foreach(self::$tournaments as $tournament) $tournament->delete();
    }

    /**
     * Default set up - statically cache a tournament, and load
     *  one of its open matches - standard elimination brackets only, double elim
     */
    protected function setUp() {
        $this->set_object(false);
    }
    /**
     * Creates a new active tournament, starts the brackets,
     *  and saves it to self::$tournament
     * 
     * @param $groups - create group rounds, false by default
     */
    private function init_tournament($groups = false) {
        $tour = $this->bb->tournament();
        $tour->title = $groups ? 'PHP API library 3.0.0 BBMatch (Groups) Unit Test' : 'PHP API library 3.0.0 BBMatch Unit Test';
        $tour->description = 'PHPUnit test for the BBMatch class';
        $tour->elimination = 1;
        $tour->bronze = true;
        $tour->max_teams = 8;
        $tour->team_mode = 1;
        $tour->type_id = $groups ? 1 : 0;

        //Add 8 players
        $countries = array('USA', 'GBR', 'USA', 'NOR', 'JPN', 'SWE', 'NOR', 'GBR');
        for($x = 0; $x < 8; $x++) {
            $team = $tour->team();
            $team->confirm();
            $team->display_name = 'Player ' . ($x + 1);
            $team->country_code = $countries[$x];
        }

        //All matches are BO3, finals are BO5
        $tour->save();
        $maps = array('Abyssal Caverns', 614, 'Akilon Flats', 71, 'Arid Plateau', 337, 'Backwater Gulch', 225);
        foreach($tour->rounds as &$bracket) {
            foreach($bracket as $x => &$round) {
                $round->best_of = 3;
                $round->map = isset($maps[$x]) ? $maps[$x] : null;
            }
        }
        $tour->rounds->bronze[0]->best_of = 5;
        $tour->save();
        $tour->start();

        self::$tournaments[] = $tour;
        if($groups) self::$tournament_with_groups = $tour;
        else        self::$tournament = $tour;
    }
    /**
     * Set $this->object to an open match - based on $groups input it will
     *  either use the default tournament, or create one with group rounds enabled
     * 
     * @param boolean $groups
     */
    private function set_object($groups = false) {
        if(!$groups) {
            if(is_null(self::$tournament)) $this->init_tournament();
            $tournament = &self::$tournament;
        }
        else {
            if(is_null(self::$tournament_with_groups)) $this->init_tournament(true);
            $tournament = &self::$tournament_with_groups;
        }

        $matches = &$tournament->open_matches();
        if(sizeof($matches) == 0) {
            $this->init_tournament($groups);
            return $this->set_object();
        }
        $this->object = null;
        foreach($matches as &$match) {
            if(!($match instanceof BBMatch)) {
                /**
                 * Somewhere along the way, one of the matches is being set to NULL,
                 *  and not being removed - wtf
                 */
                var_dump(['gay' => $matches]); die();
            }
            if($match->is_new() && !$match->changed) {
                //success!
                $this->object = &$match;
                return;
            }
        }

        //Didn't find an untouched match - grab a new tournament etc and try again
        $this->init_tournament();
        return $this->set_object();
    }
    /**
     * Returns a BBTeam object KNOWN not to be part of this match
     * @return BBTeam
     */
    private function get_invalid_team() {
        $teams = self::$tournament->teams();
        $team1 = $this->object->team();
        $team2 = $this->object->team2();
        foreach($teams as $team) {
            if($team != $team1 && $team != $team2) return $team;
        }
    }

    /**
     * Initial test - make sure we're getting a match correctly
     */
    public function test_get_object() {
        $this->assertInstanceOf('BBMatch', $this->object);
    }
    /**
     * Test winner()
     */
    public function test_winner() {
        $winner = $this->object->team2();
        $loser = $this->object->team();
        $this->assertTrue($this->object->set_winner($winner));
        $this->assertEquals($winner, $this->object->winner());
        // make sure they swap properly
        $this->assertTrue($this->object->set_winner($loser));
        $this->assertEquals($loser, $this->object->winner());
    }
    /**
     * Test loser()
     */
    public function test_loser() {
        $winner = $this->object->team();
        $loser = $this->object->team2();
        $this->assertTrue($this->object->set_winner($winner));
        $this->assertEquals($loser, $this->object->loser());
        // make sure they swap properly
        $this->assertTrue($this->object->set_winner($loser));
        $this->assertEquals($winner, $this->object->loser());
    }
    /**
     * Test trying to load winner() and loser() BEFORE
     *  defining this - we should get an error
     */
    public function test_winner_before_setting() {
        $this->assertNull($this->object->winner());
        $this->assertNull($this->object->loser());
    }
    /**
     * Test getting the participant team objects
     */
    public function test_get_participants() {
        $this->assertInstanceOf('BBTeam', $this->object->team());
        $this->assertInstanceOf('BBTeam', $this->object->team2());
        $this->assertInstanceOf('BBTeam', $this->object->opponent());
    }
    /**
     * Test set_winner functionality
     */
    public function test_set_winner() {
        //Just use the first team
        $this->assertInstanceOf('BBTeam', $team = $this->object->team());
        $this->assertTrue($this->object->set_winner($team));
        $this->assertEquals($team, $this->object->winner());
    }
    /**
     * Test to insure that it can accuratenly load the round data, by pulling
     * a BBRound model from its tournament
     */
    public function test_round() {
        $this->assertInstanceOf('BBRound', $this->object->round);
    }
    /**
     * Test team_in_match, by passing in a BBTeam - one that is part, and one
     *  that isn't
     */
    public function test_team_in_match_by_object() {
        $this->assertInstanceOf('BBTeam', $this->object->team_in_match($this->object->team()));
        $this->assertFalse($this->object->team_in_match($this->get_invalid_team()));
    }
    /**
     * Test team_in_match, by passing in a BBTeam - one that is part, and one
     *  that isn't
     */
    public function test_team_in_match_by_id() {
        $this->assertInstanceOf('BBTeam', $this->object->team_in_match($this->object->team->id));
        $invalid_team = $this->get_invalid_team();
        $this->assertFalse($this->object->team_in_match($invalid_team->id));
    }
    /**
     * Simply compares the return of $match->tournament to the tournament we have
     *  statically cached to create the match
     */
    public function test_tournament() {
        $this->assertEquals(self::$tournament, $this->object->tournament());
    }
    /**
     * Tests to make sure game() returns a new BBGame model
     */
    public function test_game() {
        $this->assertInstanceOf('BBMatchGame', $this->object->game());
    }
    /**
     * Test to make sure that reset() clears the matche's
     *  games array, and that winner() no longer returns a team
     */
    public function test_reset() {
        //Create a game and make sure games() returns it
        $this->assertInstanceOf('BBMatchGame', $game = $this->object->game());
        $this->assertTrue(is_array($this->object->games()));
        $this->assertTrue(in_array($game, $this->object->games()));
        //Winner
        $this->assertInstanceOf('BBTeam', $winner = $this->object->team());
        $this->assertTrue($this->object->set_winner($winner));
        $this->assertEquals($winner, $this->object->winner());
        //Reset
        $this->object->reset();
        //Games should be empty now
        $this->assertTrue(is_array($this->object->games()));
        $this->assertTrue(sizeof($this->object->games()) == 0);
        //Winner/Loser should now return null
        $this->assertNull($this->object->winner());
        $this->assertNull($this->object->loser());
    }
    /**
     * Tests to make sure that game() processes the defined winner correctly
     */
    public function test_game_winner() {
        //Fresh game should use the matche's winner as default winner - first step: new game in a match that doesn't have a game
        $game = $this->object->game();
        $this->assertNull($game->winner());
        //Start again - this time, give the match a winner first
        $this->object->reset();
        $this->object->set_winner($this->object->opponent());
        $game = $this->object->game();
        $this->assertEquals($this->object->winner(), $game->winner());
        $this->assertEquals($this->object->loser(), $game->loser());
        //Start again - this time override the matche's winner
        $this->object->reset();
        $this->object->set_winner($this->object->team());
        $game = $this->object->game($this->object->loser());
        $this->assertEquals($this->object->loser(), $game->winner());
        $this->assertEquals($this->object->winner(), $game->loser());
    }
    /**
     * BBMatch should throw a fit when we try to create too many games (than the round's best_of allows)
     */
    public function test_too_many_games() {
        $round = $this->object->round();
        for($x = 0; $x < $round->best_of + 5; $x++) {
            if($x <= $round->best_of)   $this->assertInstanceOf('BBMatchGame', $this->object->game());
            else                        $this->assertNull($this->object->game());
        }
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
     * Test the accuracy of toggle_team()
     */
    public function test_toggle_team() {
        $invalid_team   = $this->get_invalid_team();
        $invalid_value  = 1234;
        $invalid_value2 = 'Something here';
        $team1          = &$this->object->team();
        $team2          = &$this->object->opponent();

        //Test by direct BBTeam input
        $this->assertEquals($team2, $this->object->toggle_team($team1));
        $this->assertEquals($team1, $this->object->toggle_team($team2));
        $this->assertNull($this->object->toggle_team($invalid_team));

        //Test by team_id integer input
        $this->assertEquals($team2, $this->object->toggle_team($team1->id));
        $this->assertEquals($team1, $this->object->toggle_team($team2->id));
        $this->assertNull($this->object->toggle_team($invalid_team->id));

        //Test invalid input values
        $this->assertNull($this->object->toggle_team($invalid_value));
        $this->assertNull($this->object->toggle_team($invalid_value2));
    }
    /**
     * Test set_draw
     */
    public function test_set_draw() {

        //First, we have to make sure our match is from a tournament in group rounds
        $this->set_object(true);

        //set_draw
        $this->assertTrue($this->object->set_draw());
        $this->assertTrue($this->object->draw);
        $this->assertFalse($this->object->winner());
        $this->assertFalse($this->object->loser());

        //Now try setting it back to a player
        $this->object->reset();
        $this->assertTrue($this->object->set_draw());
        $this->assertTrue($this->object->is_draw());
        $this->assertTrue($this->object->set_winner($this->object->team(), null, null, true));
        $this->assertFalse($this->object->is_draw());

        //Reset and try again using set_winner(null)
        $this->object->reset();
        $this->assertFalse($this->object->is_draw());
        $this->assertNull($this->object->winner());
        $this->assertNull($this->object->loser());
        //
        $this->object->set_winner(null);
        $this->assertTrue($this->object->is_draw());
        $this->assertFalse($this->object->winner());
        $this->assertFalse($this->object->loser());

        //Reset and try again using set_winner(false)
        $this->object->reset();
        $this->assertFalse($this->object->is_draw());
        $this->assertNull($this->object->winner());
        $this->assertNull($this->object->loser());
        //
        $this->object->set_winner(false);
        $this->assertTrue($this->object->is_draw());
        $this->assertFalse($this->object->winner());
        $this->assertFalse($this->object->loser());

        //Now test to make sure that new games are also considered draws
        $game = $this->object->game();
        $this->assertFalse($game->winner());
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
        $this->assertEquals(0, $this->object->get_game_wins($winner));

        $this->object->game();

        $this->assertEquals(0, $this->object->get_game_wins($winner));
        
        $this->object->set_winner($winner);
        $this->object->game();
        $this->object->game($loser);
        $this->assertEquals(1, $this->object->get_game_wins($winner));
        $this->assertEquals(1, $this->object->get_game_wins($loser));

        //Changing a game's winner should change the result
        $this->object->games[0]->set_winner($loser);
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
        $winner = &$this->object->team();
        $this->object->set_winner($winner);
        $loser = &$this->object->loser();

        //Make sure it's BO3
        $round = &$this->object->round();
        $round->best_of = 3;
        $this->assertEquals(2, $this->object->round->wins_needed);

        //Should not validate yet - but at least it works even without any games set
        $this->assertFalse($this->object->validate_winner_games());
        $this->assertFalse($this->object->validate_winner_games(true));

        //Give the loser the first win
        $this->object->game($loser);
        $this->assertFalse($this->object->validate_winner_games());
        $this->assertFalse($this->object->validate_winner_games(true));

        //Give the winner a win - still shouldn't validate
        $this->object->game();
        $this->assertFalse($this->object->validate_winner_games());
        $this->assertFalse($this->object->validate_winner_games(true));

        //Give the winner one last win - now it should validate, even with $strict enabled
        $this->object->game();
        $this->assertTrue($this->object->validate_winner_games());
        $this->assertTrue($this->object->validate_winner_games(true));

        //Reset, so we can try giving him too many wins
        $this->object->reset();
        $this->assertTrue(sizeof($this->object->games()) == 0);
        $this->object->set_winner($winner);

        //Give the winner 3 wins
        $this->object->game($winner);
        $this->object->game();
        $this->object->game($winner);

        //It should still validate, unless we enable $strict
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
     * Reporting a match that has a round with unsaved changes can be bad - if we changed
     *  the best_of value and report before sending it to the API... BinaryBeast will
     *  have the original best_of value, and our match may not be saved correctly
     */
    public function test_report_with_unsaved_round() {
        $round = $this->object->round();
        $round->best_of = 3;
        $this->assertTrue($round->save());
        $round->best_of = 15;
        $this->object->set_winner($this->object->team());
        $this->assertFalse($this->object->report());
    }
    /**
     * Report a match in elimination brackets
     */
    public function test_report_brackets() {
        $round = &$this->object->round();
        $round->best_of = 3;
        $this->assertTrue($round->save());
        //
        $this->assertInstanceOf('BBTeam', $winner = &$this->object->opponent());
        $this->assertInstanceOf('BBTeam', $loser = &$this->object->team());
        $this->assertTrue($this->object->set_winner($winner));
        //Setup the games
        $this->assertInstanceOf('BBMatchGame', $game1 = $this->object->game($loser));
        $this->assertEquals($loser, $game1->winner());
        $this->assertInstanceOf('BBMatchGame', $game2 = $this->object->game($winner));
        $this->assertEquals($winner, $game2->winner());
        $this->assertInstanceOf('BBMatchGame', $game3 = $this->object->game($winner));
        $this->assertEquals($winner, $game3->winner());
        //Report and assert!
        $this->assertID($this->object->report());
        $this->assertNotNull($this->object->id);
        $this->assertFalse($this->object->changed);
        //Make sure each game got an ID too
        foreach($this->object->games as &$game) $this->assertNotNull($game->id);
    }
    /**
     * Test to make sure that after reporting a match, it is removed
     *  from the tournament's open_matches array
     */
    public function test_report_removes_from_open_matches() {
        $tournament = $this->object->tournament();
        //Get the match directly, to be 100% sure that it's a reference to a value in open_matches, and that 
        //BBTournament does not set it to null
        /* @var $match BBMatch */
        $match = &$tournament->open_matches[0];

        //Should be in there to start
        $this->assertTrue(in_array($match, $tournament->open_matches()));
        $match->set_winner($match->team());
        $this->assertID($match->report());

        //After reporting, it should be removed from open_matches, but make sure it wasn't set to NULL first
        $this->assertNotNull($match);

        //After reporting, it should have been removed
        $this->assertFalse(in_array($match, $tournament->open_matches()));
    }
    /**
     * Test is_draw
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
     * Test reporting matches through BBTournament::save()
     */
    public function test_report_brackets_batch() {
        $open_matches = self::$tournament->open_matches();
        if(sizeof($open_matches) < 2) {
            $this->init_tournament();
            $open_matches = self::$tournament->open_matches();
        }
        $this->assertInstanceOf('BBMatch', $match1 = $open_matches[0]);
        $this->assertInstanceOf('BBMatch', $match2 = $open_matches[1]);
        $this->assertInstanceOf('BBTournament', $tournament = $match1->tournament());

        //Make sure best_of is 3 for both matches
        $match1->round->best_of = 3;
        $match2->round->best_of = 3;
        $this->assertTrue($match1->tournament->save_rounds());

        //Setup match 1 teams
        $this->assertTrue($match1->set_winner($match1->team()));
        $this->assertInstanceOf('BBTeam', $winner1 = $match1->winner());
        $this->assertInstanceOf('BBTeam', $loser1 = $match1->loser());

        //Set match 2 teams
        $this->assertTrue($match2->set_winner($match2->opponent()));
        $this->assertInstanceOf('BBTeam', $winner2 = $match2->winner());
        $this->assertInstanceOf('BBTeam', $loser2 = $match2->loser());

        //Match 1 Game details
        $this->assertInstanceOf('BBMatchGame', $game11 = $match1->game($winner1));
        $this->assertEquals($winner1, $game11->winner());
        $this->assertInstanceOf('BBMatchGame', $game12 = $match1->game($loser1));
        $this->assertEquals($loser1, $game12->winner());
        $this->assertInstanceOf('BBMatchGame', $game13 = $match1->game($winner1));
        $this->assertEquals($winner1, $game13->winner());

        //Match 2 Game details
        $this->assertInstanceOf('BBMatchGame', $game21 = $match2->game($loser2));
        $this->assertEquals($loser2, $game21->winner());
        $this->assertInstanceOf('BBMatchGame', $game22 = $match2->game($winner2));
        $this->assertEquals($winner2, $game22->winner());
        $this->assertInstanceOf('BBMatchGame', $game23 = $match2->game($winner2));
        $this->assertEquals($winner2, $game23->winner());

        //GOGOGO!
        $this->assertTourneyID($tournament->save());

        //Both matches should have match ids now
        $this->assertNotNull($match1->id);
        $this->assertNotNull($match2->id);

        //Matches should not have changes flagged
        $this->assertFalse($match1->changed);
        $this->assertFalse($match2->changed);

        //All games within matches should have ids too
        foreach($match1->games as &$game) $this->assertNotNull($game->id);
        foreach($match2->games as &$game) $this->assertNotNull($game->id);
    }
    /**
     * Report a match in elimination brackets - in strict mode (winner has to have exactly enough game wins)
     */
    public function test_report_brackets_strict() {
        $round = &$this->object->round();
        $round->best_of = 3;
        $this->assertTrue($round->save());
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
        $this->assertID($this->object->report(true));
        //Now double check to make sure everything has a proper ID
        $this->assertNotNull($this->object->id);
        $this->assertFalse($this->object->changed);
        //Make sure each game got an ID too
        foreach($this->object->games as &$game) $this->assertNotNull($game->id);
    }
    /**
     * Test BBMatchGame delete - reporting with strict mode shoudl fail if we
     *  report with too many wins - and should work if we delete one
     */
    public function test_delete_game_plus_report_strict() {
        $this->assertTrue($this->object->set_winner($this->object->team()));
        $winner = &$this->object->winner();
        //Make sure it's BO3
        $this->object->round->best_of = 3;
        $this->assertTrue($this->object->round->save());
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
        $this->assertID($this->object->report());
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
        $this->assertTrue($game1->is_draw());
        $this->assertInstanceOf('BBMatchGame', $game2 = $this->object->game($team1));
        $this->assertFalse($game2->is_draw());
        $this->assertInstanceOf('BBMatchGame', $game3 = $this->object->game($team2));
        $this->assertFalse($game3->is_draw());

        //GOGOGO!
        $this->assertID($id = $this->object->report());
        $this->assertTrue($this->object->is_draw());

        //use the id to load the match seperately, directly from the BinaryBeast library - and make sure it is considered a draw
        $this->assertInstanceOf('BBMatch', $match = $this->bb->match($id));
        $this->assertEquals($match, $match->load());
        $this->assertTrue($match->is_draw());
    }
    /**
     * Test updating games in an existing match
     */
    public function test_save_games() {
        $this->assertTrue($this->object->set_winner($this->object->team()));
        $this->assertInstanceOf('BBTeam', $winner = $this->object->winner());
        $this->assertInstanceOf('BBTeam', $loser = $this->object->loser());
        $this->assertID($this->object->report());
        //Reported - try adding matches after the fact
        $this->assertInstanceOf('BBMatchGame', $game1 = $this->object->game($winner));
        $this->assertInstanceOf('BBMatchGame', $game2 = $this->object->game($loser));
        $this->assertTrue($this->object->save_games());
        //Make sure they have ids
        $this->assertID($game1->id);
        $this->assertID($game2->id);
        //Add one more game, this time use match::save to submit
        $this->assertInstanceOf('BBMatchGame', $game3 = $this->object->game($winner));
        $this->assertID($this->object->save());
        $this->assertID($game3->id);
    }
    /**
     * Test trying to call report() on an alaready reported match
     */
    public function test_double_report() {
        $this->assertTrue($this->object->set_winner($this->object->team()));
        $this->assertID($this->object->report());
        $this->assertFalse($this->object->report());
    }

    /**
     * Test unreport() on a match that was part of a tournament
     *  phase that is no longer active - group rounds, in a tournament
     *  in active-brackets
     */
    public function test_unreport_invalid_status() {
        $this->set_object(true);
        
        $tournament = &$this->object->tournament();
        $matches = &$tournament->open_matches();
        $matches_count = sizeof($matches);
        foreach($matches as $x => &$match) {
            $name = 'match_' . $x;
            ${$name} = &$match;
        }

        //Guarantee that both of these teams are included in the brackets
        $team = &$this->object->team();
        $team2 = &$this->object->opponent();

        //This match is from the group rounds
        $this->assertTrue($tournament->start());

        //Loop through our matches until we find one with a team() still in the tournament
        $match = null;
        for($x = 0; $x < $matches_count; $x++) {
            $name = 'match_' . $x;
            /* @var $match BBMatch */
            $match = ${$name};
            if($match->set_winner($match->team())) break;
            else $match = null;
        }
        $this->assertNotNull($match);
        $this->assertFalse($match->report());
    }
    /**
     * Test unreport()
     */
    public function test_unreport() {
        $this->assertTrue(false, 'build this - also have to build fetching latest match in BBTeam');
    }
    /**
     * Test unreport() on a match that is not allowed
     *  to be unreported - because either team has 
     *  reported other wins since
     */
    public function test_unreport_bracket_invalid() {
        $this->assertTrue(false, 'build this');
    }


}