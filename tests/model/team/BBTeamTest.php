<?php

/**
 * Test match team functionality
 * 
 * @group team
 * @group model
 * @group all
 */
class BBTeamTest extends BBTest {

    /** @var BBTeam */
    protected $object;

    /**
     * By default, make sure we have a "new" unsaved tournament to play with
     */
    protected function setUp() {
        $this->get_tournament_inactive();
        $this->object = $this->tournament->team();
        parent::setUp();
    }

    /**
     * Set $object to a team that has an open match within group rounds
     */
    private function set_object_with_open_match($groups = false) {
        $this->object->delete();
        $this->get_tournament_with_open_matches($groups);
        $this->object = &$this->tournament->open_matches[0]->team;
    }

    /**
     * @covers BBTeam::init
     */
    public function test_init() {
        //Make a generic team, and test tournament() before and after init()
        $team = $this->bb->team();
        $this->assertNull($team->tournament());

        //Associate it with our tournament, and verify
        $team->init($this->tournament);
        $this->assertEquals($this->tournament, $team->tournament);

        //The team should now be in the tournament's teams() list
        $this->assertTrue(in_array($team, $this->tournament->teams()));

        //Clean up
        $this->assertTrue($team->delete());
    }

    /**
     * Attempt to save a team in a new tournament 
     * @covers BBTeam::save
     */
    public function test_save_unsaved_tournament() {
        //Verify that we're starting with a fresh unchanged tournament - then trigger a change
        $this->get_tournament_new();
        $this->object = &$this->tournament->team();
        $this->tournament->title = 'asdf';

        //Create  new team
        $team = $this->tournament->team();
        $team->display_name = 'Should never be saved';

        //Team should not let us save
        $this->assertFalse($team->save());

        //Clean up
        $team->delete();
    }

    /**
     * Test saving a single team
     *
     * @covers BBTeam::save
     */
    public function test_save() {
        $this->assertNull($this->object->id);
        $this->assertSave($this->object->save());
    }
    /**
     * Test save() to update a team - then reload externally
     *  to verify that it actually updated the remote value
     */
    public function test_update() {
        $this->assertNull($this->object->id);
        $this->assertSave($this->object->save());
        //
        $this->object->display_name = 'updated + banned';
        $this->assertTrue($this->object->ban());
        $this->assertSave($this->object->save());
        //
        $this->AssertTeamValueExternally($this->object->id, 'display_name', 'updated + banned');
        $this->AssertTeamValueExternally($this->object->id, 'status', -1);
    }

    /**
     * @covers BBTeam::tournament
     */
    public function test_tournament() {
        $this->assertEquals($this->tournament, $this->object->tournament());
    }

    /**
     * Test retrieving a team's opponent in elimination brackets
     *
     * @covers BBTeam::opponent
     */
    public function test_opponent_elimination() {
        $this->set_object_with_open_match();
        $this->assertInstanceOf('BBTeam', $oppponent = $this->object->opponent());
        $this->assertEquals($this->object, $oppponent->opponent());
    }

    /**
     * Test retrieving a team's opponent in elimination brackets, after the team
     *  has been eliminated
     * @covers BBTeam::opponent
     */
    public function test_opponent_elimination_eliminated() {
        $this->set_object_with_open_match();

        //To guarnatee elimination, change it to single elimination, with bronze disabled
        $this->tournament->elimination  = 1;
        $this->tournament->bronze       = false;
        $this->assertSave($this->tournament->save());

        //Give our $object team a loss
        $this->assertInstanceOf('BBMatch', $match = $this->object->match());
        $this->assertInstanceOf('BBTeam', $opponent = $this->object->opponent);
        $this->assertTrue($match->set_winner($opponent));
        $this->assertSave($match->report());

        //Eliminated!!
        $this->assertFalse($this->object->opponent());
    }

