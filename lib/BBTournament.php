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
 * @version 1.0.3
 * @date 2013-02-10
 * @author Brandon Simmons
 */
class BBTournament extends BBModel {

    /** Model services / Tournament manipulation **/
    const SERVICE_LOAD                      = 'Tourney.TourneyLoad.Info';
    const SERVICE_CREATE                    = 'Tourney.TourneyCreate.Create';
    const SERVICE_UPDATE                    = 'Tourney.TourneyUpdate.Settings';
    const SERVICE_DELETE                    = 'Tourney.TourneyDelete.Delete';
    const SERVICE_START                     = 'Tourney.TourneyStart.Start';
    const SERVICE_REOPEN                    = 'Tourney.TourneyReopen.Reopen';
    const SERVICE_ALLOW_CONFIRMATIONS       = 'Tourney.TourneySetStatus.Confirmation';
    const SERVICE_DISALLOW_CONFIRMATIONS    = 'Tourney.TourneySetStatus.Building';
    /** Listing / searching **/
    const SERVICE_LIST                      = 'Tourney.TourneyList.Creator';
    const SERVICE_LIST_POPULAR              = 'Tourney.TourneyList.Popular';
    const SERVICE_LIST_OPEN_MATCHES         = 'Tourney.TourneyLoad.OpenMatches';
    //const SERVICE_LIST_SEARCH     = 'Tourney.TourneyList.Creator'; //Coming soon - search public tournaments
    //const SERVICE_LIST_SEARCH_MY   = 'Tourney.TourneyList.Creator'; //Coming soon - search tournaments made by you
    /** Child listing / manipulation**/
    const SERVICE_LOAD_TEAMS                = 'Tourney.TourneyLoad.Teams';
    const SERVICE_LOAD_ROUNDS               = 'Tourney.TourneyLoad.Rounds';
    const SERVICE_UPDATE_ROUNDS             = 'Tourney.TourneyRound.BatchUpdate';
    const SERVICE_UPDATE_TEAMS              = 'Tourney.TourneyTeam.BatchUpdate';

    /**
     * Caching settings
     */
    const CACHE_OBJECT_TYPE				= BBCache::TYPE_TOURNAMENT;
	const CACHE_TTL_LOAD				= 60;
    const CACHE_TTL_LIST				= 60;
    const CACHE_TTL_TEAMS				= 30;
    const CACHE_TTL_ROUNDS				= 60;
    const CACHE_TTL_LIST_OPEN_MATCHES	= 20;


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

    //Cache results from loading open matches from the API
    private $open_matches;

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
     * Not that it honestly really matters, the API only changes acceptable values submitted anyway
     */
    protected $read_only = array('status', 'tourney_id');

    /**
     * Returns an array of players/teams/participants within this tournament
     * 
     * This method takes advantage of BBModel's __get, which allows us to emulate public values that we can
     * intercept access attempts, so we can execute an API request to get the values first
     * 
     * WARNING:  Be careful when evaluating the result of this method, if you try to evaluate the result
     * as a boolean, you may be surprised when an empty array is returned making it look like the service failed
     * 
     * If you want to check for errors with this method, make sure you do it like this:
     *      if($tournament->teams() === false) {
     *          OH NO!!!
     *      }
     * 
     * @return boolean      False if it fails for any reason - it will only return false for API / validation errors, never for an empty array set
     */
    public function &teams() {

        //Already instantiated
        if(!is_null($this->teams)) return $this->teams;

		//Use BBSimpleModels' listing method, for caching etc
		if( ($this->teams = $this->get_list(self::SERVICE_LOAD_TEAMS, array('tourney_id' => $this->tourney_id)
			, 'teams', 'BBTeam')) === false) {
			$this->set_error('Error loading the teams in this tournament! see error() for details');
            return $this->bb->ref(null);
		}

		//Loop through each time, and "initialize" it, telling it which tournament it belongs to
		foreach($this->teams as &$team) $team->init($this);

		//Success!
		return $this->teams;
    }
    public function &players() { return $this->teams(); }
    public function &participants() { return $this->teams(); }

