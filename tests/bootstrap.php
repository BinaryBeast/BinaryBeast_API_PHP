<?php

require_once('PHPUnit/Autoload.php');
require_once 'PHPUnit/Framework/Assert.php';

$path = str_replace('\\', '/', dirname(__DIR__ ));
chdir($path);

require_once('BinaryBeast.php');
$bb = new BinaryBeast('e17d31bfcbedd1c39bcb018c5f0d0fbf.4dcb36f5cc0d74.24632846');
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

    /** @var BBTournament[] */
    protected static $tournaments_inactive;
    /** @var BBTournament[] */
    protected static $tournaments_ready;
    /** @var BBTournament[] */
    protected static $tournaments_open_matches_brackets;
    /** @var BBTournament[] */
    protected static $tournaments_open_matches_groups;
    /** @var BBTournament[] */
    protected static $tournaments_to_delete;



    function __construct($name = NULL, array $data = array(), $dataName = '') {
        global $bb;
        $this->bb = &$bb;
        if(is_null(self::$bb_static)) self::$bb_static = &$this->bb;

        parent::__construct($name, $data, $dataName);
    }

    /**
     * Intercept failed tests so that we can dump any logged errors in $bb
     * @param \Exception $e
     */
    protected function onNotSuccessfulTest(\Exception $e) {
        $this->dump_errors();
        parent::onNotSuccessfulTest($e);
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
        self::assertObjectHasAttribute($list_name, $result, $msg);
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
        //Treat it as a tournament id
        if(is_string($value)) {
            return self::assertTourneyID($value);
        }

        //Treat it as a normal integer id
        else if(is_numeric($value)) {
            return self::assertTrue($value > 0);
        }

        self::fail('id value type (' . gettype($value) . ') invalid');
    }
    public static function assertArraySize($array, $size) {
        self::assertTrue(is_array($array));
        self::assertTrue(sizeof($array) == $size, "Array size $size expected, " . sizeof($array) . ' found');
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





    /*
     * 
     * 
     * Tournament fetching / caching helper methods - cut down
     *  on the ridiculous number of API calls made just to create tournaments
     * 
     * 
     */
    
    
    
    
    
    
    /**
     * Returns and removes a tournament from the requested list
     * @param BBTournament[] $list
     * @return BBTournament|null
     */
    private function fetch_tournament(array &$list) {
        return array_pop($list);
    }
    /**
     * Stores a touranemnt into the given local array reference
     * @param BBTournament[] $list
     * @param BBTournament $tournament
     * @return null
     */
    private function stash_tournament(array &$list, BBTournament $tournament) {
        $list[] = $tournament;
        return null;
    }
    /**
     * Stashes a tournament if it's active / complete
     * @param BBTournament $tournament
     * @return BBTournament|null
     */
    private function stash_active_tournament(BBTournament $tournament) {
        if($tournament->status == 'Complete') {
            $tournament = $this->stash_tournament(self::$tournaments_to_delete, $tournament);
        }
        else if($tournament->status == 'Active-Brackets' || $tournament->status == 'Active') {
            $tournament = $this->stash_tournament(self::$tournaments_open_matches_brackets, $tournament);
        }
        else if($tournament->status == 'Active-Groups') {
            $tournament = $this->stash_tournament(self::$tournaments_open_matches_groups, $tournament);
        }
        else {
            $teams_size = sizeof($tournament->teams());
            if($teams_size > 0) {
                $tournament = $this->stash_tournament($teams_size == 8 ? self::$tournaments_ready : self::$tournaments_to_delete, $tournament);
            }
        }
        return $tournament;
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
         $this->assertSave($tournament->save());

        //All matches are BO3, bronze / finals are BO5
        $maps = array('Abyssal Caverns', 614, 'Akilon Flats', 71, 'Arid Plateau', 337, 'Backwater Gulch', 225);
        foreach($tournament->rounds as &$bracket) {
            foreach($bracket as $x => &$round) {
                $round->best_of = 3;
                $round->map = isset($maps[$x]) ? $maps[$x] : null;
            }
        }
        if($double_elimination) $tournament->rounds->finals[0]->best_of = 5;
        else                    $tournament->rounds->bronze[0]->best_of = 5;

        if($save) $this->assertSave($tournament->save());
    }
    /**
     * Adds 8 confirmed teams to the tournament reference
     * @param BBTournament $tournament
     */
    private function add_tournament_teams(BBTournament &$tournament, $save = true) {
        //Add 8 players
        $countries = array('USA', 'GBR', 'USA', 'NOR', 'JPN', 'SWE', 'NOR', 'GBR');
        for($x = 0; $x < 8; $x++) {
            $team = $tournament->team();
            $team->confirm();
            $team->display_name = 'Player ' . ($x + 1);
            $team->country_code = $countries[$x];
        }
        if($save) $this->assertTrue($tournament->save_teams());
    }
    /**
     * Returns a brand new unsaved tournament
     * @return BBTournament
     */
    protected function get_tournament_new() {
        return $this->tournament = $this->bb->tournament();
    }
    /**
     * Returns a saved inactive tournament - aka hasn't started, no teams added - rounds have been setup though
     * @param boolean $groups
     * @param boolean $double_elimination
     * @return BBTournament
     */
    protected function get_tournament_inactive($groups = false, $double_elimination = false, $save = true) {
        //If we found an existing tour, make sure it's still valid
        if(!is_null($tournament = $this->fetch_tournament(self::$tournaments_inactive))) {
            $tournament = $this->stash_active_tournament($tournament);
        }
        if(is_null($tournament)) $tournament = $this->get_tournament_new();

        //Tournament settings
        $this->configure_tournament($tournament, $groups, $double_elimination, $save);

        //Success!
        return $this->tournament = $tournament;
    }
    /**
     * Returns a tournament that is ready to start(), with settings, teams, round format
     *  all setup
     * @param boolean $groups
     * @param boolean $double_elimination
     * @return BBTournament
     */
    protected function get_tournament_ready($groups = false, $double_elimination = false) {
        //Make sure it's still ready
        if(!is_null($tournament = $this->fetch_tournament(self::$tournaments_ready))) {
            //Make sure it hasn't started yet
            if(!is_null($tournament = $this->stash_active_tournament($tournament))) {
                //Must have 8 confirmed saved teams
                if(sizeof($tournament->confirmed_teams()) != 8) {
                    $tournament = $this->stash_tournament(self::$tournaments_to_delete, $tournament);
                }
                //Confirmed teams must be saved + unchanged
                else foreach($tournament->confirmed_teams() as $team) {
                    if($team->is_new() || $team->changed) {
                        $tournament = $this->stash_tournament(self::$tournaments_to_delete, $tournament);
                        break;
                    }
                }
            }
        }

        //New tournament - configure + add teams
        if(is_null($tournament)) {
            $tournament = $this->get_tournament_inactive($groups, $double_elimination, false);
            $this->add_tournament_teams($tournament, true);
        }

        //Existing - update configuration to be sure we return consistent tournaments
        else $this->configure_tournament($tournament, $groups, $double_elimination);

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
        if(!is_null($tournament = $this->fetch_tournament($groups ? self::$tournaments_open_matches_brackets : self::$tournaments_open_matches_groups))) {
            //Must be in brackets phase
            if($tournament->status != 'Active' && $tournament->status != 'Active-Brackets') {
                $tournament = $this->stash_tournament(self::$tournaments_to_delete, $tournament);
            }

            //Keep track of how many removed, to make sure the array count matches afterwards
            $matches_count = sizeof($tournament->open_matches());

            //Must have open matches
            if($matches_count == 0) {
                $tournament = $this->stash_tournament(self::$tournaments_to_delete, $tournament);
            }

            //Remove any saved / changed matches
            else {
                foreach($tournament->open_matches as &$match) {
                    if(!$match->is_new() || $match->changed) {
                        $this->assertTrue($tournament->remove_child($match, null, false));
                        $this->assertNull($match);
                        --$matches_count;
                    }
                }
                //open_matches should now be equal to matches_count
                if( (sizeof($tournament->open_matches) != $matches_count) || $matches_count <= 0) {
                    $tournament = $this->stash_tournament(self::$tournaments_to_delete, $tournament);
                }
            }
        }

        //new tournament - star the brackets!
        if(is_null($tournament)) {
            $tournament = $this->get_tournament_ready($groups, $double_elimination);
            $this->assertTrue($tournament->start());
            $this->assertEquals($groups ? 'Active-Brackets' : 'Active', $tournament->status);
        }

        //Success!
        return $this->tournament = $tournament;
    }
}

?> 