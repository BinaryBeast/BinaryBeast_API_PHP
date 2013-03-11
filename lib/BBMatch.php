<?php

/**
 * This class represents a single match result withint a tournament
 * 
 * Important!  See BBMatch::default_values for possible values and descriptions
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-02-10
 * @author Brandon Simmons
 * 
 * ******* Property documentation *********
 * @property int $tourney_team_id
 *  <pre>
 *      The ID of the match's overall winner
 *  </pre>
 * 
 * @property int $o_tourney_team_id
 *  <pre>
 *      The ID of the match's overall loser
 *  </pre>
 * 
 * @property-read int $bracket
 *  <b>Read Only</b>
 *  <pre>
 *      Numeric value of the bracket is in
 *      You can use {@link BBHelper::get_bracket_label()} to get the friendly translation
 *  </pre>
 * 
 * @property string $notes
 *  <pre>
 *      General notes / description on the match
 *  </pre>
 * 
 * @property int $score
 *  <pre>
 *      Winner's score - using this is NOT recommended however
 *  </pre>
 *  <b>Please use {@link BBMatch::game()} to defined more detailed results</b>
 * 
 * @property int $o_score
 *  <pre>
 *      Loser's score - using this is NOT recommended however
 *  </pre>
 *  <b>Please use {@link BBMatch::game()} to defined more detailed results</b>
 * 
 * @property boolean $draw
 *  <b>Group Rounds Only</b>
 *  <pre>
 *      Simple boolean to indicate whether or not this match resulted in a draw
 *  </pre>
 * 
 * @property BBMatchGame[] $games
 *  <b>Alias for {@link BBMatch::games()}</b>
 *  <pre>
 *      an array of games in this match
 *  </pre>
 * 
 * @property BBRound $round
 *  <b>Alias for {@link BBMatch::round()}</b>
 *  <pre>
 *      The BBRound object defining the format for this match
 *      Note: null may be returned if for some reason we can't determine
 *          which round within the bracket this match is
 *  </pre>
 * 
 * @property BBTeam $team
 *  <b>Alias for {@link BBMatch::team()}</b>
 *  <pre>
 *      BBTeam object for the first player in this match
 *  </pre>
 * 
 * @property BBTeam $team2
 *  <b>Alias for {@link BBMatch::team2()}</b>
 *  <pre>
 *      BBTeam object for the second player in this match
 *  </pre>
 * 
 * @property BBTeam $opponent
 *  <b>Alias for {@link BBMatch::opponent()}</b>
 *  <pre>
 *      BBTeam object for the second player in this match
 *  </pre>
 * 
 * @property BBTeam $winner
 *  <b>Alias for {@link BBMatch::winner()}</b>
 *  <pre>
 *      BBTeam object for the winner of the match
 *  </pre>
 *  <b>Returns NULL if set_winner hasn't been called</b>
 *  <b>Returns FALSE if match was a draw</b>
 * 
 * @property BBTournament $tournament
 *  <b>Alias for {@link BBMatch::tournament()}</b>
 *  <pre>
 *      The tournament this match is in
 *  </pre>
 */
class BBMatch extends BBModel {

    //Service names for the parent class to use for common tasks
    const SERVICE_LOAD   = 'Tourney.TourneyLoad.Match'; 
    const SERVICE_CREATE = 'Tourney.TourneyTeam.ReportWin';
    const SERVICE_UPDATE = 'Tourney.TourneyMatch.Update';
    const SERVICE_DELETE = 'Tourney.TourneyMatch.Delete';
	//
	const SERVICE_UPDATE_GAMES  = 'Tourney.TourneyMatchGame.ReportBatch';

    //Cache setup (cache for 10 minutes)
    const CACHE_OBJECT_TYPE		= BBCache::TYPE_TOURNAMENT;
    const CACHE_TTL_LIST        = 10;
    const CACHE_TTL_LOAD        = 10;

    /**
     * Keep a reference to the tournament that instantiated this class
     * @var BBTournament
     */
    private $tournament;

    //This Match's ID, using BinaryBeast's naming convention
    public $tourney_match_id;

    //So BBModal knows which property use as the unique id
    protected $id_property = 'tourney_match_id';