    /**
     * Returns am array of teams within this tournament that have have a status of 1
     *      aka confirmed
     * 
     * @param boolean   $ids        False by default, set to true to return JUST the tourney_team_id instead of a reference to the BBTeam object itself
     * 
     * @return array
     */
    public function confirmed_teams($ids = false) {
        //Use teams() to guarantee up to date values, and so we can return false if there are errors set by it
        if(!$teams = $this->teams()) return false;

        //Initialize the output
        $confirmed = array();

        //Simply loop through and return the confirmed teams
        foreach($teams as &$team) {
            if($team->status == BinaryBeast::TEAM_STATUS_CONFIRMED) {
                $confirmed[] = $ids ? $team->id : $team;
            }
        }

        //Qapla!
        return $confirmed;
    }
    /**
     * Returns an array of tourney_team_ids of teams  within this tournament that have have a status of 1
     *      aka confirmed
     * 
     * Used internally for validating the value of $order while starting a touranment
     * 
     * @return array
     */
    public function confirmed_team_ids() {
        return $this->confirmed_teams(true);
    }
    /**
     * Used internally after a major change to refresh our list of teams, in hopes of
     *  getting changed data
     * 
     * Refresh our teams array, and deletes our cache of open_matches
     * 
     * @return void
     */
    private function reload() {
        //Clear ALL cache for this specific tournament
        $this->clear_id_cache();

        //GOGOGO!
        $this->rounds = null;
        $this->teams = null;
        $this->teams();
        $this->open_matches = null;
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
    public function &rounds() {
        //Already instantiated
        if(!is_null($this->rounds)) return $this->rounds;

        //New tournaments must be saved before we can start saving child data
        if(is_null($this->tourney_id)) {
            return $this->bb->ref(
                $this->set_error('Please execute save() before manipulating rounds or teams')
            );
        }

        //Ask the API for the rounds.  By default, this service returns every an array with a value for each bracket
        $result = $this->call(self::SERVICE_LOAD_ROUNDS, array('tourney_id' => $this->tourney_id), self::CACHE_TTL_ROUNDS, self::CACHE_OBJECT_TYPE, $this->id);

        //Error - return false and save the result 
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
            return $this->bb->ref($this->set_error($result));
        }

		//Initialize the rounds object, use BBHelper to give us the available brackets for this tournament
		$this->rounds = (object)BBHelper::get_available_brackets($this, true, true);

        //Initialize each returned round, and store it into local propeties
        foreach($result->rounds as $bracket => &$rounds) {
            //key each round by the round label
            $bracket_label = BBHelper::get_bracket_label($bracket, true);

			//Only import rounds for relevent brackets
			if(!isset($this->rounds->$bracket_label)) continue;

            //Save each new BBRound after initializing it
            foreach($rounds as $round => &$format) {
                $new_round = $this->bb->round($format);
                $new_round->init($this, $bracket, $round);
				$this->rounds->{$bracket_label}[] = $new_round;
            }
        }

        //Success! return the result
        return $this->rounds;
    }

