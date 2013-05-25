<?php

/**
 * Library class used to register new event callbacks with the API
 * 
 * When you register a callback, BinaryBeast will POST or GET the specified URL when that specific event is triggered
 * 
 * <br /><br />
 * While it is certainly possible to use this class directly, developers are encouraged to use the event-specific methods defined in each BBModel class
 * 
 * <br /><br />
 * One thing you can use this class for however, is handling data sent from from BinaryBeast to your $url
 * <br />This will be demonstrated below
 * 
 * 
 * ### Handling Callbacks ### .[#handling]
 * 
 * When BinaryBeast executes a callback, it send information about the event in either <var>$_POST</var> or <var>$_GET</var>,<br />
 * Depending on what you set <var>$action</var> to in {@link register()}
 * 
 * <br /><br />
 * Some events will include custom data.. For example some tournament-specific events may include a <var>$tourney_info</var> object
 * 
 * <br /><br />
 * However ALL callback will send at least the information documented in {@link BBCallbackObject}
 * 
 * 
 * ## Example: Handling a Callback .[#example-handling]
 * 
 * Let's quickly demonstrate how to property handle a callback that you've registered
 * 
 * Assume we have an on_change callback registered for a tournament, <br />
 * The URL is <b>http://yoursite.com/tournament/handle_callback.php</b>
 * 
 * <br /><br />
 * We'll use {@link BBCallback::handle_callback()} to help process the data from BinaryBeast a bit before we do anything with it
 * 
 * <br /><br />
 * <b>handle_callback.php</b>
 * <code>
 *	$data = $bb->callback->handle_callback();
 *	//handle_callback() found and created a tournament for us, clear all local api cache for this tournament
 *	if(!is_null($data->tournament)) {
 *		//Could do our own custom SQL changes here etc
 *		//...
 * 
 *		//Clear all API Response cache for this tournament
 *		$data->tournament->clear_id_cache();
 *	}
 * </code>
 * 
 * 
 * ## Deleting Callback Registrations .[#deleting]
 * 
 * Deleting a callback is known as unregistering, and we use {@link unregister()} to do it
 * 
 * <br /><br />
 * Assume that <var>$id</var> is a callback id that we want to delete
 * 
 * <br /><br />
 * <b>Delete the Callback:</b>
 * <code>
 *	$bb->callback->unregister($id);
 * </code>
 * 
 * 
 *
 * @package BinaryBeast
 * @subpackage Library
 * 
 * @version 3.0.5
 * @date    2013-05-24
 * @since   2013-03-29
 * @author  Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
class BBCallback {

	/**
	 * Reference to the main {@link BinaryBeast} library class
	 * 
	 * @var BinaryBeast
	 */
	private $bb;
	
	/**
	 * Service name used to register a new callback
	 * @var string
	 */
	const SERVICE_REGISTER = 'Callback.Callback.Register';
	/**
	 * Service name used to delete an existing callback
	 * @var string
	 */
	const SERVICE_UNREGISTER = 'Callback.Callback.Unregister';
	/**
	 * Service name used to load a list of callbacks registered by our api_key
	 * @var string
	 */
	const SERVICE_LIST = 'Callback.Callback.LoadList';
	/**
	 * Service name used to ask BinaryBeast to execute a mock-callback with some fake data
	 * @var string
	 */
	const SERVICE_TEST = 'Callback.Callback.Test';

	/**
	 * Used by {@link BBCache} to define the cache scope when caching result
	 * @var int
	 */
	const CACHE_OBJECT_TYPE = BBCache::TYPE_CALLBACK;
	/**
	 * Default TTL (in minutes) setting for caching result from {@link load_list()}
	 * @var int
	 */
	const CACHE_TTL = 60;


    /**
     * Triggered when a tournament is created
     * 
     * <b>Recurrent: </b>Yes
     * 
     * @var int
     */
    const EVENT_TOURNAMENT_CREATED = 12;
    /**
     * Triggered when the group rounds start in a tournament
     *
     * <b>Recurrent: </b>Yes
     *
     * @var int
     */
    const EVENT_TOURNAMENT_START_GROUPS = 1;
    /**
     * Triggered when the brackets start in a tournament
	 * 
	 * <b>Recurrent: </b>Yes
     * 
     * @var int
     */
    const EVENT_TOURNAMENT_START_BRACKETS = 2;
    /**
     * Triggered when a tournament finishes
     * 
     * <b>Recurrent: </b>Yes
	 * 
	 * <br /><br />
	 * <b>Values expected:</b>
	 * <ul>
	 *	<li><b>array</b> match_info</li>
	 *	<li><b>array</b> games</li>
	 *	<li><b>array</b> winners</li>
	 * </ul>
	 * 
	 * <br /><br />
	 * <b>Values with {@link handle_callback()}:</b>
	 * <ul>
	 *	<li><b>{@link BBMatch}</b> match</li>
	 *	<li><b>{@link BBTeam}[]</b> winners</li>
	 * </ul>
     * 
     * @var int
     */
    const EVENT_TOURNAMENT_COMPLETE = 3;
    /**
     * Triggered each time a team is added to the tournament
     * 
     * <b>Recurrent: </b>Yes
     * 
	 * <br /><br />
	 * <b>Values Expected:</b>
	 * - <b>object</b> teamz
	 * 
	 * <br /><br />
	 * <b>Values with {@link handle_callback()}:</b>
	 * - <b>{@link BBTeam}</b> team
     * 
     * @var int
     */
    const EVENT_TOURNAMENT_TEAM_ADDED = 4;
    /**
     * Triggered each time a team is removed from the tournament
     * 
     * <b>Recurrent: </b>Yes
     * 
	 * <br /><br />
	 * <b>Values Expected:</b>
	 * - <b>object</b> team
	 * 
	 * <br /><br />
	 * <b>Values with {@link handle_callback()}:</b>
	 * - <b>{@link BBTeam}</b> team
     * 
     * @var int
     */
    const EVENT_TOURNAMENT_TEAM_REMOVED = 5;
    /**
     * Triggered each time a match is reported in a tournament
     * 
     * <b>Recurrent: </b>Yes
     * 
	 * <br /><br />
	 * <b>Values Expected:</b>
	 * - <b>object</b> match_info
	 * - <b>array</b> games
	 * 
	 * <br /><br />
	 * <b>Values with {@link handle_callback()}:</b>
	 * - <b>{@link BBMatch}</b> match
	 * 
     * @var int
     */
    const EVENT_TOURNAMENT_MATCH_REPORTED = 6;
    /**
     * Triggered each time a match is unreported in a tournament
     * 
	 * <b>Recurrent: </b>Yes
     * 
	 * <br /><br />
	 * <b>Values Expected:</b>
	 * - <b>object</b> match_info
	 * - <b>array</b> games
	 * 
	 * <br /><br />
	 * <b>Values with {@link handle_callback()}:</b>
	 * - <b>{@link BBMatch}</b> match
	 * 
     * 
     * @var int
     */
    const EVENT_TOURNAMENT_MATCH_UNREPORTED = 7;
    /**
     * Triggered each time a match is reported in a tournament
	 * 
	 * <b>Recurrent: </b>No
     * 
	 * <br /><br />
	 * <b>Values Expected:</b>
	 * - <b>object</b> tourney_info
	 * 
	 * <br /><br />
	 * <b>Values with {@link handle_callback()}:</b>
	 * - <b>{@link BBTournament}</b> tournament
     * 
     * @var int
     */
    const EVENT_TOURNAMENT_DELETED = 8;
    /**
     * Triggered each time a team's status changes (banned/unconfirmed/confirmed)
     * 
     * <b>Recurrent: </b>Yes
     * 
	 * <br /><br />
	 * <b>Values Expected:</b>
	 * - <b>array</b> team
	 * 
	 * <br /><br />
	 * <b>Values with {@link handle_callback()}:</b>
	 * - <b>{@link BBTeam}</b> team
     * 
     * @var int
     */
    const EVENT_TOURNAMENT_TEAM_STATUS_CHANGED = 9;
    /**
     * Triggered each time a tournament's settings change
     * 
     * <b>Recurrent: </b>Yes
     * 
	 * <br /><br />
	 * <b>Values Expected:</b>
	 * - <b>array</b> tourney_info
	 * 
	 * <br /><br />
	 * <b>Values with {@link handle_callback()}:</b>
	 * - <b>{@link BBTournament}</b> tournament
	 * 
     * @var int
     */
    const EVENT_TOURNAMENT_SETTINGS_CHANGED = 10;
	/**
	 * Very generic event, triggered anytime ANYTHING changes within a tournament
	 * 
	 * <b>Recurrent: </b>Yes
	 * 
	 * <br /><br />
	 * <b>Values Expected:</b>
	 * - <b>string</b> description
     * - <b>string</b> tourney_id
	 * 
	 * @var int
	 */
	const EVENT_TOURNAMENT_CHANGED = 11;

    /**
     * Constructor
	 * 
	 * Stores a reference to the a {@link BinaryBeast instance}
	 * 
	 * @ignore
	 * 
	 * @param BinaryBeast $bb
     */
    function __construct(BinaryBeast &$bb) {
        $this->bb = &$bb;
    }

	/**
	 * Register a URL with a callback event
	 * 
	 * ## Note to Developers
	 * We recommend using the BBModle::on_* methods instead going directly to BBCallback
	 * 
	 * 
	 * @param int $event_id
	 *	You can refer to the EVENT_ constants in this class for this value
	 * 
	 * @param string|int $trigger_id
	 *	The unique ID of the object meant to trigger the event<br /><br />
	 *	If for example, you're registering a EVENT_TOURANMENT_* event, use <var>$tournament->id</var>
	 * 
	 * @param string $url
	 *	The URL you'd like called when the event is triggered<br /><br />
	 * 
	 * @param string $action
	 *	The HTTP method you'd like BinaryBeast to use when calling your $url<br /><br />
	 *	<b>Can only be one of the following values:</b>
	 * - post
	 * - get
	 * 
	 *	Expect $_POST data to be sent<br /><br />
	 *	For the values expected from $_POST, see the description of each constant
	 * 
	 * @param boolean $recurrent
	 *	By default, once a callback is satisifed, it is unregistered<br /><br >
	 *	As long as the specified <var>$event_id</var> supports it, set it to true to disable this feature, so that
	 *	the callback continues to be executed each time the the conditions are met
	 * 
	 * @param array $args
	 *	Optional array of custom arguments the API will send to the specified <var>$url</var>
	 * 
	 * @return int|boolean
	 *	- <b>integer</b> The callback id, which you can later use to delete if necessary<br />
	 *	- <b>false</b> An error occurred, and was most likely due to a duplicate $event_id / $trigger_id pair.  Use {@link BinaryBeast::$last_error} for details
	 * 
	 */
	public function register($event_id, $trigger_id, $url, $action = 'post', $recurrent = false, $args = null) {
		//Must be an array
		if(is_object($args)) $args = (array)$args;
		else if(!is_array($args)) $args = null;

		$result = $this->bb->call(self::SERVICE_REGISTER, array(
			'event_id'			=> $event_id, 
			'trigger_id'		=> $trigger_id,
			'url'				=> $url,
			'action'			=> $action,
			'recurrent'			=> $recurrent,
			'custom_args'		=> $args,
		));

		//Success!
		if($result->result == BinaryBeast::RESULT_SUCCESS) {
			//Clear ALL callback cache
			$this->bb->clear_cache(null, self::CACHE_OBJECT_TYPE);

			//Return the new callback id
			return $result->id;
		}

		//Failure!
		return $this->bb->set_error('API returned an error from svc "' . self::SERVICE_REGISTER . '", result code ' . $result->result, 'BBCallback');
	}

	/**
	 * Unregister / Delete a callback 
	 * 
	 * @param int $id
	 * 
	 * @return boolean
	 *	A <b>false</b> return may indicate an invalid $id<br />
	 *	But as always, you can refer to {@link BinaryBeast::$result_history} for details
	 */
	public function unregister($id) {
		$result = $this->bb->call(self::SERVICE_UNREGISTER, array('id' => $id));

		//Failure!
		if($result->result != BinaryBeast::RESULT_SUCCESS) {
			return $this->bb->set_error('Error deleting callback id "' . $id . '", check $bb->result_history for details', 'BBCallback');
		}

		//Success - Clear all callback cache and return true
		$this->bb->clear_cache(null, self::CACHE_OBJECT_TYPE);
		return true;
	}

	/**
	 * Fetch a list of callbacks registered with your api_key
	 * 
	 * The filters are completely optionally, but you can provide any combination of them 
	 * specify the sort of callbacks returned
	 * 
	 * @param int|null $event_id
	 *	Optionally limit the returned callbacks to a specific event_id
	 * @param int|string|null $trigger_id
	 *	Optionally limit the returned callbacks to a specific trigger id (model object id, tour->id etc)
	 * @param string|null $url
	 *	Optionally limit the returned callback to a specific URL
	 * 
	 * @return BBCallbackObject[]|boolean
	 *	<b>False</b> if there was an error accessing the API, or returned from the API
	 */
	public function load_list($event_id = null, $trigger_id = null, $url = null ) {
		$result = $this->bb->call(self::SERVICE_LIST, array(
			'event_id'		=> $event_id,
			'trigger_id'	=> $trigger_id,
			'url'			=> $url
		), self::CACHE_TTL, self::CACHE_OBJECT_TYPE);

		//Failure!
		if($result->result != BinaryBeast::RESULT_SUCCESS) {
			return $this->bb->set_error('Error fetch the callback list, please refer to $bb->result_history for details', 'BBCallback');
		}

		//Success!
		return $result->list;
	}
	
	
	/**
	 * Test your callback handler, by requesting a fake callback from BinaryBeast
     * 
     * You can either define a callback, as if you were calling {@link register()}, <br />
     * Or you can provide a callback id integer to use the values of a callback that you've already registered
     * 
     * @param int $callback_id <br />
     *  <b>Note:</b> This value takes priority over all other arguments<br />
     *  If you've registered a callback, provide the callback_id, and BinaryBeast will use the event_id, url, trigger_id etc etc from your callback
	 * @param int $event_id
     * <br /><b>Only used if <var>$callback_id</var> not provided</b>
	 * @param int|string $trigger_id
     * <br /><b>Only used if <var>$callback_id</var> not provided</b>
	 * @param string $url
     * <br /><b>Only used if <var>$callback_id</var> not provided</b>
	 * @param string $action
     * <br /><b>Only used if <var>$callback_id</var> not provided</b>
	 * @param boolean $recurrent
     * <br /><b>Only used if <var>$callback_id</var> not provided</b>
	 * @param array|object $args
     * <br /><b>Only used if <var>$callback_id</var> not provided</b>
	 * 
	 * @return string|boolean
	 *	<b>False</b> If BinaryBeast failed to call your URL, like non-200 result codes from your $url
	 * 
	 *	<br /><br />
	 *	If BinaryBeast was successfully able to send data to your $url.. either your url's response will be returned,
	 *	or a true boolean, in case your URL yielded an empty response
	 */
	public function test($callback_id = null, $event_id = null, $trigger_id = null, $url = null, $action = 'post', $recurrent = false, $args = null) {
		$result = $this->bb->call(self::SERVICE_TEST, array(
            'callback_id'       => $callback_id,
			'event_id'			=> $event_id, 
			'trigger_id'		=> $trigger_id,
			'url'				=> $url,
			'action'			=> $action,
			'recurrent'			=> $recurrent,
			'custom_args'		=> $args,
		));

		//Failure!
		if($result->result != BinaryBeast::RESULT_SUCCESS) {
			return false;
		}

		//Success! Return the response if available, true otherwise
		return $result->response ? $result->response : true;
	}

	/**
	 * Attempt to extract data sent from a BinaryBeast callback
	 * 
	 * <br />
	 * This method will try to extract the data from $_POST and $_GET, and then
	 * process the data further based on the event_id
	 * 
	 * <br /><br />
	 * For example if a tourney_info array is provided, you'll get a full {@link BBTournament} instance
	 * 
	 * 
	 * @return BBCallbackHandlerObject|boolean
	 *	<b>FALSE</b> returned if unable to find any relevent data in $_POST and $_GET
	 * 
	 *	<br /><br />
	 *	Refer to the properties documented in {@link BBCallbackObject} for values that you can expect
	 * 
	 *	<br /><br />
	 *	In additon to the standard values in {@link BBCallbackObject}, each event may return data that this method will convert into <b>model objects</b><br/ >
	 *	For example, if the callback sends a tourney_info array, you can expect <var>$tournament</var> to be a {@link BBTournament} model object
	 * 
	 *	<br /><br />
	 *	Events that expect these values are documented in each event_id constant in this class
	 */
	public function handle_callback() {
		//Prepare the object we'll be returning
		$data = new stdClass();

		//Figure out if we should use $_POST or $_GET, then save a refernece to $request
		$request = null;
		if(isset($_POST['callback_id'])) {
			$request = &$_POST;
		}
		else if(isset($_GET['callback_id'])) {
			$request = &$_GET;
		}

		//Couldn't even find a callback_id - return null to indicate failure
		else {
            return $this->bb->set_error('Unable to locate a "callback_id" value in either $_POST or $_GET');
        }

		//All standard callback values must be present
		$keys = array('callback_id', 'event_id', 'event_description', 'event_utc_date');
		foreach($keys as $key) {
			if(!isset($request[$key])) {
				return $this->bb->set_error('Unable to locate a "' . $key . '" value, unable to process this as a valid callback', 'BBCallback');
			}
			$data->$key = $request[$key];
		}

		//Try to extract a tournament
		if(isset($request['tourney_info'])) {
			$data->tournament = $this->bb->tournament($request);
		}
		else if(strpos($request['trigger_id'], 'x') === 0) {
			$data->tournament = $this->bb->tournament($request['trigger_id']);
		}
		else if(isset($request['tourney_id'])) {
			$data->tournament = $this->bb->tournament($request['tourney_id']);
		}

		//Try to extract a match
		if(isset($request['match_info'])) {
			if(isset($request['games'])) {
				$data->match = $this->bb->match($request);
			}
		}
		else if(isset($request['tourney_match_id'])) {
			$data->match = $this->bb->match($request['tourney_match_id']);
		}

		//Try to extract a team
		if(isset($request['team_info'])) {
			$data->team = $this->bb->team($request);
		}
		else if(isset($request['tourney_team_id'])) {
			$data->team = $this->bb->team($request['tourney_team_id']);
		}
		
		//Success!
		return $data;
	}
}

