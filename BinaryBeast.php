<?php

/**
 * Entry point for the BinaryBeast PHP API Library
 * Relies on everything included in lib/*.php
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

    /**
     * URL to send API Requests
     */
    private $url = 'https://api.binarybeast.com/';

    /**
     * Calculated path for loading libraries on-demand
     * @deprecated - nevermind, decided to auto load them anyway: is easier
     */
    //private $lib_path = null;

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
     * Used for two purposes:  
     *      1: During __call to check the $name against a list of models that we can auto load
     *      2: To define a list of dependent classes to automatically load when a class is included
     * 
     * Note that the names of the classes in here are the simplified versions, since
     * @example tournament represents BBTournament
     * @example match_game respresnts BBMatchGame
     */
    private static $models = array(
        'tournament'    => array('model', 'match', 'match_game', 'team', 'round'),
        'map'           => array('simple_model'),
        'game'          => array('simple_model'),
        'country'       => array('simple_model'),
    );

    /**
     * Store the result codes / values for the previous
     * API Request response
     */
    public $last_error;
    public $last_result;
    public $last_friendly_result;

    /**
     * Store an instance of BBLegacy, it's only even
     * included() on demand
     * @var BBLegacy
     */
    private $legacy = null;

    /**
     * cache instances of BBSimpleModel classes
     */
    private $map;
    private $country;
    private $game;

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

        /**
         * Make sure some of the core libraries are pre-loaded, like
         * BBHelper and BBResult
         */
        $this->load_library('helper');
        $this->load_library('result');
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
        $args['api_return']     = is_null($return_type) ? $this->return : $return_type;
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
     * @see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_call
     * 
     * New in 3.0.0 - this method now by default, automatically returns the result wrapped in a new BBResult class
     * This can be overridden however, by setting the third paramater to false
     * 
     * I suggest reading the documentation in BBResult.php to see what it does, but in short:
     * it makes accessing the values returned a lot more flexibile and forgiving, basically
     * allowing you to refer to values within it in any format you'd like (ie underscore_keys or camelCasee)
     *
     * @param string    Service to call (ie Tourney.TourneyCreate.Create)
     * @param array     Arguments to send
     * @param wrapped   True by default, wraps API results in the BBResult class - which allows flexibile accessability, custom iteration etc etc
     *
     * @return BBResult
     */
    public function call($svc, $args = null, $wrapped = true) {
        //This server does not support curl or fopen
        if (!self::$server_ready) {
            return self::get_server_ready_error();
        }

        //GOGOGO! grab the raw result (call_raw), and parse it through decode for json decoding + (object) casting
        $response = $this->decode($this->call_raw($svc, $args));

        //Store the latest result code, so developers can have easy access to it in $bb->result[_friendly]
        $this->set_result($response);

        //Success!  Now return it either raw, or wrapped in a new BBResult()
        return $wrapped ? new BBResult($response) : $response;
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
     * @param mixed $error
     * @return array
     */
    public function set_error($error = null) {
        //Convert objecst into arrays
        if(is_object($error)) $error = (array)$error;

        /*
         * If it's already an array - save it as-is
         * Otherwise, save an error containing the value provided as the property 'error_message'
         */
        if(!is_null($error)) {
            $this->last_error = is_array($error)
                ? $error
                : array('error_message' => $error);
        }
        //Sent a blank error, so we'll delete any previous errors now
        else $this->last_error = null;

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
     * @return void
     */
    private function set_result(&$result) {
        $this->last_result = isset($result->result) ? $result->result : false;
        $this->last_friendly_result = BBHelper::translate_result($this->last_result);
    }

    /**
     * Simply returns an array with a Result to return in case no method could be determined
     *
     * @return object {int result, string Message}
     */
    private static function get_server_ready_error() {
        return array('result' => false,
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
        curl_setopt($curl, CURLOPT_URL, $this->url);
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
            return json_encode(array('result' => curl_errno($curl), 'error_message' => curl_error($curl)));
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
    public function __get($name) {
        //attempting to access a valid model?
        $model = strtolower($name);
        if(key_exists(strtolower($model), self::$models)) {
            return $this->{$model}();
        }

        //Invalid access
        return null;
    }
    /**
     * Returns a new BBTournament model class
     * You can optionally pre-define the tourney_id in the constructor
     * It's nice to have this be a real public method, to avoid the slight
     * overhead of having to go through __get and __call
     * 
     * @param string $tourney_id    Optionally pre-define the tournament to load
     * @return BBTournament
     */
    public function tournament($tourney_id = null) {
        return $this->get_model('tournament', $tourney_id, false);
    }
    /**
     * Returns a new BBMap simple_model class
     * @return BBMap
     */
    public function &map() {
        return $this->get_simple_model('map');
    }
    /**
     * Returns a new BBCountry simple_model class
     * @return BBCountry
     */
    public function &country() {
        return $this->get_simple_model('map');
    }
    /**
     * Returns a new BBGame simple_model class
     * @return BBGame
     */
    public function &game() {
        return $this->get_simple_model('game');
    }
    
    /**
     * To avoid the overhead of include|require_once.. we use this method
     * to load libraries on demand, but we use class_exists first to see
     * if we actually need to load the file
     * 
     * Be sure that when you call this method, you provide the simplified
     * libary name... for example ask for round, not BBRound..
     * and ask for match_game, not BBMatchGame
     * 
     * @param string $library
     * @return string       Returns the full class_name of the actual class and file name
     */
    private function load_library($library) {
        //Convert the library name to the full class name
        $class = $this->full_library_name($library);

        //require(), only if the class isn't already defined
        if(!class_exists($class)) {
            require("lib/$class.php");
        }

        //Load all dependents
        if(key_exists($library, self::$models)) {
            if(is_array(self::$models[$library])) {
                foreach(self::$models[$library] as $child) {
                    $this->load_library($child);
                }
            }
        }

        //Return the class_name we converted
        return $class;
    }
    /**
     * Converts a simplified library name to the full
     * class name (@example tournament => BBTournament, match_game => BBMatchGame)
     * @param string $library
     */
    private function full_library_name($library) {
        /*
         * First replace underscores with spaces, so that ucwords
         * will capitalize each word.. then return it
         * with BB prepended, and the spaces removed
         */
        return 'BB' . str_replace(' ', '', ucwords(
            str_replace('_', ' ', (
                strtolower($library)
            ))
        ));
    }

    /**
     * Mode like get_model, except this method cached
     * the instances locally before returning
     * @param string $model
     * @return BBSimpleModel
     */
    private function &get_simple_model($model) {

        //Already cached
        if(!is_null($this->{$model})) return $this->{$model};

        //Use get_model to do two things:  auto load BBSimpleModel, and to return a new instance of $model
        $this->{$model} = $this->get_model($model, null, true);

        //Success! return a reference to the newly instnatated cached simple model
        return $this->{$model};
    }
    /**
     * Returns a newly instantiated modal class, either returning
     * 
     * @param string  $model        Base name of the model, for example BBTournament is just "touranment"
     * @param string  $data         Optional data to send to the new model, like a result set or id value
     * @param boolean $simple       False by default - autoload BBSimpleModel instead of BBModel if true
     * @param boolean $direct       Return the class direclty instead of an instance - allows model() methods to instantiate manually
     * @return BBModal
     */
    private function get_model($model, $data = null, $simple = false) {
        //Make sure the base BBModel class is available
        $this->load_library($simple ? 'simple_model' : 'model');

        /**
         * Use load_library to make sure the class is available,
         * and it will also return to us the real class_name we can use
         * while creating a new instance
         */
        $class_name = $this->load_library($model);

        //Success! Now return a new instance
        return new $class_name($this, $data);
    }

    /**
     * Call a legacy wrapper method
     * 
     * Intercept attempts to call missing methods that may have previous been
     * defined in 2.7.7- or earlier version of this library
     * 
     * In which case, we'll use BBLegacy to do the work, since I want to keep this class 
     * as clean as possible
     * 
     * If we can find the method to call, we'll go ahead and use the new
     * BBResult wrapper to wrap the results for a more flexibily accessible result set
     */
    public function __call($name, $args) {

        //Get the legacy API class in hopes that this method is an old wrapper method from an older library version
        $legacy = $this->get_legacy();

        /**
         * This method exists in BBLegacy, call it, store the result code,
         * then return the result direclty
         */
        if(method_exists($legacy, $name)) {
            $result = call_user_func_array(array($legacy, $name), $args);
            $this->set_result($result);
            return $result;
        }

        //Failure!
        $this->set_error('Method name ' . $name . ' does not exist!');
        return false;
    }

    /**
     * Returns a reference to the locally stored version of BBLegacy
     * The reason we setup a method for this, is we want to be able to quickly
     * grab a reference if already stored.. 
     * 
     * and if it isn't already stored: include the file, instantiate it with the local
     * api_key or username and email, save a reference, then return that reference
     * 
     * That allows us to keep BBLegacy as a load-on-demand class only
     * 
     * aka this returns the version 2.7.2 API Library 
     * 
     * @return BBLegacy
     */
    private function &get_legacy() {
        //Already instantiated
        if(!is_null($this->legacy)) return $this->legacy;

        //Make sure that BBLegacy.php is included
        $this->load_library('legacy');

        //Return a reference to a newly instantated BBLegacy class
        $this->legacy = new BBLegacy($this);
        return $this->legacy;
    }
}

?>