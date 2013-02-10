<?php

/**
 * Base class for all other object-specific binarybeast service classes (tournaments, teams, etc)
 * 
 * Extends the functionality defined in the SimpleModel class.  Simple model provides most
 *      functionality for error handling / result storage / API interaction etc, 
 *      while this function provides logic for data manipulation + synchronizing the changes with BinaryBeast
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-02-09
 * @author Brandon Simmons
 */
class BBModel extends BBSimpleModel {

    /**
     * We're storing values in this array to allow us to handle intercept an attempt to load a value
     * Made public for print_r, var_dump etc
     * @var array
     */
    public $data = array();

    /**
     * Each time a setting is changed, we store the new setting here, so we know
     * which values to send to the API, instead of dumping everything and sending it all
     * @var array
     */
    protected $new_data = array();

    //Should be defined by children classes to let us know which property to use as the unique ID for this instance
    //For example for a tournament, this value should be tourney_id, so we can refer to $this->tourney_id dynamically
    protected $id_property = 'id';
    public $id;

    //Overloaded by children to define default values to use when creating a new object
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
     * Constructor
     * Stores a reference to the main BinaryBeast library class, 
     * and imports any data or id provided
     * 
     * @param BinaryBeast $bb       Reference to the main API class
     * @param object      $data     Optionally auto-load this object with values from $data
     */
    function __construct(BinaryBeast &$bb, $data = null) {
        $this->bb = $bb;

        //If provided with data, automatically import the values into this instance
        if(is_object($data) || is_array($data)) {
            //Import the provided values directly into this instance
            $this->import_values($data);

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
    }


    /**
     * Use the main BinaryBeast library class to send an API service request to BinaryBeast.com
     * 
     * Overloads the simple model's call() to set the default value of 
     * $wrapped to false
     * 
     * 
     * @see BBSimpleModel::call();
     * 
     * @param string $svc
     * @param array $args
     * @return object
     */
    protected function call($svc, $args, $wrapped = false) {
        //Let BBSimpleModel::call() handle it
        return parent::call($svc, $args, $wrapped);
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
     *      $new_data
     *      $data
     *      $default_data (new objects only)
     *      {$method}()
     *      $id as ${$id_property}
     * 
     * @param string $name
     * @return mixed
     */
    public function &__get($name) {
        
        /**
         * I want __get to return references in most cases, like BBTournament::load()|rounds|teams, etc etc
         * but I also need to be able to return values without sending references, because I also don't want 
         * developers directly editing references to the internal value arrays, because that would
         * circum-vent the __save method, and we would therefore not know to send changes to the API
         * 
         * So if we find the value locally, we use BBModel::ref() to save the value to a temporary
         * reference, and to return THAT
         * 
         * 
         * Regardless of wether or not this object has an ID, we'll first check new_data
         */
        if(isset($this->new_data[$name])) {
            return $this->bb->ref($this->new_data[$name]);
        }

        /**
         * Nothing in new_data, so next we need to figure out if this is a new object, or if we are creating a new one
         */
        if(is_null($this->id)) {
            /**
             * New object, now we can safely return from default_values if set
             */
            if(isset($this->default_values[$name])) {
                return $this->bb->ref($this->default_values[$name]);
            }
        }

        /**
         * So at this point we know it's an existing object (it has an ID to load), so the last thing
         * we can try is to actually ask BinaryBeast for the information of this object
         * 
         * So next: load() (if we haven't already), and try again
         */
        else {
            //We need to load the data first
            if(sizeof($this->data) === 0) {
                $this->load();
                return $this->__get($name);
            }

            //Data already exists, see if our $name is in there
            else if(isset($this->data[$name])) {
                return $this->bb->ref($this->data[$name]);
            }
        }

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
         * Invalid property / method - return null (through the stupid ref() function)
         */
        else return $this->bb->ref(null);
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
        //Read only? - set a warning and return false
        if(in_array($name, $this->read_only)) {
            $this->set_error($name . ' is a read-only property');
            return false;
        }

        //Very simply assign the new value into the new values array
        $this->new_data[$name] = $value;

        //Flag changes have been made
        $this->changed = true;
    }

    /**
     * Handle attempts to directly echo / print this model
     * 
     * What we do is just return the result of var_dump of get_sync_values,
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
        $data = $this->get_sync_values();
        if(!is_null($this->id)) {
            $data[$this->id_property] = $this->id;
        }

        /**
         * Get the string result of var_dump by using an output buffer
         */
        ob_start();
            var_dump($this->$data);
        return ob_end_flush();
    }

    /**
     * Reset this object back to its original state, as if nothing
     * had changed since its instantiation
     * 
     * @return void
     */
    public function reset() {
        $this->new_data = array();

        //We no longer have any unsaved changes
        $this->changed = false;
    }
    
    /**
     * Copy all new changes into $this->data
     * This is used primarily by models that perform batch updates on 
     * child models (like BBTournament on an array of rounds)
     * 
     * So we can tell each round to import the changes without 
     * calling the update server for every single one of them
     * 
     * It's also used internally as a result handler after a successful save()
     * 
     * @return void
     */
    public function sync_changes() {
        //Simple - let get_sync_values figure out which values to merge together
        $this->import_values($this->get_sync_values());

        //This object no longer has unsaved changes
        $this->changed = false;
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
    protected function import_values($data) {
        //Cast it as an array now
        $data = (array)$data;

        //Extract a sub value if requested if the child class defines the key
        if(!is_null($this->data_extraction_key)) {
            //Found it! extract it and cast it as an array again
            if(isset($data[$this->data_extraction_key])) {
                $data = (array)$data[$this->data_extraction_key];
            }
        }

        //Cast as an array for standardization and compatability
        $this->data             = (array)$data;
        $this->new_data         = array();
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
     * @param mixed $id     If you did not provide an ID in the instantiation, they can provide one now
     * @param array $child_args   Allow child classes to define additional paramaters to send to the API (for example the primary key of an object may consist of multiple values)
     * 
     * @return BBModel  Returns itself unless there was an error, in which case it returns false
     */
    public function &load($id = null, $child_args = array()) {

        /**
         * If defining an ID manually, go ahead make sure that we 
         * completely wipe everything that may have changed, and THEN save it
         */
        if(!is_null($id)) {
            $this->reset();
            $this->set_id($id);
        }
        /**
         * Otherwise, make sure we actually HAVE an id to load
         */
        else {
            $id = $this->get_id();
        }

        //No ID to load
        if(is_null($id)) {
            return $this->bb->ref(
                $this->set_error('No ' . $this->id_property . ' was provided, there is nothing to load!')
            );
        }

        //Determine which sevice to use, return false if the child failed to define one
        $svc = $this->get_service('LOAD');
        if(is_null($svc)) {
            return $this->bb->ref(
                $this->set_error('Unable to determine which service to request for this object, please contact a BinaryBeast administrator for assistance')
            );
        }

        //GOGOGO!
        $result = $this->call($svc, array_merge(array(
            $this->id_property => $this->{$this->id_property}
        ), $child_args) );

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
     * Sends the values in this object to the API, to either update or create the tournament, team, etc
     * 
     * By default this method returns the new or existing ID, or false on failure
     *      However for $return_result = true, it will simply return the API's response directly
     * 
     * Child classes may also define additional arguments to send using the second $args argument
     * 
     * @param boolean $return_result        By default this method returns the id or false, but setting this to true will make it return the api's result instead
     * @param array   $args                 Child classes may define additional arguments to send along with the request
     * 
     * @return string|int       false if the call failed
     */
    public function save($return_result = false, $child_args = null) {

        //Initialize some values to send to the API
        $svc    = null;
        $args   = array();

        //Determine the id to update, if there is one
        $id = $this->get_id();

        //Update
        if(!is_null($id) ) {

            //Nothing has changed! Save an error, but since we dind't exactly fail, return true
            if(!$this->changed) {
                $this->set_error('You have not changed any values to submit!');
                return true;
            }

            //GOGOGO! determine the service name, and save the id
            $args = $this->new_data;
            $args[$this->id_property] = $id;
            $svc = $this->get_service('update');
        }

        //Create - merge the arguments with the default / newly set values
        else {
            //Copy default values into $data, so when we sync it will merge them in with the data_new values
            $this->data = $this->default_values;
            $args = array_merge($this->data, $this->new_data);
            $svc = $this->get_service('CREATE');
        }

        //If child defined additonal arguments, merge them in now
        if(is_array($child_args)) $args = array_merge($args, $child_args);

        //GOGOGO
        $result = $this->call($svc, $args);

        /*
         * Saved successfully - reset some local values and return true
         */
        if($result->result == BinaryBeast::RESULT_SUCCESS) {

            //For new objects just created, make sure we extract the id and save it locally
            if(is_null($id)) {
                if(isset($result->{$this->id_property})) {                    
                    $id = $result->{$this->id_property};
                    $this->set_id($id);
                }
            }

            /**
             * Merge the new values into $this->data using the sync() method
             * Which also takes care of updating the $changed flag for us
             */
            $this->sync_changes();

            //Child requested the result directly, do so now before we do anything else
            if($return_result) return $result;

            //Otherwise, return the id to indicate success
            return $id;
        }

        /*
         * Oh noes!
         * Save the response as the local error, and return false
         */
        else {
            return $this->set_error($result);
        }
    }

    /**
     * Delete the current object from BinaryBeast!!
     * @return boolean
     */
    public function delete() {
        //Determine the service name and arguments
        $svc = $this->get_service('DELETE');
        $args = array(
            $this->id_property => $this->{$this->id_property}
        );

        //GOGOGO!!!
        $result = $this->call($svc, $args);

        //DELETED!!!
        if($result->result == BinaryBeast::RESULT_SUCCESS) {
            //Reset all local values and errors
            $this->set_id(null);
            $this->data = array();
            $this->new_data = array();
            $this->clear_error();
            return true;
        }

        /**
         * Error!
         * We'll rely on set_error to translate it into a friendly version
         * for the developer
         */
        else {
            return $this->set_error($result);
        }
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
    protected function set_id($id) {
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
     * Retrive the unique id of this instance, taking into consideration
     * children clases definining the property name, (ie tournament -> tourney_team_id)
     * 
     * @return mixed
     */
    protected function get_id() {
        return $this->{$this->id_property};
    }

    /**
     * Returns an array of values that have changed since the last save() / load()
     * @return array
     */
    public function get_changed_values() {
        return $this->new_data;
    }
    
    /**
     * This method returns a combination of current values + new values
     * But the trick is that it's sensitive to wether or not this object is new..
     * 
     * So for new objects that don't yet have an ID associated with it, it actually
     * returns a combination of default values + changed values
     * 
     * For existing objects, it returns a combination of current values + changed values
     * 
     * @return array
     */
    public function get_sync_values() {
        /**
         * New object: default + new
         */
        if(is_null($this->id)) {
            return array_merge($this->default_values, $this->new_data);
        }
        /**
         * Existing object: existing + new
         */
        return array_merge($this->data, $this->new_data);
    }

}

?>
