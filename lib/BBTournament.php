<?php

/**
 * This class represents a single BinaryBeast tournament, 
 * and provides static methods for loading lists of public and private tournaments
 * 
 * @see BBTournament::defaults for possible values!
 * For example default_values['title'] shoudl be set with $tournament->title = 'new title'
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-02-02
 * @author Brandon Simmons
 */
class BBTournament extends BBModel {

    //Service names for the parent class to use for common tasks
    const SERVICE_LOAD   = 'Tourney.TourneyLoad.Info';
    const SERVICE_CREATE = 'Tourney.TourneyCreate.Create';
    const SERVICE_UPDATE = 'Tourney.TourneyUpdate.Settings';
    const SERVICE_DELETE = 'Tourney.TourneyDelete.Delete';

    /**
     * Array of participants within this tournament
     * @var array
     */
    private $teams;
    /**
     * Array of round format for each bracket + round
     * It's keyed by bracket, like so:
     * {'Groups'     => [
     *      (BBRound)[0]=> [{'best_of' => 3, 'map_id' => 7212, 'date' => null}, {'best_of' => 5, 'map_id' => 123, 'map' => 'map_name', 'date' => null}]
     *  ], 'Winners' => [...],
     *  'Losers'  => [...],
     *  'Finals'  => [...],
     *  'Bronze'  => [...],
     * ]
     * @var object
     */
    private $rounds;

    //This tournament's ID, using BinaryBeast's naming convention
    public $tourney_id;

    //So BBModal knows which property use as the unique id
    protected $id_property = 'tourney_id';

    //Define the key to use for extracting data from the API result
    protected $data_extraction_key = 'tourney_info';

    /**
     * Default values for a new tournament
     * @see BinaryBeast::update()
     * @var array
     */
    protected $default_values = array(
        //string: Tournament title
        'title'             => 'PHP API Test',
        //string:   Unique game identifier, @see BinaryBeast::game_search([$filter]), or BinaryBeast::game_list_top([$limit])
        'game_code'         => 'HotS',
        //int:      What kind of tournament, see BinaryBeast::BRACKET_* for values
        'type_id'           => 0,
        //int:      Single or Double elimination, see BinaryBeast::ELIMINATION_* for values
        'elimination'       => 1,
        //int:      Number of players per team (1 = a normal 1v1, anything else indicates this is a team-based game
        'team_mode'         => 1,
        //int:      Number of groups to setup when starting round robin group rounds
        'group_count'       => 0,
        //int:      Number of players per group to advance to the brackets (cup / round robin only)
        'teams_from_group'  => 2,
        //string:   Generic description of where players should meet / coordinate (ie a bnet channel)
        'location'          => null,
        //int:      Maximum number of participants allowed to confirm their positions
        'max_teams'         => 32,
        //int:      Replay upload mode, see BinaryBeast::REPLAY_UPLOADS_* for values
        'replay_uploads'    => BinaryBeast::REPLAY_UPLOADS_OPTIONAL,
        //int:      Replay download mode, see BinaryBeast::REPLAY_DOWNLOADS_* for values
        'replay_downloads'  => BinaryBeast::REPLAY_DOWNLOADS_ENABLED,
        //string:   Generic description of the event, plain-text, no html allowed (bbcode may be allowed later)
        'description'       => '',
    );

    //Used to track when round information has been updated
    private $rounds_changed = false;

    /**
     * Override the getter method - we may have to load teams instead of just general info
     */
    public function &__get($name) {

        /**
         * If attempting to access the array of participants, load them now
         */
        if($name == 'teams' && is_null($this->teams)) {

            //GOGOGO!
            $this->load_teams();

            //Success! return the newly populated array of teams
            return $this->teams;
        }

        /**
         * If attempting to access the round format object, load that now
         */
        else if($name == 'rounds' && is_null($this->rounds)) {

            //GOGOGO!
            $this->load_rounds();

            //Success! return the newly populated array of rounds
            return $this->rounds;
        }

        //Execute default __get method defined in the base BBModel class
        return parent::__get($name);
    }
    
    /**
     * Save the tournament - overloads BBModel::save() so we can 
     * check to see if we need to save rounds too
     */
    public function save() {
        //First save the tournament data
        parent::save();

        //Do we need to send the new round data to the API?
        if($this->rounds_changed) {

            /*
             * 
             * 
             * 
             * 
             * 
             * 
             * 
             * @TODO BUILD THIS LOGIC
             * don't forget to figure out how to update
             * each BBRound's $data to reflect its new values, since
             * BBModal normally does that through save, but we're not
             * using BBRound::save, we're manually calling it here as a batch update
             * 
             * 
             * 
             * 
             * 
             * 
             */

            //Reset the flag
            $this->rounds_changed = false;
        }
    }

    /**
     * When $this->teams is accessed, this method is executed to
     * load the array with values from the API
     * 
     * @return boolean      False if it fails for any reason
     */
    private function load_teams() {
        /*
         * 
         * 
         * 
         * 
         * 
         * 
         * 
         * 
         * 
         * @TODO build this method!!!
         * 
         * 
         * 
         * 
         * 
         * 
         * 
         * 
         * 
         */
    }

    /**
     * When $this->rounds is accessed, this method is executed to
     * load the array with values from the API
     * 
     * If no rounds are setup, we'll still create an template rounds object,
     * so you can access brackets that would exist for your tournament, and set their values
     * 
     * @return boolean      False if it fails for any reason
     */
    private function load_rounds() {

        //Ask the API for the rounds.  By default, this service returns every an array with a value for each bracket
        $result = $this->bb->call('Tourney.TourneyLoad.Rounds', array('tourney_id' => $this->tourney_id));

        //Store the result code
        $this->result = $result->result;

        //Error - return false and save the result 
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
            return $this->set_error($result);
        }

        //We'll need the BBHelper class to translate bracket integers into string label
        $helper = $this->bb->helper();

        //Initalize the rounds property, and start importing instantiated BBRounds into it
        foreach($result->rounds as $bracket => &$rounds) {
            //key each round by the round label
            $bracket_label = $helper->get_bracket_label($bracket, true);

            //Now all we do is loop and instantiate
            $this->rounds->{$bracket_label} = array();
            foreach($rounds as $round => &$format) {
                //Initialize, then give it a few extra important values
                $new_round = new BBRound($this->bb, $format);
                $new_round->init($this, $bracket, $round);

                //Done son! now save it to the local rounds array
                $this->rounds->{$bracket_label}[] = $new_round;
            }
        }

        //Success!
        return true;
    }

    /**
     * Load a list of tournaments created by the user of the current api_key
     * 
     * Note: each tournament is actually instantiated as a new BBTournament class, so you 
     * can update / delete them in iterations etc etc
     * 
     * @param 
     */
    public function list_my($filter = null, $limit = 30, $private = true) {
        $result = $this->bb->call('Tourney.TourneyList.Creator', array(
            'filter'    => $filter,
            'page_size' => $limit,
            'private'   => $private,
        ));

        //OH NOES!
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
            $this->result = $result;
            return $this->set_error($result);
        }

        //Success! Cast each returned tournament as a local BBTournament instance and return the array
        return $this->wrap_list($result->list);
    }
    
    /**
     * BBRound uses this method to let us know that round data has changed,
     * so we know to call save_rounds() when save() is called
     * 
     * @return void
     */
    public function flag_rounds_changed() {
        $this->rounds_changed = true;
    }
}

?>