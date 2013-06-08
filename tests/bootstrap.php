<?php
/**
 * Test suite bootstrap file
 *
 * Runs before any PHPUnit tests are run,
 *  sets up a global $bb instance, and defines
 *  a base test class with a several custom bb-specific assertions
 *
 * @global BBTournament$bb
 *
 * @version 1.0.5
 * @date    2013-04-24
 * @author  Brandon Simmons <brandon@binarybeast.com>
 */

require_once('PHPUnit/Autoload.php');
require_once 'PHPUnit/Framework/Assert.php';

//Change the working directly to the root of BinaryBeast.php
$path = str_replace('\\', '/', dirname(__DIR__ ));
chdir($path);

//Create a global BinaryBeast instance, and disable ssl verification for debugging and development purposes
require_once('BinaryBeast.php');
$bb = new BinaryBeast();

//Cache configuration
$config = new BBConfiguration();
$config->cache_db_database      = 'test';
$config->cache_db_password      = null;
$config->cache_db_server        = 'localhost';
$config->cache_db_table         = 'bb_api_cache';
$config->cache_db_username      = 'test';

$bb = new BinaryBeast($config);
$bb->disable_ssl_verification();


/**
 * Base line for all tests - defines custom asserts, and imports $bb, and has methods
 *  for creating / storing tournaments with certain conditions
 *
 * @version 1.0.5
 * @date    2013-04-24
 * @author  Brandon Simmons <brandon@binarybeast.com>
 */
abstract class BBTest extends PHPUnit_Framework_TestCase {
    /** @var BinaryBeast */
    protected $bb;

    /** @var BinaryBeast */
    protected static $bb_static;

    /** @var BBTournament */
    protected $tournament;

    /** var BBTournament[] */
    private static $tournaments = array();

    //<editor-fold defaultstate="collapsed" desc="Private / Internal methods and properties">
    /**
     * Test suite class constructor
     *
     * Sets up the local {@link BinaryBeast} (<var>$bb</var>) instance
     *
     * {@inheritdoc}
     */
    function __construct($name = NULL, array $data = array(), $dataName = '') {
        global $bb;
        $this->bb = &$bb;
        self::$bb_static = &$this->bb;
        parent::__construct($name, $data, $dataName);
    }

    /**
     * When the instance is deleted, attempt to delete
     *  any tournaments that were created
     *
     * @return void
     */
    function __destruct() {
        foreach(self::$tournaments as $tournament) {
            if($tournament instanceof BBTournament) {
                $tournament->delete();
            }
        }
    }

    /**
     * Quickly performs a var_dump with of all recent errors and API results in the BinaryBeast class
     *
     * @return void
     */
    protected function dump_history() {
        var_dump(array('errors' => $this->bb->error_history, 'results' => $this->bb->result_history));
    }

    /**
     * Quickly performs a var_dump with all recent error messages in the BinaryBeast class
     *
     * @return void
     */
    protected function dump_errors() {
        var_dump(array('errors' => $this->bb->error_history));
    }

