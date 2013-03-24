<?php

/**
 * Base class for all manipulatable/CRUD model objects
 * 
 * Extends the functionality defined in the SimpleModel class.  Simple model provides most
 *      functionality for error handling / result storage / API interaction etc, 
 *      while this function provides logic for data manipulation + synchronizing the changes with BinaryBeast
 * 
 * @package BinaryBeast
 * @subpackage Library
 * 
 * @version 3.0.0
 * @date 2013-03-17
 * @author Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
abstract class BBModel extends BBSimpleModel {

    /**
     * Public "preview" of current data values, mostly for the benefit
     *  for var_dump, as it will always have the latest value (even pending save())
     * @var array
     */
    public $data = array();

    /**
     * Array of original data that we can use later if we need to 
     *  revert changed
     * @var array
     */
    protected $current_data = array();

    /**
     * Stores JUST values that have been changed and are pending the next save()
     * @var array
     */
    protected $new_data = array();

    //Should be defined by children classes to let us know which property to use as the unique ID for this instance
    //For example for a tournament, this value should be tourney_id, so we can refer to $this->tourney_id dynamically
    protected $id_property = 'id';
    public $id;

    /**
     * Overloaded by children to define default values to use when creating a new object
     * This also allows us to define public properties for models, IDE auto-completion
     *  however we still want __set and __get to handle the values, so we use the keys in
     *  this array to unset() the properties, so the magic methods will still be called
     */
    protected $default_values = array();

    //Overloaded by children to define a list of values that developers are NOT allowed to change
    protected $read_only = array();

    /**
     * Child classes can define a data extract key to attempt to use
     * This is necessary due to the fact that BinaryBeast does not return consistent formats unfortunately
     * For example Tourney.TourneyLoad.Info returns array[int result, array tourney_inf], so BBTournament
     *      defines this values as 'tourney_info', 
     * But Tourney.TourneyLoad.match returns array[int result, array match_info], so BBMatch
     *      defines this value as 'match_info'
     * 
     * It just let's import_values() know to first try extract from $result['match_info'] before importing
     * $result directly
     * 
     * @access protected
     */
    protected $data_extraction_key;

    /**
     * Flags wether or not this object has any unsaved changes
     */
    public $changed = false;

    /**
     * Keep track of child model classes that have unsaved changes
     */
    protected $changed_children = array();
    protected $changed_children_count = 0;

    /**
     * Allows models to define their parent class, so that we can 
     *  execute the parent's flag_child_changed() method when appropriate
     * 
     * Moved this here once I realized that half of my models were overriding everything so that
     *  they could flag changes
     * 
     * @var BBModel
     */
    protected $parent = null;

	/**
     * Used by a few child classes (like teams and MatchGames) to flag themselves
     *  after having been deleted - to prevent any further manipulation
	 */
	private $orphan = false;

	/**
	 * BBClasses objects can set this "iterating" flag to indicate that we're in the middle
	 *		of a batch update, and should therefore ignore attempts to unflag / flag changes in child classes
	 * @var boolean
	 */
	protected $iterating = false;
	
	/**
	 * To avoid multiple queries, set a flag to track whether not
	 *	we've already asked the API for this object's values
	 */
	protected $loaded = false;

    /**
     * Constructor
     * Stores a reference to the main BinaryBeast library class, 
     * and imports any data or id provided
     * 
     * @param BinaryBeast $bb       Reference to the main API class
     * @param object      $data     Optionally auto-load this object with values from $data
     */
    function __construct(BinaryBeast &$bb, $data = null) {
        parent::__construct($bb);

        //If provided with data, automatically import the values into this instance
        if(is_object($data) || is_array($data)) {
            //Import the provided values directly into this instance
            $this->import_values(array_merge($this->default_values, (array)$data));

            //Attempt to extract this object's id from the imported data
            $this->extract_id();
        }

        /**
         * If provided with an ID, keep track of it so that we know which
         * ID to load when data is accessed
         * It also saves a copy to $this->id, for standardization and convenience
         */
        else if(is_numeric($data) || is_string($data)) {
            $this->set_id($data);
        }

		//For new objects, use default values for values
		else {
            $this->data = $this->default_values;
        }
    }

    /**
     * Whenever we'd like to change a setting, we use this method to do two things:
     *  1) Add it to new_data
     *  2) Update or add it to the public $data array
     * 
     * @param string $key
     * @param mixed $value
     */
    protected function set_new_data($key, $value) {
        //If it's the same, then don't flag it a change
		if(array_key_exists($key, $this->data)) {
            if($this->data[$key] == $value) return;
        }

        //Add it to both $new_data and $data
        $this->data[$key]       = $value;
        $this->new_data[$key]   = $value;

        //Flag changes in this object, and the parent if appropriate
        $this->on_change();
    }
    /**
     * Update a current value without flagging changes, just direclty change it
     * 
     * @param string $key       Value name
     * @param string $value     New value
     * @return void
     */
    protected function set_current_data($key, $value) {
        $this->data[$key]           = $value;
        $this->current_data[$key]   = $value;
    }

	/**
	 * Methods that make any changes to this team use this method to first check to see
	 *	if this team has been orphaned... aka deleted
	 * 
	 * returns a boolean - false if not orphaned, true if orphaned
	 * 
	 * It also will call set_error, so you don't have to handle the error if orphaned
	 * 
	 * @return boolean
	 */
	protected function orphan_error() {
		if($this->orphan) {
			$this->set_error('Team has been removed from the tournament, you can no longer make changes to it');
			return true;
		}
		return false;
	}

    /**
     * Intercept attempts to load values from this object
     * 
     * Note: If you update a value, it will return the new value, even if you have
     * not yet saved the object
     * 
     * However you can always call reset() if you would like to revert the
     * accessible values to the original import values
     * 
     * This method also allows executing methods as if they were properties, for example
     * $bb->load returns BBTournament::load()
     * 
     * Priority of returned value:
     *      {$method}()
     *      $data
     *      $new_data
     *      $id as ${$id_property}
     * 
     * @param string $name
     * @return mixed
     */
    public function &__get($name) {
        
        /**
         * If a method exists with this name, execute it now and return the result
         * Nice for a few reasons - but most importantly, child classes
         * 
         * can now define methods for properties that may require an API Request before
         * returning (like BBTournament->rounds for example)
         */
        if(method_exists($this, $name)) {
            return $this->{$name}();
        }

        /**
         * Next step is to try $data, which hosts a combination depending on the state of this object
         *  For new objects, it's a combination of new_data + default_values
         *  For real objects, it's a combination of new_data + current_data
         * 
         * Since &__get is defined as returning a reference, we return the result through BinaryBeast::ref()
         */
        if(isset($this->data[$name])) {
            return $this->bb->ref($this->data[$name]);
        }

        /**
         * Next thing to try depends on whether this is a new tournament yet to be created,
         *  or an existing one
         * 
         * It exists, do we need to call the load() method to get the current values?
         */
        if(!is_null($this->id)) {
            if(sizeof($this->current_data) === 0) {
                $this->load();
                return $this->__get($name);
            }
        }

        //Invalid property, simply return null
        return $this->bb->ref(null);
    }
    
    /**
     * Returns the default value for the given $key if available
     * @return mixed
     */
    public function default_value($key) {
        if(array_key_exists($key, $this->default_values)) return $this->default_values[$key];
        return null;
    }

    /**
     * Intercepts attempts to set property values
     * Very simply stores in $this->new_data
     * 
     * This method actually returns itself to allow chaining
     * @example
     * $tournament = $bb->tournament->title = 'asdf';
     * 
     * @param string $name
     * @param mixed $value
     * 
     * @return void
     */
    public function __set($name, $value) {
        if($this->orphan_error()) return false;

        //Read only? - set a warning and return false
        if(in_array($name, $this->read_only)) {
            $this->set_error($name . ' is a read-only property');
            return false;
        }

        //Load first if we can
        if(!is_null($this->id)) {
            $this->load();
        }

        //Let set_new_data handle this
        $this->set_new_data($name, $value);
    }

    /**
     * Handle attempts to directly echo / print this model
     * 
     * What we do is just return the result of var_dump of $data,
     * and add the id (if available)
     * 
     * We can do so by creating an output buffer, performing a var_dump, and
     * then ending the buffer and returning the values queued within it
     * 
     * @return string       Value to print / echo
     */
    public function __toString() {
        /**
         * Compile the values to dump
         */
        $out = $this->data;
        if(!is_null($this->id)) {
            $out[$this->id_property] = $this->id;
        }

        /**
         * Get the string result of var_dump by using an output buffer
         */
        ob_start();
            var_dump($out);
        return ob_end_flush();
    }

    /**
     * Reset this object back to its original state, as if nothing
     * had changed since its instantiation
	 * 
	 * Warning: this method resets all changed children too!!!!
     * 
     * @return void
     */
    public function reset() {
        if($this->orphan_error()) return;

        //Revert changes
        $this->new_data = array();
		$this->data		= array();

        //Reset the live data "view"
        $this->data = $this->get_sync_values();
        
		//Reset ALL changed child classes
		$this->reset_changed_children();

        //We no longer have any unsaved changes
        $this->changed = false;

        //Unflag child-changes in parent
        $this->on_change();
    }

    /**
     * Update internal value arrays to merge any 
     *  new values in with the current_values and the live data "view" array
     * 
     * So we can tell each round to import the changes without 
     * calling the update server for every single one of them
     * 
     * It's also used internally as a result handler after a successful save()
     * 
     * @return void
     */
    public function sync_changes() {
        if($this->orphan_error()) return;

        //Simple - let get_sync_values figure out which values to merge together
        $this->import_values($this->get_sync_values());

		//Reset $changed flags
		$this->on_change();
    }

    /**
     * When values for this object (tournament, team, game, etc), we use this method
     * to assign them to local data
     * 
     * This method is overridden by children classes in order to extract the specific
     * properties containing data, but they then pass it back here
     * to actually cache it locally
     * 
     * Note that $this->data is cast as an array, for consistence access
     * 
     * If you provide a value for $extract, it will attempt to use that value as a key to $extract from within $data first
     * Meaning if $data = ['result' => 500, 'tourney_info' => {'title' => blah'}},
     *  a $key value of 'tourney_info' means {'title' => blah'} will be extracted into $this->data
     *  otherwise the $this->data would end be the entire $data input
     * 
     * Lastly, it resets new_data
     * 
     * @param object    $data
     * @return void
     */
    public function import_values($data) {
        if($this->orphan_error()) return false;

        //Cast it as an array now
        $data = (array)$data;

        /**
         * If the child defined a key for extraction, attempt to use that 
         *  value from the input now, instead of the entire $data input
         */
        if(!is_null($this->data_extraction_key)) {
            //Found it! extract it and cast it as an array
            if(isset($data[$this->data_extraction_key])) {
                $data = (array)$data[$this->data_extraction_key];
            }
        }

        //This will be our new current_value set, update our public $data to match, and empty changed data array
        $this->data             = $data;
        $this->current_data     = $data;
        $this->new_data         = array();

		//Flag object as loaded, so load() will not ask the API again
		$this->loaded = true;
    }

    /**
     * Call the child-defined load service
     * 
     * This method returns the current instance, allowing us to 
     * chain like this: 
     * @example $tournament = $bb->tournament->load('id_here');
     * Which is basically tournament returning a new instance, then 
     * calling the load method within that, which returns itself (as long as nothing went wrong)
     * 
     * @param mixed     $id             If you did not provide an ID in the instantiation, they can provide one now
     * @param array     $child_args     Allow child classes to define additional paramaters to send to the API (for example the primary key of an object may consist of multiple values)
     * @param boolean   $skip_cache     Disabled by default - set true to NOT try loading from local cache
     * 
     * @return self Returns itself unless there was an error, in which case it returns false
     */
    public function &load($id = null, $child_args = array(), $skip_cache = false) {
        
        /**
         * If defining a new ID manually, go ahead make sure that we 
         * completely wipe everything that may have changed, and THEN load it
         */
        if(!is_null($id) && $id != $this->id) {
            //Can't load from a different id an ID is already set, and we've already loaded
            if($this->loaded) {
                $this->set_error('Data has already been set for this ' . get_called_class() . ' object, you cannot load() using a different id');
                return $this->bb->ref(false);
            }
            $this->reset();
            $this->set_id($id);
        }

        //No ID to load
        if(is_null($this->id)) {
            return $this->bb->ref(
                $this->set_error('No ' . $this->id_property . ' was provided, there is nothing to load!')
            );
        }

        //Already loaded
        if($this->loaded) return $this;

        //Determine which sevice to use, return false if the child failed to define one
        $svc = $this->get_service('LOAD');
        if(is_null($svc)) {
            return $this->bb->ref(
                $this->set_error(get_called_class() . 'does not seem to have defined a loading service, BBModel::save expects a constant value for SERVICE_LOAD')
            );
        }

		//Cache settings
        $ttl            = null;
        $object_type    = null;
        if(!$skip_cache) {
            $ttl			= $this->get_cache_setting('ttl_load');
            $object_type	= $this->get_cache_setting('object_type');
        }

        //GOGOGO!
        $result = $this->call($svc, array_merge(array(
            $this->id_property => $this->id
        ), $child_args), $ttl, $object_type, $this->id);

        //If successful, import it now
        if($result->result == BinaryBeast::RESULT_SUCCESS) {
            $this->import_values($result);
            return $this;
        }

        /**
         * OH NOES! The ID is most likely invalid, the object doens't exist
         * However we'll leave it up to set_error to translate the code for us
         */
        else {
            return $this->bb->ref($this->set_error($result));
        }
    }

    /**
     * Re-loads all data for this object, fresh from the API (skips cache)
     * 
     * @return self - false if there was an error loading
     */
    public function &reload() {
        //Easy as resetting the loaded flag, so that load() won't skip if already loaded
        $this->loaded = false;

        //Call load, and skip cache
        return $this->load(null, array(), true);
    }
    /**
     * Trigger a reload next time any data is accessed, to allow
     *  refreshing on demand without having to make a billion API requests for arrays of child objects
     */
    public function flag_reload() {
        $this->loaded = false;
    }

    /**
     * Sends the values in this object to the API, to either update or create the tournament, team, etc
	 * 
	 * Important note: this method will NOT save unsaved children
     * 
     * By default this method returns the new or existing ID, or false on failure
     *      However for $return_result = true, it will simply return the API's response directly
     * 
     * Specific classes may also define additional arguments to send using the second $args argument
     * 
     * @param array   $args		child classes may define additional arguments to send along with the request
     * 
     * @return string|int       false if the call failed
     */
    public function save($return_result = false, $child_args = null) {
        if($this->orphan_error()) return false;

        //Initialize some values to send to the API
        $svc    = null;
        $args   = array();

        //Update
        if(!is_null($this->id) ) {
            //if they call save() with no changes, save a warning in BinaryBeast::error_history
            if(!$this->changed) {
                $this->set_error('Warning: save() saved no changes, since nothing has changed');
                return $this->id;
            }
            //Changed is true but new_data is empty, which means that we have changed children - therefore just return true without an warning
            if(sizeof($this->new_data) == 0) return $this->id;

            //GOGOGO! determine the service name, and save the id
            $args = $this->new_data;
            $args[$this->id_property] = $this->id;
            $svc = $this->get_service('update');
        }

        //Create - merge the arguments with the default / newly set values
        else {
            //Copy default values into $data, so when we sync it will merge them in with the data_new values
            $this->data = array_merge($this->default_values, $this->data);
            $args = array_merge($this->data, $this->new_data);
            $svc = $this->get_service('CREATE');
        }

        //If child defined additonal arguments, merge them in now
        if(is_array($child_args)) $args = array_merge($args, $child_args);

        //GOGOGO
        $result = $this->call($svc, $args);

        /*
         * Saved successfully - update some local values and return true
         */
        if($result->result == BinaryBeast::RESULT_SUCCESS) {

			//Clear cache for this svc
			$this->clear_id_cache($this->get_service('load'));

            //For new objects just created, make sure we extract the id and save it locally
            if(is_null($this->id)) {
                if(isset($result->{$this->id_property})) {                    
                    $this->id = $result->{$this->id_property};
                    $this->set_id($this->id);
                }
            }

			//Merge all unsaved changes into current_data, and unflag ourselves as having unsaved changes
            $this->sync_changes();

            //Child requested the result directly, do so now before we do anything else
            if($return_result) return $result;

            //Success!
            return $this->id;
        }

        //Failure!
        else return $this->set_error('Error returned from svc ' . $svc . ', please refer to BinaryBeast::results_history for details');
    }

    /**
     * Delete the current object from BinaryBeast!!
	 * 
	 * If false is returned, use $object->result() and $object->error() to see why
	 * 
     * @return boolean
     */
    public function delete() {
         if($this->orphan_error()) return false;

        //Only call the API if we have an id
        if(!is_null($this->id)) {
            //Determine the service name and arguments
            $svc = $this->get_service('DELETE');
            $args = array($this->id_property => $this->id);

            //GOGOGO!!!
            $result = $this->call($svc, $args);

            //DELETED!!!
            if($result->result == BinaryBeast::RESULT_SUCCESS) {
                //Reset all local values and errors
                $this->set_id(null);
                $this->data = array();
                $this->new_data = array();
            }

            //API request failed - developers should evaluate last_error and last_result for details
            else return $this->set_error($result);
        }

        //Now that we've deleted successfully, we can remove ourselves from our parent (if we have one)
        if(!is_null($this->parent)) {
            $this->parent->remove_child($this);
            $this->parent = &$this->bb->ref(null);
            $this->orphan = true;
        }

        //Successfully deleted
        return true;
    }

    /**
     * Returns a boolean based on whether this object is
     *  new (aka has no id), or existing
     * 
     * @return boolean
     */
    public function is_new() {
        return is_null($this->id);
    }
    /**
     * Returns a boolean that reflects whehter or not this object
     *  has been orphaned
     * 
     * Orphaned objects are BBModel instances that have been removed from their parent BBModel objects,
     *  for example when you call $team->delete(), if you still have a copy of that team in a variable, it
     *  will become orphaned - since it no longer has a tournament associated with it
     * 
     * @return boolean
     */
    public function is_orphan() {
        return $this->orphan;
    }

    /**
     * Save the unique id of this instance, taking into consideration
     * children clases definining the property name, (ie tournament -> tourney_team_id)
     * 
     * It also saves a reference in this->id for internal use, and it's nice to have a
     * standardized property name for ids, so any dev can use it if they wish
     * 
     * @param int|string $id
     * @return void
     */
    public function set_id($id) {
		//Can't change orphaned children
		if($this->orphan_error()) return false;

        $this->{$this->id_property} = $id;
        if($this->id_property !== 'id') $this->id = &$this->{$this->id_property};
    }

    /**
     * Attempt to extract the id value from $this->data, and assign it to $ths->{$id_property}
     * 
     * @return boolean - true if found 
     */
    protected function extract_id() {
        if(isset($this->data[$this->id_property])) {
            $this->set_id($this->data[$this->id_property]);
            return true;
        }

        //Either not instantiated or wasn't present in $data
        return false;
    }

    /**
     * Returns an array of values that have changed since the last save() / load()
     * @return array
     */
    public function get_changed_values() {
        return $this->new_data;
    }

    /**
     * Returns an array of up to date values, merging
	 *		default + current + new 
     * 
     * @return array
     */
    public function get_sync_values() {
		return array_merge($this->default_values, $this->current_data, $this->data);
    }

    /**
     * Allows models to keep track of changes that their child objects make
     * 
     * For example for a tournament, we want to make sure that when we save(), we know
     *      if there are any rounds or teams that need to be saved too
     * 
     * When a child is flagged as having unsaved changes, we store a reference
     *      to the child in $this->changed_children['type'][], and also update
     *      the integer changed_children_count
     * 
     * @param BBModel $child - a reference to child object to be tracked
     * @return void
     */
    public function flag_child_changed(BBModel &$child) {
		//Parents set an iteration flag to indicate a batch update, therefore telling us to skip individual flagging / unflagging of children
		if($this->iterating) return;

		//Figure out which array to use, based on the class of the provided child (also creates the array if it doesn't already exist)
		$array = &$this->get_changed_children(get_class($child));

        //If it isn't already being tracked, add it now and increment the changed_children counter
        if(!in_array($child, $array)) {
            $array[] = &$child;
            ++$this->changed_children_count;
        }

        //Flag this entire model has having unsaved changes
        $this->changed = true;

        //Propogate up, flag all parents of parents of parents etc etc
        $this->on_change(false);
    }
    /**
     * Removes any references to a child class (team/round for tournaments.. etc), so we know that
     * the child has no unsaved changes
     * 
     * @param BBModel $child - a reference to the child to unflag
     * @return void
     */
    public function unflag_child_changed(BBModel &$child) {
		//Parents set an iteration flag to indicate a batch update, therefore telling us to skip individual flagging / unflagging of children
		if($this->iterating) return;

		//Figure out which array to use, based on the class of the provided child (also creates the array if it doesn't already exist)
		$array = &$this->get_changed_children(get_class($child));

        //Try to find the child's index within the changed_children array
        if(($key = array_search($child, $array)) !== false) {
            unset($array[$key]);
            //Re-index the array
            $array = array_values($array);
            --$this->changed_children_count;
        }

        //Set the $changed flag
        $this->calculate_changed();

        /*
		 * If determined that we no longer have unsaved changes..
		 * Propogate up, flag all parents of parents of parents etc etc
		 */
        if(!$this->changed) $this->on_change(false);
    }

    /**
     * Attempts to return the matching child within the provided
     *  array of child BBModel instances
     * 
     * @todo don't initalize $ids unless its needed
     * 
     * @param BBModel|int $child
     * @param BBModel[] $children
     * @return BBModel|null
     */
    protected function &get_child($child, &$children) {
        //Compile an array of ids for searching by ID in case we can't find the child itself
        $ids = array();
        foreach($children as $sub_child) $ids[] = $sub_child->id;

        //If given a BBModel directly, try searching for it in the array
        if($child instanceof BBModel) {
            //See if the object itself is in our aray
            if(($key = array_search($child, $children)) === false) {
                //We have no matching instance, try comparing ids in case our local teams have changed (but only if we HAVE an ID)
                if(!is_null($child->id)) {
                    $key = array_search($child->id, $ids);
                }
            }
        }

        //If it's not a BBModel, try to find the team by id
        else $key = array_search($child, $ids);

        //Success!
        if($key !== false) return $children[$key];

        //Team is not in thie tournament
        return $this->bb->ref(null);
    }

    /**
     * Retrieve an array of children of the provided $class name, that have been flagged
     *  as having unsaved changes
     * 
     * @param string $class		null returns ALL children with changes
     * @return array
     */
    public function &get_changed_children($class = null) {
		if(is_null($class)) return $this->changed_children;

		//Make sure an array exists for this class first, then return
        if(!isset($this->changed_children[$class])) $this->changed_children[$class] = array();
        return $this->changed_children[$class];
    }

    /**
     * Empties the lists of children being tracked as having changes
     * 
     * If no $class is provided, ALL children will be removed
     * 
     * @param string $class
     */
    protected function reset_changed_children($class = null) {
		//When we reset all children, ignore them when they call our unflag_changed_children() method
		$iterating = $this->iterating;
		$this->iterating = true;

        //clear them all
        if(is_null($class)) {
			//Call reset() on each child
			foreach($this->changed_children as $children) $this->reset_children($children);

			//Empty our changed_child tracking array and counter
            $this->changed_children = array();
            $this->changed_children_count = 0;
        }

        //Specific child type
        else {
			//Grab a reference to the array holding this $class type
			$children = $this->get_changed_children($class);

			//decrement our changed-children counter based on the number of children by $class type
            $this->changed_children_count -= sizeof($children);

			//Tell each child to reset(), and empty the array
			$this->reset_children($children);
            $this->changed_children[$class] = array();
        }

        //Set the $changed flag
        $this->calculate_changed();

		//Reset our iterating flag to whatever it was before we started
		$this->iterating = $iterating;
    }
	/**
	 * Used by reset_changed_children to loop through an array
	 *	of children, and to tell each one to reset() themselves
	 * 
	 * @param BBModel[] $children
	 */
	private function reset_children(&$children) {
		foreach($children as &$child) $child->reset();
	}

    /**
     * Remove a child class from this object
     *  It's up to each class to define how this works however, the second
     *  paramater needs to be a reference to the array the class
     *  is using to track them 
     *
     * For example for touranments, you call tournament->remove_child($team),
     *  and it will call BBModel, providing the $teams array
     * 
     * @param BBModel   $child
     * @param array     $children
     * @param bool      $preserve       false by default - set this to true to skip the step where
     *      we set the value to null before removing it - thereby setting all references to null
     *      This would be undesirable for something like a match that was reported however, and all you want to do
     *          is remove it from the list of open_matches
     * @return boolean      true if removed
     */
    protected function remove_child_from_list(BBModel &$child, &$children = null, $preserve = false) {
        if(!is_array($children)) return false;

        //First - run the unflag method to remove from teams_changed - and re-calculate the $changed flag
        $this->unflag_child_changed($child);

        //Finally, remove it from the teams array (use teams() to ensure the array is popualted first)
		if( ($key = array_search($child, $children)) !== false) {
            //Set the actual value to null, in hopes of setting references to null too - but skip if $preserve was set
            if(!$preserve) $children[$key] = null;

            //Remove the value from the array
			unset($children[$key]);

            //Re-index the array
            $children = array_values($children);

            //Return true to indicate that we found and removed the object
            return true;
		}

        //Didn't find anything
        return false;
    }

    /**
     * Overloaded by models so that they can decide which array to use, and then to pass
     *  the input along to remove_child 
     * 
     * @param BBModel $child
     * @param bool    $preserve - enable to attempt setting any references to null
     */
    public function remove_child(BBModel &$child, $preserve = false) {}

    /**
     * Used internally to remove any children being tracked internally, that do not exist on the API - 
     *      aka they don't have an id - so we're deleting all unsaved children
     * 
     * @param BBModel[] $children
     */
    protected function remove_new_children(&$children) {
        if(is_array($children)) {
            foreach($children as &$child) {
                if($child->is_new()) {
                    $child->delete();
                    $child = null;
                }
            }
        }
    }

    /**
     * Called when something changes, so that we can decide
     *  notify parent classes of unsaved changes when appropriate
     * 
     * @param bool $changed    true by default, set to false to skip notifying the parent class of changes
     * @return void
     */
    protected function on_change($flag_parent = true) {
        if($this->orphan_error()) return false;

        //Determine the $changed flag
        $this->calculate_changed();

		//If we have a parent defined, let them know we either now or no longer have unsaved changes
        if(!is_null($this->parent) && $flag_parent) {
            if($this->changed)	$this->parent->flag_child_changed($this);
            else                $this->parent->unflag_child_changed($this);
        }
    }
	/**
	 * Clears all cache associated with this object_id+object_type
	 * 
	 * @param string|array $svc		Optionally limit this to certain services
	 */
	public function clear_id_cache($svc = null) {
		$object_type = $this->get_cache_setting('object_type');
		if(!is_null($this->id) && !is_null($object_type)) {
			$this->bb->clear_cache($svc, $object_type, $this->id);
		}
	}
    
    /**
     * Sets the public $changed flag based on 
     *  if we have any new data in $new_data, and if we have
     *  any unsaved children
     */
    protected function calculate_changed() {
        $this->changed = sizeof($this->new_data) > 0 || $this->changed_children_count > 0;
    }
}

?>