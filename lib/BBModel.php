<?php

/**
 * Base class for all other object-specific binarybeast service classes (tournaments, teams, etc)
 * 
 * @version 0.0.5
 * @date 2013-01-22
 * @author Brandon Simmons
 */
class BBModel {

    /**
     * Reference to the main API class
     * @var BinaryBeast
     */
    private $bb;

    /**
     * Store a publically accessible "result" code from loading
     * @var type
     */
    public $result;

    /**
     * If loading failed, stored the result
     * @var type
     */
    private $error;

    /**
     * We're storing values in this array to allow us to handle intercept an attempt to load a value
     */
    protected $data = array();

    /**
     * Each time a setting is changed, we store the new setting here, so we know
     * which values to send to the API, instead of dumping everything and sending it all
     * @var array
     */
    protected $new_data = array();

    /**
     * Storing previous data values allows us to provide methods for reverting changes
     */
    protected $original_data = array();

    //Flag wether or not we've loaded this object
    protected $loaded = false;

    //Should be defined by children classes to let us know which property to use as the unique ID for this instance
    protected $id_property = 'id';

    //Can be overloaded by children to define default values to use when creating a new object
    protected $default_values = array();

    /**
     * Constructor - accepts a reference the BinaryBeats API $bb
     * 
     * @param BinaryBeast $bb       Reference to the main API class
     * @param object      $data     Optionally auto-load this object with values from $data
     */
    function __construct(&$bb, $data = null) {
        $this->bb = $bb;
        
        //If provided with data, automatically import the values into this instance
        if(is_object($data)) {
            $this->result = 200;
            $this->import_values($data);
        }

        //If provided with an ID, keep track of it so that we know which
        //ID to load when data is accessed
        else if(is_numeric($data) || is_string($data)) {
            $this->{$this->id_property} = $data;
        }
    }

    /**
     * Intercept attempts to load values from this object
     * 
     * Note: If you update a value, it will return the new value, even if you have
     * not yet saved the object
     * 
     * @param type $name
     */
    public function &__get($name) {

        //First see if we have the value already in $data, or in new_data
        if(isset($this->new_data[$name]))   return $this->new_data[$name];
        if(isset($this->data[$name]))       return $this->data[$name];

        //Since the value does not exist, see if we've already loaded this object
        if(sizeof($this->data) === 0) {
            $this->load();
            //After loading, try again
            return $this->$name;
        }

        //Invalid property requested, return null
        //@todo consider throwing an error instead
        return null;
    }

    /**
     * Intercepts attempts to set property values
     * 
     * @param string $name
     * @param mixed $value
     * 
     * @return void
     */
    public function __set($name, $value) {

        //Determine wether or not this is new 
        
    }

    /**
     * In case anything has been changed, this method can be used
     * to reset this objects attributes to their original values
     * 
     * @return void
     */
    public function reset() {
        if(!is_null($this->original_data)) {
            if(sizeof($this->original_data) > 0 ) {
                $this->data = $this->original_data;
            }
        }
    }

    /**
     * When values for this object (tournament, team, game, etc), we use this method
     * to assign them to local data
     * 
     * It also updaates the original_data value, allowing us to call reset();
     * 
     * This method is overridden by children classes in order to extract the specific
     * properties containing data, but they then pass it back here
     * to actually cache it locally
     * 
     * Lastly, it resets new_data
     * 
     * @param int    $result
     * @param object $data
     * @return void
     */
    protected function import_values($data) {
        $this->data             = $data;
        $this->original_data    = $data;
        $this->new_data         = array();
    }

    /**
     * Call the child-defined load service
     * 
     * @param mixed $id     If you did not provide an ID in the instantiation, they can provide one now
     * 
     * @return boolean - true if result is 200, false otherwise
     */
    protected function load($id = null) {

        //If no ID was provided, we can't do anything
        if(!is_null($id)) $this->{$this->id_property} = $id;
        if(is_null($this->{$this->id_property})) return false;

        //Determine which sevice to use, return false if the child failed to define one
        //@todo throw an error here if it can't determine the service name
        $svc = $this->get_service('SERVICE_LOAD');
        if(is_null($svc)) return false;
        
        //GOGOGO!
        $result = $this->bb->call($svc, array(
            $this->id_property => $this->{$this->id_property}
        ));

        global $doc;
        $doc->ThrowError($result);

        //If successful, import it now
        if($result->result == BinaryBeast::RESULT_SUCCESS) {
            $this->result = 200;
            $this->import_values($result);
        }

        //OH NOES!
        else {
            $this->result = $result->result;
            return false;
        }
    }

    /**
     * Sends the values in this object to the API, to either update or create the tournament, team, etc
     * 
     * @return boolean
     */
    public function save() {

        //Initialize some arguments for the API
        $svc    = null;
        $args   = array();

        //Update
        if( !is_null($this->{$this->id_property}) ) {
            //Nothing has changed!
            if(sizeof($this->new_data) === 0) {
                $this->error = array('message' => 'You have no changed any values to submit!');
                return false;
            }
 
            //GOGOGO! determine the service name, and save the id
            $args[$this->id_property] = $this->{$this->id_property};
            $svc = self::SERVICE_UPDATE;
        }

        //Create - merge the arguments with the default values
        else {
            $args = array_merge($this->new_data, $this->default_values);
            $svc = self::SERVICE_CREATE;
        }

        var_dump(array($svc, $args));
        return;

        //GOGOGO
        $result = $this->bb->call($svc, $args);

        /*
         * Saved successfully - reset some local values and return true
         */
        if($result->result == BinaryBeast::RESULT_SUCCESS) {
            $this->import_values($result->result, $args);

            return true;
        }

        /*
         * Oh noes!
         * Save the response as the local error, store the result, and return false
         */
        else {
            $this->error = $result;
            $this->result = $result->result;

            return false;
        }
    }
    

    /**
     * Delete the current object from BinaryBeast!!
     * @return boolean
     */
    private function delete() {
        //Determine which method to do

        //Delete!!!
    }

    /**
     * Returns the last error (if it exists)
     * @return mixed
     */
    public function error() {
        return $this->error;
    }

    /**
     * Get the service that the child class supposedly defines
     * 
     * @param string $svc
     * 
     * @return string
     */
    private function get_service($svc) {
        return constant(get_called_class() . '::' . $svc);
    }
}

?>