    /**
     * Save the tournament - overloads BBModel::save() so we can 
     * check to see if we need to save rounds too
     * 
     * @return string       The id of this tournament to indicate a successful save, false if something failed
     */
    public function save($return_result = false, $child_args = null) {

        //For new tournaments - use save_new for special import instructions, otherwise use the standard save() method
        $result = is_null($this->id) ? $this->save_new() : parent::save();

        //Oh noes!! save failed, so return false - developer should check the value of $bb->last_error
        if(!$result) return false;

		//Now save any new/changed teams and rounds
		if(!$this->save_rounds())	return false;
		if(!$this->save_teams())	return false;
		if(!$this->save_matches())	return false;

		//clear ALL cache for this tournament id
		$this->clear_id_cache();

        //Success! Return the original save() result (if this tour is new, it'll give us the tourney_id)
        return $result;
    }
    /**
     * If creating a new tournament, we want to make sure that when the data is sent
     * to the API, that we request return_data => 2, therefore asking BinaryBeast to send
     * back a full TourneyInfo object for us to import all values that may have been
     * generated on BB's end
     * 
     * @return string       The new tourney_id for this object
     */
    private function save_new() {
        //return_data => 2 asks BinaryBeast to include a full tourney_info dump in its response
        $args = array('return_data' => 2);

        //If any teams have been added, include them too - the API can handle it
        $changed_teams = $this->get_changed_children('BBTeam');
        $teams = array();
        if(sizeof($changed_teams) > 0) {
            foreach($changed_teams as $team) $teams[] = $team->data;
        }
        if(sizeof($teams) > 0) $args['teams'] = $teams;

        //Let BBModel handle it from here - but ask for the api respnose to be returned instead of a generic boolean
        if(!$result = parent::save(true, $args)) return false;

        //OH NOES!
        if($result->result !== 200) return false;

        /**
         * Import the new data, and save the ID
         */
        $this->import_values($result);
        $this->set_id($result->tourney_id);

        //Use the api's returned array of teams to update to give each of our new teams its team_id
        if(isset($result->teams)) {
            if(is_array($result->teams)) {
                if(sizeof($result->teams) > 0) {
                    $this->iterating = true;
                    foreach($result->teams as $x => $team) {
                        $result = $this->teams[$x]->import_values($team);
                        $this->teams[$x]->set_id($team->tourney_team_id);
                    }
                    $this->iterating = false;
                }
            }
        }
        
        //Reset count of changed children
        $this->reset_changed_children('BBTeam');

        //Success!
        return $this->id;
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

        //New tournaments must be saved before saving teams
        if(is_null($this->id)) return $this->set_error('Can\t save teams before saving the tournament!');

        //Get a list of changed rounds
        $rounds = &$this->get_changed_children('BBRound');
		
        //Nothing has changed, just return true
        if(sizeof($rounds) == 0) return true;

        //Compile values into the format expected by the API - one array for each value, keyed by bracket, indexed by round
        $format = array();

        foreach($rounds as &$round) {
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
        foreach($format as $bracket => &$bracket_rounds) {
            //Determine the arguments for this bracket
            $args = array_merge($bracket_rounds, array(
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
        foreach($rounds as &$round) {
            $round->sync_changes();
        }

        //Reset our list of changed rounds, and return true, yay!
        $this->reset_changed_children('BBRound');
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
		
        //New tournaments must be saved before saving teams
        if(is_null($this->id)) return $this->set_error('Can\t save teams before saving the tournament!');

        //Get a list of changed teams
        $teams = $this->get_changed_children('BBTeam');

        //Nothing has changed, just return true
        if(sizeof($teams) == 0) return true;

        /**
         * Using the array of tracked / changed teams, let's compile
         *  a couple arrays of team values to send to the API
         * 
         * Initialize the two team arrays: new + update
         */
        $update_teams = array();
        $new_teams = array();

        /**
         * GOGOGO!
         */
        foreach($teams as &$team) {
            /**
             * New team - get all default + new values and add to $new_teams
             */
            if(is_null($team->id)) {
                $new_teams[] = $team->data;
            }
            /**
             * Existing team - get only values that are new, and key by team id
             */
            else {
                $update_teams[$team->tourney_team_id] = $team->get_changed_values();
            }
        }

        //Send the compiled arrays to BinaryBeast
        $result = $this->call(self::SERVICE_UPDATE_TEAMS, array(
            'tourney_id'        => $this->tourney_id,
            'teams'             => $update_teams,
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
		$this->iterating = true;
        foreach($teams as &$team) {

            //Tell the tournament to merge all unsaved changed into $data
            $team->sync_changes();

            /**
             * For new tournaments, make sure they get the new team_id
             */
            if(is_null($team->tourney_team_id)) {
                $team->set_id($result->team_ids[$new_id_index]);
                ++$new_id_index;
            }
        }
		$this->iterating = false;

        //Clear the list of teams with changes
        $this->reset_changed_children('BBTeam');

        //Success!
        return true;
    }
	/**
	 * Submit changes to any matches that have pending changes
	 * 
	 * @return boolean
	 */
	public function save_matches() {
		$matches = &$this->get_changed_children('BBMatch');
		
		/**
		 * Nothing special, just save() each one
		 * Difference is we're not "iterating", because if anything goes wrong, we'll
		 *	leave it flagged 
		 */
		foreach($matches as &$match) $match->save();

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
     * Therefore, all tournaments returned by this service are simple data objects
     * 
     * @param string $game_code
     *      You have the option of limiting the tournaments returned by context of a specific game,
     *      In otherwords for example game_code QL will ONLY return the most popular games that are using
     *      Quake Live
     * @param int $limit            Defaults to 10, cannot exceed 100
     * @return array<object>
     */
    public function list_popular($game_code = null, $limit = 10) {
        return $this->get_list(self::SERVICE_LIST_POPULAR, array(
            'game_code'     => $game_code,
            'limit'         => $limit
        ), 'list');
    }

    /**
     * Remove a team from this tournament's teams lists
     * Warning: this method does NOT perform any API requests, it's strictly
     * used to disassociate a BBTeam object from this tournament
     * 
     * The most likely use of this method is from BBTeam::delete() to remove itself from this
     *  tournament
     * 
     * @param BBTeam $team
     * @return void
     */
    public function remove_team(BBTeam &$team) {
        //First - run the unflag method to remove from teams_changed
        //It also has the nice side-effect of recalculating the local $changed flag
        $this->unflag_child_changed($team);

        //Finally, remove it from the teams array (use teams() to ensure the array is popualted first)
		if( ($key = array_search($team, $this->teams())) !== false) {
			unset($this->teams[$key]);
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
     * Either returns a new BBTeam to add to the tournament, or returns a reference to an existing one
     * 
     * This method will return a value of false if the tournament is already active!
     * 
     * The secondary use of this method is to validate a team argument value, allowing either
     *      the team's id or the team object itself to be used
     *      this method will convert the id to a BBTeam if necessary, and verify that
     *      it belongs to this tournament
     * 
     * A cool tip: this method can actually be used to pre-define a list of players in a new tournaemnt, before
     *      you even create it :)
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

        /**
         * Insure that the local teams array is up to date according to BinaryBeast
         * Wouldn't want to risk adding a new player to make teams() look initilaized,
         *  when in reality it hasn't yet
         */
		if(!is_null($this->id)) {
			if($this->teams() === false) {
				return $this->bb->ref(false);
			}
		}
        //If it's a new object, allow devs to add teams before save(), so we need to make sure $teams is initialized
		else if(is_null($this->teams)) $this->teams = array();

        /**
         * If they provided an $id, that means they we need to return a 
         * reference to a specific team that should already exist
         * 
         * It could also be a method allowing argument values to be both ids, and teams, 
         *      therefore relying on BBTournament::team() to make sure that either way,
         *      it ends up being a BBTeam, and that it's part of this tournament
         */
        if(!is_null($id)) {
            //If given a BBTeam directly, make sure it's part of this tournament
            if($id instanceof BBTeam) {
                if(($key = array_search($id, $this->teams)) !== false) return $this->teams[$key];
                $this->bb->ref(false);
            }

            //Simply have to iterate through until we find it
            foreach($this->teams as &$team) {
                if($team->id == $id) {
                    return $team;
                }
            }
            //Invalid team id
            $this->set_error('Tournament (' . $this->id . ') does not have a team by that id (' . $id . ')');
            return $this->bb->ref(null);
        }

        //We can't add new players to an active-tournament
        if(BBHelper::tournament_is_active($this)) {
            return $this->bb->ref($this->set_error('You cannot add players to active tournaments!!'));
        }

        //Instantiate a blank Team, and give it a reference to this tournament
        $team = $this->bb->team();
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
		$this->flag_child_changed($this->teams[$key]);

        //Flag the entire tournament as having unsaved changes
        $this->changed = true;

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

    /**
     * If you plan to allow players to join externaly from BinaryBeast.com, 
     *  you can use this method to allow them to start confirming their positions
     * 
     * Note that only confirmed players are included in the brackets / groups once the tournament starts
     * 
     * @return boolean
     */
    public function enable_player_confirmations() {
        //Make sure we're loaded first
        if(!$this->load()) return false;

        //Not even going to bother validating, let the API handle that
        $result = $this->call(self::SERVICE_ALLOW_CONFIRMATIONS, array('tourney_id' => $this->id));

        //Uh oh - likely an active tournament already
        if(!$result) return false;

        //Success - update local status value
        $this->set_current_data('status', 'Confirmation');
        return true;
    }
    /**
     * Disable participant's option of confirming their positions
     * 
     * Note that only confirmed players are included in the brackets / groups once the tournament starts
     * 
     * @return boolean
     */
    public function disable_player_confirmations() {
        //Make sure we're loaded first
        if(!$this->load()) return false;

        //Not even going to bother validating, let the API handle that
        $result = $this->call(self::SERVICE_DISALLOW_CONFIRMATIONS, array('tourney_id' => $this->id));

        //Uh oh - likely an active tournament already
        if(!$result) return false;

        //Success - update local status value
        $this->set_current_data('status', 'Building');
        return true;
    }

    /**
     * DANGER DANGER DANGER DANGER DANGER
     * DANGER!
     * 
     * This method will actually revert the tournament to its previous stage,
     *      So if you have brackets or groups, ALL progress in them will be lost forever
     * 
     * 
     * So BE VERY CAREFUL AND BE CERTAIN YOU WANT TO USE THIS
     * 
     * 
     * @return boolean      Simple result boolean, false on error true on success 
     */
    public function reopen() {
        //Can only reopen an active tournament
        if(!BBHelper::tournament_is_active($this)) {
            return $this->set_error('Tournament is not currently actve (current status is ' . $this->status . ')');
        }

        //Don't reopen if there are unsaved changes
        if($this->changed) {
            return $this->set_error(
                'Tournament currently has unsaved changes, you must save doing something as drastic as starting the tournament'
            );
        }

        /**
         * Who you gonna call??
         */
        $result = $this->call(self::SERVICE_REOPEN, array('tourney_id' => $this->id));

        //Uh oh
        if($result->result != BinaryBeast::RESULT_SUCCESS) return false;

        /**
         * Reopened successfully
         * update local status (sent by api), and reload teams
         */
        $this->set_current_data('status', $result->status);

        //Reload all data that would likely change from a major update like this (open matches, teams)
        $this->reload();

        //Success!
        return true;
    }

    /**
     * Start the tournament!!
     * 
     * This method will move the tournament into the next stage
     *      Depending on type_id and current status, the next stage could be Active, Active-Groups, or Active-Brackets
     * 
     * 
     * If you try to call this method while the tournament has unsaved changes, it will return false, you must save changes before starting the tournament
     * 
     * 
     * The reason we go through so much time validating the input, is BinaryBeast actually allows you to be flexible about defining team orders, which in
     *      My experience so far is NOT a good thing and will likely change - but for now, we'll enforce a string input, and make sure that all valid teams and ONLY
     *      valid teams are sent
     * 
     * Unfortunatley due to uncertainty in inevitable changes to the way Groups are seeding through the API... this method currently does not support
     *      manually seeeding groups, it will only allow randomized groups
     * 
     *      This will change soon through, as soon as the API group seeding service can get it's sh*t together
     * 
     * Seeding brackets however is ezpz - just give us an array of either team id's, or team models either in order of bracket position, or seed ranking
     * 
     * 
     * @param string $seeding       'random' by default
     *      How to arrange the team matches
     *      'random' (Groups + Brackets)        =>      Randomized positions
     *      'manual' (Groups + Brackets)        =>      Arranges the teams in the order you provide in $order
     *      'sports' (Brackets only)            =>      This is the more traditional seeding more organizers are likely used to,
     *              @see link to example here
     *              Top seeds are given the greatest advantage, lowest seeds the lowest
     *              So for a 16 man bracket, seed 1 players against 32 first, 2 against 31, 3 against 30... 8 against 9 etc
     *      'balanced' (brackets only)          =>      BinaryBeast's own in-house algorithm for arranged team seeds, we felt 'Sports' was a bit too inbalanced,
     *              @see link to example here
     *              Unlike sports where the difference in seed is dynamic, favoring top seeds, in Balanced the seed different is the same for every match, basically top seed + $players_count/2
     *              For a 16 man bracket, seed 1 is against 9, 2 against 10, 3 against 11, 4 against 12.. 8 against 16 etc
     * 
     * 
     * @param array $order          If any seeding method other than 'random', use this value
     *      to define either the team arrangements, or team seeds
     * 
     *      You may either provide an array of BBTeam objects, or an array of tourney_team_id integer values
     *          Example of BBTeam array: $order = [BBTeam (seed|position 1), BBTeam (seed|position 2)...]
     *          Example of ids array:    $order = [1234 (seed|position 1), 1235 (seed|position2)...]
     * 
     *      For 'Manual', the match arrangements will be determined by the order of teams in $order, 
     *          So for [1234, 1235, 1236, 1237]... the bracket will look like this:
     *          1234
     *          1235
     *          --
     *          1236
     *          1237
     * 
     *      For Sports and Balanced, the match arranagements are determined by an seed pairing algorithm,
     *          and each team's seed is equal to his position in $order
     * 
     *      Please note that you may ommit teams from the $order if you wish, and they will be
     *          randomly added to the end of the $order - which could be useful if you'd like to define
     *          only the top $x seeds, and randomize the rest
     * 
     *      If you want to define freewin positions (for example when manually arranging the matches)...
     *          Use an integer value of 0 in $order to indicate a FreeWin
     * 
     * @return boolean
     */
    public function start($seeding = 'random', $order = null) {
        //Use BBHelper to run verify that this touranment is ready to start
        if(is_string($error = BBHelper::tournament_can_start($this))) {
            //If it returned a string, that means there's an error to set
            return $this->set_error($error);
        }

        //Make sure the seeding type is valid, use BBHelper - returned value of null indicates invalid seeding type
        if(is_null($seeding = BBHelper::validate_seeding_type($this, $seeding))) {
            return $this->set_error("$seeding is not a valid seeding method value! Valid values: 'random', 'manual', 'balanced', 'sports' for brackets, 'random' and 'manual' for groups");
        }

        //Initialize the real $teams value we send to the API - $order is just temporary
        $teams = array();

        /**
         * If we need an order or teams / seeds, we need to make sure that 
         *      all confirmed teams are provided, and nothing more
         */
        if($seeding != 'random') {
            /**
             * Will be supported in the future, however for now we don't allow
             *      seeding groups with this class
             */
            if(BBHelper::get_next_tournament_stage($this) == 'Active-Groups') {
                return $this->set_error('Unfortunately for the time being, seeding groups has been disabled.  It will be supported in the future.  However for now, only "random" is supported for group rounds');
            }
            

            /**
             * First grab a list of teams that need to be included
             * 
             * Any teams not specifically provided in $order will be random
             *  added to the end
             */
            $confirmed_teams = $this->confirmed_team_ids();

            //Start looping through each team provided, adding it to $teams only if it's in $confirmed_teams
            foreach($order as &$team) {
                //If this is an actual BBTeam object, all we want is its id
                if($team instanceof BBTeam) $team = $team->id;

                //Now make sure that this team is supposed to be here
                if(!in_array($team, $confirmed_teams) && intval($team) !== 0) {
                    return $this->set_error("Team {$team} is a valid tourney_team_id of any team in this tournament, please include only valid team ids, or 0's to indicate a FreeWin");
                }

                /**
                 * Valid team! Now we need to do two things:
                 *  1) Remove the team from confirmed_teams (so we can randomize + add any remaining teams after we're finished)
                 *  2) Add its tourney_team_id to $teams, which is the actual value sent to BinaryBeast
                 */
                $teams[] = $team;
                unset($confirmed_teams[array_search($team, $confirmed_teams)]);
            }

            /*
             * If there are any teams left over, randomize them and add them to the end of the teams array
             */
            if(sizeof($confirmed_teams) > 0) {
                shuffle($teams);
                array_push($teams, $confirmed_teams);
            }
        }
        //For random tournaments, just send null for $order
        else $order = null;

        //GOGOGO!
        $result = $this->call(self::SERVICE_START, array(
            'tourney_id'        => $this->id,
            'seeding'           => $seeding,
            'teams'             => $teams
        ));

        //oh noes!
        if($result->result !== BinaryBeast::RESULT_SUCCESS) {
            return false;
        }

        /**
         * Started successfully!
         * Now we update our status value, and reload the teams arary
         * 
         * Conveniently the API actually sends back the new status, so we'll use that to update our local values
         */
        $this->set_current_data('status', $result->status);

        //Reload all data that would likely change from a major update like this (open matches, teams)
        $this->reload();

        //Success!
        return true;
    }

    /**
     * Returns an array of matches that still need to be reported
     * 
     * Each item in the array will be an instance of BBMatch, which you can use 
     *      to submit the results
     * 
     * @return array
     */
    public function &open_matches() {
        //Already cached
        if(!is_null($this->open_matches)) return $this->open_matches;

        //Inactive tournament
        if(!BBHelper::tournament_is_active($this)) {
            $this->set_error('cannot load open matches of an inactive tournament, start() it first');
            return $this->bb->ref(null);
        }

        //Ask the api
        $result = $this->call(self::SERVICE_LIST_OPEN_MATCHES, array('tourney_id' => $this->id), self::CACHE_TTL_LIST_OPEN_MATCHES, self::CACHE_OBJECT_TYPE, $this->id);
        if(!$result) return $this->bb->ref(false);

        //Cast each match into BBMatch, and call init() so it knows which tournament it belongs to
        foreach($result->matches as $match) {
            $match = $this->bb->match($match);
            $match->init($this);
            $this->open_matches[] = $match;
        }

        //Success!
        return $this->open_matches;
    }
	/**
	 * Given two teams / team ids, this method can be used to 
	 *		create a new BBMatch object with the new teams
	 * 
	 * returns false if either team provided does not belong to this tournament
	 * 
	 * You can use this method to load a match using a match id
	 *		param 1 = match id, param 2 = null
	 * 
	 * @param int|BBTeam	$team1
	 * @param int|BBTeam	$team2
	 * 
	 * @return BBMatch
	 */
	public function &match($team1, $team2 = null) {
		//If asking for an existing match, load that now
		if(is_null($team2) && is_numeric($team1)) {
			$match = $this->bb->match($team1);
			$match->init($this);
			return $match;
		}

		if(is_null($team1 = &$this->team($team1))) return false;
		if(is_null($team2 = &$this->team($team2))) return false;

		//Return from open_matches, but make sure it's populated first
		$this->open_matches();
		foreach($this->open_matches as $key => &$match) {
			if(($match->team() == $team1 && $match->team2() == $team2)
			|| ($match->team() == $team2 && $match->team2() == $team1)) {
				return $this->open_matches[$key];
			}
		}

		//Invalid
		$this->set_error("{$team1->id} vs {$team2->id} is not a valid match in this tournament, unable to createa a match object for this pair");
		return $this->bb->ref(null);
	}
}

?>