    //Helps BBModal know how to extract the right value from the API result
    protected $data_extraction_key = 'match_info';

    /**
     * Public array of games within this match
     * @var BBMatchGame[]
     */
    private $games = array();

    /**
     * For new matches, winner() and loser() won't return a value unless
     *  this flag indicates that a winner has been determined
     */
    private $winner_set = false;

    /**
     * BBRound format for this match's round
     * @var BBRound
     */
    private $round;

    /**
     * BBTeam object for player 1 -- winner for existing matches
     * @var BBTeam
     */
    private $team;

    /**
     * BBTeam object for player 2 -- loser for existing matches
     * @var BBTeam
     */
    private $opponent;

    /**
     * Default values for a new match
     * @var array
     */
    protected $default_values = array(
        'tourney_team_id'           => null,
        'o_tourney_team_id'         => null,
        'bracket'                   => BinaryBeast::BRACKET_WINNERS,
        'notes'                     => null,
		'score'						=> 1,
		'o_score'					=> 0,
        'draw'                      => false
    );

    //Values sent from the API that I don't want developers to accidentally change
    protected $read_only = array('team', 'opponent', 'tourney_team_id', 'o_tourney_team_id', 'draw', 'bracket');

    /**
     * Import parent tournament class
     * 
     * @param BBTournament  $tournament
     * @param int           $bracket
     * @return void
     */
    public function init(BBTournament &$tournament, $bracket = null) {
        $this->tournament = &$tournament;

        //Associate tournament as parent, so BBModel will flag child changes for us
        $this->parent = &$this->tournament;

        //Import bracket if defined
        if(!is_null($bracket)) $this->set_current_data('bracket', $bracket);

		//Update any games we may have (necessary to allow directly loaded matches ($match = $bb->match(id) without calling init())
		foreach($this->games as &$game) $game->init($this->tournament, $this);
    }

    /**
     * Overloads the BBModel save() so we can define additional arguments to send
     * 
     * @param boolean $return_result    Ignored
     * @param array $child_args         Ignored
     * @return boolean
     */
    public function save($return_result = false, $child_args = null) {
		//Report() before saving
		if(is_null($this->id)) return $this->report();

		//Already exists - let BBModel update basic settings (notes is the only thing really that can be updated this way)
        if(!parent::save(false, null)) return false;

		//Update all game details
		if(!$this->save_games()) return false;

		//Wipe all tournament cache
		$this->tournament->clear_id_cache();

		//Success! return id for consistency
		return $this->id;
    }

    /**
     * Overload BBModel's __set so we can handle setting team ids, and draw manually
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        if($name == 'tourney_team_id')      return $this->set_winner($value);
        if($name == 'o_tourney_team_id')    return $this->set_loser($value);
        if($name == 'draw') {
            if($value != false) return $this->set_draw();
            return;
        }
        parent::__set($name, $value);
    }

    /**
     * Overrides BBModel::reset() so we can define the $teams array for removing unsaved teams,
     *  and so we can unflag $winner_set if appropriate
     */
    public function reset() {
        //BBModel's default action first
        parent::reset();

        //For new matches, reset the winner if defined
        if(is_null($this->id)) $this->winner_set = false;

        //Now let BBmodel remove any unsaved teams from $this->teams
        $this->remove_new_children($this->games());
    }

    /**
     * Returns the BBRound containing the round format for this match
     * 
     * returns null if unable to determine the round format
     * 
     * @return BBRound - null if unavailable
     */
    public function &round() {
        //Already set
        if(!is_null($this->round)) return $this->round;

        //If we have a value for round and bracket, grab the round from the tournament now
        if(isset($this->current_data['round']) && isset($this->current_data['bracket'])) {
            //The tournament's rounds array is keyed by "friendly" bracket, determine ours now
            $bracket = BBHelper::get_bracket_label($this->current_data['bracket'], true);
            $round  = $this->current_data['round'];

            //Found it!
            $this->round = &$this->tournament->rounds->{$bracket}[$round];
        }

        //Failure!
        else {
            $this->set_error('Unable to figure out which round / bracket this match is in');
            return $this->bb->ref(null);
        }

        //Success!
        return $this->round;
    }

