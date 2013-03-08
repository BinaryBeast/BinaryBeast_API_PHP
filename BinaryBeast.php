<?php

/**
 * Entry point for the BinaryBeast PHP API Library
 * 
 * *********************** READ ME! ***********************
 * 
 * Developers: If you want to take advantage of the BBCache library, you must
 *      set a few things up first:
 * 
 *      1) Make sure that your PHP installation has PDO_{your-database-library} available
 *          For MySQL, you need PDO_MYSQL
 *          For Postgres, you need PDO_PGSQL
 *          etc, etc
 *      2) You must define the values in the BBCache::config array (lib/BBCache.php)
 * 
 * 
 * And that's all you have to do - as long as the values you set were valid, 
 *  BBCache will handle everything for you autmoatically - each BBModel class
 *  has a public TTL constants that BBCache uses when creating the cache, and
 *  offers methods for manually clearing them too
 * 
 * 
 * I HIGHLY recommend you take advantage of this, as it will drastically cut down
 *  on the number of API requests that this library is forced to make, which 
 *  means faster execution for you, and less burden on our API
 * 
 * *********************** READ ME! ***********************
 * 
 * This is the 3rd release of our public library written in PHP.  The biggest change from the 
 * previous being that it now takes on a more object-oriented approach in exchange for
 * the more procedural feel of the former version
 * 
 * However we have included all of the originally wrapper methods defined previously, and 
 * moved them into a new library class called BBLegacy - so you confidently upgrade your code
 * without worrying about breaking anything
 * 
 * This class is depedent on classes included in the /lib directory, so please make sure
 * to include both BinaryBeast.php, and /lib
 * 
 * This class in fact, does not offer any wrapper methods at all any more, you should be using
 * models for everything.   For example use BBGame even for loading a list of games, and
 * BBCountry for listing / searching countries, and BBMap for listing maps for specific games
 * 
 * As for the object oriented approach, note that most values returned are instances of BBModel. 
 * Note for example $tour = $bb->tournament('x12345'); - this will return an instance of BBTournament, 
 * and it will autoamtically assume it's for tournament id x12345
 * 
 * Note that BBModel classes do not automatically load any data from the API, it is actually
 * loaded on-demand.  So until you try to access a property from $tour, it will be blank
 * 
 * But the moment you do try to access $tour->title for example, it will first execute the 
 *  API request required to load the tournament information, then try to return that value
 *  to you
 * 
 * Same goes for lists of child models within models, like BBRound instances within BBTournament,
 *  $tour->rounds is an array of rounds within that tournament, but the list is not populated
 *  until you try to access it
 * 
 * We will be releasing new documntnation on the site soon,
 *      meanwhile, please direct all questions to contact@binarybeast.com
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * 
 * @version 3.0.0
 * @date 2013-02-04
 * @author Brandon Simmons <contact@binarybeast.com>
 */
class BinaryBeast {

    //URL to send API Requests
    private static $url = 'https://api.binarybeast.com/';

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
     */
    public $last_error;
    public $last_result;
    public $last_friendly_result;
    /**
     * When a new error or result is set, we stash previous values in these arrays
     */
    public $error_history   = array();
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
     * Really stupid way of getting around the ridiculous error when trying
     * to return null in &__get, so we use $bb->ref() to set this value and return it
     */
    private $ref = null;

