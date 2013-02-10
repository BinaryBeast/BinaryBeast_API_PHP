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
 * @version 1.0.2
 * @date 2013-02-08
 * @author Brandon Simmons
 */
class BBTournament extends BBModel {

    /** Model services / Tournament manipulation **/
    const SERVICE_LOAD          = 'Tourney.TourneyLoad.Info';
    const SERVICE_CREATE        = 'Tourney.TourneyCreate.Create';
    const SERVICE_UPDATE        = 'Tourney.TourneyUpdate.Settings';
    const SERVICE_DELETE        = 'Tourney.TourneyDelete.Delete';
    /** Listing / searching **/
    const SERVICE_LIST          = 'Tourney.TourneyList.Creator';
    const SERVICE_LIST_POPULAR  = 'Tourney.TourneyList.Popular';
    //const SERVICE_LIST_SEARCH     = 'Tourney.TourneyList.Creator'; //Coming soon - search public tournaments
    //const SERVICE_LIST_SEARCH_MY   = 'Tourney.TourneyList.Creator'; //Coming soon - search tournaments made by you
    /** Child listing / manipulation**/
    const SERVICE_LOAD_TEAMS    = 'Tourney.TourneyLoad.Teams';
    const SERVICE_LOAD_ROUNDS   = 'Tourney.TourneyLoad.Rounds';
    const SERVICE_UPDATE_ROUNDS = 'Tourney.TourneyRound.BatchUpdate';
    const SERVICE_UPDATE_TEAMS  = 'Tourney.TourneyTeam.BatchUpdate';

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
     * An array of value keys that we would like
     * to prevent developers from changing
     */
    protected $read_only = array('status', 'tourney_id');

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
     * @return boolean      False if it fails for any reason - it will only return false for API / validation errors, never for an empty array set
     */
    public function &teams() {

        //Already instantiated
        if(!is_null($this->teams)) return $this->teams;

        //New tournaments must be saved before we can start saving child data
        if(is_null($this->tourney_id)) {
            return $this->bb->ref(
                $this->set_error('Please execute save() before manipulating rounds or teams')
            );
        }

        //Ask the API for the rounds.  By default, this service returns every an array with a value for each bracket
        $result = $this->call(self::SERVICE_LOAD_TEAMS, array('tourney_id' => $this->tourney_id));

        //Error - return false and save the result 
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
             return $this->bb->ref($this->set_error($result));
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
    public function &participants() {
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

        //New tournaments must be saved before we can start saving child data
        if(is_null($this->tourney_id)) {
            return $this->bb->ref(
                $this->set_error('Please execute save() before manipulating rounds or teams')
            );
        }

        //Ask the API for the rounds.  By default, this service returns every an array with a value for each bracket
        $result = $this->call(self::SERVICE_LOAD_ROUNDS, array('tourney_id' => $this->tourney_id));

        //Error - return false and save the result 
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
            return $this->bb->ref($this->set_error($result));
        }

        //Initalize the rounds property, and start importing instantiated BBRounds into it
        $this->rounds = (object)array();
        foreach($result->rounds as $bracket => &$rounds) {
            
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
             * 
             * 
             * 
             * 
             * Move this to a "helper"
             * 
             * 
             * 
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
    public function save($return_result = false, $child_args = null) {
        /**
         * Before trying to save any teams or rounds, first step is to save the tournament itself
         * Depending on new vs old, we save it and stash the result, then return
         * the result later after updating children
         */
        //For new tournaments - use save_new for special import instructions
        if(is_null($this->id)) $result = $this->save_new();

        //Simple update - use standard save() method
        else $result = parent::save();

        //Info update failed, return fasle
        if(!$result) return false;

        //Submit any round format changes
        if(!$this->save_rounds()) return false;

        //Save any new / updated teams
        if(!$this->save_teams()) return false;
        
        //Success! Return the original save() result (if this tour is new, it'll give us the tourney_id)
        return $result;
    }
    /**
     * If creating a new tournament, we want to make sure that when the data is sent
     * to the API, that we request return_data => 2, therefore asking BinaryBeast to send
     * back a full TourneyInfo object for us to import all values that may have been
     * generated on BB's end
     * 
     * @return boolean
     */
    private function save_new() {
        /**
         * Use the parent save(), but request the result to be returned, and add
         * return_data = 2 to the arguments
         * 
         * If all goes well, bb will send back a full tourney_info object we can import
         */
        if(!$result = parent::save(true, array('return_data' => 2))) return false;

        //OH NOES!
        if($result->result !== 200) return false;

        /**
         * Import the new data
         */
        $this->import_values($result);

        //Success!
        return true;
    }

    /**
     * Update all rounds that have had any values changed in one go
     * 
     * You can either call this directly (if for some reason you don't yet want touranmetn changes saved)
     * or call save(), which will save EVERYTHING, including tour data, teams, and rounds
     * 
     * @return boolean
     */
    public function save_rounds() {

        /**
         * Can't save children before the tournament even exists!
         */
        if(is_null($this->tourney_id)) {
            return $this->set_error('Can\t save teams before saving the tournament!');
        }

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
            $result = $this->call(self::SERVICE_UPDATE_ROUNDS, $args);

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
     * Update all teams/participants etc that have had any values changed in one go
     * 
     * You can either call this directly (if for some reason you don't yet want touranmetn changes saved)
     * or call save(), which will save EVERYTHING, including tour data, teams, and rounds
     * 
     * @return boolean
     */
    public function save_teams() {

        /**
         * Can't save children before the tournament even exists!
         */
        if(is_null($this->tourney_id)) {
            return $this->set_error('Can\t save teams before saving the tournament!');
        }

        /**
         * Using the array of tracked / changed teams, let's compile
         * a couple arrays of team values to send to the API
         * 
         * Initialize the two team arrays: new + update
         */
        $teams = array();
        $new_teams = array();

        /**
         * GOGOGO!
         */
        foreach($this->teams_changed as &$team) {
            /**
             * New team - get all default + new values and add to $new_teams
             */
            if(is_null($team->id)) {
                $new_teams[] = $team->get_sync_values();
            }
            /**
             * Existing team - get only values that are new, and key by team id
             */
            else {
                $teams[$team->tourney_team_id] = $team->get_changed_values();
            }
        }

        //Send the compiled arrays to BinaryBeast
        $result = $this->call(self::SERVICE_UPDATE_TEAMS, array(
            'tourney_id'        => $this->tourney_id,
            'teams'             => $teams,
            'new_teams'         => $new_teams
        ));
        //Oh noes!
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
            return $this->set_error($result);
        }

        /**
         * Tell all team instances to synchronize their settings
         */
        $new_id_index = 0;
        foreach($this->teams_changed as &$team) {
            
            //Tell the tournament to merge all unsaved changed into $data
            $team->sync_changes(true);

            /**
             * For new tournaments, make sure they get the new team_id
             */
            if(is_null($team->tourney_team_id)) {
                $team->set_tourney_team_id($result->team_ids[$new_id_index]);
                ++$new_id_index;
            }
        }

        //Clear the list of teams with changes
        $this->teams_changed = array();

        //Success!
        return true;
    }

    /**
     * Load a list of tournaments created by the user of the current api_key
     * 
     * Note: each tournament is actually instantiated as a new BBTournament class, so you 
     * can update / delete them in iterations etc etc
     * 
     * @param string $filter        Optionally search through your tournaments using a simple filter
     * @param int    $limit         Number of results returned
     * @param bool   $private       false by default - set this to true if you want your private tournaments included
     * @return array<BBTournament>
     */
    public function list_my($filter = null, $limit = 30, $private = true) {
        return $this->get_list(self::SERVICE_LIST, array(
            'filter'    => $filter,
            'page_size' => $limit,
            'private'   => $private,
        ), 'list', 'BBTournament');
    }

    /**
     * Returns a list of popular tournaments
     * 
     * However since this service is loading public tournaments, that means we likely
     *      won't have access to edit any of them
     * 
     * Therefore, all tournaments returned by this service are simplly BBResult wrapped values, they are not
     *  full BBTournament models
     * 
     * @param string $game_code
     *      You have the option of limiting the tournaments returned by context of a specific game,
     *      In otherwords for example game_code QL will ONLY return the most popular games that are using
     *      Quake Live
     * @param int $limit            Defaults to 10, cannot exceed 100
     * @return BBResult
     */
    public function list_popular($game_code = null, $limit = 10) {
        return $this->get_list(self::SERVICE_LIST_POPULAR, array(
            'game_code'     => $game_code,
            'limit'         => $limit
        ), 'list');
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
        if(!in_array($child, $this->{$property})) {
            $this->{$property}[] = &$child;
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
        if(in_array($child, $this->{$property})) {
            unset($this->{$property}[ array_search($child, $this->{$property}) ]);
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
     * 
     * 
     * The secondary use of this method is for retrieving a reference to the BBTeam object of
     *      team that you know the ID of, just pass in the tourney_team_id
     * 
     * 
     * 
     * @param int $id       Optionally attempt to retrieve a reference to the BBTeam of an existing team
     * 
     * @return BBTeam
     */
    public function &team($id = null) {

        //We can't add new players to an active-tournament
        if(BBHelper::tournament_is_active($this)) {
            return $this->bb->ref($this->set_error('You cannot add players to active tournaments!!'));
        }

        //Insure that the local teams array is up to date according to BinaryBeast
        if(!$this->teams()) return $this->bb->ref(false);


        /**
         * If they provided an $id, that means they we need to return a 
         * reference to a specific team that should already exist
         */
        if(!is_null($id)) {
            //Simply have to iterate through until we find it
            foreach($this->teams as $key => &$team) {
                if($team->id == $id) {
                    return $this->teams[$key];
                }
                //Invalid team id
                $this->set_error('Tournament (' . $this->id . ') does not have a team by that id (' . $id . ')');
                return $this->ref(null);
            }
            
        }

        //Instantiate a blank Team, and give it a reference to this tournament
        $team = new BBTeam($this->bb);
        $team->init($this);

        /**
         * Add it to the local lists and return a reference to it
         * 
         * We'll also have to determine the next key for its position in teams, so we can save it,
         * and then return a reference to it
         */
        //If teams is still null, that means this is a new tournament, so let's manually initalize teams()
        if(is_null($this->teams)) $this->teams = array();
        
        //Figure out the next key, so we can more easily return references to the new element
        $key = sizeof($this->teams);

        //Add it our teams list, then add a reference to the changed array
        $this->teams[$key] = $team;
        $this->teams_changed[] = &$this->teams[$key];

        //Success! return a reference to the new team
        return $this->teams[$key];
    }
    /**
     * Alias for team()
     * 
     * @return BBTeam
     */
    public function &add_team() {
        return $this->team();
    }
}

?>