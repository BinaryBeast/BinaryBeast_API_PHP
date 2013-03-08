<?php

/**
 * This class represents a participant within a Tournament
 * 
 * The naming may be a bit misleading, seeing as you can do 1v1s (players) as well 2v2+'s (teams).
 * 
 * This is because in the BinaryBeast back end, all 1v1 "players" are actually treated as
 * teams with only a single member, this was to simplify the development process
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-02-05
 * @author Brandon Simmons
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
    private $opponent;

    /**
     * For group rounds, this team may currently have several opponents available to play against
     * @var array
     */
    private $opponents;

	/**
	 * This team's current match, use current_match or match() to get
	 * @var BBMatch
	 */
	private $match;
	/**
	 * If eliminated, store the team that eliminated us here
	 * @var BBTeam
	 */
	private $eliminated_by;

	/**
	 * When a team is deleted, we flag it as an orphan, so that
	 *		any future attempts to edit it will return an accurate error
	 */
	private $orphan = false;

    /**
     * Default values for a new participant, also a useful reference for developers
     * @var array
     */
    protected $default_values = array(
        //The name displayed on the brackets for this participant
        'display_name'                          => 'New Participant',
        //3 character ISO CountryCode, use $bb->country_search([$filter]) for values
        'country_code'                          => null,
        //The initial status of this team, see BinaryBeast::TEAM_STATUS_* for values
        'status'                                => BinaryBeast::TEAM_STATUS_CONFIRMED,
        /**
         * This is a special value that could take a while to explain.. but in short:
         * It's a special hidden value that allows developers to store custom data.. for example you could store
         * a json encoded string that stores some data about this team that's specific to your site, like
         * his local user_id, or his local email address. etc etc
         */
        'notes'                                 => null,
        /**
         * If your tournament is using a game that is associated with a network (like sc2 => bnet2),
         * This is the value you can use to define his character code / aka his in-game name
         * Same goes for steam, xbox live, etc etc
         */
        'network_display_name'                  => null,
    );

    //Values that developers aren't allowed to change
    protected $read_only = array('players');

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
     * @return void
     */
    public function init(BBTournament &$tournament) {
        $this->tournament = &$tournament;

        //Set parent so BBModel will auto flag changes
        $this->parent = &$this->tournament;
    }
	
	/**
	 * Methods that make any changes to this team use this method to first check to see
	 *	if this team has been orphaned... aka deleted
	 * 
	 * returns a boolean - false if not orphaned, true if orphaned
	 * 
	 * It also will call set_error, so you don't have to handle the error if orphaned
	 * 
	 * @return boolean
	 */
	private function orphan_error() {
		if($this->orphan) {
			$this->set_error('Team has been removed from the tournament, you can no longer make changes to it');
			return true;
		}
		return false;
	}

	/**
	 * Overrides BBModel::save() so we can return false if trying to save an oprhaned team
	 */
	public function save($return_result = false, $child_args = null) {
		//Orphaned team - don't save
		if($this->orphan_error()) return false;

		//Tournament must be saved first
		if(is_null($this->tournament->id)) {
			return $this->set_error('Tournament must be saved before adding teams');
		}

		//Let BBModel handle the rest
		return parent::save($return_result, array('tourney_id' => $this->tournament->id));
	}

	/**
	 * Returns the BBTournament object this team belongs to
	 * 
	 * returns null if "orphaned", aka the team was deleted
	 * 
	 * @return BBTournament
	 */
	public function &tournament() {
		//Already set
		if(!is_null($this->tournament)) return $this->tournament;

		//If orphaned, return null
		if($this->orphan_error()) return $this->bb->ref(null);

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
     * Delete this team!!!!!!!
	 * WARNING - USE CAUTING! THIS IS A DANGEROUS METHOD THAT CANNOT BE REVERSED!!!!
	 * 
	 * If for any reason we should fail to delete the team,
	 *		please use $team->result() and $team->error() to see why
	 * 
	 * 
     * Note for new unsaved team, this method removes itself from the tournament
     * 
     * @return boolean
     */
    public function delete() {
		//Already deleted - derp
		if($this->orphan_error()) return false;

		/**
		 * First ask the API to delete it from BinaryBeast, but only 
		 *		if the team has an ID
		 */
		if(!is_null($this->id)) {
			if(!parent::delete()) return false;
		}

		/**
		 * At this point we either deleted from the API successfully, 
		 *	or didn't even have a team_id to delete (new team)
		 * 
		 * So now we remove the tournament reference, affectively
		 *		orphaning this object - so that it can no longer be edited
		 */
		$this->tournament->remove_team($this);
		$this->tournament = null;
		$this->orphan = true;

		//Deleted successfully
		return true;
    }

    /**
	 * Overrides BBModel's id setter so we can throw a fit if this team has been orphaned
     * 
     * @param int $tourney_team_id
     */
	public function set_id($id) {
		//Can't change orphaned teams
		if($this->orphan_error()) return false;

		parent::set_id($id);
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
     * @return BBTeam (null if no opponent available)
     */
    public function &opponent() {
		//Orphaned team
		if($this->orphan_error()) return $this->bb->ref(null);

        //Already figured it out
        if(!is_null($this->opponent)) return $this->opponent;

        //Tournament is not active, can't possibly have an opponent - derp
        if(!BBHelper::tournament_is_active($this->tournament)) {
            return $this->bb->ref(
                $this->set_error('Tournament is not even active yet, impossible to determine a team\'s current opponent!')
            );
        }

		//Ask the API
		$result = $this->call(self::SERVICE_GET_OPPONENT, array(
			'tourney_team_id' => $this->id
			), self::CACHE_TTL_OPPONENTS, self::CACHE_OBJECT_TYPE, $this->id);

		//Found him!
		if($result->result == 200) $this->opponent = &$this->tournament->team($result->o_tourney_team_id);

		//Eliminated
		else if($result->result == 735) {
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
	 * @return BBTeam
	 */
	public function &eliminated_by() {
		if(!is_null($this->eliminated_by)) return $this->eliminated_by;

		//Not set - try running opponent() then returning, as it will be set after opponent() is run
		$this->opponent();

		return $this->eliminated_by;
	}
	
	/**
	 * If a match is reported, this method can be called to clear any
	 *		cached opponent results this team may have saved
	 */
	public function reset_opponents() {
		$this->opponent		= null;
		$this->opponents	= null;
		$this->match		= null;
	}

	/**
	 * Confirm this team's position in the tournament
	 * 
	 * @return boolean
	 */
	public function confirm() {
		return $this->set_status(BinaryBeast::TEAM_STATUS_CONFIRMED, self::SERVICE_CONFIRM);
	}
	/**
	 * Confirm this team's position in the tournament
	 * 
	 * @return boolean
	 */
	public function unconfirm() {
		return $this->set_status(BinaryBeast::TEAM_STATUS_UNCONFIRMED, self::SERVICE_UNCONFIRM);
	}
	/**
	 * Ban this team from the tournament
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
	 * @return BBMatch
	 */
	public function &match() {
		//Already
		if(!is_null($this->match)) return $this->match;

		//No opponent - can't make a match
		if(is_null($this->opponent())) return $this->bb->ref(null);

		//Use tournament to create the BBModel object, cache it, and return
		return $this->match = &$this->tournament->match($this, $this->opponent);
	}

	/**
	 * Clears all cache associated with this team
	 *	pertaining to getting oppponent ids / lists
	 */
	public function clear_opponent_cache() {
		$this->clear_id_cache(array(
			self::SERVICE_GET_OPPONENT,
			self::SERVICE_LIST_OPPONENTS
		));
	}
}

?>
