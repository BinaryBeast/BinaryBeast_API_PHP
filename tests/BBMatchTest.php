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

    protected function setUp() {
        if(is_null(self::$tournament)) $this->init_tournament();
        $matches = self::$tournament->open_matches();
        if(sizeof($matches) == 0) {
            $this->init_tournament();
            $this->setUp();
        }
        $this->object = $matches[0];
        $this->object->reset();
    }
    /**
     * Creates a new active tournament, starts the brackets,
     *  and saves it to self::$tournament
     */
    private function init_tournament($groups = false) {
        $tour = $this->bb->tournament();
        $tour->title = 'PHP API library 3.0.0 BBMatch Unit Test';
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

        $result = $tour->start();

        self::$tournament = $tour;
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
        $this->assertInstanceOf('BBMatchGame', $this->object->game());
        $this->assertTrue(false, 'finish implementing this test');
    }


}
