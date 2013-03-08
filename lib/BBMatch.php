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
     * @var array<BBMatchGame>
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
     * Default values for a new tournament
     * @see BinaryBeast::update()
     * @var array
     */
    protected $default_values = array(
        //tourney_team_id of the match's overall winner
        'tourney_team_id'           => null,

        //tourney_team_id of the matche's overall loser
        'o_tourney_team_id'         => null,

        //Integer - Which bracket was this in?
        'bracket'                   => BinaryBeast::BRACKET_WINNERS,

        //General notes / description on the match
        'notes'                     => null,
		
		//Winner's score - using this is NOT recommended however, please using $match->game to defined more detailed results
		'score'						=> 1,

		//Loser's score - using this is NOT recommended however, please using $match->game to defined more detailed results
		'o_score'					=> 0
    );

    //Values sent from the API that I don't want developers to accidentally change
    protected $read_only = array('team', 'opponent', 'tourney_team_id', 'o_tourney_team_id', 'draw');

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
        if(!parent::save($return_result, $child_args)) return false;

		//Update all game details
		return $this->save_games();

		//Wipe all tournament cache
		$this->tournament->clear_id_cache();

		//Success!
		return true;
    }
    
    /**
     * Overloaded so that we can delete also reset the array of games
     * @return void
     */
    public function reset() {
        parent::reset();
        if(is_null($this->id)) {
            $this->games = array();
            $this->winner_set = false;
        }
    }

    /**
     * Returns the BBRound containing the round format for this match
     * 
     * returns null if unable to determine the round format
     * 
     * @return BBRound
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
     * 
     * @return boolean - true if result is 200, false otherwise
     */
    public function &load($id = null, $args = array()) {
        //Let BBModal handle this, just pass it extra paramater
		$result = parent::load($id, array_merge(array('get_round' => true), $args) );
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
    public function &team2() {
        //Already set
        if(!is_null($this->opponent)) return $this->opponent;
        
        //Use the internal get_team method for this, using the team property
        return $this->opponent = &$this->get_team('opponent');
    }
    /**
     * alias for BBMatch::team2()
     * @return BBTeam
     */
    public function &opponent() {
        return $this->team2();
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
     */
    public function &winner() {
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
        return parent::import_values($data);
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
     * @param BBTeam|int $winner      tourney_team_id of the winner
	 * @param int $winner_score
	 * @param int $loser_score
     * @return boolean
     */
    public function set_winner($winner, $winner_score = null, $loser_score = null) {
        //Only appropriate for new matches
        if(!is_null($this->id)) return $this->set_error('Can\'t use set_winner to change the results of a match, you must unreport() first');

		//$winner = null means setting a draw, use set_draw()
		if(is_null($winner)) return $this->set_draw();

        //Use team_in_match to both make sure we have a BBTeam, and to make sure it's part of this match
        if(($winner = &$this->team_in_match($winner)) == false) {
			return $this->set_error("Error setting winner to the provided team, team does not seem to be part of this match!");
		}

        //If we need to swap the team / opponent, do so now
        if($winner == $this->opponent()) {
            $this->opponent = &$this->team();
        }

        //new_loser is set, now save new_winner and return 
        $this->team         = &$winner;
        $this->winner_set   = true;

		//Set the team_ids now, the values that the API will be looking for
		$this->set_new_data('tourney_team_id',		$this->winner->id);
		$this->set_new_data('o_tourney_team_id',	$this->loser->id);
		if(!is_null($winner_score)) $this->set_new_data('score', $winner_score);
		if(!is_null($loser_score))	$this->set_new_data('o_score', $loser_score);

        return true;
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
		if($this->tournament->status != 'Active-Groups') return $this->set_error('Only matches in group-rounds can be draws');

		//First make sure we have a valid team and opponent
		if(is_null($this->team()) || is_null($this->opponent())) return false;

		//The API expects team ids
		$this->set_new_data('tourney_team_id',		$this->winner->id);
		$this->set_new_data('o_tourney_team_id',	$this->loser->id);
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
     * 
     * @return int		returns the id if successfull, false otherwise
     */
    public function report() {
        //Already reported
        if(!is_null($this->id)) {
            return $this->set_error('This match has already been reported, please use save() if you wish to change the details');
        }

        //No winner defined
        if(is_null($this->winner_set)) {
            return $this->set_error('Please define a winner before reporting ($team->set_winner($winning_team)) You can refer to $match->team and $match->opponent for participant details');
        }

		//Let BBModel handle this
		$result = parent::save(false, array('tourney_id' => $this->tournament->id));

		//Report all of the game details
		if($result) {
			if(!$this->save_games()) return false;
		}

		//Wipe all tournament cache, and tournament opponent cache
		$this->tournament->clear_id_cache();
		$this->team->clear_opponent_cache();
		$this->opponent->clear_opponent_cache();

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
			$winners[]		= $game->winner->id;
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

			if(!is_null($dump->map_id))		$game->map_id		= $dump->map_id;
			if(!is_null($dump->map))		$game->map			= $dump->map;
			if(!is_null($dump->race))		$game->race			= $dump->race;
			if(!is_null($dump->o_race))		$game->o_race		= $dump->o_race;
			if(!is_null($dump->race_id))	$game->race_id		= $dump->race_id;
			if(!is_null($dump->o_race_id))	$game->o_race_id	= $dump->o_race_id;

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
     * @param BBTeam|int $winner
     * @return BBGame
     */
    public function &game($winner = null) {
		//Make sure we have any existing games first
		if(!is_null($this->id)) $this->games();

        //Determine the next game_number, based on the number of games currently in the games array
        $game_number = sizeof($this->games);

        //Make sure game_number is within bounds of best_of (only if able to determine round format)
        if(!is_null($round = &$this->round())) {
            if($game_number > $round->best_of) {
                //If $winner is a game number and it exists, just return that
                if(is_numeric($winner)) {
                    if(isset($this->games[$winner])) return $this->games[$winner];
                }

                $this->set_error("Attempted to set details for game $game_number in a best of {$round->best_of} series");
                return $this->bb->ref(null);
            }
        }

        //Create a new one, initialize it
        $game = $this->bb->match_game();
        $game->init($this->tournament, $this);

		//Automatically set matche's winner as game winner unless otherwise defined
		if(is_null($winner)) $winner = &$this->winner();

        //Set the winner
        if(!is_null($winner)) $game->set_winner($winner);

        //Save it locally, and flag changes
        $this->games[$game_number] = $game;
		$this->flag_child_changed($this->games[$game_number]);

		//Success!
        return $this->games[$game_number];
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
        if( is_null($team = &$this->tournament->team($team)) ) return $this->bb->ref(false);

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