    /**
     * Returns an array of BBGames in this match
     * 
     * @return array
     */
    public function &games() {
		//Try loading first
		if(!is_null($this->id)) $this->load();
        return $this->games;
    }

    /**
     * Overrides BBModal::load because we need to change the argument
     * of get_round when requesting the data from BinaryBeast
	 * 
	 * This also allows us to initialize a tournament object for this match
	 *		if created directly without calling init()
     * 
     * get_round asks BinaryBeast to make sure that it sends the round information
     * used for this match in addition to the match details
     * 
     * All we have to do is build additional paramaters and then let
     * BBModal handle the rest
     * 
     * @param mixed $id     If you did not provide an ID in the instantiation, they can provide one now
     * @param array $args   ignored
     * @param boolean   $skip_cache     Disabled by default - set true to NOT try loading from local cache
     * 
     * @return self - false if there was an error loading
     */
    public function &load($id = null, $args = array(), $skip_cache = false) {
        //Let BBModal handle this, just pass it extra paramater
		$result = &parent::load($id, array_merge(array('get_round' => true), $args), $skip_cache );
		if(!$result) return $result;

		//If we don't have a tournament, try to load it now
		if(isset($this->data['tourney_id']) && is_null($this->tournament)) {
			$tournament = $this->bb->tournament($this->data['tourney_id']);

			//init() to make sure that parent() is set, and that our games have the new tournament reference
			$this->init($tournament);
		}

		//Success!
		return $result;
    }

    /**
     * Returns the BBTeam object for the first player in this match
     * 
     * If the match has already been reported, this also represents
     *      the team that <code>WON</code> this match
     * 
     * @return BBTeam
     */
    public function &team() {
        //Already set
        if(!is_null($this->team)) return $this->team;

        //Use the internal get_team method for this, using the team property
        return $this->team = &$this->get_team('team');
    }
    /**
     * Returns the BBTeam object for the second player in this match
     * 
     * If the match has already been reported, this also represents
     *      the team that <code>LOST</code> this match
     * 
     * @return BBTeam
     */
    public function &opponent() {
        //Already set
        if(!is_null($this->opponent)) return $this->opponent;

        //Use the internal get_team method for this, using the team property
        return $this->opponent = &$this->get_team('opponent');
    }
    /**
     * alias for BBMatch::opponent()
     * @return BBTeam
     */
    public function &team2() {
        return $this->opponent();
    }
    /**
     * Simply returns the team in this match, that is NOT
     *  the team provided
     * 
     * Warning: you MUST provide a valid team however, it will only
     *  give you the other team if the one you provide is actually
     *  part of this match
     * 
     * @param BBTeam|int    $team
     * @return BBTeam   - null if input invalid
     */
    public function &toggle_team($team) {
        //If input is false, assume it's from a draw and return false in kind
        if(is_null($team) || $team === false) return $this->bb->ref(false);

        //use team_in_match to both validate it's a valid team, and to allow using either an id, or a team instance
        if( ($team = &$this->team_in_match($team)) ) {
            if($team == $this->team())              return $this->opponent();
            else if($team == $this->opponent())     return $this->team();
        }

        //Failure!
        return $this->bb->ref(null);
    }
    /**
     * Used internally to keep our code dry - try to 
     *  use internal team_id values to get a BBTeam class
     *  for teams in this match from our BBTournament
     * 
     * @param string $property      Which value to use for the team_id (tourney_team_id, o_tourney_team_id)
     * @return BBTournament
     */
    private function &get_team($property) {
		//Try loading first
		if(!is_null($this->id)) $this->load();

        //Try to load it using $property provided
        if(!is_null($team = $this->current_data[$property])) {
            //let BBTournament::team() handle setting any errors, we can return it directly
            return $this->tournament->team($team->tourney_team_id);
        }

        //OH NOES!
        $this->set_error('Match does not have any value set for ' . $property . ', unable to figure out which teams are in this match');
        return $this->bb->ref(null);
    }

