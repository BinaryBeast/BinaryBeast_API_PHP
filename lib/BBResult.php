<?php

/**
 * A simple result wrapper class that allows developers to 
 * access result values in a flexible manner, aka
 * they can access values as array elements, object elements, and 
 * in any naming convention (camel case, underscores, upper case, lower, etc)
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-02-08
 * @author Brandon Simmons
 */
class BBResult implements ArrayAccess, Iterator {

    /**
     * Simple integers for iteration tracking
     */
    private $position = 0;
    private $length = 0;

    /**
     * Main property storing the result, publically accessible to make var_dumps useful, 
     * basically a storage of the direct raw response from the API
     */
    public $result_values;

    /**
     * Internal storage of values and keys for iteration
     * 
     * Data stored in $values are stored using a standardized key, so we can remove any chance
     * of not being able to access data due to invalid format when accessing it
     * 
     * However keys stores the original keys in their original format, so that while 
     * iterating or calling array_keys, it returns the real key values as they were
     * originally intended to be
     */
    private $values = array();
    private $keys   = array();

    /**
     * Constructor
     * resurisvely imports the provided values into this instance
     * 
     * @param mixed $result      The service result
     * @return void
     */
    function __construct($result) {

        /**
         * If somehow we're handed a BBResult, extract JUST the values from it
         * 
         * Using instanceof to check, since I've run tests to show that it's incredibly fast,
         * especially compared to is_subclass_of, is_a... etc
         */
        if($result instanceof BBResult) {
            $result = $result->result_values;
        }

        /**
         * Loop through all values given to us, and either
         * save them locally with a standardized key, and them
         * save them publically with the original key
         * 
         * If sub-values are array|objects, recursively do the same to them
         * 
         * We save a public reference using the original notation (for instance
         *  $result->tourney_info points to $this->iterable_values('tourneyinfo'), or
         *  as would $result->TourneyInfo)
         */
        if(is_array($result) || is_object($result)) {
            $this->result_values = (array)$result;
            foreach($this->result_values as $key => &$value) {

                /**
                 * 
                 * Note: I had originally planned to re-use my logic in
                 * offsetSet for this, however in order to insure that
                 * external calls could pass values directly (not by reference), 
                 * and that we could still save by reference here in the constructor..
                 * I decided against it
                 * 
                 */

                /*
                 * If it's an object or array, cast it as yet another nested BBResult, so that 
                 * all nested values can be accessed flexibly
                 */
                if(is_object($value) || is_array($value)) {
                    $value = new BBResult($value);
                }

                //Create a standardized key, to avoid any chance of mistaken formatting (strip underscores, all lower case)
                $key_standard = $this->get_standard_key($key);

                /**
                 * Keep a storage of values we'll use for iteration / array access, and store
                 * them using the standardized key
                 * 
                 * We'll after all, only need to use the ArrayAccess offsetget method 
                 * if they didn't refer to it in the original notation anyway, so at that point
                 * we will go ahead and try to give them the value they want using the
                 * standardized key in hopes that the developer either used 
                 * camelCase when he shouldn't have, or put in a capital letter where it 
                 * was not appropriate
                 * 
                 * The value in keys[] however is saved as the original, so that when
                 * we return array_keys / $key from foreach iterations etc, the developer
                 * has the key in the original / correct format
                 */
                $this->values[$key_standard] = &$this->result_values[$key];
                $this->keys[]                = $key;

                //Calculate the iteratable length / size now
                $this->length = sizeof($this->values);
            }
        }
        //For non arrays/objects, store directly and leave it at that
        else {
            $this->result_values = $result;
        }
    }

    /**
     * Converts the given value key, and converts it into a standardized key for internal storage
     * we remove all underscores, and return it in all lower case
     * 
     * @param string $key
     * @return string
     */
    private function get_standard_key($key) {
        //Perhaps an index / number was provided
        if(!is_string($key)) return $key;

        //GOGOGO! all lowercase, remove underscores
        return strtolower(str_replace('_', '', $key));
    }

    /**
     * Handle attempts to directly echo / print this object
     * 
     * What we do is just return the result of var_dump as a string
     * 
     * We can do so by creating an output buffer, performing a var_dump, and
     * then ending the buffer and returning the values queued within it
     * 
     * @return string       Value to print / echo
     */
    public function __toString() {
        ob_start();
            var_dump($this->result_values);
        return ob_end_flush();
    }

