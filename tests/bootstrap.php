<?php

require_once('PHPUnit/Autoload.php');
require_once 'PHPUnit/Framework/Assert.php';

$path = str_replace('\\', '/', dirname(__DIR__ ));
chdir($path);

require_once('BinaryBeast.php');
$bb = new BinaryBeast();
$bb->disable_ssl_verification();


/**
 * Base line for all tests - defines custom asserts, and imports $bb, and has methods
 *  for creating / storing tournaments with certain conditions
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

    function __construct($name = NULL, array $data = array(), $dataName = '') {
        global $bb;
        $this->bb = &$bb;
        self::$bb_static = &$this->bb;

        parent::__construct($name, $data, $dataName);
    }

    function __destruct() {
        foreach(self::$tournaments as $tournament) {
            if($tournament instanceof BBTournament) {
                $tournament->delete();
            }
        }
    }

    protected function dump_history() {
        var_dump(array('errors' => $this->bb->error_history, 'results' => $this->bb->result_history));
    }
    protected function dump_errors() {
        var_dump(array('errors' => $this->bb->error_history));
    }
    protected function dump_results() {
        var_dump(array('history' => $this->bb->result_history));
    }

    /**
     * Check against a returned list to verify that each object in the array has the 
     *  specified attributes
     * 
     * Each value in $keys can either be a string, to simply check for the existance of a property, 
     * or it can be a key => value pair with any of the following values, to check the property type:
     *      'numeric', 'int', 'float', 'string', 'array', 'object', 'boolen', 'null'
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

    public static function assertServiceIsObject($svc, $msg = 'Service result is not an object!') {
        return self::assertTrue(is_object($svc), $msg);
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
    public static function assertTourneyID($value) {
        self::assertTrue(is_string($value), 'provided value is not a string, it\'s a ' . gettype($value));
        self::assertStringStartsWith('x', $value);
    }
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
    public static function assertArraySize($array, $size) {
        self::assertTrue(is_array($array));
        self::assertTrue(sizeof($array) == $size, "Array size $size expected, " . sizeof($array) . ' found');
    }
    public static function assertChildrenArray($array, $class) {
        self::assertTrue(is_array($array));
        self::assertTrue(sizeof($array) > 0);
        foreach($array as $child) self::assertInstanceOf($class, $child);
    }
    public static function assertArrayContains($array, $search) {
        self::assertTrue(is_array($array));
        self::assertTrue(in_array($search, $array));
    }
    public static function assertArrayNotContains($array, $search) {
        self::assertTrue(is_array($array));
        self::assertFalse(in_array($search, $array));
    }

    public static function AssertTeamValueExternally($team, $key, $value) {
        if($team instanceof BBTeam) $team = $team->id;
        self::assertInstanceOf('BBTeam', $team = self::$bb_static->team($team));
        //
        if(is_null($value)) self::assertNull($team->$key);
        else                self::assertEquals($value, $team->$key);
    }
    public static function AssertTournamentValueExternally($tourney, $key, $value) {
        if($tourney instanceof BBTournament) $tourney = $tourney->id;
        self::assertInstanceOf('BBTournament', $tourney = self::$bb_static->tournament($tourney));
        //
        if(is_null($value)) self::assertNull($tourney->$key);
        else                self::assertEquals($value, $tourney->$key);
    }
    public static function AssertMatchValueExternally($match, $key, $value) {
        if($match instanceof BBMatch) $match = $match->id;
        self::assertInstanceOf('BBMatch', $match = self::$bb_static->match($match));
        //
        if(is_null($value)) self::assertNull($match->$key);
        else                self::assertEquals($value, $match->$key);
    }





    /**
     * Configures tournament reference settings and saves
     * 
     * @param BBTournament $tournament
     * @param bool $groups
     * @param bool $double_elimination
     * @param bool $save
     */
    private function configure_tournament(BBTournament &$tournament, $groups = false, $double_elimination = false, $save = true) {
        //Settings
        $tournament->title = $groups ? 'PHP API library 3.0.0 BBMatch (Groups) Unit Test' : 'PHP API library 3.0.0 BBMatch Unit Test';
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
     * @param boolean $groups
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
     * @param BBTournament $tournament
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
     * @param boolean $groups
     * @param boolean $double_elimination
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
}

?>