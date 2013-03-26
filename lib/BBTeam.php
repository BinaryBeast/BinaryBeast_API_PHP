<?php

/**
 * Model object representing a participant within a {@link BBTournament}
 * 
 * The naming may be a bit misleading, seeing as you can do 1v1s (players) as well 2v2+'s (teams).
 * 
 * This is because in the BinaryBeast back end, all 1v1 "players" are actually treated as
 * teams with only a single member, this was to simplify the development process
 * 
 * 
 * ### Quick tutorials
 * 
 * Here are a few quick tutorials for some common tasks when working with teams
 * 
 * The following examples assume the following:
 * 
 * <var>$bb</var> is an instance of {@link BinaryBeast}
 * 
 * <var>$tournament</var> is an instance of {@link BBTournament}
 * 
 * 
 * ### Create a New Team
 * 
 * Adding teams is rather simple
 * 
 * The best way, is to ues {@link BBTouranment::team()}
 * 
 * <b>Example: </b>
 * <code>
 *  $team = $tournament->team();
 * </code>
 * 
 * It's as simple as that - <var>$team</var> now refers to a new <b>unsaved</b> team in the touranment
 * 
 * The team will not exist remotely on BinaryBeast's servers until you execute either {@link BBTournament::save()}, or {@link BBTeam::save()}
 * 
 * 
 * ### Configuring Teams
 * 
 * Please refer to the list of properties in this document for all of the available properties you can set
 * 
 * Setting a team's property is very simple
 * 
 * <b>Example:</b>
 * <code>
 *  $team->country_code = 'NOR';
 *  $team->display_name = 'New norwegian team name';
 *  $team->save();
 * </code>
 * 
 * 
 * ### Reporting Matches
 * 
 * There are a few key methods/properties you'll need to make reporting matches painless
 * 
 * 
 * {@link BBTeam::opponent} can be used to fetch your <var>$team</var>'s current opponent
 * 
 * {@link BBTeam::match} gives you the open/unreported {@link BBMatch} your <var>$team</var> is currently in
 * 
 * {@link BBTeam::eliminated_by} can be used to fetch the team that knocked out your <var>$team</var>
 * 
 * 
 * ### More Tutorials
 * 
 * The rest is self-explanatory, please review the list of available methods / properties for a team
 * 
 * The next step is to review the documentation for {@link BBMatch}
 * 
 * 
 * @property string $display_name
 *  The name displayed on the brackets for this participant
 * 
 * @property string $country_code
 * <b>Default: null</b><br />
 * <b>3 characters (ISO 3166-1 alpha-3)</b><br />
 * This team's country, defined by the 3-character country code<br />
 * <b>Where to find the country codes:</b><br />
 * Wikipedia: {@link http://en.wikipedia.org/wiki/ISO_3166-1_alpha-3}<br />
 * BinaryBeast API: {@link BBCountry::search()}<br />
 * 
 * @property int $status
 *  <b>Default: 1 (confirmed)</b><br />
 *      The status of this team - Unconfirmed, Confirmed, and Banned<br />
 * 
 * <b>Friendly translation:</b><br />
 *  Use the BBHelper library: {@link BBHelper::translate_team_status()}<br /><br />
 * 
 * <b>Values found from BinaryBeast constants:</b>
 * <ul>
 *  <li>Confirmed: {@link BinaryBeast::TEAM_STATUS_CONFIRMED}</li>
 *  <li>Unconfirmed: {@link BinaryBeast::TEAM_STATUS_UNCONFIRMED}</li>
 *  <li>Banned: {@link BinaryBeast::TEAM_STATUS_BANNED}</li>
 * </ul>
 * 
 * @property int $wins
 * <b>Read Only During Group Rounds</b><br />
 * The number of wins this team has in the winners' bracket<br />
 * For brackets, it also represents which round he has progressed to in the bracket
 * 
 * @property int $lb_wins
 * The number of wins this team has in the losers' bracket<br />
 * For brackets, it also represents which round he has progressed to in the bracket
 * 
 * @property int $bronze_wins
 * Number of wins this team has in the bronze / 3rd place bracket
 * 
 * @property-read int $losses
 *  <b>Read Only</b><br />
 *  <b>Group Rounds Only</b><br />
 *      Number of losses this team has in his group
 * 
 * @property-read int $draws
 *  <b>Read Only</b><br />
 *  <b>Group Rounds Only</b><br />
 *      Number of draws this team has in his group
 * 
 * @property-read int $position
 *  <b>Read Only</b><br />
 *  <b>Elimination Brackets Only</b><br />
 *      The team's starting position in the winner brackets
 * 
 * @property string $notes
 *  Special hidden value that you can use to store custom data<br /><br />
 * 
 *  The recommended use is to store a json_encoded string that contains a local user_id, or similiar data
 * 
 * @property string $network_display_name
 *  If your tournament is using a game that is associated with a network (like sc2 => bnet2),<br />
 *  This is the value you can use to define his character code / aka his in-game name<br /><br />
 *  Same goes for steam, xbox live, etc etc
 * 
 * @property-read BBTournament $tournament
 *  <b>Alias for {@link BBTeam::tournament()}</b><br />
 *      The tournament this team is in<br />
 *  <b>NULL returned if created from BinaryBeast::team() without running {@link BBTeam::init()}</b>
 * 
 * @property-read BBMatch $match
 *  <b>Alias for {@link BBTeam::match()}</b><br />
 *  If this team has an opponent waiting, this method can be used to get the<br />
 *      BBMatch object for the match, so that it can be reported<br />
 *  <b>NULL if no match available</b>
 * 
 * @property-read BBMatch $last_match
 *  <b>Alias for {@link BBTeam::last_match()}</b><br />
 *      Returns the last match that this team was a part of<br />
 *  <b>NULL if no match available</b>
 * 
 * @property-read BBTeam $opponent
 *  <b>Alias for {@link BBTeam::opponent()}</b><br />
 *  the BBTeam object of this team's current opponent<br />
 *  <b>NULL return</b> means the team doesn't have an opponent yet<br />
 *  <b>FALSE return</b> means the team has been eliminated<br />
 * 
 * @property-read BBTeam $eliminated_by
 *  <b>Alias for {@link BBTeam::eliminated_by()}</b><br />
 *  the BBTeam object of the team that eliminated this team, if applicable<br />
 *  <b>FALSE return means the team has not yet been eliminated</b>
 * 
 * 
 * @package BinaryBeast
 * @subpackage Model
 * 
 * @version 3.0.0
 * @date 2013-03-26
 * @author Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
class BBTeam extends BBModel {

    //Service names for the parent class to use for common tasks
    const SERVICE_LOAD   = 'Tourney.TourneyLoad.Team';
    const SERVICE_CREATE = 'Tourney.TourneyTeam.Insert';
    const SERVICE_UPDATE = 'Tourney.TourneyTeam.Update';
    const SERVICE_DELETE = 'Tourney.TourneyTeam.Delete';
	//
	const SERVICE_CONFIRM		= 'Tourney.TourneyTeam.Confirm';
	const SERVICE_UNCONFIRM		= 'Tourney.TourneyTeam.Confirm';
	const SERVICE_BAN			= 'Tourney.TourneyTeam.Ban';
	//
	const SERVICE_GET_LAST_MATCH    = 'Tourney.TourneyTeam.LoadLastMatch';
    //
	const SERVICE_GET_OPPONENT		= 'Tourney.TourneyTeam.GetOTourneyTeamID';
	const SERVICE_LIST_OPPONENTS	= 'Tourney.TourneyTeam.GetOpponentsRemaining';

    //Cache setup (cache for 10 minutes)
    const CACHE_OBJECT_TYPE		= BBCache::TYPE_TEAM;
    const CACHE_TTL_LIST        = 10;
    const CACHE_TTL_LOAD        = 10;
	const CACHE_TTL_OPPONENTS	= 20;

    /**
     * Keep a reference to the tournament that instantiated this class
     * @var BBTournament
     */
    private $tournament;

    //This Team's ID, using BinaryBeast's naming convention
    public $tourney_team_id;

    //So BBModal knows which property use as the unique id
    protected $id_property = 'tourney_team_id';

    //Helps BBModal know how to extract the team data from the API result
    protected $data_extraction_key = 'team_info';

    /**
     * The BBTeam of this team's current opponent
     * @var BBTeam
     */
    protected $opponent;

    /**
     * For group rounds, this team may currently have several opponents available to play against
     * @var array
     */
    private $opponents;

	/**
	 * This team's current / unreported match
	 * @var BBMatch
	 */
	protected $match;
	/**
	 * This team's previous match
	 * @var BBMatch
	 */
	protected $last_match;
	/**
	 * If eliminated, store the team that eliminated us here
	 * @var BBTeam
	 */
	protected $eliminated_by;

    /**
     * Default values for a new team
     * @var array
     */
    protected $default_values = array(
        'display_name'                          => 'New Participant',
        'country_code'                          => null,
        'status'                                => BinaryBeast::TEAM_STATUS_CONFIRMED,
        'notes'                                 => null,
        'network_display_name'                  => null,
    );

    //Values that developers aren't allowed to change
    protected $read_only = array('players', 'losses', 'draws', 'position');

    /**
     * Array of players within this team (only for tours with team_mode > 1, aka only for team games)
     * 
     * @deprecated - BinaryBeast is currently rebuilding the way it handles teams, so we will be releasing a new API library to accomodate once it's done..
     *  so no point in releasing code that will break in a few months
     */
    //private $players;

    /**
     * Since PHP doens't allow overloading the constructor with a different paramater list,
     * we'll simply use a psuedo-constructor and call it init()
     * 
     * @param BBTournament $tournament
     * @param boolean $add_to_parent        True by default, try to add ourselves to the parent BBTournament
     * @return void
     */
    public function init(BBTournament &$tournament, $add_to_parent = true) {
        $this->tournament = &$tournament;

        //Set parent so BBModel will auto flag changes
        $this->parent = &$this->tournament;

        //If not already being tracked, flag ourselves in the new tournament
        if($add_to_parent) {
            if(is_array($teams = &$this->tournament->teams())) {
                if(!in_array($this, $teams)) {
                    $this->tournament->add_team($this);
                }
            }
        }
    }
    
    /**
     * Overloaded to allow setting the status value - so we can intercept with the appropriate
     *  status method (confirm() unconfirm() ban())
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        if($name == 'status') {
            if($value == -1)    return $this->ban();
            if($value == 0)     return $this->unconfirm();
            if($value == 1)     return $this->confirm();
        }
        //Don't allow changing wins during active-groups, only during brackets
        if($name == 'wins') {
            if(BBHelper::tournament_in_group_rounds($this->tournament())) {
                return;
            }
        }
        parent::__set($name, $value);
    }

	/**
	 * Overrides BBModel::save() so we can return false if trying to save an oprhaned team
	 */
	public function save($return_result = false, $child_args = null) {
		//Tournament must be saved first
		if(is_null($this->tournament->id)) {
			return $this->set_error('Tournament must be saved before adding teams');
		}

        //Remember whether or not we need to clear cache after the save is successful
        $reset_opponents = false;
        foreach(array_keys($this->new_data) as $key) {
            $key = strtolower($key);
            if($key == 'wins' || $key == 'lb_wins' || $key == 'bronze_wins') {
                $reset_opponents = true;
                break;
            }
        }

		//Let BBModel handle the rest
		if(! ($save_result = parent::save($return_result, array('tourney_id' => $this->tournament->id))) ) {
            return false;
        }

        //If bracket position was adjusted manually, reset opponents / cache etc 
        if($reset_opponents) {
            $this->tournament->clear_id_cache();
            $this->reset_opponents();
            $this->tournament->open_matches(true);
        }

        //Success!
        return $save_result;
	}

	/**
	 * Returns the BBTournament object this team belongs to
	 * 
	 * returns null if "orphaned", aka the team was deleted
	 * 
	 * @return BBTournament - null if unable to find it
	 */
	public function &tournament() {
		if($this->orphan_error()) return $this->bb->ref(null);

		//Already set
		if(!is_null($this->tournament)) return $this->tournament;

		//Make sure we even have an ID to load
		if(is_null($this->id)) {
			$this->set_error('Unable to load the tournament for this team, as this team does not have a tourney_team_id associated with it!');
			return $this->bb->ref(null);
		}
	
		//Make sure we're loaded first so we have the tour id
		$this->load();
		$id = $this->current_data['tourney_id'];
		$tournament = $this->bb->tournament->load($id);
	
		//Did it load correctly?
		if($tournament->id == $id) {
			$this->tournament = $tournament;
			return $this->tournament;
		}

		//Failure!
		$this->set_error('Error loading tournament ' . $id);
		return $this->bb->ref(null);
	}

    /**
     * Returns the BBTeam object of this team's current opponent
     * 
	 * Returns false if this teams has been eliminated
	 * 
	 * returns null if this team is currently waiting on an opponent
     * 
     * IMPORTANT NOTE: If this tournament is configured to use group_rounds,
     *      there may be several opponents currently waiting to play agains this team,
     *		If that's the case, then the this method will simply return the first one found
     * 
     * @return BBTeam
     *      null indicates that this team currently has no opponent, if the tournament is active it means he's waiting for another match to finish
     *      false indicates that this team has been eliminated, you can use elimianted_by to see by whome
     */
    public function &opponent($fail = false) {
		//Orphaned team
		if($this->orphan_error()) return $this->bb->ref(null);

        //Value already set
        if(!is_null($this->opponent) || $this->opponent === false) return $this->opponent;

        //Tournament is not active, can't possibly have an opponent - derp
        if(!BBHelper::tournament_is_active($this->tournament)) {
            return $this->bb->ref(
                $this->set_error('Tournament is not even active yet, impossible to determine a team\'s current opponent!')
            );
        }

		//Ask the API - cache it as tournament cache
		$result = $this->call(self::SERVICE_GET_OPPONENT, array(
			'tourney_team_id' => $this->id
			), self::CACHE_TTL_OPPONENTS, self::CACHE_OBJECT_TYPE, $this->id);
        if($fail) {
            return $result;
        }

        //Default to false, unless we determine otherwise
        $this->eliminated_by = &$this->bb->ref(false);

		//Found him!
		if($result->result == 200) {
            $this->opponent = &$this->tournament->team($result->o_tourney_team_id);
        }

		//Eliminated
		else if($result->result ==  735) {
			$this->opponent = false;
			$this->eliminated_by = &$this->tournament->team($result->victor->tourney_team_id);
			$this->set_error('Team has been eliminated! see $team->eliminated_by to see who defeated this team');
		}

		//Waiting for an opponent
		else if($result->result == 734) {
			$this->set_error('Team ' . $this->id . ' is currently waiting on an opponent');
		}

		return $this->opponent;
    }
	/**
	 * If this team has been eliminated, this method will return the BBTeam object
	 *	of the team that eliminated it
	 * 
	 * @return BBTeam - false if the team hasn't been eliminated
	 */
	public function &eliminated_by() {
		if(!is_null($this->eliminated_by) || $this->eliminated_by === false) {
            return $this->eliminated_by;
        }

		//Not set - try running opponent() then returning, as it will be set after opponent() is run
		$this->opponent();

		return $this->eliminated_by;
	}
    
    /**
     * Overloaded so that we can invoke reset_opponents() when a reload is required
     * 
     * @return self
     */
    public function &reload() {
        $this->reset_opponents();
        return parent::reload();
    }

    /**
     * Overload BBModel's reset() so we can also 
     *  reset any cached opponent / match data
     * 
     * This is important, since when reportin wins, reload() is called, which calls
     *      reset() - so we need to make sure that we honor the reaload() and re-query for
     *      opponent / eliminated_by etc
     */
    public function reset() {
        parent::reset();
        $this->reset_opponents();
    }

	/**
	 * If a match is reported, this method can be called to clear any
	 *		cached opponent results this team may have saved
     * 
     * @return void
	 */
	public function reset_opponents() {
        //If the current match was reported, save it as the last_match
        $this->last_match       = &$this->bb->ref(null);
        if(!is_null($this->match)) {
            if($this->match->team_in_match($this)) {
                if(!$this->match->is_new()) {
                    $this->last_match = $this->match;
                }
            }
        }

        //Assign to a new reference, so that we're not changing the original team value in Tournament::$teams
        $this->opponent         = &$this->bb->ref(null);
        $this->match            = &$this->bb->ref(null);
        $this->eliminated_by    = &$this->bb->ref(null);

        //Clear all cache for this team's ID
		$this->clear_id_cache();
	}

	/**
	 * Confirm this team's position in the tournament
	 * @return boolean
	 */
	public function confirm() {
		return $this->set_status(BinaryBeast::TEAM_STATUS_CONFIRMED, self::SERVICE_CONFIRM);
	}
	/**
	 * Confirm this team's position in the tournament
	 * @return boolean
	 */
	public function unconfirm() {
		return $this->set_status(BinaryBeast::TEAM_STATUS_UNCONFIRMED, self::SERVICE_UNCONFIRM);
	}
	/**
	 * Ban this team from the tournament
     * @return boolean
	 */
	public function ban() {
		return $this->set_status(BinaryBeast::TEAM_STATUS_BANNED, self::SERVICE_BAN);
	}

	/**
	 * Used by confirm, unconfirm, and ban to change the status of this eam
	 * @param int $status
	 * @param string $svc
	 * @return boolean
	 */
	private function set_status($status, $svc) {
		//Orphaned team
		if($this->orphan_error()) return false;

		//No change
		if($this->data['status'] == $status) return true;

		//Tournament already started
		if(BBHelper::tournament_is_active($this->tournament)) {
			return $this->set_error('Cannot change team status after tournament has already started!');
		}

		//Not a real team yet, just change the status to 1
		if(is_null($this->id)) {
			$this->set_new_data('status', $status);
			return true;
		}

		//Let the API handle the rest
		$result = $this->call($svc, array('tourney_team_id' => $this->id));
		if($result->result != BinaryBeast::RESULT_SUCCESS) {
			return $this->set_error('Unable to set team ' . $this->id . ' status to ' . BBHelper::translate_team_status($status) );
		}

		//Success!
		$this->set_current_data('status', $svc);
		return true;
	}

	/**
	 * If this team has an opponent waiting, this method can be used to get the
	 *		BBMatch object for the match, so that it can be reported
	 * 
	 * @return BBMatch|null
	 */
	public function &match() {
        if($this->orphan_error()) return $this->bb->ref(null);

		//Already cached
		if(!is_null($this->match)) return $this->match;

		//No opponent - can't make a match
		if(is_null($this->opponent())) return $this->bb->ref(null);

		//Use tournament to create the BBModel object, cache it, and return
        $this->match = &$this->tournament->match($this, $this->opponent);
		return $this->match;
	}

	/**
	 * Returns the last reported match that this team participated in
	 * 
	 * @return BBMatch|null
	 */
	public function &last_match() {
        if($this->orphan_error()) return $this->bb->ref(null);

		//Already cached
		if(!is_null($this->last_match)) return $this->last_match;

		//Simply rely on the API
        $this->tournament();
        $result = $this->call(self::SERVICE_GET_LAST_MATCH, array('tourney_team_id' => $this->id), 10, BBCache::TYPE_TOURNAMENT, $this->tournament->id);

        //Failure!
        if($result->result != BinaryBeast::RESULT_SUCCESS) return $this->bb->ref(null);

        //Import settings, and associate the tournament
        $match = $this->bb->match($result);
        $match->init($this->tournament());

        $this->last_match = $match;

        //Success!
        return $this->last_match;
	}

}

?>