    /**
     * Test retrieving a team's opponent in elimination brackets, when the team has no opponent
     * @covers BBTeam::opponent
     */
    public function test_opponent_elimination_waiting() {
        $this->set_object_with_open_match();

        //Make sure that $team wins, so his next match won't have an opponent yet
        $this->assertInstanceOf('BBMatch', $match = $this->object->match());
        $this->assertTrue($match->set_winner($this->object));
        $this->assertSave($match->report());

        //Eliminated!!
        $this->assertNull($this->object->opponent());
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
        $this->assertInstanceOf('BBMatch', $match = $this->object->match());
        $this->assertInstanceOf('BBTeam', $opponent = $this->object->opponent());
        $this->assertTrue($match->set_winner($opponent));
        $this->assertSave($match->report());

        //Eliminated!!
        $this->assertEquals($opponent, $this->object->eliminated_by());
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
        $this->assertInstanceOf('BBMatch', $match = $this->object->match());
        $this->assertTrue($match->set_winner($this->object));
        $this->assertSave($match->report());

        //Eliminated!!
        $this->assertFalse($this->object->eliminated_by());
    }
    
    /**
     * Tests to make sure that the team and opponent's wins and lb_wins are updated
     *      after reporting a match
     */
    public function test_wins_after_report() {
        $this->set_object_with_open_match();

        //Make sure that $team wins, so his next match won't have an opponent yet
        $this->assertInstanceOf('BBMatch', $match = $this->object->match());
        $this->assertEquals($this->object->opponent(), $opponent = &$match->toggle_team($this->object));

        //Should start with no wins / lbwins
        $this->assertEquals(0, $this->object->wins);
        $this->assertEquals(-1, $this->object->lb_wins);
        $this->assertEquals(0, $opponent->wins);
        $this->assertEquals(-1, $opponent->lb_wins);
        //
        $this->assertTrue($match->set_winner($this->object));
        $this->assertSave($this->object->match->report());

        //opponent should have lb_wins now, and team should have wins
        $this->assertTrue($this->object->wins > 0);
        $this->assertEquals(-1, $this->object->lb_wins);
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
        $this->object->delete();

        //Add a new unconfirmed team (unconfirmed is the default status)
        $this->object = &$this->tournament->team();
        $this->object->display_name = 'unconfirmed team';
        $this->assertTrue($this->object->unconfirm());
        $this->assertSave($this->object->save());

        //Restart without the new team
        $this->assertTrue($this->tournament->start());

        //Make 100% sure team unconfirmed remotely
        $this->AssertTeamValueExternally($this->object, 'status', BinaryBeast::TEAM_STATUS_UNCONFIRMED);

        //So we have an unconfirmed team in an active tournament - we shouldn't be able to confirm him now
        $this->assertFalse($this->object->confirm());
    }

    /**
     * @covers BBTeam::unconfirm
     */
    public function test_unconfirm() {
        //Should start with default status of confirmed
        $this->assertEquals(BinaryBeast::TEAM_STATUS_CONFIRMED, $this->object->status);
        $this->assertTrue($this->object->unconfirm());
        $this->assertSave($this->object->save());

        //Verify the updated status locally, and remotely
        $this->assertEquals(BinaryBeast::TEAM_STATUS_UNCONFIRMED, $this->object->status);
        $this->AssertTeamValueExternally($this->object, 'status', BinaryBeast::TEAM_STATUS_UNCONFIRMED);
    }

    /**
     * Tests to make sure that confirm() does NOT work when trying to confirm()
     *  within an active tournament
     * @covers BBTeam::unconfirm
     */
    public function test_unconfirm_active() {
        $this->set_object_with_open_match();
        $this->assertFalse($this->object->unconfirm());
    }

    /**
     * @covers BBTeam::ban
     */
    public function test_ban() {
        //Should start with default status of confirmed
        $this->assertTrue($this->object->ban());
        $this->assertSave($this->object->save());

        //Verify with reload
        $this->assertEquals(BinaryBeast::TEAM_STATUS_BANNED, $this->object->status);
        $this->AssertTeamValueExternally($this->object, 'status', BinaryBeast::TEAM_STATUS_BANNED);
    }

    /**
     * Tests to make sure that ban() does NOT work when the tournament is active
     * @covers BBTeam::confirm
     */
    public function test_ban_active() {
        $this->set_object_with_open_match();
        $this->assertFalse($this->object->ban());
    }