    /**
     * Alias for team()
     * 
     * Return the BBTeam object of this match's winning team
     * 
     * returns null if the match hasn't been reported
     * 
     * @return BBTeam
     *      Note: returns false if this match was defined as a draw - use team() and opponent() instead
     */
    public function &winner() {
        //Draw - return false
        if($this->is_draw()) return $this->bb->ref(false);

        //For new matches, only return the winner if it has been specifcally set, using set_winner
        if(is_null($this->id)) {
            if($this->winner_set) return $this->team();

            //Unplayed match, without a winner set
            $this->set_error('Cannot retrieve the winner of unreported matches, try accessing $match->team and $match->team2(or $match->opponent)');
            return $this->bb->ref(null);
        }
        return $this->team();
    }
    /**
     * Return the BBTeam object of this match's losing team
     * 
     * returns null if the match hasn't been reported
     * 
     * @return BBTeam
     */
    public function &loser() {
        //Draw - return false
        if($this->is_draw()) return $this->bb->ref(false);

        //For new matches, only return the winner if it has been specifcally set, using set_winner
        if(is_null($this->id)) {
            if($this->winner_set) return $this->opponent();

            //Unplayed match, without a winner set
            $this->set_error('Cannot retrieve the loser of unreported matches, try accessing $match->team and $match->team2(or $match->opponent)');
            return $this->bb->ref(null);
        }
        return $this->opponent();
    }
    /**
     * Returns a simple boolean to indicate wheter or not 
     *  this match was considered a draw
     */
    public function is_draw() {
        return $this->data['draw'] == true;
    }

    /**
     * BinaryBeast sends 'match_info', as well as the array 'games', so 
     * we need overload BBModal to ensure that the games array is imported
     * as well
     * 
     * Once games is imported, we pass control back to BBModal
     * 
     * If we find the games array, we will cast each value into a new
     *  BBMatchGame class, then pass control back to BBModal for the rest 
     * 
     * @param object $data
     * @return void
     */
    public function import_values($data) {
        //Found it!
        if(isset($data->games)) {
            //Now loop through each game as instantiate a new BBMatchGame for it
            $this->games = array();
            foreach($data->games as &$game) {
                //Instantiate a new game, tell it to remember us, then store it in games[]
                $game = $this->bb->match_game($game);
				//If we have a tournament (we wouldn't if this was created directly from $bb), give each game a reference
                if(!is_null($this->tournament)) {
					$game->init($this->tournament, $this);
				}
                $this->games[] = $game;
            }
        }

        //Let BBModal handle the rest, business as usual
        parent::import_values($data);

        //If not given a 'draw' value, initialize it as false
        if(!isset($this->data['draw'])) $this->set_current_data('draw', false);
    }

    /**
     * If this is an unplayed match, this method can be used
     *      to define which team won the match
     * 
     * Will return false if you try to define a team that's not in this match
     * 
     * You can provide either the BBTeam directly, or its id
     * 
     * You can't use this method to change match results,
     *      you'll have to unreport it first using BBMatch::unreport()
	 * 
	 * If you'd like to skip making individual game results, you can define the score of
	 *		the winner and loser here - it will 
	 * 
	 * Warning: winner_score and loser_score will be OVERWRITTEN if you define any games!!!
     * 
     * @param BBTeam|int $winner      tourney_team_id of the winner (null or false to indicat a draw)
	 * @param int $winner_score
	 * @param int $loser_score
     * @return boolean
     */
    public function set_winner($winner, $winner_score = null, $loser_score = null) {
        //Only appropriate for new matches
        if(!is_null($this->id)) return $this->set_error('Can\'t use set_winner to change the results of a match, you must unreport() first');

		//$winner = null means setting a draw, use set_draw()
		if(is_null($winner) || $winner === false) return $this->set_draw();

        //Use team_in_match to both make sure we have a BBTeam, and to make sure it's part of this match
        if(($winner = &$this->team_in_match($winner)) == false) {
			return $this->set_error("Error setting winner to the provided team, team does not seem to be part of this match!");
		}

        //If we need to swap the team / opponent, do so now
        if($winner == $this->opponent()) {
            $this->opponent = &$this->team;
        }

        //new_loser is set, now save new_winner and return 
        $this->team         = &$winner;
        $this->winner_set   = true;

		//Set the team_ids now, the values that the API will be looking for
		$this->set_new_data('tourney_team_id',		$this->team->id);
		$this->set_new_data('o_tourney_team_id',	$this->opponent->id);
        $this->set_new_data('draw', false);
		if(!is_null($winner_score)) $this->set_new_data('score', $winner_score);
		if(!is_null($loser_score))	$this->set_new_data('o_score', $loser_score);

        return true;
    }
    /**
     * Define which team lost this match - uses set_win for the actual work, after "toggling" the team
     * 
     * @param BBTeam|int $loser      tourney_team_id of the loser (null or false to indicat a draw)
	 * @param int $winner_score
	 * @param int $loser_score
     * @return boolean
     */
    public function set_loser($loser, $winner_score = null, $loser_score = null) {
        if( !is_null($winner = $this->toggle_team($loser)) ) {
            return $this->set_winner($loser_score, $winner_score, $loser);
        }
        return false;
    }