/**
 * Data structure for the values sent from BinaryBeast when a Callback is executed
 * 
 * This class is never used, it soley exists for documentation
 * 
 * @property-read int $callback_id
 *	The id unique to the registered callback
 * 
 * @property-read int|string $trigger_id
 *	The ID of the object associated with the event<br />
 *	For a tournament event for example, it would be the {@link BBTournament::id}
 * 
 * @property-read int $event_id
 *	The event that triggered the callback<br />
 *	The value can be matched against EVENT_* constants in this class
 * 
 * @property-read string $event_description
 *	A string describing what triggered the callback
 * 
 * @property-read string $event_utc_date
 *	The timestamp of when the event was triggered, in the UTC timezone
 * 
 * @property BBTournament $tournament
 * <b>Only Included for the following event_ids:</b>
 * <ul>
 *	<li>8 ({@link BBCallback::EVENT_TOURNAMENT_DELETED})</li>
 *	<li>10 ({@link BBCallback::EVENT_TOURNAMENT_SETTINGS_CHANGED})</li>
 * </ul>
 * 
 * @property BBTeam $team
 * <b>Only Included for the following event_ids:</b>
 * <ul>
 *	<li>4 ({@link BBCallback::EVENT_TOURNAMENT_TEAM_ADDED})</li>
 *	<li>5 ({@link BBCallback::EVENT_TOURNAMENT_TEAM_REMOVED})</li>
 *	<li>9 ({@link BBCallback::EVENT_TOURNAMENT_TEAM_STATUS_CHANGED})</li>
 * </ul>
 * 
 * @property BBMatch $match
 * <b>Only Included for the following event_ids:</b>
 * <ul>
 *	<li>3 ({@link BBCallback::EVENT_TOURNAMENT_COMPLETE})</li>
 *	<li>6 ({@link BBCallback::EVENT_TOURNAMENT_MATCH_REPORTED})</li>
 *	<li>7 ({@link BBCallback::EVENT_TOURNAMENT_MATCH_UNREPORTED})</li>
 * </ul>
 * 
 * 
 * @package BinaryBeast
 * @subpackage Library_ObjectStructure
 */