    /**
     * @covers BBTeam::match
     */
    public function test_match() {
        $this->set_object_with_open_match();
        $this->assertInstanceOf('BBMatch', $this->object->match);
    }
    
    /**
     * @covers BBTeam::last_match
     */
    public function test_last_match() {
        $this->set_object_with_open_match();
        $match = $this->object->match();
        //
        $this->assertEquals($match, $this->object->match);
        $this->assertTrue($match->set_winner($this->object));
        $this->assertSave($match->report());
        //
        $this->assertNotEquals($match, $this->object->match());
        $this->assertEquals($match, $this->object->last_match());
    }

    /**
     * Testing to see if you can perform a ++ operator
     *  against magically returned properties from BBModel::__get
     */
    public function test_increment_wins() {
        $this->set_object_with_open_match();

        $this->assertInstanceOf('BBMatch', $this->object->match());

        $wins = $this->object->wins;
        $this->object->wins++;
        $this->assertEquals($wins + 1, $this->object->wins);

        $this->assertSave($this->object->save());
        
        //Changing the wins should have triggered a reset of opponent / match properties
        $this->assertNull($this->object->match());
    }

    /**
     * Test setting the team's race by the race ID
     */
    public function test_set_race_id() {
        $this->assertNull($this->object->race);
        $this->assertNull($this->object->race_id);

        //HotS race: 1-Zerg 2-Terran 7-Random 3-Protoss
        $this->object->race = 7;
        $this->assertSave($this->object->save());

        $this->assertEquals('Random', $this->object->race);
        $this->assertEquals(7, $this->object->race_id);
    }

    /**
     * Test setting the team's race by the race name string
     * @covers BBTeam::$race
     */
    public function test_set_race_name() {
        $this->assertNull($this->object->race);
        $this->assertNull($this->object->race_id);

        //HotS race: 1-Zerg 2-Terran 7-Random 3-Protoss
        $this->object->race = 'Terran';
        $this->assertSave($this->object->save());

        $this->assertEquals('Terran', $this->object->race);
        $this->assertEquals(2, $this->object->race_id);
    }

    /**
     * Tests setting the race value for multiple teams at once
     * @todo debug this
     * @fail
     */
    public function test_set_race_id_batch() {
        $teams = array($this->object);

        $this->object->race = 7;

        for($x = 0; $x < 6; $x++) {
            $team = &$this->object->tournament->team();
            $team->race = $x % 2 == 0 ? 7 : 'Random';

            $teams[] = &$team;
        }
        $this->assertSave($this->object->tournament->save_teams());

        //Each new team should have an ID, and should have random race id + race
        foreach($teams as &$team) {
            $this->assertID($team->id);
            $this->assertEquals('Random', $team->race);
            $this->assertEquals(7, $team->race_id);
        }
    }

    /**
     * Test to insure that a team is removed from cached results
     *  after being deleted / updated
     */
    public function test_cache() {
        $this->assertID( $id = $this->object->save() );
        $tournament = $this->object->tournament();

        $this->assertArrayContains($tournament->teams(), $this->object);
        $this->assertArrayContains($tournament->confirmed_teams(), $this->object);

        //Update
        $this->object->display_name = ($name = uniqid());
        $this->object->save();

        //Test BBTeam::load()
        $this->AssertTeamValueExternally($this->object, 'display_name', $name);

        //Fetch a fresh tournament and check
        $fresh_tournament = $this->bb->tournament($tournament->id);
        $fresh = $fresh_tournament->team($id);

        $this->assertEquals($name, $fresh->display_name);

        //Delete!
        $this->object->delete();

        //Test BBTeam::load()
        $fresh = $this->bb->team($id);
        $this->assertFalse($fresh->load());

        //Test BinaryBeast::teams()
        $fresh_tournament = $this->bb->tournament($tournament->id);
        $this->assertArrayNotContains($tournament->teams(true), $id);
        $this->assertArrayNotContains($fresh_tournament->teams(true), $id);
    }
}

?>