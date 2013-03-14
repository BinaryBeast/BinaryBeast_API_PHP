<?php

/**
 * This class represents a single game within a match result withint a tournament
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-03-10
 * @author Brandon Simmons
 * 
 * 
 * ******* Property documentation *********
 * @property int $tourney_team_id
 *  <pre>
 *      The unique int tourney_team_id of this game's winner
 *  </pre>
 * 
 * @property int $score
 *  <pre>
 *      Score of the match's winner
 * 
 *      WARNING: this can be a little bit confusing at first, so be careful
 *          Game scores are tracked based on who won the overall match,
 *          therefore this value represents the score of the team who won the entire match
 *  </pre>
 * 
 * @property int $o_score
 *  <pre>
 *      Score of the match's loser
 * 
 *      WARNING: this can be a little bit confusing at first, so be careful
 *          Game scores are tracked based on who won the overall match,
 *          therefore this value represents the score of the team who lost the entire match
 *  </pre>
 * 
 * @property string|int $map
 *  <pre>
 *      The map name this game was played on
 *      You can define this as the map_id integer, or any map name string
 * 
 *      Using a map_id has many benefits, like image preview / stat tracking etc
 *          so if you want to use a map_id, you can use {@link BBMap::game_search()}
 *  </pre>
 * 
 * @property-read int $map_id 
 *  <b>Read Only</b>
 *  <pre>
 *      Value set when loading the game - the unique int id of the map 
 *          this game was played on
 *      Warning: attempts to change this value will result in updating the value of $map
 *  </pre>
 * 
 * @property string|int $race
 *  <pre>
 *      The match winner's race - can be the race_id integer, or a custom race name string
 *      Use {@link BBRace::game_list()} for race_ids values
 *  </pre>
 * 
 * @property string|int $o_race
 *  <pre>
 *      The match loser's race - can be the race_id integer, or a custom race name string
 *      Use {@link BBRace::game_list()} for race_ids values
 *  </pre>
 * 
 * @property string $notes General description / notes on the match
 * 
 * @property string $replay
 *  <pre>
 *      This will be updated soon to be more flexible, but for now
 *          all this value serves as is as a URL to the replay of this match
 *  </pre>
 * 
 * @property BBTeam $winner
 *  <b>Alias for {@link BBMatchGame::winner()}</b>
 *  <pre>
 *      BBTeam object for the winner of the game
 *  </pre>
 *  <b>Returns NULL if winner hasn't been defined ({@link BBMatchGame::set_winner()}</b>
 *  <b>Returns FALSE if match was a draw</b>
 * 
 * @property BBTeam $loser
 *  <b>Alias for {@link BBMatchGame::loser()}</b>
 *  <pre>
 *      BBTeam object for the loser of the game
 *  </pre>
 *  <b>Returns NULL if winner hasn't been defined ({@link BBMatchGame::set_winner()}</b>
 *  <b>Returns FALSE if match was a draw</b>
 * 
 * @property BBMatch $match
 *  <b>Alias for {@link BBMatchGame::match()}</b>
 *  <pre>
 *      The match this game is in
 *  </pre>
 */
class BBMatchGame extends BBModel {

    //Service names for the parent class to use for common tasks
    //const SERVICE_LOAD   = 'Tourney.TourneyLoad.Info'; //Not necessary, BBMatch does the loading
    const SERVICE_CREATE = 'Tourney.TourneyMatchGame.Create';
    const SERVICE_UPDATE = 'Tourney.TourneyMatchGame.Update';
    const SERVICE_DELETE = 'Tourney.TourneyMatchGame.Delete';

