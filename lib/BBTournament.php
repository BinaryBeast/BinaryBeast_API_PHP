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
 * @date 2013-02-04
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
     * Default values for a new tournament, also a useful reference for developers
     * @var array
     */
    protected $default_values = array(
        //string: Tournament title
        'title'             => 'PHP API Test',
        //boolean:  If true, this tournament can be found in the public lists + search
        'public'            => true,
        //string:   Unique game identifier, @see BinaryBeast::game_search([$filter]), or BinaryBeast::game_list_top([$limit])
        'game_code'         => 'HotS',
        //int:      What kind of tournament, see BinaryBeast::TOURNEY_TYPE_* for values
        'type_id'           => BinaryBeast::TOURNEY_TYPE_BRACKETS,
        //int:      Elimination mode, single or double, simply 1 for single 2 for double, but you can also use BinaryBeast::ELIMINATION_* for values
        'elimination'       => BinaryBeast::ELIMINATION_SINGLE,
        //boolean:  Single elimination only - if true, your tournament will have a 3rd/4th place decider aka "bronze bracket"
        'bronze'            => false,
        //boolean:  Double elim
        'bronze'            => false,
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
        /**
         * string: This is a special value that could take a while to explain.. but in short:
         * It's a special hidden value that allows developers to store custom data.. for example you could store
         * a json encoded string that stores some data about this team that's specific to your site, like
         * his local user_id, or his local email address. etc etc
         */
        'hidden'            => null,
        /**
         * string:   I higghly recommend you set something here
         * In fact, this class even provides a method that generates a random password,
         * You can use $tournament->generate_player_password([bool $auto_save]);
         * 
         * This reason this is important is otherwise.. ANYONE from the outside 
         * can join your tournaments.. but if you set a player_password, they will
         * be promted to provide the password before they can join
         * 
         * So it's a bit of a hackish way of insuring that only your API can add
         * participants to your events
         */
        'player_password'       => null,
    );

    /**
     * An array of rounds with unsaved changes, so we know
     * to save them when save() is invoked
     */
    private $rounds_changed = array();

    /**
     * An array of rounds with unsaved changes, so we know
     * to save them when save() is invoked
     */
    private $teams_changed = array();

    /**
     * Returns an array of players/teams/participants within this tournament
     * 
     * This method takes advantage of BBModel's __get, which allows us to emulate public values that we can
     * intercept access attempts, so we can execute an API request to get the values first
     * 
     * @return boolean      False if it fails for any reason
     */
    public function &teams() {

        //Already instantiated
        if(!is_null($this->teams)) return $this->teams;

        //Ask the API for the rounds.  By default, this service returns every an array with a value for each bracket
        $result = $this->bb->call('Tourney.TourneyLoad.Teams', array('tourney_id' => $this->tourney_id));

        //Store the result code
        $this->set_result($result->result);

        //Error - return false and save the result 
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
            return $this->set_error($result);
        }

        //Initalize the teams array, and start importing instantiated BBTeam instances into it
        $this->teams = array();
        foreach($result->teams as &$team) {

            //Instantiate a new BBTeam, and tell it to save a reference to this tournament
            $team = new BBTeam($this->bb, $team);
            $team->init($this);

            //Success!
            $this->teams[] = $team;
        }

        //Success! return the result
        return $this->teams;
    }
    /**
     * Alias for BBTournament::teams()
     * Returns an array of players/teams/participants within this tournament
     * @return array
     */
    public function &players() {
        return $this->teams();
    }
    /**
     * Alias for BBTournament::teams()
     * Returns an array of players/teams/participants within this tournament
     * @return array
     */
    public function participants() {
        return $this->teams();
    }

    /**
     * Returns an object containing the format for each round within this tournament, in the following layout:
     * {winners => [0 => {best_of=>3, map_id=>1234, wins_needed=>2, map=>Shakuras}, 1 =>{...}...], losers =>... finals =>... bronze =>... groups=>...}
     * 
     * This method takes advantage of BBModel's __get, which allows us to emulate public values that we can
     * intercept access attempts, so we can execute an API request to get the values first
     * 
     * 
     * Note: for new tournaments, you'll want to save() the tournament before attempting to setup the round format
     * 
     * @return boolean      False if it fails for any reason
     */
    protected function &rounds() {

        //Already instantiated
        if(!is_null($this->rounds));

        //Ask the API for the rounds.  By default, this service returns every an array with a value for each bracket
        $result = $this->bb->call('Tourney.TourneyLoad.Rounds', array('tourney_id' => $this->tourney_id));

        //Store the result code
        $this->set_result($result->result);

        //Error - return false and save the result 
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
            return $this->set_error($result);
        }

        //Initalize the rounds property, and start importing instantiated BBRounds into it
        $this->rounds = (object)array();
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

        //Success! return the result
        return $this->rounds;
    }

    /**
     * Save the tournament - overloads BBModel::save() so we can 
     * check to see if we need to save rounds too
     */
    public function save($data_only = false) {
        //First save the tournament data, stop if it fails (store the result so we can return it)
        $result = parent::save();
        if(!$result) return false;

        /**
         * Check sub models (rounds, teams) unless requested not to
         */
        if(!$data_only) {
            //Submit any round format changes
            if(!$this->save_rounds()) return false;

            //Submit any team changes
            //TODO
        }

        //Success! Return the original save() result (if this tour is new, it'll give us the tourney_id)
        return $result;
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
     * Save a reference a child round/match etc that has unsaved chnages, so that we can
     * update all rounds that have any changes once save() is invoked
     * 
     * It stores a reference to the child with changes in rounds|teams_changed, which
     * makes iterating through them much much easier when we decide to submit
     * the changes
     * 
     * @param BBModel $child - a reference to child object to be tracked
     * @return void
     */
    public function flag_child_changed(BBModel &$child) {
        //Determine property name based on the child object type
        $property = get_class($child) == 'BBTeam' ? 'teams_changed' : 'rounds_changed';

        //If it isn't already being tracked, add it now
        if(!in_array($child, $this->$property)) {
            $this->$property[] = &$child;
        }
    }
    /**
     * Removes any references to a child class (team/round etc), so we know that
     * the child has no unsaved changes
     * 
     * @param BBModel $child - a reference to the Round calling
     * @return void
     */
    public function unflag_child_changed(BBModel &$child) {
        //Determine property name based on the child object type
        $property = get_class($child) == 'BBTeam' ? 'teams_changed' : 'rounds_changed';

        //If it's currently being tracked, remove it now
        if(in_array($child, $this->$property)) {
            unset($this->$property[ array_search($child, $this->$property) ]);
        }
    }

    /**
     * This method can be used to generate a random password that 
     * people would be required to provide before joining your tournament
     * 
     * It's a nice way to insure that only YOU can add playesr through your API
     * 
     * Warning: if you enable auto_save, any exiting values that you've already set
     * will also be sent to the API as update values
     * 
     * @param bool $auto_save       Automatically save the new value? 
     * @return $this->save(); if auto_save, null otherwise
     */
    public function generate_player_password($auto_save = true) {
        $this->player_password = uniqid();
        if($auto_save) return $this->save();
    }

    /**
     * Returns a new BBTeam object to add to the tournament
     * 
     * This method will return a value of false if the tournament is already active!
     * 
     * A cool tip: this method can actually be used to pre-definea list of players in a new tournaemnt, before
     * you even create it :)
     * 
     * Note: ALL this method does is return a new BBTeam! it does NOT send it to the API and save it!
     * 
     * However, it is automatically added to this tournament's save queue, so as soon as you $tour->save(), it will be added
     * If you want to avoid this, you must call $team->delete()
     * 
     * Assuming $team = $bb->player|participant; or $team = $bb->team|player|participant(); or $team = $bb->new_team|new_player|new_participant()
     * Next, configure the team: $team->display_name = 'blah'; $team->country_code = 'NOR'; etc...
     * 
     * Now there are three ways to actually save the team and send it to the API:
     * 1) $team->save()
     * 2) $bb->save_teams();
     * 3) $bb->save();
     * 
     * @return BBTeam
     */
    public function &team() {
        //We can't add new players to an active-tournament
        if(BBHelper::tournament_is_active($this)) {
            return $this->set_error('You cannot add players to active tournaments!!');
        }

        //Instantiate a blank Team, and give it a reference to this tournament
        $team = new BBTeam($this->bb);
        $team->init($this);

        /**
         * Add it to the local lists and return a reference to it
         * 
         * First step though is to intialize the teams array, so we can
         * safely add the new team without flagging the teams array as intialized when it really hasn't been
         * 
         * We'll also have to determine the next key for its position in teams, so we can save it,
         * and then return a reference to it
         */
        $this->teams();
        $this->teams[] = $team;
        $this->teams_changed[] = $team;
        
        
        
        
    }
}

?>