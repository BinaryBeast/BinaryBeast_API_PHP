<?php

/**
 * Test tournament map pool functionality
 *
 * @group map_pool
 * @group tournament
 * @group model
 * @group all
 */
class BBMapPoolTest extends BBTest {

    /** @var BBTournament */
    protected $object;

    protected function setUp() {
        parent::setUp();

        $this->object = $this->bb->tournament();
        $this->object->title = 'PHP API library map_pool Unit Test';
        $this->object->game_code = 'HotS';
    }

    /**
     * Add maps to the map pool, using map_ids
     *
     * @covers BBTournament::add_map
     */
    public function test_add_id() {
        $this->AssertArraySize($this->object->map_pool, 0);

        $this->assert_map_object($map = $this->object->add_map(125));
        $this->AssertArraySize($this->object->map_pool, 1);

        $this->assertEquals('Abyssal Caverns', $this->object->map_pool[0]->map);
        $this->assertEquals(125, $this->object->map_pool[0]->map_id);

        $this->assert_map_object($this->object->add_map(125));
        $this->AssertArraySize($this->object->map_pool, 1);

        $this->AssertSave($this->object->save());

        $fresh = $this->bb->tournament( $this->object->id );
        $this->AssertArraySize($fresh->map_pool, 1);
        $this->assertEquals('Abyssal Caverns', $fresh->map_pool[0]->map);
    }
    /**
     * Removes maps from the map pool, using map_ids
     *
     * @covers BBTournament::remove_map
     */
    public function test_remove_id() {
        $this->test_add_id();

        $this->AssertTrue($this->object->remove_map(125));
        $this->assertArraySize($this->object->map_pool, 0);

        $this->assertSave($this->object->save());
        $this->assertArraySize($this->object->map_pool, 0);

        $fresh = $this->bb->tournament( $this->object->id );
        $this->assertArraySize($fresh->map_pool, 0);
    }

    private function assert_map_object($map) {
        $this->assertTrue(is_object($map), 'map value is not an object! (' . gettype($map) . ')');
    }
}