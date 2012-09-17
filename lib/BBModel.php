<?php

/**
 * This class is used as a bass class for all model-specific data type classes, like Teams, Tournaments, leagues, Maps, etc
 */
class BBModel {

    /**
     * Reference to the main API class
     * @var BinaryBeast
     */
    private $bb;

    /**
     * We're storing values in this array to allow us to handle intercept an attempt to load a value
     */
    protected $data = array();

    /**
     * Storing previous data values allows us to provide methods for reverting changes
     */
    protected $original_data = array();

    //Flag wether or not we've loaded this object
    protected $loaded = false;

    //If it's a new object, we'll invoke a different method while saving();
    protected $new = false;

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
    public function __get($name) {
        
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
     * Just a placeholder really - the child class is responsible for this, returned data for each object is unfortunately too variable for a consistent loading method
     */
    private function load(){}

    /**
     * Send all current values in $data to the server in hopes of updating the remote values
     */
    private function save() {
        
        //Figure out which service to invoke - based on wether or not this is a new object
        
        //Compile the request
        
        //GOGOGO
    }

    /**
     * BE CAREFUL WITH THIS METHOD - it will delete the object from BinaryBeast!!!
     */
    private function delete() {
        //Determine which method to do
        
        //Delete!!!
    }
}

?>