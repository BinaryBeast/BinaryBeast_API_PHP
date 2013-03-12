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
    /** @var BBTournament[] */
    private static $tournaments;

    /**
     * Attempt to delete any tournaments we've created
     */
    function __destruct() {
        if(!is_null(self::$tournament_static))          self::$tournament_static->delete();
        if(!is_null(self::$tournament_static_groups))   self::$tournament_static_groups->delete();
    }
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->init_tournament();
        $this->set_object_new();
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
                //Use the static cached tournament, unless it has started or has any teams in it
                if(!BBHelper::tournament_is_active($property)) {
                    if(sizeof($property->teams()) == 0) {
                        $this->tournament = &$property;
                        return;
                    }
                }
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
        $tour->group_count = 2;
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
        if(!$force_new) {
            $property = $tour;
            $this->tournament = &$property;
        }
        else $this->tournament = $tour;
    }
    /**
     * Set $team to a new BBTeam without an ID
     */
    private function set_object_new($groups = false) {
        if(!is_null($this->team)) $this->team->delete();

        //If tournament is active, get a new one
        if(BBHelper::tournament_is_active($this->tournament)) $this->init_tournament($groups, true);

        $this->team = &$this->tournament->team();
        $this->team->display_name = 'New PHPUnit Test Team!';
    }
    /**
     * set $team to a team that currently has an open match in an elimination bracket
     */
    private function set_object_with_open_match($groups = false) {
        //Just start from scratch
        $this->init_tournament($groups, true);
        //Add some teams before starting
        for($x = 0; $x < 8; $x++) {
            $this->assertInstanceOf('BBTeam', $team = $this->tournament->team());
            $team->display_name = 'Team ' . ($x + 1);
            $this->assertTrue($team->confirm());
        }
        $this->assertTrue(sizeof($this->tournament->teams()) == 8);
        //
        $this->assertSave($this->tournament->save());
        $this->assertTrue($this->tournament->start());
        $this->assertEquals('Active', $this->tournament->status);
        //Simply return the a team from the first open match we can find
        $this->team = &$this->tournament->open_matches[0]->team;
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
        $this->assertSave($this->team->save());
    }
    /**
     * Test save() to update a team - then reload externally
     *  to verify that it actually updated the remote value
     */
    public function test_update() {
        $this->assertNull($this->team->id);
        $this->assertSave($this->team->save());
        //
        $this->team->display_name = 'updated + banned';
        $this->assertTrue($this->team->ban());
        $this->assertSave($this->team->save());
        //
        $this->AssertTeamValueExternally($this->team->id, 'display_name', 'updated + banned');
        $this->AssertTeamValueExternally($this->team->id, 'status', -1);
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
        $this->set_object_with_open_match();
        $this->assertInstanceOf('BBTeam', $oppponent = $this->team->opponent());
        $this->assertEquals($this->team, $oppponent->opponent());
    }

    /**
     * Test retrieving a team's opponent in elimination brackets, after the team
     *  has been eliminated
     * @covers BBTeam::opponent
     */
    public function test_opponent_elimination_eliminated() {
        $this->set_object_with_open_match();
        //To guarnatee elimination, change it to single elimination
        $this->tournament->elimination = 1;
        $this->assertSave($this->tournament->save());
        //Make sure that $team loses
        $this->assertInstanceOf('BBMatch', $match = $this->team->match());
        $this->assertInstanceOf('BBTeam', $opponent = $this->team->opponent);
        $this->assertTrue($match->set_winner($opponent));
        $this->assertSave($match->report());
        //Eliminated!!
        $this->assertFalse($this->team->opponent());
    }

    /**
     * Test retrieving a team's opponent in elimination brackets, when the team has no opponent
     * @covers BBTeam::opponent
     */
    public function test_opponent_elimination_waiting() {
        $this->set_object_with_open_match();
        //Make sure that $team wins, so his next match won't have an opponent yet
        $this->assertInstanceOf('BBMatch', $match = $this->team->match());
        $this->assertTrue($match->set_winner($this->team));
        $this->assertSave($match->report());
        //Eliminated!!
        $this->assertNull($this->team->opponent());
    }

    /**
     * @covers BBTeam::eliminated_by
     */
    public function test_eliminated_by() {
        $this->set_object_with_open_match();
        //Single elim, disable bronze - to guarnatee he's eliminated after reporting, and not simply sent to the LB
        $this->tournament->elimination = 1;
        $this->tournament->bronze = false;
        $this->assertSave($this->tournament->save());
        //Make sure that $team wins, so his next match in the WB should be without an opponent
        $this->assertInstanceOf('BBMatch', $match = $this->team->match());
        $this->assertInstanceOf('BBTeam', $opponent = $match->toggle_team($this->team));
        $this->assertTrue($match->set_winner($opponent));
        $this->assertSave($match->report());
        //Eliminated!!
        $this->assertEquals($opponent, $this->team->eliminated_by());
    }
    /**
     * Test eliminated_by to make sure it accurately returns null to indicate
     *  that the team is currently waiting on an opponent
     * 
     * @covers BBTeam::eliminated_by
     */
    public function test_eliminated_by_waiting() {
        $this->set_object_with_open_match();
        //Make sure that $team wins, so his next match won't have an opponent yet
        $this->assertInstanceOf('BBMatch', $match = $this->team->match());
        $this->assertTrue($match->set_winner($this->team));
        $this->assertSave($match->report());
        //Eliminated!!
        $this->assertFalse($this->team->eliminated_by());
    }
    
    /**
     * Tests to make sure that the team and opponent's wins and lb_wins are updated
     *      after reporting a match
     */
    public function test_wins_after_report() {
        $this->set_object_with_open_match();
        //Make sure that $team wins, so his next match won't have an opponent yet
        $this->assertInstanceOf('BBMatch', $match = $this->team->match());
        $this->assertEquals($this->team->opponent(), $opponent = &$match->toggle_team($this->team));
        //Should start with no wins / lbwins
        $this->assertEquals(0, $this->team->wins);
        $this->assertEquals(-1, $this->team->lb_wins);
        $this->assertEquals(0, $opponent->wins);
        $this->assertEquals(-1, $opponent->lb_wins);
        //
        $this->assertTrue($match->set_winner($this->team));
        $this->assertSave($this->team->match->report());
        //opponent should have lb_wins now, and team should have wins
        $this->assertTrue($this->team->wins > 0);
        $this->assertEquals(-1, $this->team->lb_wins);
        $this->assertEquals(0, $opponent->wins);
        $this->assertTrue($opponent->lb_wins > -1);
    }

    /**
     * Test confirming a team AFTER brackets started
     * @covers BBTeam::confirm
     */
    public function test_confirm_active() {
        $this->set_object_with_open_match();
        //Reopen so we can add a team
        $this->assertTrue($this->tournament->reopen());

        //Add a new unconfirmed team (unconfirmed is the default status)
        $unconfirmed = $this->tournament->team();
        $unconfirmed->display_name = 'unconfirmed team';
        $this->assertTrue($unconfirmed->unconfirm());
        $this->assertSave($unconfirmed->save());

        //Make 100% sure it's confirmed from BinaryBeast's POV
        $this->AssertTeamValueExternally($unconfirmed->id, 'status', BinaryBeast::TEAM_STATUS_UNCONFIRMED);

        //Restart without the new team
        $this->assertTrue($this->tournament->start());

        //Make sure unconfirmed is still in the tournament - and it should have a NULL position
        $this->assertID($id = $unconfirmed->id);
        $this->assertInstanceOf('BBTeam', $unconfirmed = $this->tournament->team($unconfirmed));
        $this->assertEquals($id, $unconfirmed->id);

        $this->assertArrayContains($this->tournament->teams(), $unconfirmed);
        $this->assertArrayContains($this->tournament->unconfirmed_teams, $unconfirmed);
        $this->assertArrayNotContains($this->tournament->confirmed_teams(), $unconfirmed);

        //Verify it's unconfirmed, and has a NULL position
        $this->assertNull($unconfirmed->position);
        $this->assertEquals(BinaryBeast::TEAM_STATUS_UNCONFIRMED, $unconfirmed->status);

        //Verify again externally
        $this->AssertTeamValueExternally($unconfirmed, 'position', null);
        $this->AssertTeamValueExternally($unconfirmed, 'status', BinaryBeast::TEAM_STATUS_UNCONFIRMED);

        //So we have an unconfirmed team in an active tournament - we shouldn't be able to confirm him now
        $this->assertFalse($unconfirmed->confirm());
    }

    /**
     * @covers BBTeam::unconfirm
     */
    public function test_unconfirm() {
        //Should start with default status of confirmed
        $this->assertEquals(BinaryBeast::TEAM_STATUS_CONFIRMED, $this->team->status);
        $this->assertTrue($this->team->unconfirm());
        $this->assertSave($this->team->save());

        //Verify with reload
        $this->AssertTeamValueExternally($this->team->id, 'status', BinaryBeast::TEAM_STATUS_UNCONFIRMED);
    }

    /**
     * Tests to make sure that confirm() does NOT work when trying to confirm()
     *  within an active tournament
     * @covers BBTeam::confirm
     */
    public function test_unconfirm_active() {
        $this->set_object_with_open_match();
        $this->assertFalse($this->team->unconfirm());
    }

    /**
     * @covers BBTeam::ban
     */
    public function test_ban() {
        //Should start with default status of confirmed
        $this->assertEquals(BinaryBeast::TEAM_STATUS_CONFIRMED, $this->team->status);
        $this->assertTrue($this->team->ban());
        $this->assertSave($this->team->save());

        //Verify with reload
        $this->AssertTeamValueExternally($this->team->id, 'status', BinaryBeast::TEAM_STATUS_BANNED);
    }

    /**
     * Tests to make sure that ban() does NOT work when the tournament is active
     * @covers BBTeam::confirm
     */
    public function test_ban_active() {
        $this->set_object_with_open_match();
        $this->assertFalse($this->team->ban());
    }

    /**
     * @covers BBTeam::match
     */
    public function test_match() {
        $this->set_object_with_open_match();
        $this->assertInstanceOf('BBMatch', $this->team->match);
    }
}