	/**
	 * Define the winner of this match as a draw, aka no one won this match
	 * 
	 * Only valid for group rounds
	 * 
	 * @param int $winner_score
	 * @param int $loser_score
	 * @return boolean 
	 */
	public function set_draw($winner_score = null, $loser_score = null) {
		//Draws are only valid in group rounds
        $tournament = &$this->tournament();
		if($tournament->status != 'Active-Groups') return $this->set_error('Only matches in group-rounds can be draws');

		//First make sure we have a valid team and opponent
		if(is_null($this->team()) || is_null($this->opponent())) return false;

		//The API expects team ids
		$this->set_new_data('tourney_team_id',		$this->team->id);
		$this->set_new_data('o_tourney_team_id',	$this->opponent->id);
		$this->set_new_data('draw',					true);

		if(!is_null($winner_score)) $this->set_new_data('score', $winner_score);
		if(!is_null($loser_score))	$this->set_new_data('o_score', $loser_score);

		//So report() doens't complain about not having a winner
		$this->winner_set = true;

		//Success!
		return true;
	}


    /**
     * This method is used to report the results of unplayed matches
     * 
     * returns false if this match has already been played
     * 
     * Note: For new matches, you must use set_winner() to define which 
     *      team won the match
     * 
     * You also have the option of using $match->game($game_number) to save details about
     *      each individual game within this match
     * 
     * @param boolean $strict       disabled by default - if you force strict, it will
     *      only report if it validate_winner_games tells us that the match winner was given enough wins to satisfy
     *      this round's best_of setting
     * 
     * @return int		returns the id if successfull, false otherwise
     */
    public function report($strict = false) {
        //Already reported
        if(!is_null($this->id)) {
            return $this->set_error('This match has already been reported, please use save() if you wish to change the details');
        }

        //No winner defined
        if(!$this->winner_set) {
            return $this->set_error('Please define a winner before reporting ($team->set_winner($winning_team)) You can refer to $match->team and $match->opponent for participant details');
        }

        /**
         * Do a quick last check to make sure that this match is still in the touranment's list of open_matches,
         *  if it' snot, it could be caused by a number of things - like being reported elsewhere, or the tournament advancing
         *  to the next stage etc
         */
        $tournament = &$this->tournament();
        if(!in_array($this, $tournament->open_matches())) {
            return $this->set_error('This match is no longer listed as an open match for this tournament, perhaps it was reported elsewhere, or the tournament has begun the next stage');
        }

        //We can't report the match if the round has unsaved changes, because the API may not process it correctly, as it may
        //have a different value for this round's best_of
        $round = &$this->round();
        if(!is_null($round)) {
            //Stop now - round has to be saved first
            if($round->changed) return $this->set_error('The round for this match has unsaved changes, please save them first (either with $round->save(), $tournament->save_rounds, or $tournament->save()');

            //Strict - validate the winner has enough game wins first
            if($strict) {
                if(!$this->validate_winner_games($strict)) {
                    return $this->set_error('Winning team does not have enough game wins! This round requires at least ' . $this->round->best_of . ' game wins');
                }
            }
        }

		//Let BBModel handle this
		$result = parent::save(false, array('tourney_id' => $this->tournament->id));

		//Report all of the game details
		if($result) {
			if(!$this->save_games()) return false;
		}

		//Wipe all tournament cache, and tournament opponent cache / wins lb_wins losses draws bronze_draws etc
		$this->tournament->clear_id_cache();
        $this->team->reload();
        $this->opponent->reload();
        
        /**
         * Tell the touranment that this is no longer an open match
         * But specify preserve to avoid this object becoming null
         */
        $this->tournament->remove_child($this);

		//Return the save() result
		return $result;
    }
	/**
	 * Perform a batch update on the games in this match
	 * 
	 * @return boolean
	 */
	public function save_games() {
		//Update the match first
		if(is_null($this->id)) return $this->set_error('Please report() or save() beforing saving game data');

		//No games saved - stop now.  We don't actually use this array though, we'll just create a new batch of games
		if(sizeof($this->get_changed_children('BBMatchGame')) == 0) return true;

		//Start compiling the value array for the API
		$scores			= array();
		$o_scores		= array();
		$races			= array();
		$o_races		= array();
		$maps			= array();
		$winners		= array();
		$notes			= array();

		//Loop through each game and extact the appropriate values
		foreach($this->games as &$game) {
			$scores[]		= $game->score;
			$o_scores[]		= $game->o_score;
			$races[]		= $game->race;
			$o_races[]		= $game->o_race;
			$maps[]			= $game->map;
			$winners[]		= $game->is_draw() ? 0 : $game->winner->id;
			$notes[]		= $game->notes;
		}

		//Make the call!
		$result = $this->call(self::SERVICE_UPDATE_GAMES, array(
			'tourney_match_id'		=> $this->id,
			'scores'				=> $scores,
			'o_scores'				=> $o_scores,
			'races'					=> $races,
			'o_races'				=> $o_races,
			'maps'					=> $maps,
			'winners'				=> $winners,
			'notes'					=> $notes,
			'dump'					=> true,
		));
		if($result->result != 200) return $this->set_error('Error saving game details! see $bb->error_history for details');

		//Update each game with new id, maps, races, and synchronize
		$this->iterating = true;
		foreach($this->games as $key => &$game) {
			$dump = &$result->games[$key];

			if(!isset($dump->map_id))		$game->map_id		= $dump->map_id;
			if(!isset($dump->map))          $game->map			= $dump->map;
			if(!isset($dump->race))         $game->race			= $dump->race;
			if(!isset($dump->o_race))		$game->o_race		= $dump->o_race;
			if(!isset($dump->race_id))      $game->race_id		= $dump->race_id;
			if(!isset($dump->o_race_id))	$game->o_race_id	= $dump->o_race_id;

			$game->set_id($result->ids[$key]);
			$game->sync_changes();
		}
		$this->iterating = false;

		//Clear our list of changed games
		$this->reset_changed_children('BBMatchGame');

		//Success!
		return true;
	}
    /**
     * Retuns a boolean telling you whether or not the games give this
     *  match's winner enough wins - if $round is available
     * 
     * Warning: if for some reason we can't find the $round format, we'll return true
     *  to avoid issues
     * 
     * @param boolean $strict   false by default - normally we'd just return true if we can't find round() data, but strict mode
     *      would result returning false in that case
     * 
     *      Strict mode also means that the match winner must have EXACTLY $round->wins_needed
     *          because otherwise he'd have to have a LEAST wins_needed, if he has more it would validate - but not in strict mode
     * 
     * @return boolean
     */
    public function validate_winner_games($strict = false) {
        //Continue only if we can figure out the round format for this match
        if(!is_null($round = &$this->round())) {
            $winner = &$this->winner();
            $wins = $this->get_game_wins($winner);

            //In strict mode, he must have EXACTLY enough wins - otherwise we flag valid if he has at LEAST enough wins
            if($strict) return $wins == $round->wins_needed;
            if($wins >= $round->wins_needed) return true;

            //Fail!
            return false;
        }

        //Couldn't figure out the round format, return false if strict, true otherwise
        return !$strict;
    }
    /**
     * Get the number of games that the provided team has won
     * 
     * @param int|BBTeam $team
     * @return int
     *  null indicates that the $team provided was invalid
     */
    public function get_game_wins($team) {
        if(!($team = &$this->team_in_match($team))) {
            return null;
        }

        $games = &$this->games();
        $wins = 0;
        foreach($games as &$game) {
            if($game->winner() == $team) ++$wins;
        }
        return $wins;
    }