    /**
     * A few constants to make a few values a bit easier to read / use
     */
    const API_VERSION = '3.0.0';
    //
    const BRACKET_GROUPS    = 0;
    const BRACKET_WINNERS   = 1;
    const BRACKET_LOSERS    = 2;
    const BRACKET_FINALS    = 3;
    const BRACKET_BRONZE    = 4;
    //
    const ELIMINATION_SINGLE = 1;
    const ELIMINATION_DOUBLE = 2;
    //
    const TOURNEY_TYPE_BRACKETS = 0;
    const TOURNEY_TYPE_CUP      = 1;
    //
    const SEEDING_RANDOM        = 'random';
    const SEEDING_SPORTS        = 'sports';
    const SEEDING_BALANCED      = 'balanced';
    const SEEDING_MANUAL        = 'manual';
    //
    const REPLAY_DOWNLOADS_DISABLED         = 0;
    const REPLAY_DOWNLOADS_ENABLED          = 1;
    const REPLAY_DOWNLOADS_POST_COMPLETE    = 2;
    //
    const REPLAY_UPLOADS_DISABLED   = 0;
    const REPLAY_UPLOADS_OPTIONAL   = 1;
    const REPLAY_UPLOADS_MANDATORY  = 2;
    /**
     * Team int "status" values
     */
    const TEAM_STATUS_UNCONFIRMED           = 0;
    const TEAM_STATUS_CONFIRMED             = 1;
    const TEAM_STATUS_BANNED                = -1;
    /**
     * Result code values
     */
    const RESULT_SUCCESS                        = 200;
    const RESULT_NOT_LOGGED_IN                  = 401;
    const RESULT_AUTH                           = 403;
    const RESULT_NOT_FOUND                      = 404;
    const RESULT_API_NOT_ALLOWED                = 405;
    const RESULT_LOGIN_EMAIL_INVALID            = 406;
    const RESULT_EMAIL_UNAVAILABLE              = 415;
    const RESULT_INVALID_EMAIL_FORMAT           = 416;
    const RESULT_PENDING_ACTIVIATION            = 418;
    const RESULT_LOGIN_USER_BANNED              = 425;
    const RESULT_PASSWORD_INVALID               = 450;
    const GAME_CODE_INVALID                     = 461;
    const INVALID_BRACKET_NUMBER                = 465;
    const RESULT_DUPLICATE_ENTRY                = 470;
    const RESULT_ERROR                          = 500;
    const RESULT_SEARCH_FILTER_TOO_SHORT        = 601;
    const RESULT_INVALID_USER_ID                = 604;
    const RESULT_TOURNAMENT_NOT_FOUND           = 704;
    const TEAM_NOT_IN_TOURNEY_ID                = 705;
    const TOURNEY_TEAM_ID_INVALID               = 706;
    const RESULT_MATCH_ID_INVALID               = 708;
    const RESULT_MATCH_GAME_ID_INVALID          = 709;
    const RESULT_NOT_ENOUGH_TEAMS_FOR_GROUPS    = 711;
    const RESULT_TOURNAMENT_STATUS              = 715;

    /**
     * Constructor - import the API Key
     * 
     * If you want to use an email / password instead, you'd use login, like this:
     *  @example $bb = new BinaryBeast();
     *      $bb->login('name@domain.tld', 'your_password');
     *
     * @param string		Optional: your api_key
     */
    function __construct($api_key = null) {
        //Cache the api key
        $this->api_key = $api_key;

        /**
         * Make sure this server supports json and cURL
         * Static because there's no point in checking for each instantiation
         */
        self::$server_ready = self::check_server();

        //Execute the static "constructor", but only for the first instantiation
        if(self::$first) self::init($this);
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
        if (!is_null($this->api_key)) {
            $args['api_key'] = $this->api_key;
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
     * @return array
     */
    public function set_error($error = null, $class = null) {

        //Convert objects into arrays
        if(is_object($error)) $error = (array)$error;

        //Either store it into a new array, or add some values to the input
        if(!is_null($error)) {
            //Use default values if not provided
            if(is_null($class)) $class = get_called_class();

            //Compile an array using the input
            $details = array('class' => $class);

            //For existing arrays, simply add our details
            if(is_array($error)) $error = array_merge($details, $error);

            //For everything else, add the input into a new array
            else                 $error = array_merge($details, array('error_message' => $error));
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
        $this->result_history[] = array('result' => $this->last_result, 'friendly' => $this->last_friendly_result, 'svc' => $svc, 'args' => $args);
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
        return $this->ref($this->set_error('Attempted to access invalid property "' . $name . '"'));
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
     *  service wrappers that were defined in version 2.7.2 of this API Library
     * 
     * @return BBLegacy
     */
    private function &legacy() {
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
        $this->cache = new BBCache($this);

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
     * @return boolean      False if the library name is invalid
     */
    private function load_library($library) {

        //require(), only if the class isn't already defined
        if(!class_exists($library)) {
            //Does the file even exist??
            if(file_exists("lib/$library.php")) {
                require("lib/$library.php");
            }

            //Invalid library name
            else {
                $this->set_error("Unable to load file \"lib/$library.php\" - file does not exist");
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
     * Converts a simplified library name to the full
     * class name (@example tournament => BBTournament, match_game => BBMatchGame)
     * @param string $library
     */
    private function full_library_name($library) {
        //replace underscores with spaces, capitalize each word, then "implode" 
        return 'BB' . str_replace(' ', '', ucwords(
            str_replace('_', ' ', (
                strtolower($library)
            ))
        ));
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
        $this->ref = $value;
        return $this->ref;
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