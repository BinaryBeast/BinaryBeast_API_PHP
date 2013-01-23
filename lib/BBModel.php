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
     */
    function __construct(&$bb) {
        $this->bb = $bb;
    }

    /**
     * Intercept attempts to load values from this object
     * 
     * @param type $name
     */
    public function &__get($name) {
        
        //First see if we have the value already in $data
        
        //Since the value does not exist, see if we've already loaded this object - if not, then load it
        
        //Invalid property requested, return null

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
     * Lastly, it resets new_data
     * 
     * @param int    $result
     * @param object $data
     * @return void
     */
    protected function import_values($result, $data) {
        $this->result           = $result;
        $this->data             = $data;
        $this->original_data    = $data;
        $this->new_data         = array();
    }

    /**
     * Just a placeholder really - the child class is responsible for this, returned data for each object is unfortunately too variable for a consistent loading method
     */
    private function load(){}

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
}

?>