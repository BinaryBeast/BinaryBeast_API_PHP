<?php

/**
 * Base class for models - simple and full
 * 
 * The SimpleModel class provides most of the error handling / result storage, 
 *      logic for determining a service name, and so on
 * 
 * It does not howerver, provide any functionality for updating, creating, or deleting data
 * 
 * That's why I've separated that logic into a different model: BBModel
 * 
 * It's not to save space or ram or whatever, it would make little difference anyway,
 *  the point is to avoid any confusion with developers as to what
 *  data they have the ability to manipulate
 * 
 * So this class is used primary for service wrapper hosting (like BBGame)
 *      while BBModel is used for full manipulatable objects for creating/creating/updating - like BBTournament
 *  
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * 
 * @version 1.0.0
 * @date 2013-02-14
 * @author Brandon Simmons
 */
class BBSimpleModel {

    /**
     * Reference to the main API library class
     * @var BinaryBeast
     */
    protected $bb;

    /**
     * Publicly accessible result code for the previous api call
     * @var int
     */
    public $result = null;
    /**
     * Publically accessible friendly/human-readable version of the previous result code
     * @var string
     */
    public $result_friendly = null;
    /**
     * If loading failed, stored the result
     * @var array
     */
    protected $last_error = null;
    /**
     * Allows child classes to define the object_type when caching api responses, and
     *  ttls for certain tasks (listing / loading)
     * 
     * cache all results for 10 minutes by default
     */
    const CACHE_OBJECT_TYPE = null;
    const CACHE_TTL_LIST    = 10;
    const CACHE_TTL_LOAD    = 10;

	/**
	 * To insure that each child is truly unique while flagging changes, we give each
	 *		new object an arbitrary "uid"
	 * 
	 * Determined this to be necessary once we realized that when child classes attempted to 
	 *	flag themselves as changed with their parents, it would fail to be flagged
	 *	if there happend to be any other objects with the exact same values, setting this
	 *	unique id prevents that from happening
	 */
	protected $uid;

    /**
     * Constructor
     * Stores a reference to the main BinaryBeast library class
     * 
     * @param BinaryBeast $bb       Reference to the main API class
     */
    function __construct(BinaryBeast $bb) {
        $this->bb = $bb;

		//Set an arbitrary uid to insure that each object is unique
		$this->uid = uniqid();
    }

    /**
     * Calls the BinaryBeast API using the given service name and arguments, 
     * and grabs the result code so we can locally stash it 
     * 
     * @param string $svc    		Service to call (ie Tourney.TourneyCreate.Create)
     * @param array $args     		Arguments to send
	 * @param int $ttl				If you configured the BBCache class, use this to define how many minutes this result should be cached
	 * @param int $object_type		For caching objects: see BBCache::type_ constants
     * @param mixed $object_id		For caching objects: The id of this the object, like tourney_id or tourney_team_id
     * @return object
     */
    protected function call($svc, $args = null, $ttl = null, $object_type = null, $object_id = null) {
        //First, clear out any errors that may have existed before this call
        $this->clear_error();

        //Use BinaryBeast library to make the actual call
        $response = $this->bb->call($svc, $args, $ttl, $object_type, $object_id);

        //Store the result code in the model itself, to make debuggin as easy as possible for developers
        $this->set_result($response->result);

        //Finallly, return the response
        return $response;
    }

    /**
     * Stores a result code into $this->result, and also stores a
     * readable translation into result_friendly
     * 
     * @param int $result
     * @return void
     */
    protected function set_result($result) {
        $this->result = $result;
        $this->result_friendly = BBHelper::translate_result($result);
    }

    /**
     * Store an error into $this->error, developers can refer to it
     * as $tournament|$match|etc->error()
     * 
     * In order to standardize error values, we send it first to the main library class,
     * which will either save as-is or convert to an array - either way it will return us the new value
     *      We locally store the value returned back from the main library
     * 
     * Lastly, we return false - this allows model methods simultaneously set an error, and return false
     * at the same time - allowing me to be lazy and type that all into a single line :)
     * 
     * @param array|string $error
     * @return false
     */
    protected function set_error($error) {
        //Send to the main BinaryBeast API Library, and locally save whatever is sent back (a standardized format)
        $this->last_error = $this->bb->set_error($error, get_called_class());

        //Allows return this directly to return false, saves a line of code - don't have to set_error then return false
        return false;
    }

    /**
     * Returns the last error (if it exists)
     * @return mixed
     */
    public function error() {
        return $this->last_error;
    }

    /**
     * Remove any existing errors
     * @return void
     */
    protected function clear_error() {
        $this->set_error(null);
        $this->bb->clear_error();
    }

