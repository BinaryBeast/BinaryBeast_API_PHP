<?php

/**
 * Test match team functionality
 * 
 * @group team
 * @group model
 * @group all
 */
class BBTeamTest extends bb_test_case {

    /** @var BBTeam */
    protected $team;
    /** @var BBTournament */
    private $tournament;
    /** @var BBTournament */
    private static $tournament_static;
    /** @var BBTournament */
    private static $tournament_static_groups;

    /**
     * Attempt to delete any tournaments we've created
     */
    function __destruct() {
        if(!is_null(self::$tournament_static)) self::$tournament_static->delete();
        if(!is_null(self::$tournament_static_groups)) self::$tournament_static_groups->delete();
    }
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->init_tournament();
        $this->reset_tournament();
        $this->set_new();
    }
    /**
     * Try to delete the team after running the team
     */
    protected function tearDown() {
        if(!is_null($this->team)) $this->team->delete();
    }

    /**
     * Set a value for $tournament
     */
    private function init_tournament($groups = false, $force_new = false) {
        if($groups) $property = &self::$tournament_static_groups;
        else        $property = &self::$tournament_static;

        if(!$force_new) {
            if(!is_null($property)) {
                $this->tournament = &$property;
                $this->reset_tournament();
                return;
            }
        }
        //Try to delete before re-creating
        else if(!is_null($property)) $property->delete();

        $tour = $this->bb->tournament();
        //Settings
        $tour->title = $groups ? 'PHP API library 3.0.0 BBTeam (Groups) Unit Test' : 'PHP API library 3.0.0 BBTeam Unit Test';
        $tour->description = 'PHPUnit test for the BBTeam class';
        $tour->elimination = 2;
        $tour->max_teams = 8;
        $tour->team_mode = 1;
        $tour->type_id = $groups ? 1 : 0;
        $tour->save();
        //Rounds (all BO3)
        $maps = array('Abyssal Caverns', 614, 'Akilon Flats', 71, 'Arid Plateau', 337, 'Backwater Gulch', 225);
        foreach($tour->rounds as &$bracket) {
            foreach($bracket as $x => &$round) {
                $round->best_of = 3;
                $round->map = isset($maps[$x]) ? $maps[$x] : null;
            }
        }
        //Give the finals a BO5
        $tour->rounds->finals[0]->best_of = 5;
        $tour->save();

        //Success!
        $property = $tour;
        $this->tournament = &$property;
    }
    /**
     * If active, reopen - and remove all teams from $tournament
     */
    private function reset_tournament() {
        //Might have to reopen the tournament, can't add teams to active tournaments
        while($this->tournament->status != 'Building' && $this->tournament->status != 'Confirmation') {
            $this->assertTrue($this->tournament->reopen());
        }
        $this->reset_teams();
    }
    /**
     * Removes all teams from the tournament (Only if tournament hasn't started)
     */
    private function reset_teams() {
        var_dump('reset');
        if(!BBHelper::tournament_is_active($this->tournament)) {
            var_dump('!is_active :: ' . $this->tournament->status);
            if(!is_null($teams = &$this->tournament->teams())) {
                foreach($teams as &$team) {
                    var_dump(['before', 'tour_id' => $this->tournament->id, 'status' => $this->tournament->status]);
                    $result = $team->delete();
                    $this->assertTrue($result);
                }
                $this->assertTrue(sizeof($this->tournament->teams()) == 0);
            }
        }
    }

    /**
     * Set $team to a new BBTeam without an ID
     */
    private function set_new($delete = true) {
        if($delete) {
            if(!is_null($this->team)) $this->team->delete();
        }
        $this->team = &$this->tournament->team();
        $this->team->display_name = 'New PHPUnit Test Team!';
    }
    /**
     * set $team to a team that currently has an open match in an elimination bracket
     */
    private function set_open_match_brackets($skip_init = false) {
        if(!$skip_init) $this->init_tournament();

        //if we have any open matches, jsut return the first one
        if($this->tournament->status == 'Active') {
            //If no open matches, re-start
            if(sizeof($this->tournament->open_matches()) == 0) {
                $this->assertTrue($this->tournament->reopen());
                $this->assertTrue($this->tournament->start());
            }

            $this->assertInstanceOf('BBTeam', $this->team = &$this->tournament->open_matches[0]->team);
            return;
        }

        //Just start from scratch
        $this->reset_tournament();
        for($x = 0; $x < 8; $x++) {
            $team = $this->tournament->team();
            $team->display_name = 'Team ' . ($x + 1);
            $this->assertTrue($team->confirm());
        }
        $this->assertTrue(sizeof($this->tournament->teams()) == 8);
        //
        $this->assertID($this->tournament->save());
        $this->assertTrue($this->tournament->start());
        $this->assertEquals('Active', $this->tournament->status);
        //
        return $this->set_open_match_brackets(true);
    }

    /**
     * @covers BBTeam::init
     */
    public function test_init() {
        //Make a generic team, and test tournament() before and after init()
        $team = $this->bb->team();
        $this->assertNull($team->tournament());
        //
        $team->init($this->tournament);
        $this->assertEquals($this->tournament, $team->tournament);
        //The team should now be in the tournament's teams() list
        $this->dump_history();
        $this->assertTrue(in_array($team, $this->tournament->teams()));
    }

    /**
     * Attempt to save a team in a new tournament 
     */
    public function test_save_inactive_tournament() {
        $tournament = $this->bb->tournament();
        $tournament->title = 'asdf';
        //
        $team = $tournament->team();
        $team->display_name = 'Should never be saved';
        //
        $this->assertFalse($team->save());
    }

    /**
     * Test saving a single team
     */
    public function test_save() {
        $this->assertNull($this->team->id);
        $this->assertID($this->team->save());
    }
    /**
     * Test save() to update a team - then reload externally
     *  to verify that it saved
     */
    public function test_update() {
        $this->assertNull($this->team->id);
        $this->assertID($this->team->save());
        //
        $this->team->display_name = 'updated + banned';
        $this->assertTrue($this->team->ban());
        $this->assertID($this->team->save());
        //
        $team = $this->bb->team($this->team->id);
        $this->assertEquals('updated + banned', $team->display_name);
        $this->assertEquals(-1, $team->status);
    }

    /**
     * @covers BBTeam::tournament
     */
    public function test_tournament() {
        $this->assertEquals($this->tournament, $this->team->tournament());
    }

    /**
     * Test retrieving a team's opponent in elimination brackets
     * @covers BBTeam::opponent
     */
    public function test_opponent_elimination() {
        $this->set_open_match_brackets();
        $this->assertInstanceOf('BBTeam', $oppponent = $this->team->opponent());
        $this->assertEquals($this->team, $oppponent->opponent());
    }

    /**
     * @covers BBTeam::eliminated_by
     * @todo   Implement testEliminated_by().
     * @group new
     */
    public function test_eliminated_by() {
        $this->set_open_match_brackets();
        //To guarnatee elimination, change it to single elimination
        $this->tournament->elimination = 1;
        $this->assertID($this->tournament->save());
        //Make sure that $team loses
        $this->assertInstanceOf('BBMatch', $match = $this->team->match());
        $this->assertInstanceOf('BBTeam', $opponent = $match->toggle_team($this->team));
        $this->assertEquals($this->team->opponent(), $opponent);
        $this->assertTrue($match->set_winner($opponent));
        $this->assertID($match->report());
        //Eliminated!!
        $this->assertEquals($opponent, $this->team->eliminated_by());

        /**
         * Now start deleting the teams
         *
        foreach($this->tournament->teams as $x => &$team) {
            $id = $team->id;
            var_dump(['id' => $id, 'in_array' => in_array($team, $this->tournament->teams())]);
            $result = $team->delete();
            var_dump(['delete_result' => $result]);
            var_dump(['id_after' => $id, 'in_array' => in_array($team, $this->tournament->teams()), 'x_after' => $this->tournament->teams[$x]]);
            die();
        }
        var_dump(['teams_after_del' => $this->tournament->teams()]);die();
         */
    }
    /**
     * Test eliminated_by to make sure it accurately returns false to indicate
     *  that the team hasn't been eliminated yet
     * 
     * @covers BBTeam::eliminated_by
     * @todo   Implement testEliminated_by().
     * @group new
     */
    public function test_eliminated_by_waiting() {
        $this->set_open_match_brackets();
        //To guarnatee elimination, change it to single elimination
        $this->tournament->elimination = 1;
        $this->assertID($this->tournament->save());
        //Make sure that $team loses
        $this->assertInstanceOf('BBMatch', $match = $this->team->match());
        $this->assertInstanceOf('BBTeam', $opponent = $match->toggle_team($this->team));
        $this->assertEquals($this->team->opponent(), $opponent);
        $this->assertTrue($match->set_winner($opponent));
        $this->assertID($match->report());
        //Eliminated!!
        $e = $this->team->eliminated_by();
        $this->dump_errors();
        $this->assertEquals($opponent, $e);
    }

    /**
     * @covers BBTeam::reset_opponents
     * @todo   Implement testReset_opponents().
     */
    public function testReset_opponents() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers BBTeam::confirm
     * @todo   Implement testConfirm().
     */
    public function testConfirm() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers BBTeam::unconfirm
     * @todo   Implement testUnconfirm().
     */
    public function testUnconfirm() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers BBTeam::ban
     * @todo   Implement testBan().
     */
    public function testBan() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers BBTeam::match
     * @todo   Implement testMatch().
     */
    public function testMatch() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers BBTeam::clear_opponent_cache
     * @todo   Implement testClear_opponent_cache().
     */
    public function testClear_opponent_cache() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

}