abstract class BBCallbackHandlerObject {
    //Nothing here - used for documentation only
}

/**
 * Data structure for the callback objects returned when loading registered callbacks
 * 
 * This class is never used, it soley exists for documentation
 * 
 * @property-read int $id
 *	The id of the registered callback
 * 
 * @property-read string $protocol 
 *	Not yet implemented, for now 'http' will always be returned
 * 
 * @property-read string $action
 *	HTTP method BinaryBeast should use when calling {@link $url}<br />
 *	Will be either <b>post</b> or <b>get</b>
 * 
 * @property-read int $event_id
 *	The event that triggers the callback<br />
 *	Should match one of the EVENT_* constants in {@link BBCallback}
 * 
 * @property-read string|int $trigger_id
 *	The unique ID of the object that the triggers the event (ie a tournament id, team id etc)
 * 
 * @property-read boolean $recurrent
 *	If the event type allows it, this reflects whether or not your callback is recurrent<br >
 *	In otherwise unless true, your callback will be executed once, and then deleted
 * 
 * @property-read string $custom_args
 *	If you defined custom arguments to be sent when the callback is executed, <br />
 *	this will be the json_encoded string of that array
 * 
 * @property-read string $event_description
 *	A simple description of the event_id for your callback
 * 
 * @package BinaryBeast
 * @subpackage Library_ObjectStructure
 */
abstract class BBCallbackObject {
    //Nothing here - used for documentation only
}

?>