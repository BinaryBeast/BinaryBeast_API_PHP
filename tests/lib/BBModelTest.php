<?php

/**
 * Testing general functionality of the base model class BBModel
 * 
 * @group bbmodel
 * @group library
 * @group all
 */
class BBModelTest extends BBTest {

    /** @var BBTournament */
    protected $object;

    /**
     * Grab a BBTournament BBModel object
     */
    protected function setUp() {
        $this->object = $this->bb->tournament();
    }

    /**
     * Tests removing a child with $preserve disabled, and checking to make sure
     *  that any references are set to null
     * 
     * @covers BBTournament::remove_child
     * @covers BBModel::remove_child
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
     * Tests to make sure __toString returns a string as expected
     * 
     * @covers BBModel::__toString
     */
    public function test_to_string() {
        $this->assertTrue(is_string($this->object->__toString()));
        echo $this->object;
    }

}

?>