    /**
     * Revert this match from the tournament
     * 
     * That means deleting the details, and removing the teams' progress in the tournament
	 * 
	 * Warning: you cannot unreport matches that have teams with matches AFTER this match was reported
	 * 
	 * aka you can only unreport if neither team has progress any further in the tournament or reported any other matches
	 * 
	 * That does not apply to group rounds however, only brackets - group rounds can be unreported at anytime before the brackets start
     * 
     * @return boolean
     */
    public function unreport() {
        //Only possible from the tournament's current stage
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
         * 
         * TODO
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
        return $this->set_error('unreport is not yet implemented');
    }

    /**
     * Add a new game to this match
     * 
     * Can also be used to get BBMatchGames from existing matches, but there's the $match->games array for that
     * 
     * Returns a new BBMatchGame object you can use to configure more detailed results
     *  when you call report() | save()
     * 
     * You can provide the winner while creating the object, by providing either the team id or BBTeam object
     * 
     * returns null if you've met / exceeded the best_of setting for this match's round
     *      Check $match->round->best_of if you're not sure
     * 
     * @param BBMatchGame|int   $winner
     * @param int               $match_winner_score     Optionally define the match winner's score for this game
     * @param int               $match_loser_score      Optionally define the match loser's score for this game
     * @return BBMatchGame
     *      Null if there are already enough games to satisfy the $round->best_of value
     */
    public function &game($winner = null, $match_winner_score = null, $match_loser_score = null) {
		//Make sure we have any existing games first
		if(!is_null($this->id)) $this->games();

        //Determine the next game_number, based on the number of games currently in the games array
        $game_number = sizeof($this->games);

        //Make sure game_number is within bounds of best_of (only if able to determine round format)
        if(!is_null($round = &$this->round())) {
            if($game_number > $round->best_of) {
                $this->set_error("Attempted to set details for game $game_number in a best of {$round->best_of} series");
                return $this->bb->ref(null);
            }
        }

        //Create a new one, initialize it
        $game = $this->bb->match_game();
        $game->init($this->tournament, $this);

		//Automatically set match's winner as game winner unless otherwise defined
		if(is_null($winner)) $winner = &$this->winner();

        //Set the winner
        if(!is_null($winner) || $winner === false) $game->set_winner($winner);

        //Scores
        $game->set_scores($match_winner_score, $match_loser_score);

        //Save it locally, and flag changes
        $this->games[$game_number] = $game;
		$this->flag_child_changed($this->games[$game_number]);

		//Success!
        return $this->games[$game_number];
    }

