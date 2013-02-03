<?php

/**
 * Entry point for the BinaryBeast PHP API Library
 * Relies on everything included in lib/*.php
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */

//The new API version is adopts a more OOP approach, so we have a few data modal classes to import
include_once('lib/BBHelper.php');
include_once('lib/BBModel.php');
include_once('lib/BBTournament.php');
include_once('lib/BBRound.php');
include_once('lib/BBTeam.php');
include_once('lib/BBMatch.php');
include_once('lib/BBMatchGame.php');

/**
 * This class contains the method for interaction with the BinaryBeast API
 *
 * By using this API Class, you are agreeing to the Terms and Conditions
 * The terms can be found in the file "Terms.txt" included with this file
 * 
 * Example use, let's grab a list of countries that have the word 'king' in it, there are two ways to do it...
 *  $result = $bb->call('Country.CountrySearch.Search', array('country' => 'king'));
 *  $result = $bb->country_search('king');
 *      if($result->result == 200) foreach($result->countries as $country) {
 *          echo $country . '<br />';
 *      }
 * 
 * We will be releasing new documntnation on the site soon,
 * meanwhile, please direct all questions to contact@binarybeast.com
 * 
 * @version 3.0.6
 * @date 2013-02-02
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
     * Cache the instance of BBHelper, there's no need to
     * instantiate it more than once
     * @var BBHelper
     */
    private static $helper = null;

    /**
     * A few constants to make a few values a bit easier to read / use
     */

    const API_VERSION = '3.0.6';
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
    const INVALID_BRACKET_NUMBER                = 465;
    const RESULT_DUPLICATE_ENTRY                = 470;
    const RESULT_ERROR                          = 500;
    const RESULT_INVALID_USER_ID                = 604;
    const RESULT_TOURNAMENT_NOT_FOUND           = 704;
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
        
        /* Make sure this server supports json and cURL
         * Static because there's no point in checking for each instantiation
         */
        self::$server_ready = self::check_server();
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
     * @param string Service to call (ie Tourney.TourneyCreate.Create)
     * @param array  Arguments to send
     *
     * @return int result
     */
    public function call($svc, $args = null) {
        //This server does not support curl or fopen
        if (!self::$server_ready) {
            return self::get_server_ready_error();
        }

        //Return a parsed value of call_raw
        return $this->decode($this->call_raw($svc, $args));
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
     * need to call it as a function, so we can get a tournametn for example, like this:
     * $new_tour = $bb->tournament;
     * 
     * @param string $name
     */
    public function &__get($name) {
        //Only existing models
        if(in_array(strtolower($name), array('tournament', 'team', 'match'))) {
            return $this->get_model($name);
        }

        //Allow accessing the BBHelper as a property
        if($name == 'helper') {
            return $this->helper();
        }

        //Invalid access
        return null;
    }

    /**
     * Returns a new Tournament object
     * 
     * @param string $tournament        Optionally provide a tournament id to auto-load, or object of tourney data
     * 
     * @return BBTournament
     */
    public function tournament($tournament = null) {
        return $this->get_model('tournament', $tournament);
    }
    
    /**
     * Returns (and caches) an instance of BBHelper, which 
     * hosts a lot of logic to help developers calculate
     * common tournament-related values (like bracket size, number of rounds, etc)
     * 
     * @return BBHelper
     */
    public function &helper() {
        //Already instantiated
        if(!is_null(self::$helper)) return self::$helper;

        //Instantiate, cache, and return
        self::$helper = new BBHelper();
        return self::$helper;
    }

    /**
     * Returns a newly instantiated modal class, either returning
     * 
     * @param string $model         Base name of the model, for example BBTournament is just "touranment"
     * @return BBModal
     */
    private function get_model($model, $data = null) {
        //Determine the class name to instantiate, based on the property name, IE team = BBTeam
        $class_name = 'BB' . ucfirst(strtolower($model));

        //EZ
        return new $class_name($this, $data);
    }

    /*
     * 
     * Public list loading services
     * 
     */


    /**
     * Retrieves a list of tournaments created using your account
     * 
     * @param string $filter        Optionally, you may filter by title
     * @param int    $limit         Limit the number of results - defaults to 30
     * @param bool   $private       true by default, returns ALL of your touranments, even if marked private - pass false to skip your private tournaments
     * 
     * @return object
     */
    public function tournament_list_my($filter = null, $limit = 30, $private = true) {
        //Grab a new tournament object, BBTournament hosts all of the tournament logic, keep logic in relevent classes
        $tournament = $this->get_modal('tournament');
        return $tournament->list_my($filter, $limit, $private);
    }

    /**
     *
     * 
     * 
     * 
     * Tournament wrapper methods
     * 
     * 
     * 
     *
     */

    /**
     * Retrieves round format
     * 
     * You can pass '*' for the bracket to retrieve for the entire tournament
     * 
     * @param int $bracket 
     * 
     * @return {object}
     */
    public function tournament_load_round_format($tourney_id, $bracket = '*') {
        return $this->call('Tourney.TourneyLoad.Rounds', array('tourney_id' => $tourney_id, 'bracket' => $bracket));
    }

    /**
     * This wrapper class is a shortcut to Tourney.TourneyStart.Start
     * It will generate groups or brackets, depending on TypeID and Status
     *
     * @see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_tournament_start
     *
     * @param string       $tourney_id		Obviously, we need to know which tournament to start
     * @param string       $seeding           See the help page on what these mean [random, traditional, balanced, manual]
     * @param array        $teams             If seeding is anything but random, you'll need to provide an ordered array of tourney_team_ids, either in order of team position, or rank
     *
     * @return object {int result}
     */
    public function tournament_start($tourney_id, $seeding = 'random', $teams = null) {
        return $this->call('Tourney.TourneyStart.Start', array(
            'tourney_id'    => $tourney_id,
            'seeding'       => $seeding,
            'teams'         => $teams,
        ));
    }

    /**
     * Change the format of a round within a tournament (best of, map, and date)
     * 
     * This function also works to create the details - even if they have not yet been provided
     * 
     * @see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_tournament_round_update
     * 
     * @param string    $tourney_id
     * @param int       $bracket      - which bracket the round effects - ie 0 = groups, 1 = winners (there are class constants for these values
     * @param int       $round        - which round to update, starting with 0 for the first round
     * @param int       $best_of      - just like it sounds, BO$X - pass in the interger 1 for Best of 1
     * @param string    $map
     * @param string    $date
     * 
     * @return object {int result}
     */
    public function tournament_round_update($tourney_id, $bracket, $round = 0, $best_of = 1, $map = null, $date = null) {
        return $this->call('Tourney.TourneyRound.Update', array(
            'tourney_id'    => $tourney_id,
            'bracket'       => $bracket,
            'round'         => $round,
            'best_of'       => $best_of,
            'map'           => $map,
            'date'          => $date,
        ));
    }

    /**
     * the round_update function is fine and all.. but not incredibly effecient for large tournaments with many rounds and brackets, this method
     * allows you to update all rounds with one call, by passing in a simple array
     * 
     * @see link http://wiki.binarybeast.com/index.php?title=API_PHP:_tournament_round_update_batch
     * 
     * @param string        $tourney_id
     * @param int           $bracket      - which bracket the round effects - ie 0 = groups, 1 = winners (there are class constants for these values
     * @param <int>array    $best_ofs     - array of best_of values to update, IN ORDER ($best_ofs[0] = round 1, $best_ofs[1] = round 2)
     * @param <string>array $maps         - array of maps for this bracket
     * @param <string>array $dates        - array of dates for this bracket
     * @param <int>array    $map_ids      - array of map_ids - official maps with stat tracking etc in our databased, opposed to simply trying to give us the name of the map - use map_list to get maps ids
     * 
     */
    public function tournament_round_update_batch($tourney_id, $bracket, $best_ofs = array(), $maps = array(), $dates = array(), $map_ids = array()) {
        return $this->call('Tourney.TourneyRound.BatchUpdate', array(
            'tourney_id'    => $tourney_id,
            'bracket'       => $bracket,
            'best_ofs'      => $best_ofs,
            'maps'          => $maps,
            'map_ids'       => $map_ids,
            'dates'         => $dates,
        ));
    }

    /**
     * Retrieves a list of matches that have are currently opened 
     * This does not help you determine matches that are waiting on opponents, 
     * it simply lets you know currently open matches
     * 
     * @param string $tourney_id
     * 
     * @return object[int Result [, array matches]]
     */
    public function tournament_get_open_matches($tourney_id) {
        return $this->call('Tourney.TourneyLoad.OpenMatches', array('tourney_id' => $tourney_id));
    }

    /**
     * Reopen a tournament
     * 
     * Complete -> Active,Active-Brackets -> Active-Brackets -> Active-Groups, Active/Active-Groups -> Confirmation
     * 
     * @param string $tourney_id 
     */
    public function tournament_reopen($tourney_id) {
        return $this->call('Tourney.TourneyReopen.Reopen', array('tourney_id' => $tourney_id));
    }

    /**
     * Wrapper method for Tourney.TourneySetStatus.Confirmation, allow players to confirm their positions
     * 
     * @param string $tourney_id
     * 
     * @return object {int result]
     */
    public function tournament_confirm($tourney_id) {
        return $this->call('Tourney.TourneySetStatus.Confirmation', array('tourney_id' => $tourney_id));
    }

    /**
     * Wrapper method for Tourney.TourneySetStatus.Confirmation, allow players to confirm their positions
     * 
     * @param string $tourney_id
     * 
     * @return object {int result]
     */
    public function tournament_unconfirm($tourney_id) {
        return $this->call('Tourney.TourneySetStatus.Building', array('tourney_id' => $tourney_id));
    }

    /**
     *
     * 
     * 
     * 
     * Teams/Participants wrapper methods
     * 
     * 
     * 
     *
     */

    /**
     * This wrapper class will insert a team into your tournament (Tourney.TourneyTeam.Insert)
     * It will automatically confirm the team unless it has already been filled according to your MaxTeams setting
     *
     * @see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_team_insert
     * 
     * Available options:
     *      string country_code
     *      int    status               (0 = unconfirmed, 1 = confirmed, -1 = banned)
     *      string notes                Notes on the team - this can also be used possibly to store a team's remote userid for your own website
     *      array  players              If the TeamMode is > 1, you can provide a list of players to add to this team, by CSV (Player1,,Player2,,Player3)
     *      string network_name         If the game you've chosen for the tournament has a network configured (like sc2 = bnet 2, sc2eu = bnet europe), you can provide their in-game name here
     * 
     * @param string $tourney_id    
     * @param string $display_name  The team / player name
     * @param array  $options        keyed array of options
     *
     * @return array [int result [, int tourney_team_id]]
     */
    public function team_insert($tourney_id, $display_name, $options = array()) {
        $args = array_merge(array(
            'tourney_id'    => $tourney_id,
            'display_name'  => $display_name,
            'status'        => 1,
        ), $options);

        return $this->call('Tourney.TourneyTeam.Insert', $args);
    }

    /**
     * Change a team's settings
     * 
     * @see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_team_update
     * 
     * Available options, check the wiki for their meanings, and default / possible values: 
     *      string country_code
     *      int    status               (0 = unconfirmed, 1 = confirmed, -1 = banned)
     *      string notes                Notes on the team - this can also be used possibly to store a team's remote userid for your own website
     *      array  players              If the TeamMode is > 1, you can provide a list of players to add to this team, by CSV (Player1,,Player2,,Player3)
     *      string network_name         If the game you've chosen for the tournament has a network configured (like sc2 = bnet 2, sc2eu = bnet europe), you can provide their in-game name here
     *
     * @param type $tourney_team_id
     * @param type $options 
     * 
     * @return object {int result}
     */
    public function team_update($tourney_team_id, $options) {
        $args = array_merge(array('tourney_team_id' => $tourney_team_id), $options);
        return $this->call('Tourney.TourneyTeam.Update', $args);
    }

    /**
     * Granted that the tournament can still accept new teams, this method will update the status of a team to confirm his position in the draw
     * 
     * Unless otherwise specified, if you manually add a team through team_insert, he is confirmed by default
     * 
     * btw here's a tip: you can actually pass in '*' for the tourney_team_id to confirm ALL teams, but you would also have to include $tourney_id
     * 
     * @see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_team_confirm
     * 
     * @param type $tourney_team_id 
     * 
     * @return object {int result [, int tourney_team_id]}
     */
    public function team_confirm($tourney_team_id, $tourney_id = null) {
        return $this->call('Tourney.TourneyTeam.Confirm', array('tourney_team_id' => $tourney_team_id, 'tourney_id' => $tourney_id));
    }

    /**
     * Granted that the tournament hasn't started yet, this method can be used to unconfirm a team, so he will no longer be included in the grid once the tournament starts
     * 
     * Unless otherwise specified, if you manually add a team through team_insert, he is confirmed by default
     * 
     * @see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_team_unconfirm
     * 
     * @param type $tourney_team_id 
     * 
     * @return object {int result [, int tourney_team_id]}
     */
    public function team_unconfirm($tourney_team_id) {
        return $this->call('Tourney.TourneyTeam.Unconfirm', array('tourney_team_id' => $tourney_team_id));
    }

    /**
     * BANNEDED!!!
     * 
     * Ban a team from the tournament
     * 
     * Warning: this will NOT work if the tournament has already started, the best you can do is rename the player (using team_update, 'display_name' => 'foo')
     * 
     * @see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_team_ban
     * 
     * @param type $tourney_team_id 
     * 
     * @return object {int result [, int tourney_team_id]}
     */
    public function team_ban($tourney_team_id) {
        return $this->call('Tourney.TourneyTeam.Ban', array('tourney_team_id' => $tourney_team_id));
    }

    /**
     * This wrapper method will delete a team from a touranment
     * as long as the tournament has not been started or the team is unconfirmed
     *
     * @see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_team_delete
     *
     * @param int $tourney_team_id 	Which team to delete - the binarybeast value TourneyTeamID
     *
     * @return object {int result}
     */
    public function team_delete($tourney_team_id) {
        return $this->call('Tourney.TourneyTeam.Delete', array('tourney_team_id' => $tourney_team_id));
    }

    /**
     * This wrapper method will report a team's win (Tourney.TourneyTeam.ReportWin)
     * 
     * Available Options:
     *  @param int     `score`				The score of the winner
     *  @param int     `o_score`                        The score fo the opponent (loser)
     *  @param bool    `draw`                           If this match was a draw, pass in true for this value
     *  @param string  `replay`				A URL to download the replay (first match only, for more detailed replay per game within the match, see Tourney.TourneyGame services for b03+)
     *  @param string  `map`				You may specify which map it took place on (applies to the first match only, for more, see the Tourney.TourneyGame services)
     *  @param string  `notes`				An optional description of the match
     *  @param boolean `force`				You can pass in true for this paramater, to force advancing the team even if he has no opponent (it would have thrown an error otherwise)
     * 
     *
     * @see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_team_report_win
     *
     * @param string    $tourney_id                     Duh
     * @param int       $winner_tourney_team_id         The winner's team id
     * @param int       $loser_tourney_team_id          Only used / necessary in group rounds, the TeamID of the loser
     * @param array     $options                        An associative array of additional options
     *
     * @return object {int result}
     */
    public function team_report_win($tourney_id, $winner_tourney_team_id, $loser_tourney_team_id = null, $options = array()) {
        $args = array_merge(array(
            'tourney_id'        => $tourney_id,
            'tourney_team_id'   => $winner_tourney_team_id,
            'o_tourney_team_id' => $loser_tourney_team_id,
        ), $options);
        return $this->call('Tourney.TourneyTeam.ReportWin', $args);
    }

    /**
     * This wrapper will return the TourneyTeamID of the given team (Tourney.TourneyTeam.GetOTourneyTeamID)
     *
     * @see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_team_get_opponent
     *
     * Note: a Result of 200 = the team currently has an opponent
     * Result 735 and OTourneyTeamID -1 = The team has been eliminated
     * Result 734 and OTourneyTeamID 0  = The team is currently waiting on an opponent
     *
     * Also, if the team has been eliminated, it will return an object 'Victor" with some information
     * about the winning team
     *
     * @param int $tourney_team_id		The team of which to determine the opponent
     *
     * @return object {int Result [, object TeamInfo, array Players]}
     */
    public function team_get_opponent($tourney_team_id) {
        return $this->call('Tourney.TourneyTeam.GetOTourneyTeamID', array('tourney_team_id' => $tourney_team_id));
    }

    /**
     * Returns as much information about a team as possible
     * 
     * @param int $tourney_team_id 
     * 
     * @return object {int result {
     */
    public function team_load($tourney_team_id) {
        return $this->call('Tourney.TourneyLoad.Team', array('tourney_team_id' => $tourney_team_id));
    }

    /**
     *
     * 
     * 
     * Match service wrappers
     * 
     * 
     *  
     */

    /**
     * Save the individual game details for a reported match
     * 
     * Each array (winners, scores, o_scores, races, maps, and replays) must be indexed in order of game
     * so winners[0] => is the tourney_team_id of the player that won the FIRST game in the match
     * winners[1] => the tourney_team_id of the player that won the second game... et c
     * 
     * 
     * It's important to note that scores refers to the score of the winner of that specific game
     * 
     * So if player 1 defeats player 2 30 to 17 in game one
     * Then let's say game 2.. player 2 defeats player 1 13:7...
     * 
     * In such a scenario, here's how your arrays should look:
     *  winners[0] => tourney_team_id of player 1
     *  score[0]   => score of player 1
     *  o_score[0] => score of player 2
     *  --
     *  winners[1] => tourney_team_id of player 2
     *  score[1]   => score of player 2
     *  o_score[1] => score of player 1
     * 
     * 
     * @param int $tourney_match_id
     * @param array $winners
     * @param array $scores
     * @param array $o_scores
     * @param array $maps
     * 
     * @return {object}
     */
    public function match_report_games($tourney_match_id, array $winners, array $scores, array $o_scores, array $maps) {
        $args = array(
            'tourney_match_id' => $tourney_match_id,
            'winners' => $winners,
            'scores' => $scores,
            'o_scores' => $o_scores,
            'races' => $races,
            'maps' => $maps,
        );
        return $this->call('Tourney.TourneyMatchGame.ReportBatch', $args);
    }

    /**
     * Load a list of maps for the given game_code
     * 
     * this is important to have in order for you to have the ability to 
     * specify maps for the round format for each bracket, as you can 
     * identify the maps by simply giving us the map_id
     * 
     * @param string $game_code
     * 
     * @return {object}
     */
    public function map_list($game_code) {
        return $this->call('Game.GameMap.LoadList', array('game_code' => $game_code));
    }

    /**
     * This wrapper will return a list of games according to the filter you provide
     *
     * Note: a Result of 601 means that your search term was too short, must be at least 3 characters long
     *
     * @param string $filter     filter the results with a generic filter
     *
     * @return object {int result [, array games]}
     */
    public function game_search($filter) {
        return $this->call('Game.GameSearch.Search', array('game' => $filter));
    }

    /**
     * Returns the currently most popular games on BinaryBeast
     *
     * @param int $limit        simply limits the number of results, as this service is NOT paginated
     *
     * @return object {int result, [array games]}
     */
    public function game_list_top($limit = 10) {
        return $this->call('Game.GameSearch.Top', array('limit' => $limit));
    }

    /**
     * This wrapper allows you to search through the ISO list of countries
     * This is useful because BinaryBeast team's use ISO 3 character character codes, so 
     * to keep it simple, you can just look through our list of countries to get the codes
     * 
     * There's nothing special about our list of countries however, you can look up the official list on wikipedia
     * 
     * @param string $country       Simple search filter, something like "united" would yield things like USA, UK, etc
     * 
     * @return object {int result, [array countries]}
     */
    public function country_search($country) {
        return $this->Call('Country.CountrySearch.Search', array('country' => $country));
    }

}
?>