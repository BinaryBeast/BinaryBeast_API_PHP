<?php

/**
 * Test callbakc functionality
 * 
 * @group callback
 * @group library
 * @group all
 */
class BBCallbackTest extends BBTest {
    
    /** @var BBCallback */
    protected $object;

    protected function setUp() {
        $this->object = $this->bb->callback();

		//Test tournament created specifically for testing callbacks
		$this->tournament = $this->bb->tournament('xSC21303290');
    }

	/**
	 * Test registering a new callback - the generic tournament_change
	 * 
	 * @covers BBCallback::register
	 */
	public function test_register() {
		$this->assertID($id = $this->object->register(BBCallback::EVENT_TOURNAMENT_CHANGED, $this->tournament->id, 'http://binarybeast.com/callback/test/' . uniqid()));
	}

	/**
	 * Attempts to register a duplicate callback should return the original ID
	 * 
	 * @covers BBCallback::register
	 */
	public function test_register_duplicate() {
		$url = 'http://binarybeast.com/callback/test/' . uniqid();
		$this->assertID($id = $this->object->register(BBCallback::EVENT_TOURNAMENT_CHANGED, $this->tournament->id, $url));
		$this->assertID($id2 = $this->object->register(BBCallback::EVENT_TOURNAMENT_CHANGED, $this->tournament->id, $url));
		$this->assertEquals($id, $id2);
	}

	/**
	 * Test deleting a callback
	 * @covers BBCallback::unregister
	 */
	public function test_unregister() {
		$this->assertID($id = $this->object->register(BBCallback::EVENT_TOURNAMENT_CHANGED, $this->tournament->id, 'http://binarybeast.com/callback/test/' . uniqid()));
		$this->assertTrue($this->object->unregister($id));
	}

	/**
	 * Load a list of registered callbacks
	 * @covers BBCallback::load_list
	 */
	public function test_list() {
		$this->assertID($this->object->register(BBCallback::EVENT_TOURNAMENT_CHANGED, $this->tournament->id, 'http://binarybeast.com/callback/test/' . uniqid()));
		$this->assertID($this->object->register(BBCallback::EVENT_TOURNAMENT_CHANGED, $this->tournament->id, 'http://binarybeast.com/callback/test/' . uniqid()));
		$this->assertID($this->object->register(BBCallback::EVENT_TOURNAMENT_CHANGED, $this->tournament->id, 'http://binarybeast.com/callback/test/' . uniqid()));
		$this->assertListFormat($this->object->load_list(), array('id', 'event_id', 'url'));
	}

	/**
	 * Load a list of registered callbacks - filtered by event_id for this tournament
	 * @covers BBCallback::load_list
	 */
	public function test_list_event() {
        //Get a fresh tournament, so we know it starts with 0 callbacks
        $this->get_tournament_inactive();

		$this->assertID($id = $this->object->register(BBCallback::EVENT_TOURNAMENT_CHANGED, $this->tournament->id, 'http://binarybeast.com/callback/test/' . uniqid()));
		$this->assertID($this->object->register(BBCallback::EVENT_TOURNAMENT_START_BRACKETS, $this->tournament->id, 'http://binarybeast.com/callback/test/' . uniqid()));
		$this->assertID($this->object->register(BBCallback::EVENT_TOURNAMENT_START_GROUPS, $this->tournament->id, 'http://binarybeast.com/callback/test/' . uniqid()));

		$this->assertListFormat($list = $this->object->load_list(BBCallback::EVENT_TOURNAMENT_CHANGED, $this->tournament->id), array('id', 'event_id', 'url'));
        $this->assertArraySize($list, 1);
        $this->assertEquals($id, $list[0]->id);
	}
	
	/**
	 * BinaryBeast.com/callback/test allows us to test the tester heh
	 * @covers BBCallback::test
	 */
	public function test_test() {
		$response = $this->object->test(null, BBCallback::EVENT_TOURNAMENT_MATCH_REPORTED, 'xMyTourney!',
                'http://binarybeast.com/callback/test/',
                null, false, array('CustomARGUMENT' => true));
		$this->assertTrue(is_string($response));
		$this->assertNotNull($decoded = json_decode($response));
		$this->assertEquals($decoded->trigger_id, 'xMyTourney!');
		$this->assertEquals($decoded->CustomARGUMENT, 1);
	}

	/**
	 * BinaryBeast.com/callback/test allows us to test the tester heh
	 * @covers BBCallback::test
	 */
	public function test_custom_args() {
		$response = $this->object->test(null, BBCallback::EVENT_TOURNAMENT_MATCH_REPORTED, 'xMyTourney!',
                'http://binarybeast.com/callback/test/',
                null, false, (object)array(
                    'custom_arg_0'  =>  0,
                    'custom_arg_1'  =>  1,
                    'custom_arg_3'  =>  3,
                    'TESTING'       =>  15,
                )
        );
		$this->assertTrue(is_string($response));
		$this->assertNotNull($decoded = json_decode($response));
		$this->assertEquals($decoded->trigger_id, 'xMyTourney!');
		$this->assertEquals($decoded->custom_arg_0, 0);
		$this->assertEquals($decoded->custom_arg_1, 1);
		$this->assertEquals($decoded->custom_arg_3, 3);
		$this->assertEquals($decoded->TESTING,      15);
	}
    
	/**
	 * Test tournament's on_change callback wrapper
	 * @covers BBTournament::on_change
	 */
	public function test_tournament_on_change() {
		//First, create a real tournament
		$this->tournament = $this->bb->tournament();
		$this->tournament->title = 'Testing on_change callback';
		$this->assertSave($this->tournament->save());

		//Register the callback hosted by bb.com
		$this->assertSave($id = $this->tournament->on_change('http://binarybeast.com/callback/test'));

        /*
         * Request a test, which returns the response of the URL we registered, which happens to be
         *  a page hosted on binarybeast.com that simply returns a json string of the callback data
         */
        $this->assertTrue(is_string($response = $this->object->test($id)));
        $this->assertObjectFormat($decoded = json_decode($response), array('callback_id', 'trigger_id', 'event_description'));

        //BinaryBeast's public callback test handler should response should include a trigger id, which should match our tournament's id
        $this->assertEquals($decoded->trigger_id, $this->tournament->id);
        $this->assertEquals($decoded->callback_id, $id);
	}

    /**
     * Test the on_create tournament callback
     */
    public function test_tournament_on_create() {
        $id = $this->tournament->on_create('http://binarybeast.com/callback/test');
        $this->assertID($id);

        //Now, invoke it!
        $response = $this->object->test($id);

        //Success!
        $this->assertTrue(is_string($response));
        $this->assertObjectFormat($decoded = json_decode($response), array('callback_id', 'trigger_id', 'event_description'));
    }
}

?>