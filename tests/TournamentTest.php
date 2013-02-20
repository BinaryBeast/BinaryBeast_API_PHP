<?php

require_once('lib/includes.php');

/**
 * Test game listing / searching
 * @group tournament
 * @group model
 * @group all
 */
class TournamentTest extends bb_test_case {

    /**
     * @var BBTournament
     */
    protected $object;
    protected static $static_object;

    protected function setUp() {
        if(!is_null(self::$static_object)) $this->object = &self::$static_object;
        else {
            self::$static_object = $this->bb->tournament();
            $this->object = &self::$static_object;
        }
    }
    public function test_get_object() {
        $this->assertInstanceOf('BBTournament', $this->object);
    }
    public function test_update_before_save() {
        $this->object->title = 'updated';
        $this->assertEquals('updated', $this->object->title);
        $this->assertNull($this->object->id);
    }
    public function test_reset() {
        $this->assertEquals('updated', $this->object->title, 'test does not retain result from prior test');
        $this->object->reset();
        $this->assertEquals($this->object->default_value('title'), $this->object->title);
    }
    public function test_setup() {
        $this->object->title            = 'PHP Unit Test Touranment';
        $this->object->game_code        = 'SC2';
        $this->object->elimination      = 1;
        $this->object->max_teams        = 64;
        $this->assertEquals('PHP Unit Test Touranment', $this->object->title);
        $this->assertEquals('SC2', $this->object->game_code);
        $this->assertEquals(1, $this->object->elimination);
        $this->assertEquals(64, $this->object->max_teams);
    }
    public function test_create() {
        $result = $this->object->save();
        $this->assertNotEquals(false, $result);
        $this->assertStringStartsWith('x', $result);
    }

}
