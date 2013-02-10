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
 * @date 2013-02-9
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
     * Constructor
     * Stores a reference to the main BinaryBeast library class
     * 
     * @param BinaryBeast $bb       Reference to the main API class
     */
    function __construct(BinaryBeast $bb) {
        $this->bb = $bb;
    }

    /**
     * Calls the BinaryBeast API using the given service name and arguments, 
     * and grabs the result code so we can locally stash it 
     * 
     * Unlike BBModel, we actually DO want our results to be automatically wrapped in a BBResult
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
}


?>