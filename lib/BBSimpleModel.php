<?php

/**
 * Base class for models - simple and full
 * 
 * The SimpleModel class provides most of the error handling / result storage, 
 *      logic for determining a service name, and so on
 * 
 * It does not however, provide any functionality for updating, creating, or deleting data
 * 
 * That's why I've separated that logic into a different model: BBModel
 * 
 * It's not to save space or ram or whatever, it would make little difference anyway,
 *  the point is to avoid any confusion with developers as to what
 *  data they have the ability to manipulate
 * 
 * So this class is used primary for service wrapper hosting (like BBGame)
 *      while BBModel is used for full manipulable objects for creating/creating/updating - like BBTournament
 *  
 * 
 * @package     BinaryBeast
 * @subpackage  Library
 * 
 * @version 3.0.4
 * @date    2013-06-05
 * @since   2013-02-08
 * @author  Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
class BBSimpleModel {

    /**
     * Reference to the main API library class
     * @ignore
     * @var BinaryBeast
     */
    protected $bb;

    /**
     * If loading failed, stored the result
     * @var object
     */
    protected $last_error = null;

    /**
     * API Service name for loading object details
     * @var string
     */
    const SERVICE_LOAD      = null;
    /**
     * API Service name for listing loading objects
     * @var string
     */
    const SERVICE_LIST      = null;
    /**
     * API Service name for creating a new object
     * @var string
     */
    const SERVICE_CREATE    = null;
    /**
     * API Service name for updating an existing object
     * @var string
     */
    const SERVICE_UPDATE    = null;
    /**
     * API Service name for deleting the object
     * @var string
     */
    const SERVICE_DELETE    = null;

    /**
     * Caching type
     *
     * Allows child classes to define the object_type when caching api responses, and
     *  ttls for certain tasks (listing / loading)
     * 
     * cache all results for 10 minutes by default
     *
     * @param int
     */
    const CACHE_OBJECT_TYPE = null;
    /**
     * Default TTL for cached list results - 10 minutes
     * @var int
     */
    const CACHE_TTL_LIST    = 10;
    /**
     * Default TTL for cached load results - 10 minutes
     * @var int
     */
    const CACHE_TTL_LOAD    = 10;

	/**
     * Unique object ID, to insure each object is unique, even if
     *  their values are identical
     * @ignore
     * @var string
	 */
	protected $uid;

    /**
     * Constructor
     * Stores a reference to the main BinaryBeast library class,
     *  and generates a UID to guarantee all new objects will be unique (important while flagging child changes, 
     *      because PHP can confuse new objects if they haven't had any unique settings set)
     * 
     * @ignore
     * 
     * @param BinaryBeast $bb       Reference to the main API class
     */
    function __construct(BinaryBeast &$bb) {
        $this->bb = &$bb;

		//Set an arbitrary uid to insure that each object is unique
		$this->uid = uniqid();
    }

    /**
     * Calls the BinaryBeast API using the given service name and arguments, 
     * and grabs the result code so we can locally stash it
     * 
     * @ignore
     * 
     * @param string $svc    		Service to call (ie Tourney.TourneyCreate.Create)
     * @param array $args     		Arguments to send
	 * @param int $ttl				If you configured the BBCache class, use this to define how many minutes this result should be cached
	 * @param int $object_type		For caching objects: see BBCache::type_ constants
     * @param mixed $object_id		For caching objects: The id of this the object, like tourney_id or tourney_team_id
     * @return object
     */
    protected function call($svc, $args = null, $ttl = null, $object_type = null, $object_id = null) {
        //Use BinaryBeast library to make the actual call
        $response = $this->bb->call($svc, $args, $ttl, $object_type, $object_id);

        //Finally, return the response
        return $response;
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
     * @ignore
     * 
     * @param array|string $error
     * @return boolean<br />
     * Always returns <b>false</b>
     */
    protected function set_error($error) {
        //Send to the main BinaryBeast API Library, and locally save whatever is sent back (a standardized format)
        $this->last_error = $this->bb->set_error($error, $this->get_class_name());

        //Allows return this directly to return false, saves a line of code - don't have to set_error then return false
        return false;
    }

    /**
     * Returns the last error (if it exists)
     * @return object|null
     */
    public function error() {
        return $this->last_error;
    }

    /**
     * Remove any existing errors
     * @ignore
     * @return void
     */
    protected function clear_error() {
        $this->set_error(null);
    }

    /**
     * Get the service that the child class supposedly defines
     * 
     * @ignore
     * 
     * @param string $svc
     * @return string
     */
    protected function get_service($svc) {
        return constant($this->get_class_name() . '::' . 'SERVICE_' . strtoupper($svc));
    }
    /**
     * Looks for a class constant for cache settings, and falls back on
     *  BBSimpleModel values
     * 
     * @ignore
     * 
     * @param string $setting  (for CACHE_OBJECT_TYPE, use "object_type")
     * @return string
     */
    protected function get_cache_setting($setting) {
        $setting = strtoupper($setting);
		if(defined($this->get_class_name() . '::CACHE_' . $setting)) {
			return constant($this->get_class_name() . '::CACHE_' . $setting);
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
     * @ignore
     * 
     * @param array $list
     * @param string $class
     *      By default this method will use cast each object into whatever the current class is
     *      However, this can be overridden by defining the class manually here by setting <$class>
     *      Just beware that it must be a Model class
     * @return BBModel[] $class
     */
    protected function wrap_list($list, $class = null) {
        //Determine which class to instantiate if not provided
        if(is_null($class)) {
            $class = $this->get_class_name();
        }

        //Add instantiated modals of each element into a new output array
        $out = array();
        foreach($list as $object) {
            $out[] = $this->bb->get_model($class, $object);
        }

        return $out;
    }

    /**
     * Used by SimpleModel classes for simple list service requests, like searching games / countries
     * 
     * @ignore
     * 
     * @param string    $svc                Service name (like Game.GameSearch.Search)
     * @param array     $args               Array of arguments to submit
     * @param string    $list_name          Name of the array we should expect to be returned containing the list (example games, or tournies)
     * @param string    $wrap_class
     *          Disabled by default.  Use this value if you want items in the resulting array to be cast
     *          into a certain class (for example, casting a list of tournaments into BBTournament)
     * @return object[]|BBModel[]|boolean
     * - Array of simple objects by default
     * - Array of BBModel instances, if you defined a value for <var>$wrap_class</var>
     * - False if the call failed
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
     * Filters the provided list if model objects,
     *  returning only the ones that have a matching $key => $value pair
     *
     * @since 2013-06-04
     *
     * @param BBModel[]
     * @param string $key
     * @param mixed $value
     * @return BBModel[]
     */
    public function filter_list($list, $key, $value) {
        //Init result array
        $filtered = array();

        foreach($list as $object) {
            if($object->{$key} == $value) {
                $filtered[] = $object;
            }
        }

        //QAPLA!
        return $filtered;
    }

    /**
     * Clears all cache stored for any services defined
     *  in this class, that contain the word 'LIST'
     */
    public function clear_list_cache() {
        if( !is_null($ttl = $this->get_cache_setting('ttl_list')) ) {
            if( !is_null($svc = $this->get_service('list')) ) {
                $this->clear_object_service_cache($svc);
            }
        }
    }
    /**
     * Clear specific services associated with this cache_typ
     * 
     * Note: The scope will be limited to the classes' CACHE_OBJECT_TYPE object_ttl
     * 
     * Use {@link BBSimpleModel::clear_service_cache} for removing
     *  cached API responses, without worrying about the object type values
     * 
     * 
     * @param string|array $svc
     * @return boolean
     */
    public function clear_object_service_cache($svc) {
        $object_type = $this->get_cache_setting('object_type');
        if(!is_null($object_type)) $this->bb->clear_cache($svc, $object_type);
    }
    /**
     * Clear specific services results
     * 
     * Note: This method does NOT limit the scope of the services, 
     * it will remove ANY response cache that used the given $svc name
     * 
     * @param string|array $svc
     * @return boolean
     */
    public function clear_service_cache($svc) {
        return $this->bb->clear_cache($svc);
    }
    /**
     * Clears ALL cache associated with this object_type
     *      For example calling this against a BBTournament, will delete
     *      ALL cache for EVERY tournament in your database
     */
    public function clear_object_cache() {
        $object_type = $this->get_cache_setting('object_type');
        if(!is_null($object_type)) $this->bb->clear_cache(null, $object_type);
    }

    /**
     * PHP 5.2 compatibility solution, allows us
     *  to get the class name of class implementations, as
     *  long as they overload this method
     *
     * @param mixed $object
     * Optionally define the object to evaluate
     * Defaults to $this
     *
     * @param boolean $extension_base
     * <b>Default:</b> false
     * If this object is a custom model extension, pass
     *  true to return the base model class name
     *
     * @ignore
     * @since 2013-05-14
     * @return string
     */
    protected function get_class_name($object = null, $extension_base = false) {
        if(is_null($object)) $object = $this;

        $class = get_class($object);

        //Extension base not requested, return now
        if(!$extension_base) return $class;

        //Simply look at the configuration
        if( ($key = array_search($class, $this->bb->config->models_extensions)) !== false) {
            return $key;
        }

        //Return as is
        return $class;
    }
}