    /**
     * Quickly performs a var_dump with of all recent API result values from the BinaryBeast class
     *
     * @return void
     */
    protected function dump_results() {
        var_dump(array('history' => $this->bb->result_history));
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="API Object/List result assertions">
    /**
     * Check against a returned list to verify that each object in the array has the 
     *  specified attributes
     * 
     * Each value in $keys can either be a string, to simply check for the existence of a property,
     * or it can be a key => value pair with any of the following values, to check the property type:
     *      'numeric', 'int', 'float', 'string', 'array', 'object', 'boolean', 'null'
     */
    public static function assertListFormat($list, $keys = array()) {
        self::assertTrue(is_array($list), 'provided value is not an array');
        self::assertTrue(sizeof($list) > 0, 'provided value was empty, unable to verify contents');

        //run assertObjectFormat on every item returned in the array
        foreach($list as $object) self::assertObjectFormat($object, $keys);
    }
    /**
     * Validate an object format
     * 
     * 
     * Each value in $keys can either be a string, to simply check for the existance of a property, 
     * or it can be a key => value pair with any of the following values, to check the property type:
     *      'numeric', 'int', 'float', 'string', 'array', 'object', 'boolen', 'null'
     * 
     * @param object $object
     * @param array $keys
     */
    public static function assertObjectFormat($object, $keys = array()) {
        foreach($keys as $key => $type) {
            //Validate existance only
            if(is_numeric($key)) {
                $key = $type;
                $type = null;
                
            }

            //BBModel - use the data + key_exists
            if($object instanceof BBModel) {
                self::assertArrayHasKey($key, $object->data, 'Object does not have attribute ' . $key);
            }

            //Assert property exists
            else self::assertObjectHasAttribute($key, $object, 'Object does not have attribute ' . $key);

            //Assert the property type
            if(!is_null($type)) {
                $method = 'is_' . $type;
                $valid = $method($object->$key) || is_null($object->$key);
                self::assertTrue($valid, $key . ' is not a valid ' . $type . ' value!');
            }
        }
    }
    /**
     * Checks a list to see if any of its elements has the provided key=>value pair[s]
     * @param array $list
     * @param mixed $key
     * @param mixed $value
     */
    public static function assertListContains($list, $key, $value) {
        $valid = false;
        foreach($list as $object) {
            if($object->$key == $value) {
                $valid = true;
                break;
            }
        }
        self::assertTrue($valid, 'Provided list did not have any objects with ' . $key . ' equal to ' . $value);
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="Service result assertions">
    /**
     * Assert that the given service result value is an object
     */
    public static function assertServiceIsObject($svc) {
        self::assertTrue(is_object($svc), 'Service result is not a ' . gettype($svc) . ', object expected!');
    }

    /**
     * Verify that a service result simply contains 'result' => 200
     */
    public static function assertServiceSuccessful($result, $msg = 'Service did not return property result = 200!') {
        self::assertServiceIsObject($result);
        self::assertAttributeEquals(200, 'result', $result, $msg);
    }
    /**
     * Assert that a service result successfully returned a list of objects
     */
    public static function assertServiceListSuccessful($result, $list_name = 'list') {
        self::assertServiceSuccessful($result);
        self::assertObjectHasAttribute($list_name, $result, 'Returned value does not contain the key ' . $list_name);
        self::assertTrue(is_array($result->$list_name), "Result $list_name value is not an array");
        if(sizeof($result->$list_name) > 0) {
            self::assertTrue(is_object($result->{$list_name}[0]), "Values in $list_name are not objects!");
        }
    }
    /**
     * Assert that the result code of a service call indicated an authorization error
     */
    public static function assertServicePermissionDenied($result, $msg = 'Result code was NOT 403!') {
        self::assertServiceSuccessful($result);
        self::assertAttributeEquals(403, 'result', $result, $msg);
    }
    /**
     * Test a svc result to see if it was loaded from local cache
     */
    public static function assertServiceLoadedFromCache($result) {
        self::assertServiceSuccessful($result);
        self::assertAttributeEquals(true, 'from_cache', $result);
    }
    /**
     * Test a svc result to see if it was NOT loaded from local cache
     */
    public static function assertServiceNotLoadedFromCache($result) {
        self::assertServiceSuccessful($result);
        self::assertTrue(!isset($result->from_cache));
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="Save() result and ID format assertions">
    /**
     * Assert that the given value is a valid Tournament ID formatted string
     */
    public static function assertTourneyID($value) {
        self::assertTrue(is_string($value), 'provided value is not a string, it\'s a ' . gettype($value));
        self::assertStringStartsWith('x', $value);
    }

    /**
     * Assert the return value of a save() result
     *
     * The result should either be a TRUE boolean, or a
     *  tournament ID if it's a string
     */
    public static function assertSave($value) {
        //Boolean
        if(is_bool($value)) {
            return self::assertTrue($value !== false, 'save() returned false');
        }

        //Treat it as an ID
        else if(is_numeric($value) || is_string($value)) {
            return self::assertID($value);
        }

        //Invalid value 
        self::fail('save() result type (' . gettype($value) . ') invalid ' . $value);
    }

    /**
     * Assert that the given value is a valid ID
     * It can either be a number greater than zero,
     * or if it's a string it must be a valid tourney_id
     */
    public static function assertID($value) {
        //Treat it as a normal integer id
        if(is_numeric($value)) {
            return self::assertTrue($value > 0);
        }

        //Treat it as a tournament id
        else if(is_string($value)) {
            return self::assertTourneyID($value);
        }

        self::fail('id value type (' . gettype($value) . ') invalid');
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="Array assertions">
    /**
     * Asserts that the provided value is:
     * A) Is an array
     * B) Has a specific number of elements
     */
    public static function assertArraySize($array, $size) {
        self::assertTrue(is_array($array));
        self::assertTrue(sizeof($array) == $size, "Array size $size expected, " . sizeof($array) . ' found');
    }

    /**
     * Asserts that the input value is a valid array, and that each element is an instance of the given $class
     */
    public static function assertChildrenArray($array, $class) {
        self::assertTrue(is_array($array));
        self::assertTrue(sizeof($array) > 0);
        foreach($array as $child) self::assertInstanceOf($class, $child);
    }

    /**
     * Asserts that the given array contains a specific element
     */
    public static function assertArrayContains($array, $search) {
        self::assertTrue(is_array($array));
        self::assertTrue(in_array($search, $array));
    }

    /**
     * Asserts that the given array does NOT contain a specific element
     */
    public static function assertArrayNotContains($array, $search) {
        self::assertTrue(is_array($array));
        self::assertFalse(in_array($search, $array));
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="External assertions">
    /**
     * Asserts the value of a team, but firsts loads the team externally
     *
     * This eliminates ANY chance of contaminated results, it loads a new
     * BBTeam directly from the API, and asserts the provided key=>value pair
     */
    public static function AssertTeamValueExternally($team, $key, $value) {
        if($team instanceof BBTeam) $team = $team->id;
        self::assertInstanceOf('BBTeam', $team = self::$bb_static->team($team));
        //
        if(is_null($value)) self::assertNull($team->$key);
        else                self::assertEquals($value, $team->$key);
    }

    /**
     * Asserts the value of a team, but firsts loads the tournament externally
     *
     * This eliminates ANY chance of contaminated results, it loads a new
     * BBTournament directly from the API, and asserts the provided key=>value pair
     */
    public static function AssertTournamentValueExternally($tourney, $key, $value) {
        if($tourney instanceof BBTournament) $tourney = $tourney->id;
        self::assertInstanceOf('BBTournament', $tourney = self::$bb_static->tournament($tourney));
        //
        if(is_null($value)) self::assertNull($tourney->$key);
        else                self::assertEquals($value, $tourney->$key);
    }

    /**
     * Asserts the value of a match result, but firsts loads the match externally
     *
     * This eliminates ANY chance of contaminated results, it loads a new
     * BBMatch directly from the API, and asserts the provided key=>value pair
     */
    public static function AssertMatchValueExternally($match, $key, $value) {
        if($match instanceof BBMatch) $match = $match->id;
        self::assertInstanceOf('BBMatch', $match = self::$bb_static->match($match));
        //
        if(is_null($value)) self::assertNull($match->$key);
        else                self::assertEquals($value, $match->$key);
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="Tournament creation/configuration helpers">
    /**
     * Configures tournament reference settings and saves
     *
     * @ignore
     *
     * @param BBTournament $tournament
     * @param bool $groups
     * @param bool $double_elimination
     * @param bool $save
     * @return void
     */
    private function configure_tournament(BBTournament &$tournament, $groups = false, $double_elimination = false, $save = true) {
        //Settings
        $tournament->title = $groups ? 'PHP API library ' . BinaryBeast::API_VERSION . ' BBMatch (Groups) Unit Test' : 'PHP API library ' . BinaryBeast::API_VERSION . ' BBMatch Unit Test';
        $tournament->description = 'New tournament for PHPUnit testing';
        $tournament->elimination = $double_elimination ? 2 : 1;
        $tournament->bronze = true;
        $tournament->max_teams = 8;
        $tournament->team_mode = 1;
        $tournament->group_count = 2;
        $tournament->type_id = $groups ? 1 : 0;

        if($save) $this->add_tournament_rounds($tournament, $double_elimination);

        return $this->tournament = $tournament;
    }
    /**
     * Configure BO3 for all rounds in the given tournament, and BO5 for the finals or bronze
     *
     * @param BBTournament $tournament
     * @param boolean $double_elimination
     * @param boolean $save
     * @return void
     */
    protected function add_tournament_rounds(BBTournament &$tournament, $double_elimination = false, $save = true) {
        //Must start with an ID
        $this->assertSave($tournament->save());

        $maps = array('Abyssal Caverns', 614, 'Akilon Flats', 71, 'Arid Plateau', 337, 'Backwater Gulch', 225);
        foreach($tournament->rounds as &$bracket) {
            foreach($bracket as $x => &$round) {
                $round->best_of = 3;
                $round->map = isset($maps[$x]) ? $maps[$x] : null;
            }
        }
        if(isset($tournament->rounds->finals)) $tournament->rounds->finals[0]->best_of = 5;
        if(isset($tournament->rounds->bronze)) $tournament->rounds->bronze[0]->best_of = 5;

        if($save) {
            $this->assertSave($tournament->save());
        }
    }
    /**
     * Adds 8 confirmed teams to the tournament reference
     * @param BBTournament  $tournament
     * @param boolean       $save
     */
    protected function add_tournament_teams(BBTournament &$tournament, $save = true) {
        //Must start with an ID
        $this->assertSave($tournament->save());

        //Add 8 players
        $countries = array('USA', 'GBR', 'USA', 'NOR', 'JPN', 'SWE', 'NOR', 'GBR');
        for($x = 0; $x < 8; $x++) {
            $team = $tournament->team();
            $team->confirm();
            $team->display_name = 'Player ' . ($x + 1);
            $team->country_code = $countries[$x];
        }
        if($save) {
            $this->assertTrue($tournament->save_teams());
        }
    }
    /**
     * Returns a brand new unsaved tournament
     * @return BBTournament
     */
    protected function get_tournament_new() {
        $this->tournament = $this->bb->tournament();
        self::$tournaments[] = &$this->tournament;
        return $this->tournament;
    }
    /**
     * Returns a saved inactive tournament - aka hasn't started, no teams added - rounds have been setup though
     * @param boolean   $groups
     * @param boolean   $double_elimination
     * @param boolean   $save
     * @return BBTournament
     */
    protected function get_tournament_inactive($groups = false, $double_elimination = false, $save = true) {
        $this->tournament = $this->get_tournament_new();
        $this->configure_tournament($this->tournament, $groups, $double_elimination, $save);

        return $this->tournament;
    }
    /**
     * Returns a tournament that is ready to start(), with settings, teams, round format
     *  all setup
     * @param boolean $groups
     * @param boolean $double_elimination
     * @return BBTournament
     */
    protected function get_tournament_ready($groups = false, $double_elimination = false) {
        $tournament = $this->get_tournament_inactive($groups, $double_elimination, false);
        $this->add_tournament_teams($tournament, false);
        $this->add_tournament_rounds($tournament, $double_elimination, true);

        //Success!
        return $this->tournament = $tournament;
    }
    /**
     * Return a tournament that has any valid open matches
     * @param bool $groups
     * @param bool $double_elimination
     * @return BBTournament
     */
    protected function get_tournament_with_open_matches($groups = false, $double_elimination = false) {
        $tournament = $this->get_tournament_ready($groups, $double_elimination);
        $this->assertTrue($tournament->start());
        $this->assertEquals($groups ? 'Active-Groups' : 'Active', $tournament->status);

        return $this->tournament = $tournament;
    }
    //</editor-fold>
}

/**
 * Custom PHPUnit test listener for BinaryBeast tests
 */
class bb_test_listener implements PHPUnit_Framework_TestListener {
    /**
     * An error occurred.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  Exception $e
     * @param  float $time
     */
    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
        global $bb;
        if(!empty($bb->error_history)) {
            var_dump(array('* BinaryBeast Results *' => $this->bb->result_history));
        }
    }

    /**
     * A failure occurred.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  PHPUnit_Framework_AssertionFailedError $e
     * @param  float $time
     */
    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        global $bb;
        if(!empty($bb->error_history)) {
            var_dump(array('* BinaryBeast Errors *' => $this->bb->error_history));
        }
    }

    /**
     * Incomplete test.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  Exception $e
     * @param  float $time
     */
    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
    }

    /**
     * Skipped test.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  Exception $e
     * @param  float $time
     * @since  Method available since Release 3.0.0
     */
    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
    }

    /**
     * A test suite started.
     *
     * @param  PHPUnit_Framework_TestSuite $suite
     * @since  Method available since Release 2.2.0
     */
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
    }

    /**
     * A test suite ended.
     *
     * @param  PHPUnit_Framework_TestSuite $suite
     * @since  Method available since Release 2.2.0
     */
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
    }

    /**
     * A test started.
     *
     * @param  PHPUnit_Framework_Test $test
     */
    public function startTest(PHPUnit_Framework_Test $test) {
    }

    /**
     * A test ended.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  float $time
     */
    public function endTest(PHPUnit_Framework_Test $test, $time) {
    }

}

?>