    /**
     * Check for the existance of a virtual array property
     * 
     * Implemented from ArrayAccess
     * @see ArrayAccess::offsetExists
     * 
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset) {
        return isset($this->values[$this->get_standard_key($offset)]);
    }

    /**
     * Returns the value for as a virtual array property
     * 
     * Implemented from ArrayAccess
     * @see ArrayAccess::offsetGet
     * 
     * @param mixed $offset
     * @return mixed
     */
    public function &offsetGet($offset) {
        return $this->values[$this->get_standard_key($offset)];
    }

    /**
     * Attempt to return the property using a standardized notation
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        //Try retrieving using the standardized notation
        $key = $this->get_standard_key($name);
        if(isset($this->values[$key])) {
            return $this->values[$key];
        }
        //Invalid
        return null;
    }

    /**
     * Implementation of the ArrayAcces's method for adding values to this result set
     * 
     * The third paramater allows us to skip a few sets that are handled by the constructor
     * for the initial import - like adding the value to the public $array_values, and for
     * recalculating the iteration length counter
     * 
     * Implemented from ArrayAccess
     * @see ArrayAccess::offsetExists
     * 
     * @param mixed $key
     * @param mixed $value
     * 
     * @return boolean
     */
    public function offsetSet($key, $value) {

        //Convert the key into standardized format
        $key_standard = $this->get_standard_key($key);

        /*
         * If it's an object or array, cast it as yet another nested BBResult, so that 
         * all nested values can be accessed flexibly
         */
        if(is_object($value) || is_array($value)) {
            $value = new BBServiceResult($value);
        }

        /**
         * Add to new values to the main public result_values property first,
         * then save standardized references
         */
        $this->result_values[$key] = $value;

        //Add a reference to the value, and a standardized key in the internal value/key arrays
        $this->values[$key_standard] = &$this->result_values[$key];
        $this->keys[]                = $key;

        //Increment the iteration length
        ++$this->length;
    }

    /**
     * We had to implement this for ArraAccess, but we're not going to do anything with it, 
     * since I don't see any point in allowing developers to remove data from API result sets
     * 
     * Implemented from ArrayAccess
     * @see ArrayAccess::offsetUnset
     * 
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset) {}

    /**
     * Returns array value in the current iteration position
     * 
     * WARNING: this method returns references!! this means be careful while
     *  iterating with foreach, as it will return references that when edited, edit the original value!!
     * 
     * @see Iterator::current()
     */
    public function &current() {
        return $this->values[ $this->get_standard_key($this->keys[$this->position]) ];
    }

    /**
     * Returns position of the current iteration
     * @return int
     */
    public function key() {
        return $this->keys[$this->position];
    }

    /**
     * Increment iteration position
     * @return void
     */
    public function next() {
        ++$this->position;
    }

    /**
     * Reset iteration position
     * @return void
     */
    public function rewind() {
        $this->position = 0;
    }

    /**
     * Validate the current iteration position (aka is there a value at the current position?)
     * @return boolean
     */
    public function valid() {
        return isset($this->keys[$this->position]);
    }

    /**
     * Returns an array of iteratable array indexes
     * 
     * @see array_keys()
     * 
     * @return array
     */
    public function array_keys() {
        return $this->keys;
    }

    /**
     * Simply returns $this->result_values, so that the returned
     * array is indexed using the original keys / indexes
     * 
     * @see array_values()
     * 
     * @return array
     */
    public function array_values() {
        return $this->result_values;
    }

    /**
     * Returns the number of array-indexed values
     * 
     * @see sizeof()
     * 
     * @return integer
     */
    public function sizeof() {
        return $this->length;
    }

    /**
     * Alias for sizeof
     * @see BBServiceResult::sizeof()
     * 
     * @return integer
     */
    public function array_size() {
        return $this->length;
    }

    /**
     * Necessary to allow performing isset() against values
     * that don't necessary publicly exist
     * 
     * @return boolean
     */
    public function __isset($name) {
        //EZ!
        if(!isset($this->result_values[$name])) {
            //Standardize
            $key = $this->get_standard_key($name);
            if(!isset($this->values[$key])) return false;
        }

        //Success!
        return true;
    }

}

?>