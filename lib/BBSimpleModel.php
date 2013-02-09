<?php

/**
 * Base class for simple models that only need to provide some api wrapper methods
 * 
 * Figured I didn't necessarily want to confuse developers by giving them 
 * full fledged Model classes for public binarybeast data, that they can't edit anyway
 * 
 * So we use this simplified version that has no save() methods etc, and all they do
 * is provide some api service wrappers
 * 
 * @version 1.0.0
 * @date 2013-02-8
 * @author Brandon Simmons
 */
class BBSimpleModel {

    /**
     * Reference to the main API class
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
     * @var string
     */
    protected $last_error = null;

    /**
     * Constructor - accepts a reference the BinaryBeats API $bb
     */
    function __construct(BinaryBeast $bb) {
        $this->bb = $bb;
    }

    /**
     * Calls the BinaryBeast API using the given service name and arguments, 
     * and grabs the result code so we can locally stash it 
     * 
     * Also unlike BBModel, we actually DO want our results to be automatically 
     * wrapped in BBResult
     * 
     * @param string $svc
     * @param array $args
     * @return object
     */
    protected function call($svc, $args, $wrapped = true) {
        //First, clear out any errors that may have existed before this call
        $this->clear_error();

        //Use BinaryBeast library to make the actual call
        $response = $this->bb->call($svc, $args, $wrapped);

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
        $this->last_error = $this->bb->set_error($error);

        //Allows return this directly to return false, saves a line of code - don't have to set_error then return false
        return false;
    }

    /**
     * Remove any existing errors
     * @return void
     */
    protected function clear_error() {
        $this->set_error(null);
        $this->bb->clear_error();
    }
}


?>