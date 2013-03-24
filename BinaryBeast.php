<?php

/**
 * The main library class, used to communicate with the API and as a factory to generate model objects
 * 
 * 
 * ### Getting started ###
 * 
 * 
 * ## First - setup the configuration in {@link BBConfiguration}
 * 
 * 
 * This class is your starting point for everything - and there are a few ways to instantiate it
 * Here are a few examples of how to instantiate this class
 * 
 * <b>Example: Minimal setup, use settings from {@link lib/BBConfiguration.php}</b>
 * <code>
 *  $bb = new BinaryBeast();
 * </code>
 * 
 * <b>Example: Manually define your api_key</b>
 * <code>
 *  $bb = new BinaryBeast('e17d31bfcbedd1c39bcb018c5f0d0fbf.4dcb36f5cc0d74.24632846');
 * </code>
 * 
 * <b>Example: Use a custom BBConfiguration object, and define custom tournament team model extensions</b>
 * Of course you could define these in {@link lib/BBConfiguration.php}, but this demonstrates how to do it manually
 * <code>
 *  $config = new BBConfiguration;
 *  $config->models_extensions['BBTournament']  = 'LocalTournament';
 *  $config->models_extensions['BBTeam']        = 'LocalTeam';
 * 
 *  $bb = new BinaryBeast($config);
 * </code>
 * 
 * 
 * Next step: start using model objects
 * 
 * 
 * ### Model Objects
 * 
 * Models are the heart and soul of this library
 * 
 * Each model represnts a remote object stored on BinaryBeast's servers
 * 
 * For example, you can have one object to represent a tournament, and that tournament can have many 
 *  child models, such as teams, matches, and rounds
 * 
 * Also for loading models with an existing id, note that the data is loaded only on-demand, so you don't have to 
 * worry about an excessive number of API calls if you load large objects with a lot of children'
 * (for example, loading a tournament with a hundred teams and matches in it)
 * 
 * Full model objects are provide basic CRUD functionality, as well as a lot of object-specific functionality
 * 
 * All models provide the delete(); method for deleting, and save() for creating and updating
 * 
 * <b>Available models: </b>
 * <ul>
 *  <li>{@link BBTournament}</li>
 *  <li>{@link BBTeam}</li>
 *  <li>{@link BBRound}</li>
 *  <li>{@link BBMatch}</li>
 *  <li>{@link BBMatchGame}</li>
 * </ul>
 * 
 * ### Simple models
 * 
 * Simple models are used for <b>searching / listing only</b>
 * 
 * {@link BinaryBeast} offers some "magic" properties for conveniently and quickly accessing these models
 * 
 * <b>Example - Load a list of popular games: </b>
 * <b>Note:</b> We're using the magic <var>$game</var> property to access {@link BBGame}
 * <code>
 *      $games = $bb->game->list_popular();
 *      foreach($games as $game) {
 *          echo '<div class="game">' .
 *              $game->game . '(' . $game->game_code . ')' .
 *              '<img src="' . $game->game_icon . '" /></div>';
 *      }
 * </code>
 * 
 * <b>Available simple models:</b>
 * <ul>
 *  <li>{@link BBCountry}</li>
 *  <li>{@link BBGame}</li>
 *  <li>{@link BBMap}</li>
 *  <li>{@link BBRace}</li>
 * </ul>
 * 
 * 
 * ### Error Handling
 * 
 * Any time an error is encountered, false or null is returned when you expected an object.. 
 * check {@link BinaryBeast::last_error} and {@link BinaryBeast::error_history}, there is likely
 * an explanation in there
 * 
 * You can use this page to view a full log your recent API requests, and the response you were given: {@link http://binarybeast.com/user/settings/api_history}
 * 
 * 
 * ### Backwards Compatability
 * 
 * If your application is currently using an older version of this library, it will still work with this one
 * 
 * To make that possible, we've included {@link lib/BBLegacy.php}, which hosts all of the old
 * legacy api service wrappers from the previous version of this library
 * 
 * 
 * ### Quick Tutorials and Examples ###
 * 
 * Each example assumes the following has already been executed,
 * and that <b>you've set the values in {@link BBConfiguration}</b>
 * 
 * <code>
 *  require('BinaryBeast.php');
 *  $bb = new BinaryBeast();
 * </code>
 * 
 * 
 * ### More Tutorials
 * 
 * <b>Next step:</b> Begin by skimming through the documentation for {@link BBTournament}
 * 
 * 
 * ### Extending the Model Classes
 * 
 * Coming soon... 
 * 
 * 
 * @property BBTournament $tournament
 * <b>Alias for {@link BinaryBeast::tournament()}</b><br />
 *  A new {@link BBTournamament} object<br />
 * 
 * @property BBTeam $team
 * <b>Alias for {@link BinaryBeast::team()}</b><br />
 *  A new {@link BBTeam} object
 * 
 * @property BBRound $round
 * <b>Alias for {@link BinaryBeast::round()}</b><br />
 *  A new {@link BBRound} object
 * 
 * @property BBMatch $match
 * <b>Alias for {@link BinaryBeast::match()}</b><br />
 *  A new {@link BBMatch} object
 * 
 * @property BBMatchGame $match_game
 * <b>Alias for {@link BinaryBeast::match_game()}</b><br />
 * A new {@link BBMatchGame} instance, used by {@link BBMatch} to define play-by-play details within the match
 * 
 * @property BBMap $map
 * <b>Alias for {@link BinaryBeast::map()}</b><br />
 *  A {@link BBMap} object, that you can use to search for maps and map_ids<br />
 * 
 * @property BBCountry $country
 * <b>Alias for {@link BinaryBeast::country()}</b><br />
 * A {@link BBCountry} object, that you can use to search for countries and country codes<br />
 * 
 * @property BBGame $game
 * <b>Alias for {@link BinaryBeast::game()}</b><br />
 * Returns a {@link BBGame} object, that you can use to search for games and game_codes<br />
 * 
 * @property BBRace $race
 * <b>Alias for {@link BinaryBeast::race()}</b><br />
 *  Returns a {@link BBRace} object, that you can use to search for races and race_ids
 * 
 * @property BBLegacy $legacy
 * <b>Alias for {@link BinaryBeast::legacy()}</b><br />
 * Returns the BBLegacy class, that is used to execute the old wrapper methods that were provided in earlier versions of this library
 * 
 * @property BBCache $cache
 * <b>Alias for {@link BinaryBeast::cache()}</b><br />
 * The {@link BBCache} class, which is used to save and retrieve API responses from a local database, to cut down on API calls<br />
 * <b>NULL</b> if your settings in {@link BBConfiguration} are invalid / not set
 * 
 * 
 * @package BinaryBeast
 * 
 * 
 * @version 3.0.0
 * @date 2013-03-17
 * @author Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
class BinaryBeast {

    //URL to send API Requests
    private static $url = 'https://api.binarybeast.com/';

    /**
     * Base path to the binarybeast library folder
     * @var string
     */
    public $lib_path;

    /**
     * BinaryBeast API Key
     * @access private
     * @var string
     */
    private $api_key = null;

    /**
     * Login email cache
     */
    private $email = null;
    private $password = null;

    /**
     * If the ssl verification causes issues, developers can disable it
     */
    private $verify_ssl = true;

    /**
     * Constructor flags wether or not server is capable of requesting and processing the API requests
     * Static so that we don't have to check it more than once if instantiated again
     */
    private static $server_ready = null;

    /**
     * Store the result codes / values for the previous
     * API Request response
     * @var object
     */
    public $last_error;
    /**
     * A summary of the last API call made
     * @var object
     */
    public $last_result;
    /**
     * A friendly translation of the last result code sent back from the API
     * @var string
     */
    public $last_friendly_result;
    /**
     * A history of error messages
     * @var object[]
     */
    public $error_history   = array();
    /**
     * A history of API call results
     * @var object[]
     */
    public $result_history  = array();

    /**
     * Store an instance of BBLegacy, since it's loaded on-demand
     * @var BBLegacy
     */
    private $legacy = null;

    /**
     * Store an instance of BBCache
     * @var BBCache;
     */
    private $cache;

    /**
     * Cache of simple models
     */
    private $models_cache = array();

    /**
     * List of dependent models to auto-load when we load
     * a library that needs them
     */
    private static $auto_load_libraries = array(
        'BBTournament'  => array('BBTeam', 'BBRound', 'BBMatch'),
        'BBMatch'       => array('BBMatchGame')
    );

    /**
     * The first time this class is instantiated, we'll auto-load 
     * some libraries, but skip that step if another instance is created
     */
    private static $first = true;

    /**
     * Object that stores our application-specific configuration values
     * @var BBConfiguration
     */
    private $config;

    /*
     * Simple constant that contains the library version
     * @var string
     */
    const API_VERSION = '3.0.0';
    /**
     * Each bracket is idenetifed by a number,
     * 0 indicates group roundes
     * @var int
     */
    const BRACKET_GROUPS    = 0;
    /**
     * Each bracket is idenetifed by a number,
     * 1 indicates winners' bracket
     * @var int
     */
    const BRACKET_WINNERS   = 1;
    /**
     * Each bracket is idenetifed by a number,
     * 2 indicates loser' bracket
     * @var int
     */
    const BRACKET_LOSERS    = 2;
    /**
     * Each bracket is idenetifed by a number,
     * 3 indicates grand finals
     * @var int
     */
    const BRACKET_FINALS    = 3;
    /**
     * Each bracket is idenetifed by a number,
     * 4 indicates bronze / 3rd place decider
     * @var int
     */
    const BRACKET_BRONZE    = 4;
    /**
     * Single elimination mode
     * @var int
     */
    const ELIMINATION_SINGLE = 1;
    /**
     * Double elimination mode
     * @var int
     */
    const ELIMINATION_DOUBLE = 2;
    /**
     * <b>Tournament type</b><br />
     * Elimination brackets only
     * @var int
     */
    const TOURNEY_TYPE_BRACKETS = 0;
    /**
     * <b>Tournament type</b><br />
     * group rounds following by elimination brackets
     * @var int
     */
    const TOURNEY_TYPE_CUP      = 1;
    /**
     * <b>Seeding type</b><br />
     * Randomized seeds / ranks / groups
     * Affects both group rounds and brackets
     * @var string
     */
    const SEEDING_RANDOM        = 'random';
    /**
     * <b>Seeding type</b><br />
     * Positions are determined by rank
     * Affects elimination brackets only
     * @var string
     */
    const SEEDING_SPORTS        = 'sports';
    /**
     * <b>Seeding type</b><br />
     * Positions are determined by rank, but matchups are more 
     *  fair than traditional / sports seeding type
     * Affects elimination brackets only
     * @var string
     */
    const SEEDING_BALANCED      = 'balanced';
    /**
     * <b>Seeding type</b><br />
     * Positions are manually defined 
     * Affects both group rounds and brackets
     * @var string
     */
    const SEEDING_MANUAL        = 'manual';
    /**
     * <b>Replay download mode</b><br />
     * Replays can never be downloaded
     * @var int
     */
    const REPLAY_DOWNLOADS_DISABLED         = 0;
    /**
     * <b>Replay download mode</b><br />
     * Replays can be downloaded at any time
     * @var int
     */
    const REPLAY_DOWNLOADS_ENABLED          = 1;
    /**
     * <b>Replay download mode</b><br />
     * Replays can only be downloaded once the tournament is complete
     * @var int
     */
    const REPLAY_DOWNLOADS_POST_COMPLETE    = 2;
    /**
     * <b>Replay upload mode</b><br />
     * Replays cannot be uploaded
     * @var int
     */
    const REPLAY_UPLOADS_DISABLED   = 0;
    /**
     * <b>Replay upload mode</b><br />
     * Replays can be uploaded
     * @var int
     */
    const REPLAY_UPLOADS_OPTIONAL   = 1;
    /**
     * <b>Replay upload mode</b><br />
     * Replays <b>MUST</b> be uploaded
     * @var int
     */
    const REPLAY_UPLOADS_MANDATORY  = 2;
    /**
     * <b>Team status</b><br />
     * Unconfirmed - the team will NOT be included in the tournament 
     * @var int
     */
    const TEAM_STATUS_UNCONFIRMED           = 0;
    /**
     * <b>Team status</b><br />
     * Confirmed - the team will be included in the tournament 
     * @var int
     */
    const TEAM_STATUS_CONFIRMED             = 1;
    /**
     * <b>Team status</b><br />
     * Banned - the team will NOT be included in the tournament, and has no permission to take any action<br />
     * This means if a user had permisssion to this team, he would not be able to report wins, confirm, leave the tournament, etc
     * @var int
     */
    const TEAM_STATUS_BANNED                = -1;
    /**
     * <b>API Result Code</b><br />
     * The API call was successful
     * @var int
     */
    const RESULT_SUCCESS                        = 200;
    /**
     * <b>API Result Code</b><br />
     * Your api_key or email/password is invalid - you were not logged in
     * @var int
     */
    const RESULT_NOT_LOGGED_IN                  = 401;
    /**
     * <b>API Result Code</b><br />
     * Your account does not have the authority to perform that action
     * @var int
     */
    const RESULT_AUTH                           = 403;
    /**
     * <b>API Result Code</b><br />
     * Invalid service name, or ID
     * @var int
     */
    const RESULT_NOT_FOUND                      = 404;
    /**
     * <b>API Result Code</b><br />
     * That service is not enabled for public API use
     * @var int
     */
    const RESULT_API_NOT_ALLOWED                = 405;
    /**
     * <b>API Result Code</b><br />
     * The email you tried to log in with is invalid
     * @var int
     */
    const RESULT_LOGIN_EMAIL_INVALID            = 406;
    /**
     * <b>API Result Code</b><br />
     * The email provied is already being used
     * @var int
     */
    const RESULT_EMAIL_UNAVAILABLE              = 415;
    /**
     * <b>API Result Code</b><br />
     * The email provided is not a valid email address
     * @var int
     */
    const RESULT_INVALID_EMAIL_FORMAT           = 416;
    /**
     * <b>API Result Code</b><br />
     * Your account is currently pending activation
     * @var int
     */
    const RESULT_PENDING_ACTIVIATION            = 418;
    /**
     * <b>API Result Code</b><br />
     * Your account has been banned
     * @var int
     */
    const RESULT_LOGIN_USER_BANNED              = 425;
    /**
     * <b>API Result Code</b><br />
     * Incorrect login password
     * @var int
     */
    const RESULT_PASSWORD_INVALID               = 450;
    /**
     * <b>API Result Code</b><br />
     * The game_code value you provided does not exist
     * @var int
     */
    const RESULT_GAME_CODE_INVALID               = 461;
    /**
     * <b>API Result Code</b><br />
     * Invalid bracket integer
     * @var int
     */
    const RESULT_INVALID_BRACKET_NUMBER         = 465;
    /**
     * <b>API Result Code</b><br />
     * Duplicate entry - must be unique
     * @var int
     */
    const RESULT_DUPLICATE_ENTRY                = 470;
    /**
     * <b>API Result Code</b><br />
     * General processing error<br />
     * <b>Please report these to BinaryBeast <contact@binarybeast.com>
     * @var int
     */
    const RESULT_ERROR                          = 500;
    /**
     * <b>API Result Code</b><br />
     * The search filter value is too short, please include at least 2 characters
     * @var int
     */
    const RESULT_SEARCH_FILTER_TOO_SHORT        = 601;
    /**
     * <b>API Result Code</b><br />
     * Provided user_id does not exist
     * @var int
     */
    const RESULT_INVALID_USER_ID                = 604;
    /**
     * <b>API Result Code</b><br />
     * Invalid tournament id
     * @var int
     */
    const RESULT_TOURNAMENT_NOT_FOUND           = 704;
    /**
     * <b>API Result Code</b><br />
     * The given team and tournament do not belong to eachother
     * @var int
     */
    const TEAM_NOT_IN_TOURNEY_ID                = 705;
    /**
     * <b>API Result Code</b><br />
     * Invalid team id
     * @var int
     */
    const TOURNEY_TEAM_ID_INVALID               = 706;
    /**
     * <b>API Result Code</b><br />
     * Invalid match id
     * @var int
     */
    const RESULT_MATCH_ID_INVALID               = 708;
    /**
     * <b>API Result Code</b><br />
     * Invalid match_game id
     * @var int
     */
    const RESULT_MATCH_GAME_ID_INVALID          = 709;
    /**
     * <b>API Result Code</b><br />
     * Your tournament does not have enough confirmed teams to fill enough groups to match $group_count<br />
     * You need at least 2 teams per {@link BBTournament::$group_count}
     * @var int
     */
    const RESULT_NOT_ENOUGH_TEAMS_FOR_GROUPS    = 711;
    /**
     * <b>API Result Code</b><br />
     * The current tournament status does not allow that action<br />
     * Could be anything from trying to add / remove teams in an active tournament, or trying to start
     *  a tournament that has already finished
     * @var int
     */
    const RESULT_TOURNAMENT_STATUS              = 715;

    /**
     * Library constructor
     * 
     * You can either provide a BBConfiguration instance, or an api_key as the argument
     * 
     * @param string|BBConfiguration
     *      You can pass in either the api_key, or a customized BBConfiguration object
     * 
     * @return BinaryBeast
     */
    function __construct($api_key = null) {
        //Determine the path to the library directory
        $this->lib_path = str_replace('\\', '/', dirname(__FILE__ )) . '/lib/';

        /**
         * Make sure this server supports json and cURL
         * Static because there's no point in checking for each instantiation
         */
        self::$server_ready = self::check_server();

        //Execute the static "constructor", but only for the first instantiation
        if(self::$first) self::init($this);

        //Use a custom BBConfiguration object
        if($api_key instanceof BBConfiguration) $this->config = $api_key;

        //Store default configuration
        else {
            $this->config = new BBConfiguration();

            //Use a custom api_key
            if(!is_null($api_key)) $this->config->api_key = $api_key;
        }
    }

    /**
     * Psuedo-static constructor, this method only once,
     * only the first time this class is instantiated
     * 
     * It's used to pre-load some of the core library classes that
     * we are most likely to use - most other libraries (for example BBLegacy, BBTournament),
     *  are only loaded as they are used
     * 
     * @param BinaryBeast $bb       Since it's a static method, we need a reference to the instance
     * 
     * @return void
     */
    private static function init(&$bb) {
        $bb->load_library('BBConfiguration');
        $bb->load_library('BBHelper');
        $bb->load_library('BBSimpleModel');
        $bb->load_library('BBModel');
        $bb->load_library('BBCache');

        //Next instantiation will know not to call this method
        self::$first = false;
    }

    /**
     * Checks to make sure this server supports json and cURL
     * 
     * @return boolean
     */
    private static function check_server() {
        return function_exists('json_decode') && function_exists('curl_version');
    }

    /**
     * Alternative Email/Password authentication
     * 
     * This library defaults to using an api_key, but you can 
     * alternatively use this method to log in using 
     * a more traditional email and password
     * 
     * @param string $email
     * @param string $Password
     * @param bool   $test
     * 
     * @return boolean
     */
    public function login($email, $password, $test = false) {
        $this->email    = $email;
        $this->password = $password;

        if($test) return $this->test_login();
        return true;
    }

    /**
     * Determines wether or not the provided api_key or email/password
     * are valid
     * 
     * It calls the Ping.Ping.Ping service, which is NOT
     * an anonymously accessible service, therefore if authentication
     * fails, we will easily be able to determine that, as the
     * result code would be reflect it
     * 
     * If you want the result code directly instead of a boolean,
     *  pass in true for the argument
     * 
     * @param bool  $get_code       Defaults to false, return the result code instead of a boolean
     * 
     * @return boolean
     */
    public function test_login($get_code = false) {
        $result = $this->call('Ping.Ping.Ping');
        return $get_code ? $result->result : $result->result == 200;
    }

    /**
     * If SSL Host verification causes any issues, call this method to disable it
     * @return void
     */
    public function disable_ssl_verification() {
        $this->verify_ssl = false;
    }

    /**
     * Re-enable ssl verification
     * @return void
     */
    public function enable_ssl_verification() {
        $this->verify_ssl = true;
    }
    /**
     * Returns the current ssl-verification mode, true = enabled, false = disabled
     * @return boolean
     */
    public function ssl_verification() {
        return $this->verify_ssl;
    }

    /**
     * Executes an API service, and returns the raw unprocessed value
     *
     * It can be useful if your PHP can't handle JSON, but you want to use it
     * to feed json results to a local ajax request or something similiar
     *
     * Otherwise, it's just used internally so ignore it lol
     * 
     * @see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_call_raw
     *
     * @param @see BinaryBeast::Call()
     *
     * @return string
     */
    public function call_raw($svc, $args = null, $return_type = null) {
        //This server isn't ready for processing API requests
        if (!self::$server_ready) {
            return self::get_server_ready_error();
        }

        //Add the service to the arguments, and the return type
        if(is_null($args)) $args = array();
        $args['api_return']     = is_null($return_type) ? 'json' : $return_type;
        $args['api_service']    = $svc;

        /*
         * Use a more readable snake_case argument/variable format
         * BinaryBeast uses CamelCase at its own back-end, and it's too late to change that now,
         */
        $args['api_use_underscores'] = true;

        //Authenticate ourselves
        if (!is_null($this->config->api_key)) {
            $args['api_key'] = $this->config->api_key;
        }

        /*
         * User alternative authentication method of email + password
         */
        else if (!is_null($this->email)) {
            $args['api_email']      = $this->email;
            $args['api_password']   = $this->password;
        }
        
        //Who you gonna call?
        return $this->call_curl(http_build_query($args));
    }

    /**
     * Make a service call to the remote BinaryBeast API
	 * 
     * @param string $svc    		Service to call (ie Tourney.TourneyCreate.Create)
     * @param array $args     		Arguments to send
	 * @param int $ttl				If you configured the BBCache class, use this to define how many minutes this result should be cached
	 * @param int $object_type		For caching objects: see BBCache::type_ constants
     * @param mixed $object_id		For caching objects: The id of this the object, like tourney_id or tourney_team_id
     * @return object
     */
    public function call($svc, $args = null, $ttl = null, $object_type = null, $object_id = null) {
        //This server does not support curl or fopen
        if (!self::$server_ready) {
            return self::get_server_ready_error();
        }

        //Cache?
        if(!is_null($ttl)) {
            if($this->cache() !== false) {
                return $this->cache->call($svc, $args, $ttl, $object_type, $object_id);
            }
        }

        //GOGOGO! grab the raw result (call_raw), and parse it through decode for json decoding + (object) casting
        $response = $this->decode($this->call_raw($svc, $args));

        //Store the latest result code, so developers can have easy access to it in $bb->result[_friendly]
        $this->set_result($response, $svc, $args);

        //Success!
        return $response;
    }


    /**
     * Store an error into $this->error, developers can refer to it
     * as $tournament|$match|etc->error()
     * 
     * In order to standardize error values, it accepts arrays, or strings
     * but if you provide a string, it is converted into array('error_message'=>$in)
     * 
     * This method will return the new error value, in case we
     * decided to reformat it / add to it, so that model classes
     * can be sure to stash the same error set here
     * 
     * @param mixed     $error          Error value to save
     * @param string    $class          Name of the class setting this error
     * @return object
     */
    public function set_error($error = null, $class = null) {

        //Convert arrays into objects
        if(is_array($error)) $error = (object)$error;

        //Either store it into a new array, or add some values to the input
        if(!is_null($error)) {
            //Use default values if not provided
            if(is_null($class)) $class = get_called_class();

            //Compile an array using the input
            $details = (object)array('class' => $class);

            //For existing objects, simply add our details
            if(is_object($error)) $error = (object)array_merge((array)$details, (array)$error);

            //For everything else, add the input into a new object
            else $error = (object)array_merge((array)$details, array('error_message' => $error));
        }

        //Stash it!
        $this->last_error = $error;

        //Add it to our error history log
        if(!is_null($error)) $this->error_history[] = $this->last_error;

        //Return the new value we came up with so that models store it the same way we did here
        return $this->last_error;
    }

    /**
     * Modal classes call this to clear out any prior error results
     * @return void
     */
    public function clear_error() {
        $this->set_error(null);
    }

    /**
     * Stores the result code from the latest API Request, so developers can
     * always access the latest result in $bb->result, or $bb->result(), or $bb->result_friendly etc
     * 
     * @param object $result    A reference to the API's response
     * @param string $svc       Service name called
     * @param array  $args      Arguments used
     * @return void
     */
    private function set_result(&$result, $svc, $args) {
        //Stash the new values, and try to translate the result code to be more readable
        $this->last_result = isset($result->result) ? $result->result : false;
        $this->last_friendly_result = BBHelper::translate_result($this->last_result);

        //Store it in the result history array too
        $this->result_history[] = (object)array('result' => $this->last_result, 'friendly' => $this->last_friendly_result, 'svc' => $svc, 'args' => $args);
    }

    /**
     * Simply returns an array with a Result to return in case no method could be determined
     *
     * @return object {int result, string Message}
     */
    private static function get_server_ready_error() {
        return (object)array('result' => false,
            'message' => 'Please verify that both cURL and json are enabled your server!'
        );
    }

    /**
     * Make a service call to the BinaryBeast API via the cURL library
     *
     * @access private
     *
     * @param string URL encoded arguments
     *
     * @return string
     */
    private function call_curl($args) {
        //Get a curl instance
        $curl = curl_init();

        //Set the standard curl options
        curl_setopt($curl, CURLOPT_URL, self::$url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->verify_ssl ? 2 : 0);
        //
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
        //
        curl_setopt($curl, CURLOPT_USERAGENT, 'BinaryBeast API PHP: Version ' . self::API_VERSION);

        //Execute, and return a parsed result
        $result = curl_exec($curl);

        //SSL Verification failed
        if (!$result) {
            $this->set_error(array('error_message' => 'cURL call failed', 'curl_error' => curl_error($curl), 'curl_code' => curl_errno($curl)));
            return json_encode((object)array('result' => false, 'error' => $this->last_error));
        }

        //Success!
        return $result;
    }

    /**
     * Converts the returned string from the API, and decodes it into a native PHP object
     *
     * @param string BinaryBeast Result value
     *
     * @return object
     */
    private function decode($result) {
        return (object)json_decode($result);
    }

    /**
     * Allows us to create new model classes as if accessing attributes, avoiding the
     * need to call it as a function, so we can get a tournament instance for example, like this:
     * $new_tour = $bb->tournament; which translates to:
     * $new_tour = $bb->touranment(); - creating a new blank tournament
     * 
     * @param string $name
     */
    public function &__get($name) {
        //Define a list of acceptable methods that are allowed to be called as property
        if(in_array($name, array('tournament', 'team', 'round', 'match', 'match_game', 'map', 'country', 'game', 'race', 'legacy', 'cache'))) {
            return $this->{$name}();
        }

        //Invalid access
        $this->set_error('Attempted to access invalid property "' . $name . '"');
        return $this->ref(false);
    }

    /**
     * Returns a new BBTournament model class
     * Use this to create a new tournament with save(), or load an existing
     *      tournament by providing the id now, or when you call load()
     * 
     * @param string $tourney_data    Optionally pre-define the tournament to load
     * @return BBTournament
     */
    public function &tournament($tourney_data = null) {
		$tour = $this->get_model('BBTournament', $tourney_data);
        return $tour;
    }
    /**
     * Returns a new BBTeam model class
     * 
     * @param object|intstring $team_data    Optionally pre-define the team's data or id
     * @return BBTeam
     */
    public function &team($team_data = null) {
		$team = $this->get_model('BBTeam', $team_data);
        return $team;
    }
    /**
     * Returns a new BBRound model class
     * 
     * @param object $round_data    Optionally the round data
     * @return BBRound
     */
    public function &round($round_data = null) {
		$round = $this->get_model('BBRound', $round_data);
        return $round;
    }
	/**
	 * Returns a new BBMatch model class
	 *	Only useful if you know the match_id, it otherwise can't be saved / reported
	 *	without a tournament associated with it
	 * 
	 * If you DO however know the match id, you can use this to load the match,
	 *	and then to load it's touranment by accessing $match->tournament or $match->tournament()
	 * 
	 * @param int|object $match_data
	 * @return BBMatch
	 */
	public function &match($match_data = null) {
		$match = $this->get_model('BBMatch', $match_data);
		return $match;
	}
	/**
	 * Returns a new BBMatch model class
	 *	Only useful if you know the match_id, it otherwise can't be saved / reported
	 *	without a tournament associated with it
	 * 
	 * If you DO however know the match id, you can use this to load the match,
	 *	and then to load it's touranment by accessing $match->tournament or $match->tournament()
	 * 
	 * @param int|object $game      Either the tourney_match_game_id or the game data
	 * @return BBMatchGame
	 */
	public function &match_game($game_data = null) {
		$game = $this->get_model('BBMatchGame', $game_data);
		return $game;
	}
    /**
     * Returns a BBMap simple_model class
     * @return BBMap
     */
    public function &map() {
        return $this->get_simple_model('BBMap');
    }
    /**
     * Returns a BBCountry simple_model class
     * @return BBCountry
     */
    public function &country() {
        return $this->get_simple_model('BBCountry');
    }
    /**
     * Returns a BBGame simple_model class
     * @return BBGame
     */
    public function &game() {
        return $this->get_simple_model('BBGame');
    }
    /**
     * Returns a BBRace simple_model class
     * @return BBGame
     */
    public function &race() {
        return $this->get_simple_model('BBRace');
    }
    /**
     * Returns a cached instance of BBLegacy, for calling old 
     *  service wrappers that were defined in version 2.7.5 of this API Library
     * 
     * @return BBLegacy
     */
    public function &legacy() {
        //Already instantiated
        if(!is_null($this->legacy)) return $this->legacy;

        //Make sure that BBLegacy.php is included
        $this->load_library('BBLegacy');

        //Return a reference to a newly instantated BBLegacy class
        $this->legacy = new BBLegacy($this);
        return $this->legacy;
    }

    /**
     * Returns a cached instance of BBCache
     *  returns null if BBCache could not connect to the database, or had
     *  any authentication ererors
     * 
     * @return BBCache
     */
    public function &cache() {
        //Already instantiated
        if(!is_null($this->cache) || $this->cache === false) return $this->cache;

        //Instantiate a new BBCache objec into $this->cache (will automatically try to connect)
        $this->cache = new BBCache($this, $this->config);

        //If BBCache can't connect, simply set it to false
        if(!$this->cache->connected()) $this->cache = false;
        return $this->cache;
    }

    /**
     * To avoid the overhead of include|require_once.. we use this method
     * to load libraries on demand, but we use class_exists first to see
     * if we actually need to load the file
     * 
     * @param string        $library        Full library class name / file name
     * @param string        $lib_path       Optionally override the default /lib 
     *      Usefulf or loading extended model classes from a specific directory
     */
    private function load_library($library, $lib_path = null) {

        //If not defined, default to /lib 
        if(is_null($lib_path)) $lib_path = $this->lib_path;

        //require(), only if the class isn't already defined
        if(!class_exists($library)) {
            //Does the file even exist??
            $path = $lib_path . $library . '.php';
            if(file_exists($path)) {
                require($path);
            }

            //Invalid filename
            else {
                $this->set_error("Unable to load file \"$path\" - file does not exist");
                return false;
            }
        }

        //Auto load any dependent libraries
        if(isset(self::$auto_load_libraries[$library])) {
            foreach(self::$auto_load_libraries[$library] as $child_library) {
                if(!$this->load_library($child_library)) return false;
            }
        }

        //Success!
        return true;
    }

    /**
     * returns a reference to a locally cached instance of a simple model
     * 
     * Simple models do not have exclusive data / offer methods for saving data
     *      to the API - all they do is wrap services
     * 
     * Therefore we can afford to keep returning a cached version
     * 
     * Returns false if either the library could not be loaded, or it was an instance of BBModel
     * 
     * Note that when provding the $model name, this method expects the FULL model name
     * 
     * @param string $model
     * @return BBSimpleModel
     */
    private function &get_simple_model($model) {
        //Already cached
        if(isset($this->models_cache[$model])) return $this->models_cache[$model];

        //Store the new instance returned by get_model directly into the local model cache
        if(!$this->models_cache[$model] = $this->get_model($model, null)) return $this->ref(false);

        //Cache it, and return the cached version
        return $this->models_cache[$model];
    }
    /**
     * Returns a newly instantiated modal class, either returning
     * 
     * @param string  $model        Full name of the model (example: BBTournament, BBMatchGame)
     * @param string  $data         Optional data to send to the new model, like a result set or id value
     * @return BBModal              False if the $model name is invalid
     */
    private function get_model($model, $data = null) {
        /**
         * load_library was not able to find the file for this class, so we return false
         * Note: We don't have to worry about setting an error, load_library does that for us
         */
        if(!$this->load_library($model)) {
            return false;
        }

        //Try to honor any defined extensions
        $extension = null;
        if(isset($this->config->models_extensions[$model])) {
            $extension      = $this->config->models_extensions[$model];

            //Load it!
            if(!is_null($extension)) {
                if(!$this->load_library($extension, $this->lib_path . '/custom/')) {
                    return false;
                }

                //Set $model to the extension, which is used to instantiate the object
                $model = $extension;
            }
        }

        //Load it first, so we can test it to make sure it's actually a model (we only want to return models)
        $instance = new $model($this, $data);

        //Only return valid model classes
        if(!($instance instanceof BBSimpleModel)) {
            $this->set_error("$model is not a BBModel class! Only classes that extend BBModel or BBSimpleModel can be returned from get_model");
            return false;
        }

        //Success! Now return the instance we created earlier
        return $instance;
    }

    /**
     * Call a legacy wrapper method
     * 
     * Intercept attempts to call missing methods that may have previous been
     * defined in 2.7.2- or earlier version of this library
     * 
     * In which case, we'll use BBLegacy to do the work, since I want to keep this class 
     * as clean as possible
     */
    public function __call($name, $args) {

        //Get the legacy API class in hopes that this method is an old wrapper method from an older library version
        $legacy = $this->legacy();

        /**
         * This method exists in BBLegacy, call it, store the result code,
         * then return the result direclty
         */
        if(method_exists($legacy, $name)) {
            $result = call_user_func_array(array($legacy, $name), $args);
            $this->set_result($result, "BBLegacy::$name()", $args);
            return $result;
        }

        //Failure!
        $this->set_error('Method name ' . $name . ' does not exist!');
        return false;
    }

    /**
     * Allows us to return directly values (such as null / false) from
     *  within methods that return references
     * 
     * @param mixed $value
     * @return mixed
     */
    public function &ref($value) {
        $ref = $value;
        return $ref;
    }
	
	/**
	 * Attempt to clear cache (keyed by any combination of service name[s], object_type, and object_id)
	 *	without having to worry about wether or not BBCache is setup
	 * 
	 * @param strinig|array $svc
	 * @param string		$object_type
	 * @param mixed			$object_id
	 * @return boolean
	 */
	public function clear_cache($svc = null, $object_type = null, $object_id = null) {
		//BBCache not configured
		if(is_null($this->cache())) return false;
        if($this->cache === false) return false;

		//Pass it straight along to BBCache
		return $this->cache->clear($svc, $object_type, $object_id);
	}
    /**
     * Attempt to delete all expired cache records from the local database
     * 
     * doing this through the main library class allow you to call the method
     *  without first having to make sure that the cache class exists / is configured
     * 
     * @return boolean
     */
    public function clear_expired_cache() {
		//BBCache not configured
		if(is_null($this->cache())) return false;
        if($this->cache === false) return false;

        //Delegate!
        return $this->cache->clear_expired();
    }
}

?>