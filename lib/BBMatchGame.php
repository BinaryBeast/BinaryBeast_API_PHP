<?php

/**
 * This class represents a single game within a match result withint a tournament
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-02-02
 * @author Brandon Simmons
 */
class BBMatchGame extends BBModel {

    //Service names for the parent class to use for common tasks
    //const SERVICE_LOAD   = 'Tourney.TourneyLoad.Info'; //Not necessary, BBMatch does the loading
    const SERVICE_CREATE = 'Tourney.TourneyMatchGame.Create';
    const SERVICE_UPDATE = 'Tourney.TourneyMatchGame.Update';
    const SERVICE_DELETE = 'Tourney.TourneyMatchGame.Delete';

    //Cache setup (cache for 10 minutes)
    const CACHE_OBJECT_ID       = BBCache::TYPE_TOURNAMENT;
    const CACHE_TTL_LIST        = 10;
    const CACHE_TTL_LOAD        = 10;

    /**
     * Reference to the match this game is in
     * @var BBMatch
     */
    private $match;
    /**
     * Keep a reference to the tournament that instantiated this class
     * @var BBTournament
     */
    private $tournament;

    //Unique ID for this object
    public $tourney_match_game_id;

    //So BBModal knows which property use as the unique id
    protected $id_property = 'tourney_match_game_id';

    /**
     * BBTeam cache of this game's winner
     * @var BBTeam
     */
    private $winner;

    /**
     * BBTeam cache of this game's loser
     * @var BBTeam
     */
    private $loser;

    /**
     * Default values for a new tournament
     * @see BinaryBeast::update()
     * @var array
     */
    protected $default_values = array(
        //tourney_team_id of the winner - loser is not required, since we assume the other player of the match was the loser
        'tourney_team_id'       => null,
        //Score of the winner
        'score'                 => 1,
        //Score of the loser
        'o_score'               => 0,
        //Map ID - you can find this value in $bb->map->game_list($game_code[$filter = null]) (map_id)
        'map_id'                => null,
        //Optionally you can provide the map name instead of map_id
        'map'                   => null,
        //Winner's race - can be the race_id or race name (use $bb->race->game_list($game_code) for race_ids)
        'race'                  => null,
        //Loser's race - can be the race_id or race name (use $bb->race->game_list($game_code) for race_ids)
        'o_race'                => null,
        //General description / notes on the match
        'notes'                 => null,
        //This will be updated soon to be more flexible, but for now - all this value serves as is as a URL to the replay of this match
        'replay'                => null,
    );

    /**
     * A few settings that shouldn't be changed manually
     */
    protected $read_only = array('tourney_team_id', 'o_tourney_team_id', 'tourney_match_id', 'tourney_id');

    /**
     * Since PHP doens't allow overloading the constructor with a different paramater list,
     * we'll simply use a psuedo-constructor and call it init()
     * 
     * @param BBTournament  $tournament
     * @param BBMatch       $match
     * @return void
     */
    public function init(BBTournament &$tournament, BBMatch &$match) {
        $this->tournament   = &$tournament;
        $this->match        = &$match;

        //Let BBModel know who our parent is, so that changes are automatically flagged in BBMatch
        $this->parent = &$this->match;
    }

    /**
     * Overloads BBModel::save so we can make sure that the match
     *  is saved before trying to update any games
     * 
     * @return boolean
     */
    public function save($return_result = false, $child_args = null) {
        //If the match hasn't been saved yet, stop now
        if(is_null($this->match->id)) {
            return $this->set_error("You must save the entire match before saving games (\$match->save() or \$match->report())");
        }
        parent::save($return_result, $child_args);
    }

    /**
     * Returns the BBTeam object of the winner of this game
	 * 
	 * If false is returned, it indicates a draw
     * 
     * @return BBTeam
     */
    public function &winner() {
        //Already cached
        if(!is_null($this->winner) || $this->winner === false) return $this->winner;

        //No winner defined
        if(is_null($id = $this->data['tourney_team_id'])) {
            $this->set_error('Winner/Loser not defined for this game');
            return $this->bb->ref(null);
        }

        //Try to load from the tournament, return directly since null is returned if the id is invalid anyway
        $this->winner = $this->tournament->team($id);
        return $this->winner;
    }

    /**
     * Returns the BBTeam object of this game's loser
	 * 
	 * If false is returned, it indicates a draw
     * 
     * @return BBTeam
     */
    public function &loser() {
        //Already cached
        if(!is_null($this->loser) || $this->loser === false) return $this->loser;

        //No loser defined
        if(is_null($id = $this->data['o_tourney_team_id'])) {
            $this->set_error('Winner/Loser not defined for this game');
            return $this->bb->ref(null);
        }

        //Try to load from the tournament, return directly since null is returned if the id is invalid anyway
        $this->loser = $this->tournament->team($id);
        return $this->loser;
    }

    /**
     * Defines which team won this game
     * 
     * Must be a team from within the match
     * 
     * You can provide either the team's integer id, or the BBTeam object
	 * 
	 * Set winner to null to indiciate a draw
     * 
     * @param BBTeam|int        The winning team
     * returns false if provided team is invalid
     */
    public function set_winner($winner) {

		//Set winner as "null", indicating a draw
		if(is_null($winner)) return $this->set_draw();

        //Use the match's team_in_match to give us the BBTeam, and to verify that it's actually in the match
        if(($winner = &$this->match->team_in_match($winner)) == false) {
            return $this->set_error('Invalid team selected for this game\'s winner');
        }

        //Update the winner property
        $this->winner = &$winner;

        //Figure out which team lost so that we can update the loser value
        if($this->winner == $this->match->winner()) {
            $this->loser = &$this->match->loser();
        }
        else $this->loser = &$this->match->winner();

        //Update the team id values
        $this->set_new_data('tourney_team_id', $this->winner->id);
        $this->set_new_data('o_tourney_team_id', $this->loser->id);

        //Success!
        return true;
    }
	/**
	 * Set the winner of this game to null, indicating a draw
	 * @return boolean
	 */
	public function set_draw() {
		//Draws are only valid in group rounds
		if($this->tournament->status != 'Active-Groups') return $this->set_error('Only matches in group-rounds can be draws');

		$this->winner = false;
		$this->loser = false;

		//Update the values
		$this->set_new_data('tourney_team_id', 0);
		
		//Success!
		return true;
	}
}

?>