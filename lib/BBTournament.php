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
        //int:      What kind of tournament, see BinaryBeast::TOURNEY_TYPE_* for values
        'type_id'           => BinaryBeast::TOURNEY_TYPE_BRACKETS,
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

    //An array of rounds with unsaved changes, so we know
    //to save them when save() is invoked
    private $rounds_changed = array();

    /**
     * Override the getter method - we may have to load teams instead of just general info
     */
    public function &__get($name) {
        /**
         * If attempting to access the array of participants, load them now
         */
        if($name == 'teams') {
            //Do we need to load them first?
            if(is_null($this->teams)) {
                $this->load_teams();
            }

            //The array should be populated by this point
            return $this->teams;
        }

        /**
         * If attempting to access the round format object, load that now
         */
        else if($name == 'rounds') {
            //Do we need to load them first?
            if(is_null($this->rounds)) {
                $this->load_rounds();
            }

            //The array should be populated by this point
            return $this->rounds;
        }

        //Execute default __get method defined in the base BBModel class
        return parent::__get($name);
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
        $this->set_result($result->result);

        //Error - return false and save the result 
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
            return $this->set_error($result);
        }

        //Initalize the rounds property, and start importing instantiated BBRounds into it
        foreach($result->rounds as $bracket => &$rounds) {

            //I only want relevent rounds imported
            $load = true;

            //Group rounds - Cup tournaments only
            if($bracket == BinaryBeast::BRACKET_GROUPS && $this->type_id != BinaryBeast::TOURNEY_TYPE_CUP) $load = false;
            //Loser/Finals brackets - double elim only
            else if(($bracket == BinaryBeast::BRACKET_LOSERS || $bracket == BinaryBeast::BRACKET_FINALS) && $this->elimination < 2) $load = false;
            //Bronze - single elim only
            else if($bracket == BinaryBeast::BRACKET_BRONZE && (!$this->bronze || $this->elimination > 1)) $load = false;

            //If we've determined not to load this bracket, skip to the next round loading iteration
            if(!$load) {
                continue;
            }

            //key each round by the round label
            $bracket_label = BBHelper::get_bracket_label($bracket, true);

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
     * Save the tournament - overloads BBModel::save() so we can 
     * check to see if we need to save rounds too
     */
    public function save($data_only = false) {
        //First save the tournament data, stop if it fails
        if(!parent::save()) return false;

        /**
         * Check sub models (rounds, teams) unless requested not to
         */
        if(!$data_only) {
            //Submit any round format changes
            if(!$this->save_rounds()) return false;

            //Submit any team changes
            //TODO
        }

        //Success!
        return true;
    }

    /**
     * Update all rounds that have had any values changed in one go
     * 
     * You can either call this direclty (if for some reason you don't yet want touranmetn changes saved)
     * or call save(), which will save EVERYTHING, including tour data, teams, and rounds
     * 
     * @return boolean
     */
    public function save_rounds() {

        /**
         * We have to compile all of the values into separate arrays, keyed by round,
         * and we'll call one service for each bracket with any rounds changed
         * 
         * BinaryBeast expects 1 array for maps, bestofs, etc.. keyed by round,
         * and we'll have to call the service once for each bracket that has
         * any changes in it
         */
        $format = array();

        foreach($this->rounds_changed as &$round) {
            //Bracket value initailized?
            if(!isset($format[$round->bracket])) $format[$round->bracket] = array(
                'maps'      => array(),
                'map_ids'   => array(),
                'dates'     => array(),
                'best_ofs'  => array(),
            );

            //Add it to the queue!
            $format[$round->bracket]['maps'][$round->round]         = $round->map;
            $format[$round->bracket]['map_ids'][$round->round]      = $round->map_id;
            $format[$round->bracket]['dates'][$round->round]        = $round->date;
            $format[$round->bracket]['best_ofs'][$round->round]     = $round->best_of;
        }

        //Loop through the results, and call the API once for each bracket with any values in it
        foreach($format as $bracket => &$rounds) {
            //Determine the arguments for this bracket
            $args = array_merge($rounds, array(
                'tourney_id'    => $this->tourney_id,
                'bracket'       => $bracket,
            ));

            //GOGOGO! store the result each time
            $result = $this->bb->call('Tourney.TourneyRound.BatchUpdate', $args);
            $this->set_result($result);

            //OH NOES!
            if($result->result != BinaryBeast::RESULT_SUCCESS) {
                return $this->set_error($result);
            }
        }

        /**
         * If we've gotten this far, that means everything updated!!
         * Last step is to reset each round and list of changed rounds
         * 
         * We waited to do this because we wouldn't want to clear the queue
         * before insuring that we submitted the changes successfully
         */
        foreach($this->rounds_changed as &$round) {
            $round->sync_changes(true);
        }

        //Reset our list of changed rounds, and return true, yay!
        $this->rounds_changed = array();
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
            $this->set_result($result->result);
            return $this->set_error($result);
        }

        //Success! Cast each returned tournament as a local BBTournament instance and return the array
        return $this->wrap_list($result->list);
    }

    /**
     * Save a reference a round that has unsaved chnages, so that we can
     * update all rounds that have any changes once save() is invoked
     * 
     * It stores a reference to the round with changes in rounds_changed, which
     * makes iterating through them much much easier when we decide to submit
     * the changes
     * 
     * @param BBRound $round - a reference to the Round calling
     * @return void
     */
    public function flag_round_changed(BBRound &$round) {
        if(!in_array($round, $this->rounds_changed)) {
            $this->rounds_changed[] = &$round;
        }
    }
    /**
     * Removes any references to a BBRound, so we know that
     * the round has no unsaved changes
     * 
     * @param BBRound $round - a reference to the Round calling
     * @return void
     */
    public function unflag_round_changed(BBRound &$round) {
        if(in_array($round, $this->rounds_changed)) {
            unset($this->rounds_changed[ array_search($round, $this->rounds_changed) ]);
        }
    }
}

?>