    /**
     * Remove a child class from this team - like a BBMatchGame
     * @param BBModel $child
     * @param type $children
     */
    public function remove_child(BBModel &$child, &$children = null, $preserve = false) {
        if($child instanceof BBMatchGame) {
            if(!is_null($game = $this->get_child($child, $this->games())) ) {
                return parent::remove_child($game, $this->games(), $preserve);
            }
        }
        return false;
    }

    /**
     * Determines if the given team (Can provide either team_id integer or BBTeam object)
     * 
     * If the team is in this match, the BBTeam object is returned
     *
     * If it's NOT part of this team, false is returned
     *
     * Returns a boolean indicating whether or not the provided team (BBModel or id integer)
     *      is actually part of this match
     * 
     * @param BBTeam|int $team
     * @return BBTeam
     */
    public function &team_in_match($team) {

        //If not given a BBTeam, use tournament->team to validate it as part of the tournament first, and to return the BBTeam instance
        if(!($team instanceof BBTeam)) {
            if( is_null($team = &$this->tournament->team($team)) ) {
                return $this->bb->ref(false);
            }
        }

        //Have to return a reference, so check against team() and opponent, and return if either match
        if( $team == ($matched_team = &$this->team()) )      return $matched_team;
        if( $team == ($matched_team = &$this->opponent()) )  return $matched_team;

        //Failure!
        return $this->bb->ref(false);
    }

	/**
	 * Returns a reference to this match's tournament
	 * 
	 * @return BBTournament
	 */
	public function &tournament() {
		//Load first, in case this object was directly loaded from BinaryBeast::match()
		if(!is_null($this->id)) $this->load();
		return $this->tournament;
	}
}

?>