    //Cache setup (cache for 10 minutes)
    const CACHE_OBJECT_TYPE		= BBCache::TYPE_TOURNAMENT;
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
        'tourney_team_id'       => null,
        'score'                 => 1,
        'o_score'               => 0,
        'map_id'                => null,
        'map'                   => null,
        'race'                  => null,
        'o_race'                => null,
        'notes'                 => null,
        'replay'                => null
    );

    /**
     * A few settings that shouldn't be changed manually
     */
    protected $read_only = array('tourney_match_id', 'tourney_id');

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
     * Overloaded to update map when trying to set map_id, and so we can treat
     *  attempts to change team ids by calling set_winner|loser
     * 
     * @see BBModel::__set()
     * 
     * @return void
     */
    public function __set($name, $value) {
        //If setting team ids, run it through set_winner / set_loser
        if($name == 'tourney_team_id')      return $this->set_winner($value);
        if($name == 'o_tourney_team_id')    return $this->set_loser($value);

        if($name == 'map_id') $name = 'map';
        parent::__set($name, $value);
    }
    
    /**
     * Returns a reference to the match this game is in
     * 
     * @return BBMatch
     */
    protected function &match() {
        if(is_null($this->match)) return $this->bb->ref(null);
        return $this->match;
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
		//Ask for the result directly, so we can import new map / race values etc
        $result = parent::save(true, array('tourney_match_id' => $this->match->id));
		if(!$result) return false;

		if(!is_null($result->map_id))		$this->set_current_data('map_id', $result->map_id);
		if(!is_null($result->race_id))		$this->set_current_data('race_id', $result->race_id);
		if(!is_null($result->o_race_id))	$this->set_current_data('o_race_id', $result->o_race_id);

		//Success!
		return true;
    }

    /**
     * Returns the BBTeam object of the winner of this game
     * 
     * This method will also seutp the value for loser, used by 
     *      loser() to keep the code DRY
	 * 
	 * If false is returned, it indicates a draw
     * 
     * @return BBTeam
     *      false if game is a draw
     *      null if not yet defined
     */
    public function &winner() {
        //Already cached
        if(!is_null($this->winner) || $this->winner === false) return $this->winner;

        //No winner defined
        if(is_null($id = $this->data['tourney_team_id'])) {
            //If this is a new object, it means no winner was set
            if(is_null($this->id)) {
                $this->set_error('Winner/Loser not defined for this game');
                return $this->bb->ref(null);
            }

            //Existing game - null team id means that this game was a draw
            $this->winner = false;
            $this->loser = false;
            return $this->bb->ref(false);
        }

        //Try to load from the tournament, return directly since null is returned if the id is invalid anyway
        $this->winner = &$this->tournament->team($id);

        //Now that we have a winner, use match::toggle_team to determine the loser
        $this->loser = &$this->match->toggle_team($this->winner);

        //If null or false, I dont' want it returned by reference - wrap it in ref()
        if(is_null($this->winner) || $this->winner === false) return $this->bb->ref($this->winner);
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
        //Let winner() figure it out for us
        $this->winner();

        //If null or false, I dont' want it returned by reference - wrap it in ref()
        if(is_null($this->loser) || $this->loser === false) return $this->bb->ref($this->loser);
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
     * @param BBTeam|int    $winner        The winning team - can be a BBTeam instance of a tourney_team_id integer
     *      returns false if provided team is invalid
     * @param int           $match_winner_score
     *      The score of the team who won the match 
     * @param int           $match_loser_score
     *      The score of the team who lost the match 
     */
    public function set_winner($winner, $match_winner_score = null, $match_loser_score = null) {
        if($this->orphan_error()) return false;

		//Set winner as "null", indicating a draw
		if(is_null($winner) || $winner == false) return $this->set_draw($match_winner_score, $match_loser_score);

        //Use the match's team_in_match to give us the BBTeam, and to verify that it's actually in the match
        if(($winner = &$this->match->team_in_match($winner)) == false) {
            return $this->set_error('Invalid team selected for this game\'s winner');
        }

        //Update the winner property
        $this->winner = &$winner;

        //Use BBMatch::toggle_team to figure out who the loser is
        $this->loser = &$this->match->toggle_team($this->winner);

        //Update the team id values
        $this->set_new_data('tourney_team_id', $this->winner->id);
        $this->set_new_data('o_tourney_team_id', $this->loser->id);

        //Set scores
        $this->set_scores($match_winner_score, $match_loser_score);

        //Success!
        return true;
    }
    /**
     * Define which team lost this game - alternative to {@link BBMatchGame::set_winner()}
     * 
     * @param BBTeam|int $loser      tourney_team_id of the loser (null or false to indicat a draw)
	 * @param int $winner_score
	 * @param int $loser_score
     * @return boolean
     */
    public function set_loser($loser, $winner_score = null, $loser_score = null) {
        if( !is_null($winner = $this->match->toggle_team($loser)) ) {
            return $this->set_winner($loser_score, $winner_score, $loser);
        }
        return false;
    }
	/**
	 * Set the winner of this game to null, indicating a draw
	 * @return boolean
	 */
	public function set_draw($match_winner_score = null, $match_loser_score = null) {
        if($this->orphan_error()) return false;

		//Draws are only valid in group rounds
		if($this->tournament->status != 'Active-Groups') return $this->set_error('Only matches in group-rounds can be draws');

        //Set both teams to false, which is the indicator for a draw
		$this->winner   = false;
		$this->loser    = false;

        $this->set_scores($match_winner_score, $match_loser_score);

		//Update the values - though the API ignores it, we'll set "draw" too, so that
        //developers can easily check $game->is_draw as a boolean
		$this->set_new_data('tourney_team_id', null);

		//Success!
		return true;
	}
    /**
     * Define the scores for this game
     * 
     * WARNING: this can be a little bit confusing at first, so be careful
     * 
     * The winner / loser scores per game are tracked based on who won the overall match
     * 
     * "score" is the score of the winner of the entire match
     * "o_score" is the score of the loser of the entire match
     * 
     * The reason we force developers to use the set_scores method to define scores, and set score and o_score to read-only, is
     *  to help cut down on confusion
     * 
     * @param int $match_winner_score
     * @param int $match_loser_score
     * @return void
     */
    public function set_scores($match_winner_score = null, $match_loser_score = null) {
        if(!is_null($match_winner_score)) $this->set_new_data('score', $match_winner_score);
        if(!is_null($match_loser_score)) $this->set_new_data('o_score', $match_loser_score);
    }

    /**
     * Returns a simple boolean to indicate whehter or not 
     *  this game was considered a draw
     */
    public function is_draw() {
        return $this->winner() === false;
    }
}

?>