	/**
	 * Returns an array containing the result code, and friendly translation
	 *	of the last API request
	 * 
	 * @return array
	 */
	public function result() {
		return array('result' => $this->result, 'friendly' => $this->result_friendly);
	}

    /**
     * Get the service that the child class supposedly defines
     * 
     * @param string $svc
     * 
     * @return string
     */
    protected function get_service($svc) {
        return constant(get_called_class() . '::' . 'SERVICE_' . strtoupper($svc));
    }
    /**
     * Looks for a class constant for cache settings, and falls back on
     *  BBSimpleModel values
     * @param string $setting  (for CACHE_OBJECT_TYPE, use "object_type")
     * @return string
     */
    protected function get_cache_setting($setting) {
        $setting = strtoupper($setting);
		if(defined(get_called_class() . '::CACHE_' . $setting)) {
			return constant(get_called_class() . '::CACHE_' . $setting);
		}
		if(defined('self::CACHE_' . $setting)) {
			return constant('self::CACHE_' . $setting);
		}
		return null;
    }

    /**
     * Iterates through a list of returned objects form the API, and "casts" them as
     * modal classes
     * for example, $bb->tournament->list_my() returns an array, each element being an instance of BBTournament,
     * so you could for example, delete all of your 'SC2' Tournaments like this (be careful, this can be dangerous and irreversable!)
     * 
     * 
     *  $tournies = $bb->tournament->list_my('SC2');
     *  foreach($tournies as &$tournament) {
     *      $tournament->delete();
     *  }
     * 
     * @param array $list
     * @param string $class
     *      By default this method will use cast each object into whatever the current class is
     *      However, this can be overridden by defining the class manually here by setting <$class>
     *      Just beware that it must be a Model class
     * @return array<BBTournament> $class
     */
    protected function wrap_list($list, $class = null) {
        //Determine which class to instantiate if not provided
        if(is_null($class)) {
            $class = get_called_class();
        }

        //Add instantiated modals of each element into a new output array
        $out = array();
        foreach($list as $object) {
            $out[] = new $class($this->bb, $object);
        }

        return $out;
    }

    /**
     * Used by SimpleModel classes for simple list service requests, like searching games / countries
     * 
     * @param string    $svc                Service name (like Game.GameSearch.Search)
     * @param array     $args               Array of arguments to submit
     * @param string    $list_name          Name of the array we should expect to be returned containing the list (example games, or tournies)
     * @param string    $wrap_class
     *          Disabled by default.  Use this value if you want items in the resulting array to be cast
     *          into a certain class (for example, casting a list of tournaments into BBTournament)
     * @return array  - false if it failed
     */
    protected function get_list($svc, $args, $list_name, $wrap_class = null) {
		
        //Try to determine cache settings
        $ttl            = $this->get_cache_setting('ttl_list');
        $object_type    = $this->get_cache_setting('object_type');
		$object_id		= null;

		//For full models, use the current ID
		if($this instanceof BBModel) {
			if(!is_null($this->id)) $object_id = $this->id;
		}

        //For lists, the "ID" will be the arguments used to query the list, to make sure it's uniquely cached
        if(!is_null($args) && sizeof($args) > 0 && is_null($object_id)) $object_id = implode('-', $args);

        //GOGOGO!
        $response = $this->call($svc, $args, $ttl, $object_type, $object_id);

        //Success!! - return the array only
        if($response->result == BinaryBeast::RESULT_SUCCESS) {
            //If the requested $property doesn't exist, return false
            if(isset($response->$list_name)) {

                //Return it wrapped if requested, directly otherwise
                return is_null($wrap_class)
                    ? $response->$list_name
                    : $this->wrap_list($response->$list_name, $wrap_class);
            }
        }

        //Fail!
        return false;
    }

    /**
     * Clears all cache stored for any services defined
     *  in this class, that contain the word 'LIST'
     */
    public function clear_list_cache() {
		if( !is_null($svc = $this->get_cache_setting('ttl_list')) ) {
			$this->clear_service_cache($svc);
		}
    }
    /**
     * Clear specific services associated with this cache_type
     * 
     * @param string|array $services
     */
    public function clear_service_cache($svc) {
        $object_type = $this->get_cache_setting('object_type');
        if(!is_null($object_type)) $this->bb->cache->clear($svc, $object_type);
    }
    /**
     * Clears ALL cache associated with this object_type
     *      For example calling this against a BBTournament, will delete
     *      ALL cache for EVERY tournament in your database
     */
    public function clear_object_cache() {
        $object_type = $this->get_cache_setting('object_type');
        if(!is_null($object_type)) $this->bb->cache->clear(null, $object_type);
    }
}


?>