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
 * 
 * 
 * ******* Property documentation *********
 * @property boolean $title
 *      Tournament title
 * 
 * @property boolean $public
 *  <b>Default: true</b>
 *  <pre>
 *      If true, this tournament can be found in the public lists + search
 *  </pre>
 * 
 * @property-read string $status
 *  <b>Read Only</b>
 *  <pre>
 *      The current tournament status
 *      Building: New tournament, still accepting signups, not accepting player-confirmations
 *      Confirmations: Getting ready to start, still accepting signups, now accepting player-confirmations
 *      Active: Brackets
 *      Active-Groups: Round robin group rounds
 *      Active-Brackets: Brackets after group-rounds 
 *      Complete: Final match has been reported
 *  </pre>
 * 
 * @property-read int $live_stream_count
 *  <b>Read Only</b>
 *  <pre>
 *      Number of streams associated with this tournament that are currently streaming
 *  </pre>
 * 
 * @property-read string $game
 *  <b>Read Only</b>
 *  <pre>
 *      Name of this tournament's game, determined from {@link BBTournament::$game_code}
 *  </pre>
 * 
 * @property-read string $network
 *  <b>Read Only</b>
 *  </pre>
 *      Name of the network for this game's tournament
 *  </pre>
 * 
 * @property string $game_code
 *  <b>Default: HotS (StarCraft 2: Heart of the Swarm)</b>
 *  <pre>
 *      Unique game identifier, 
 *      Use {@link BBGame::game_search()} to search through our games list, and get the game_code values
 *  </pre>
 * 
 * @property int $type_id
 *  <b>Default: 0 (Elimination Brackets)</b>
 *  <pre>
 *      Tournament type - defines the stages of the tournament
 *      Use {@link BinaryBeast::TOURNEY_TYPE_BRACKETS} and {@link BinaryBeast::ELIMINATION_SINGLE}
 *  </pre>
 * 
 * @property int $elimination
 *  <b>Default: 1 (Single Elimination)</b>
 *  <pre>
 *      Elimination mode, single or double, simply 1 for single 2 for double
 *      but you can also use {@link BinaryBeast::ELIMINATION_SINGLE} and {@link BinaryBeast::ELIMINATION_DOUBLE}
 *  </pre>
 * 
 * @property boolean $bronze
 *  <b>Default: false</b>
 *  <b>Single Elimination Brackets Only</b>
 *  <pre>
 *      Set this true to enable the bronze bracket
 *      aka the 3rd place decider
 *  </pre>
 * 
 * @property int $team_mode
 *  <b>Default: 1 (1v1)</b>
 *  <pre>
 *      Number of players per team
 *      1 = a normal 1v1
 *      Anything else indicates this is a team-based game
 *  </pre>
 * 
 * @property int $group_count
 *  <b>Default: 1</b>
 *  <b>Group Rounds Only</b>
 *  <pre>
 *      Number of groups to setup when starting round robin group rounds
 *  </pre>
 * 
 * @property int $teams_from_group
 *  <b>Default: 2</b>
 *  <b>Group Rounds Only</b>
 *  <pre>
 *      Number of participants from each group that advance to the brackets
 *  </pre>
 * 
 * @property string $location
 *      Generic description of where players should meet / coordinate (ie a bnet channel)
 * 
 * @property int $max_teams
 *  <b>Default: 32</b>
 *  <pre>
 *      Maximum number of participants allowed to confirm their positions
 *  </pre>
 * 
 * @property int $replay_uploads
 *  <b>Default: 1 (Optional)</b>
 *  <pre>
 *      Replay upload mode
 *      {@link BinaryBeast::REPLAY_UPLOADS_OPTIONAL}
 *      {@link BinaryBeast::REPLAY_UPLOADS_DISABLED}
 *      {@link BinaryBeast::REPLAY_UPLOADS_MANDATORY}
 *  </pre>
 * 
 * @property int $replay_downloads
 *  <b>Default: 1 (Enabled)</b>
 *  <pre>
 *      Replay download mode
 *      {@link BinaryBeast::REPLAY_DOWNLOADS_ENABLED}
 *      {@link BinaryBeast::REPLAY_DOWNLOADS_DISABLED}
 *      {@link BinaryBeast::REPLAY_DOWNLOADS_POST_COMPLETE}
 *  </pre>
 * 
 * @property string $description
 *  <pre>
 *      Generic description of the tournament
 *      Plain text only - no html allowed
 *  </pre>
 * 
 * @property string $hidden
 *  <pre>
 *      Special hidden (as you may have guessed) values that you can use to store custom data
 *      The recommended use of this field, is to store a json_encoded string that contains your custom data
 *  </pre>
 * 
 * @property string $player_password
 *  <pre>
 *      Strongly recommend you set something here
 *      In fact, this class even provides a method that generates a random password,
 *          you can use {@link BBTournament::generate_player_password()} to
 *          set a random one automatically
 * 
 *      This reason this is important is that without one, ANYONE from the outside 
 *      can join your tournaments...
 * 
 *      So it's to insure that only your application can add participants to your events, set a value
 *  </pre>
 * 
 * @property BBTeam[] $teams
 *  <b>Alias for {@link BBTournament::teams()}</b>
 *  <pre>
 *      An array of teams in this tournament
 *  </pre>
 * 
 * @property BBTeam[] $confirmed_teams
 *  <b>Alias for {@link BBTournament::confirmed_teams()}</b>
 *  <pre>
 *      An array of confirmed teams in this tournament
 *  </pre>
 * 
 * @property BBTeam[] $unconfirmed_teams
 *  <b>Alias for {@link BBTournament::unconfirmed_teams()}</b>
 *  <pre>
 *      An array of unconfirmed teams in this tournament
 *  </pre>
 * 
 * @property BBTeam[] $banned_teams
 *  <b>Alias for {@link BBTournament::banned_teams()}</b>
 *  <pre>
 *      An array of banned teams in this tournament
 *  </pre>
 * 
 * @property object $rounds
 *  <b>Alias for {@link BBTournament::rounds()}</b>
 *  <pre>
 *      An object containing arrays of BBRound objects 
 *      each array is keyed by the the simple bracket label:
 *          groups, winners, losers, bronze, finals
 * </pre>
 * 
 * @property BBMatch[] $open_matches
 *  <b>Alias for {@link BBTournament::open_matches()}</b>
 *  <pre>
 *      An array of matches in this tournament that still need to be reported
 *  </pre>
 * 
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
     * @var BBTeam[]
     */
    private $teams;

    /**
     * Object containing format for each round
     * Keyed by bracket name, each of which is an array of BBRound objects
     * @var mixed
     */
    private $rounds;

    /**
     * This tournament's ID, using BinaryBeast's naming convention
     * @var string
     */
    public $tourney_id;
    /**
     * This tournament's ID, using BinaryBeast's naming convention
     * @var string
     */
    public $id;

    /**
     * Cache results from loading open matches from the API
     * @var BBMatch[]
     */
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
        'title'             => 'PHP API Test',
        'public'            => true,
        'game_code'         => 'HotS',
        'type_id'           => BinaryBeast::TOURNEY_TYPE_BRACKETS,
        'elimination'       => BinaryBeast::ELIMINATION_SINGLE,
        'bronze'            => false,
        'team_mode'         => 1,
        'group_count'       => 0,
        'teams_from_group'  => 2,
        'location'          => null,
        'max_teams'         => 32,
        'replay_uploads'    => BinaryBeast::REPLAY_UPLOADS_OPTIONAL,
        'replay_downloads'  => BinaryBeast::REPLAY_DOWNLOADS_ENABLED,
        'description'       => '',
        'hidden'            => null,
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
     * @param boolean   $ids set true to return array of ids only
     * @param array     $args   any additonal arguments to send with the API call
     * 
     * @return BBTeam[]
     *      Null is returned if there was an error with the API request
     */
    public function &teams($ids = false, $args = array()) {

        //Already instantiated
        if(!is_null($this->teams)) {
            //Requested an array of ids
            if($ids) {
                $teams = array();
                foreach($this->teams as $x => &$team) $teams[$x] = $team->id;
                return $teams;
            }

            return $this->teams;
        }

		/**
         * Use BBSimpleModels' listing method, for caching etc
         *  also specify $full to make sure that we get ALL teams if the tournament is active
         */
		if( ($this->teams = $this->get_list(self::SERVICE_LOAD_TEAMS, array_merge($args, array('tourney_id' => $this->tourney_id, 'full' => true))
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
     * Returns an array of confirmed teams in this tournament
     * 
     * @param boolean   $ids        set true to return array of ids only
     * @return BBTeam[]
     */
    public function &confirmed_teams($ids = false) {
        return $this->filter_teams_by_status($ids, 1);
    }
    /**
     * Returns an array of unconfirmed teams in this tournament
     * 
     * @param boolean   $ids        set true to return array of ids only
     * @return BBTeam[]
     */
    public function &unconfirmed_teams($ids = false) {
        return $this->filter_teams_by_status($ids, 0);
    }
    /**
     * Returns an array of banned teams in this tournament
     * 
     * @param boolean   $ids        set true to return array of ids only
     * @return BBTeam[]
     */
    public function &banned_teams($ids = false) {
        return $this->filter_teams_by_status($ids, -1);
    }
    
    /**
     * Used by confirmed|banned|unconfirmed_teams to return an array of teams from $teams() that have a matching
     *  $status value

     * @param boolean   $ids        Return just ids if true
     * @param int       $status     Status value to match
     *      Null to return ALL teams
     * @return BBTeam[]
     */
    private function &filter_teams_by_status($ids, $status) {
        //Use teams() to guarantee up to date values, and so we can return false if there are errors set by it
        if(!$teams = &$this->teams()) return $this->bb->ref(false);

        //Initialize the output
        $filtered = array();

        //Simply loop through and return teams with matching $status
        foreach($teams as &$team) {
            if($team->status == $status || is_null($status)) {
                $filtered[] = $ids ? $team->id : $team;
            }
        }

        //Qapla!
        return $filtered;
    }
    /**
     * Used internally after a major change to refresh our list of teams, in hopes of
     *  getting changed data
     * 
     * Refresh our teams array, and deletes our cache of open_matches
     * 
     * @return void
     */
    private function on_major_update() {
        //Clear ALL cache for this specific tournament
        $this->clear_id_cache();

        //Flag reloads for any existing teams, in case any stray references exist after removing child classes
        if(is_array($this->teams)) foreach($this->teams as &$team) $team->flag_reload();

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
     * @param boolean   $return_result      ignored
     * @param array     $child_args         ignored
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
     * Save the tournament without updating any children
     */
    public function save_settings() {
        //For new tournaments - use save_new for special import instructions, otherwise use the standard save() method
        return is_null($this->id) ? $this->save_new(true) : parent::save();
    }
    /**
     * If creating a new tournament, we want to make sure that when the data is sent
     * to the API, that we request return_data => 2, therefore asking BinaryBeast to send
     * back a full TourneyInfo object for us to import all values that may have been
     * generated on BB's end
     * 
     * @param boolean $settings_only        false by default - can be used to skip saving children (BBTeams)
     * 
     * @return string       The new tourney_id for this object
     */
    private function save_new($settings_only = false) {
        //return_data => 2 asks BinaryBeast to include a full tourney_info dump in its response
        $args = array('return_data' => 2);

        //If any teams have been added, include them too - the API can handle it
        if(!$settings_only) {
            $changed_teams = $this->get_changed_children('BBTeam');
            $teams = array();
            if(sizeof($changed_teams) > 0) {
                foreach($changed_teams as $team) $teams[] = $team->data;
            }
            if(sizeof($teams) > 0) $args['teams'] = $teams;
        }

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
        if(!$settings_only) {
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
        }

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
     * Overrides BBModel::reset() so we can define the $teams array for removing unsaved teams
     */
    public function reset() {
        //BBModel's default action first
        parent::reset();

        //Now let BBmodel remove any unsaved teams from $this->teams
        $this->remove_new_children($this->teams);
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
        $teams = &$this->get_changed_children('BBTeam');

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

        //Send the compiled arrays to the API for batch update/insert
        $result = $this->call(self::SERVICE_UPDATE_TEAMS, array(
            'tourney_id'        => $this->id,
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
            if(is_null($team->id)) {
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
     * @param boolean $report       true by default - you can set this to false to ONLY save existing matches, and not to report()
     *  and now ones
     * 
	 * @return boolean
	 */
	public function save_matches($report = true) {
		$matches = &$this->get_changed_children('BBMatch');

		/**
		 * Nothing special, just save() each one
		 * Difference is we're not "iterating", because if anything goes wrong, we'll
		 *	leave it flagged 
		 */
		foreach($matches as &$match) {
            if(!$report) {
                if(is_null($match->id)) {
                    continue;
                }
            }
            $match->save();
        }

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
     * Remove a child class from this team - like a BBTeam
     * 
     * For BBTeam, we pass it thorugh $team() to avoid any mistakes caused by reloading / changed data, we base the 
     *  search purely on tourney_team_id
     * 
     * @param BBModel $child
     * @param type $children
     */
    public function remove_child(BBModel &$child, &$children = null, $preserve = false) {
        if($child instanceof BBTeam) {
            //Rely on team() to insure that even if changed, that we at LEAST get the correct reference using team_id
            if(!is_null($team = &$this->team($child))) {
                return parent::remove_child($team, $this->teams(), $preserve);
            }
            return false;
        }
        if($child instanceof BBMatch) {
            //Like team(), we use match() to standardize, in case the input has changed from our cached version
            if(!is_null($match = &$this->match($child))) {
                return parent::remove_child($child, $this->open_matches(), true);
                
            }
            return false;
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
     * Add a BBTeam object to this tournament
     * 
     * The team must NOT have an ID, and must not
     *  have a tournament associated with it yet, 
     *  (unless that tournament is this tournament)
     * 
     * @param BBTeam $team
     * @return int          The team's new index in $this->teams()
     *      Can return false, so you may want to check $add_team === false
     */
    public function add_team(BBTeam $team) {
        //We can't add new players to an active-tournament
        if(BBHelper::tournament_is_active($this)) {
            return $this->bb->ref($this->set_error('You cannot add players to active tournaments!!'));
        }

        //Derp - already part of this tournament, just return true
        if(in_array($team, $this->teams())) return true;

        //Team already has an ID
        if(!is_null($team->id)) return $this->set_error('That team already has a tourney_team_id, it cannot be added to another tournament');

        //Team already has a tournament - make sure it's THIS tournament
        if(!is_null($tournament = &$team->tournament())) {
            if($tournament != $this) {
                return $this->set_error('Team already belongs to another tournament, it cannot be added to this one');
            }
        }

        //At this point we can proceed, so associate this tournament with the team
        else $team->init($this);

        //add it to the list!
        $key = sizeof($this->teams);
        $this->teams[$key] = $team;

        //Flag changes
        $this->flag_child_changed($this->teams[$key]);

        //Success! return the key/index
        return $key;
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
     * @return BBTeam       Returns null if invalid
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
         * Team() can be used to validate a team input, and we rely on 
         *  BBModel::get_child for that
         */
        if(!is_null($id)) {
            return $this->get_child($id, $this->teams);
        }

        //We can't add new players to an active-tournament
        if(BBHelper::tournament_is_active($this)) {
            return $this->bb->ref($this->set_error('You cannot add players to active tournaments!!'));
        }

        //Instantiate, and associate it with this tournament
        $team = $this->bb->team();
        $team->init($this, false);

        //use add_team to add it to $teams - it will return the key so we can return a direct reference
        if(($key = $this->add_team($team)) === false) return $this->bb->ref(false);

        //Success! return a reference directly from the teams array
        return $this->teams[$key];
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
        $this->on_major_update();

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
            $confirmed_teams = $this->confirmed_teams(true);

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
        $this->on_major_update();

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
     * also like team(), this can be used to validate a match belongs to this tournament, and to
     *  return an updated reference
	 * 
	 * @param int|BBTeam|BBMatch        $team1
	 * @param int|BBTeam                $team2
	 * 
	 * @return BBMatch|null
	 */
	public function &match($team1 = null, $team2 = null) {
        //Could already be in open_matches
        if($team1 instanceof BBMatch) {
            return $this->get_child($team1, $this->open_matches());
        }

		//If asking for an existing match, load that now
		if(is_null($team2) && is_numeric($team1)) {
			$match = $this->bb->match($team1);
			$match->init($this);
            //Existing matches must be from this tournament
            if($match->tournament() != $this) {
                $this->set_error('Requested match id (' . $team1 . ') is not from this tournament (' . $this->id . ')');
                return $this->bb->ref(null);
            }
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
        
        /**
         * @todo support loading a match that has already been reported - but require that they specify which bracket to look in
         */

		//Invalid
		$this->set_error("{$team1->id} vs {$team2->id} is not a valid match in this tournament, unable to createa a match object for this pair");
		return $this->bb->ref(null);
	}
}

?>