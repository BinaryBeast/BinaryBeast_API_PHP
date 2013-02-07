<?php

/**
 * This class represents a single match result withint a tournament
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-02-03
 * @author Brandon Simmons
 */
class BBRound extends BBModel {

    //Service names for the parent class to use for common tasks
    //const SERVICE_LOAD   = 'Tourney.TourneyLoad.Info'; //This class is instantiated manually, we don't call the load service from here
    const SERVICE_CREATE = 'Tourney.TourneyRound.Update';
    const SERVICE_UPDATE = 'Tourney.TourneyRound.Update';
    const SERVICE_DELETE = 'Tourney.TourneyRound.Delete';

    /**
     * Keep a reference to the tournament that instantiated this class
     * @var BBTournament
     */
    private $tournament = null;

    /**
     * A round's primary key happens to be a combination of
     * the tournament id, bracket, and round
     * so we'll store them separately
     * 
     * We're storing a reference to the actual BBTouranment that this round belongs to,
     * so we can just use that to determine the tourney_id
     */
    public $bracket;
    public $round;

    /**
     * Default values for a new tournament
     * @see BinaryBeast::update()
     * @var array
     */
    protected $default_values = array(
        //int:      Best of value for this round (ie best of 1 (1), or best of 3 (3)
        'best_of'           => 1,

        //int:      id of the map you'd like to use for this round (use $bb->list_maps([filter]) for map ids and names)
        //Note: You cannot use maps id's from other games, the map you provide MUST be associated with this tournament's game
        'map_id'            => null,

        //string: Optionally you can define the actual map name instead of the map_id - if you happen to spell it the same, BB will associate it with the correct map_id
        'map'               => null,

        //string: For the moment this is a simple unformated string, it does not even validate the format.  This should reflect when this particular round should be played
        'date'              => null,
    );

    /**
     * Since PHP doens't allow overloading the constructor with a different paramater list,
     * we'll simply use a psuedo-constructor and call it init()
     * 
     * @param BBTournament $tournament
     * @param int $bracket
     * @param int $round
     * @return void
     */
    public function init(BBTournament &$tournament, $bracket, $round) {
        $this->tournament   = $tournament;
        $this->bracket      = $bracket;
        $this->round        = $round;
    }

    /**
     * Overloaded so we can let our tournament know that
     * we now have unsaved changes
     * 
     * @see BBModel::__set()
     * 
     * @return void
     */
    function __set($name, $value) {
        //Notify the tournament
        $this->tournament->flag_child_changed($this);

        //Make sure that when setting best_of, that it's a valid value
        if($name == 'best_of') $value = BBHelper::get_best_of($value);

        //Let the default method handle the rest
        parent::__set($name, $value);
    }

    /**
     * Overloaded so that we can let our tournament know that this
     * class no longer has any unsaved changes
     * 
     * @see BBModel::reset()
     * 
     * @return void
     */
    public function reset() {
        //Notify the tournament
        $this->tournament->unflag_child_changed($this);

        //Let the default method handle the rest
        parent::reset();
    }

    /**
     * Overloadsd BBModel because we have unique needs as far as how to
     * let know BinaryBeast what our unique id is, we a round
     * doesn't actually have a unique id value, it's a combination 
     * of tournament id, round, and bracket
     * 
     * We also notify the tournament this round no longer has unsaved changes
     * 
     * @return boolean
     */
    public function save() {

        //Nothing to do - set an error but return true since we didn't actually fail
        if(!$this->changed) {
            $this->set_error('You have not changed any values to submit!');
            return true;
        }

        /**
         * Build the arguments to send to BinaryBeast
         * Unfortunately for this service, we have to be careful to 
         * include all values, even if unchanged, because this particular
         * service is rather old on BB's end and won't ingore the fact
         * that some values may be missing, it'll actually update the db to 
         * "null" if we were to call the update service without defining it
         */
        $svc = self::SERVICE_UPDATE;
        $args = array_merge(array(
            'tourney_id'            => $this->tournament->tourney_id,
            'bracket'               => $this->bracket,
            'round'                 => $this->round
        ), $this->data, $this->new_data);

        //GOGOGO!
        $result = $this->bb->call($svc, $args);

        //Delete any prior error messages, and store the result of the api call
        $this->clear_error();
        $this->set_result($result->result);

        /*
         * Saved successfully - reset some local values and return true
         */
        if($result->result == BinaryBeast::RESULT_SUCCESS) {

            /**
             * Let sync_changes update local data values, and to
             * flag us as no longer having unsaved changes
             */
            $this->sync_changes();

            //Success!
            return true;
        }

        /**
         * Oh noes!
         */
        else {
            return $this->set_error($result);
        }
    }

    /**
     * Overloaded - you know the drill, let the tour know we're up to date
     * @see BBModel::sync_changes();
     * @param bool $skip_unflag     Allows the tournament to update all of the rounds at once, and clear the array manually - it's better than calling unset and array_search for every single round!
     * @return void
     */
    public function sync_changes($skip_unflag = false) {
        //Notify the tournament we're up-to-date
        if(!$skip_unflag) $this->tournament->unflag_child_changed($this);

        //Let BBModel handle the rest
        parent::sync_changes();
    }
}

?>