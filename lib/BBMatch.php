<?php

/**
 * Model object representing a match between two teams
 * 
 * 
 * Let's go through a few examples... each example assumes the following:
 * 
 * - <var>$bb</var> is an instance of {@link BinaryBeast}
 * - <var>$tournament</var> is an instance of {@link BBTournament}
 * 
 * 
 * ### Reporting Matches ###
 * 
 * The most important functionality this object offers, is reporting results
 * 
 * 
 * ## Getting Matches to Report
 * 
 * Before you can report results, first you need to get find {@link BBMatch} objects
 * 
 * The {@link BBTournament} documentation covers this more in length under "Loading / Listing Unplayed Matches",
 * 
 * View it here: 
 * 
 * Or look at the following 2 quick examples
 * 
 * <br />
 * <b>Using {@link BBTournament::open_matches()}</b>
 * <code>
 *  foreach($tournament->open_matches() as $match) {
 *      echo $match->team->display_name . ' vs ' . $match->team2->display_name . ' in round ' . ($match->round->round + 1) . '<br />';
 *  }
 * </code>
 * 
 * <br />
 * <b>Using the magic {@link BBTournament::$open_matches} property</b>
 * <code>
 *  $match = $tournament->open_matches[0];
 * </code>
 * 
 * 
 * 
 * ## Defining the Winner .[#example-define_winner]
 * 
 * The first step to reporting a match result, is to define the winner
 * 
 * There are 4 ways to do so:
 * 
 * - Call {@link BBMatch::set_winner()}
 * - Call {@link BBMatch::set_loser()}
 * - Set the {@link BBMatch::$winner} property to the winning {@link BBTeam}
 * - Set the {@link BBMatch::$loser} property to the losing {@link BBTeam}
 * 
 * <br />
 * <b>Side note: </b> {@link set_winner()} and {@link set_loser()} are recommended over the magic properties, 
 * Simply because PHP's {@link __set()} methods have a slight execution overhead, and are slightly slower
 * 
 * 
 * <b>Example - Using {@link BBMatch::set_winner()}</b>
 * <code>
 *  $winner = $match->team2();
 *  if(!$match->set_winner($winner)) {
 *      var_dump($bb->last_error);
 *  }
 * </code>
 * 
 * <br />
 * <b>Example - Using {@link BBMatch::set_loser()}</b>
 * <code>
 *  $winner = $match->team2();
 *  $loser  = $match->team();
 *  if(!$match->set_loser($loser)) {
 *      var_dump($bb->last_error);
 *  }
 * </code>
 * 
 * <br />
 * <b>Example - Using the {@link BBMatch::$winner} property</b>
 * <code>
 *  $winner = $match->team2();
 *  $loser  = $match->team();
 *  $match->winner = $winner;
 *  if($match->winner() != $winner) {
 *      var_dump($bb->last_error);
 *  }
 * </code>
 * 
 * 
 * <br />
 * <b>Example - Using the {@link BBMatch::$loser} property</b>
 * <code>
 *  $winner = $match->team2();
 *  $loser  = $match->team();
 *  $match->loser = $winner;
 *  if($match->loser() != $winner) {
 *      var_dump($bb->last_error);
 *  }
 * </code>
 * 
 * 
 * ## Match Draws
 * 
 * For <b>group rounds only,</b>, you can specify a match as a Draw - indicating that neither participant won the match<br />
 * This will result in increment the {@link BBTeam::$draws} values for each team
 * 
 * You can define a match in any of the following ways:
 * 
 * There are 4 ways to define the match as a draw:
 * 
 * - Call {@link BBMatch::set_winner()} and pass <b>null</b> or <b>false</b> for the <var>$winner</var> argument
 * - Call {@link BBMatch::set_draw()}
 * - Set the {@link BBMatch::$winner} property to <b>null</b> or <b>false</b> 
 * - Set the {@link BBMatch::$loser} property to <b>null</b> or <b>false</b> 
 * 
 * 
 * <b>Example - Using {@link BBMatch::set_winner()}</b>
 * <code>
 *  if(!$match->set_winner(null)) {
 *      var_dump($bb->last_error);
 *  }
 *  if(!$match->is_draw()) {
 *      var_dump('Failure! - Expected is_draw to return true!');
 *  }
 * </code>
 * 
 * <br />
 * <b>Example - Using {@link BBMatch::set_loser()}</b>
 * <code>
 *  if(!$match->set_loser(null)) {
 *      var_dump($bb->last_error);
 *  }
 *  if(!$match->is_draw()) {
 *      var_dump('Failure! - Expected is_draw to return true!');
 *  }
 * </code>
 * 
 * <br />
 * <b>Example - Using the {@link BBMatch::$winner} property</b>
 * <code>
 *  $match->winner = null;
 *  if(!$match->is_draw()) {
 *      var_dump('Failure! - Expected is_draw to return true!');
 *  }
 * </code>
 * 
 * 
 * <br />
 * <b>Example - Using the {@link BBMatch::$loser} property</b>
 * <code>
 *  $match->loser = null;
 *  if(!$match->is_draw()) {
 *      var_dump('Failure! - Expected is_draw to return true!');
 *  }
 * </code>
 * 
 * 
 * ## Reporting
 * 
 * Before you can report a match, you must explicitly define the winner
 * 
 * You can do so by using either {@link set_winner()}, or {@link set_loser()}
 * 
 * <b>Example: </b>
 * <code>
 *  //First let's store the team we want to give the win to, into $winner
 *  $winner = $match->team();
 * 
 *  //Define the winner
 *  if(!$match->set_winner($winner)) {
 *      var_dump($bb->last_error);
 *  }
 * 
 *  //Submit the report
 *  if(!$match->report()) {
 *      var-dump($bb->last_error);
 *  }
 * </code>
 * 
 * 
 * 
 * 
 * Simply defining a winner / loser may not always be enough though, let's look at how we can be more specific
 * 
 * 
 * ### Game Details ###
 * 
 * For more granular control / details about the match, you can create {@link BBMatchGame} objects to define each game within the series
 * 
 * 
 * There is more details documentation on game objects in the {@link BBMatchGame} documentation...
 * 
 * But here are a few quick examples:
 * 
 * <b>Example - report a 2:1 series</b>
 * <var>$team1</var> takes games number 1 and 3, resulting in a <b>2:1</b> victory against </var>$team2</var><br />
 * <var>$
 * <code>
 *  $match->set_winner($team1);
 *  $game1 = $match->game($team1);
 *  $game2 = $match->game($team2);
 *  $game3 = $match->game($team1);
 * </code>
 * 
 * **Note** that since <var>$match->round_format->best_of</var> is set to 3, we would not be allowed to create any new games
 * 
 * Therefore extending the previous code block, the following line would return NULL
 * <code>
 *  //After setting $game1, $game2, and $game3...
 *  $game4 = $match->game();
 * </code>
 * 
 * Results in <var>$game4</var> = <b>null</b>
 * 
 * 
 * ### Strict reporting ###
 * 
 * {@link report()} is normally forgiving when it comes to how you setup the game details,<br />
 * however that's not the case if you enable <var>$strict_mode</var> when you call {@link report()}
 * 
 * 
 * <br /><br />
 * If enabled, the the report will fail unless <var>$match->winner()</var> wins exactly enough {@link BBMatchGame} games<br />
 * to satisfy <var>$match->round_format->best_of</var> and <var>$match->round_format->wins_needed</var>
 * 
 * 
 * <b>Example: Strict report with invalid game wins for the winner</b>
 * 
 * <var>$team</var> Gets 3 wins, but he needs two according to <var>$round_format->best_of</var>
 * <code>
 *  //Just insure that the round's best_of is 3
 *  $match->round_format->best_of = 3;
 *  $match->round_format->save();
 * 
 *  //Prove that it's best_of 3, which requires 2 wins
 *  echo $match->team->display_name . ' vs ' . $match->team2->display_name .
 *      ' is a best_of ' . $match->round_format->best_of . ' series, requiring ' .
 *      $match->round_format->wins_needed . ' wins';
 * 
 *  //Give $team 3 wins, which is invalid
 *  $winner = $match->team();
 *  $match->set_winner($winner);
 *  $game1 = $match->game($winner);
 *  $game2 = $match->game($winner);
 *  $game3 = $match->game($winner);
 * 
 *  //report should NOT work if specifying strict mode
 *  if(!$match->report(true)) {
 *      var_dump($bb->last_error);
 *  }
 * </code>
 * 
 * 
 * ### Managing/Loading the Participants ###
 * 
 * There are several methods for loading / managing the participants in the match
 * 
 * <br /><br />
 * {@link team()}, {@link team2()}, and {@link opponent()} can be used to examine the participants of the team<br />
 * <b>Note</b> That {@link team2()} and {@link opponent()} return the same value, {@link team2()} is simply an alias for {@link opponent()}
 * 
 * <br /><br />
 * {@link winner()} and {@link loser()} return the winner/losing {@link BBTeam}<br />
 * <b>Note!</b> Only works after calling {@link set_winner}
 * 
 * <br /><br />
 * {@link toggle_team()} is a convenient method that returns the provided <var>$team</var>'s opposing {@link BBTeam} within the match
 * 
 * <br />
 * <b>Example:</b>
 * <code>
 *  $team = $match->team2();
 *  $team2 = $match->toggle_team();
 * </code>
 * 
 * 
 * @property-read string $tourney_id
 * <b>Read Only</b><br />
 * The id of the tournament this match is in
 * 
 * @property int $tourney_team_id
 * The ID of the match's overall winner<br /><br />
 * This is a magic property that can be read and written to<br /><br />
 * Note however that <b>attempts to set this value</b> are handled by {@link set_winner()}<br /><br />
 * It is <b>NOT</b> recommend that you set the value this way, as there is easy way to capture errors<br />
 * 
 * @property int $o_tourney_team_id
 * The ID of the match's overall loser<br /><br />
 * This is a magic property that can be read and written to<br /><br />
 * Note however that <b>attempts to set this value</b> are handled by {@link set_loser()}<br /><br />
 * It is <b>NOT</b> recommend that you set the value this way, as there is easy way to capture errors<br />
 *
 * @property string|null $date
 * Date the match was reported if applicable
 * YYYY-MM-DD HH:SS ({@link http://en.wikipedia.org/wiki/ISO_8601})
 * 
 * @property-read int $bracket
 * <b>Read Only</b><br />
 * Numeric value of the bracket is in<br />
 * You can use {@link BBHelper::get_bracket_label()} to get the friendly translation<br />
 * 
 * @property string $notes
 * General notes / description on the match
 * 
 * @property int $score
 * Winner's score - using this is NOT recommended however<br />
 * <b>Please use {@link BBMatch::game()} to defined more detailed results</b>
 * 
 * @property int $o_score
 * Loser's score - using this is NOT recommended however<br />
 * <b>Please use {@link BBMatch::game()} to defined more detailed results</b>
 * 
 * @property boolean $draw
 * <b>Group Rounds Only</b><br />
 * Simple boolean to indicate whether or not this match resulted in a draw<br /><br />
 * This is a magic property that can be read and written to<br /><br />
 * Note however that <b>attempts to set this value</b> are handled by {@link set_draw()}<br /><br />
 * It is <b>NOT</b> recommend that you set the value this way, as there is easy way to capture errors<br /><br />
 * <b>Warning:</b> The only value you can set for this is <var>(bool)true</var>!<br />
 * You <b>can't set this property to false to define the match a non-draw</b>, you have to define a winner instead,<br />
 * Using {@link set_winner()}, {@link set_loser()}, or by setting {@link $winner}, {@link $loser}, {@link $tourney_team_id}, or {@link $o_tourney_team_id}
 *
 * @property BBMatchGame[] $games
 * <b>Alias for {@link BBMatch::games()}</b><br />
 * an array of games in this match
 * 
 * @property-read int $round
 * The round this match is played in
 *
 * @property BBRoundObject|BBRound $round_format
 * <b>Alias for {@link BBMatch::round_format()}</b><br />
 * The {@link BBRound} object defining the format for this match<br />
 * <b>NULL return:</b> unable to determine which round this match was in
 * 
 * @property-read BBTeam $team
 * <b>Read-Only</b><br />
 * <b>Alias for {@link BBMatch::team()}</b><br />
 * BB{@link BBTeam} object for the first player in this match
 * 
 * @property-read BBTeam $team2
 * <b>Read-Only</b><br />
 * <b>Alias for {@link BBMatch::opponent()}</b><br />
 * BB{@link BBTeam} object for the second player in this match
 * 
 * @property-read BBTeam $opponent
 * <b>Read-Only</b><br />
 * <b>Alias for {@link BBMatch::opponent()}</b><br />
 * {@link BBTeam} object for the second player in this match
 * 
 * @property BBTeam $winner
 * {@link BBTeam} object for the winner of the match<br />
 * <b>Returns NULL if set_winner hasn't been called</b><br />
 * <b>Returns FALSE if match was a draw</b><br /><br />
 * Note that <b>attempts to set this value</b> are handled by {@link set_winner()}<br /><br />
 * It is <b>NOT</b> recommend that you define the loser/winner this way, as there is easy way to capture errors<br />
 *
 * @property BBTeam $loser
 * {@link BBTeam} object for the loser of the match<br />
 * <b>Returns NULL if set_winner hasn't been called</b><br />
 * <b>Returns FALSE if match was a draw</b><br /><br />
 * Note that <b>attempts to set this value</b> are handled by {@link set_loser()}<br /><br />
 * It is <b>NOT</b> recommend that you define the loser/winner this way, as there is easy way to capture errors<br />
 * 
 * @property-read BBTournament $tournament
 * <b>Read-Only</b><br />
 * <b>Alias for {@link BBMatch::tournament()}</b><br />
 * The tournament this match is in
 *
 * 
 * @todo Add callbacks
 * @todo Create a legend, like the one in {@link BBTournament}
 * 
 * 
 * @package BinaryBeast
 * @subpackage Model
 * 
 * @version 3.0.9
 * @date    2013-06-05
 * @since   2013-02-02
 * @author  Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
class BBMatch extends BBModel {

    //<editor-fold defaultstate="collapsed" desc="API svc names">
    const SERVICE_LOAD          = 'Tourney.TourneyLoad.Match';
    const SERVICE_CREATE        = 'Tourney.TourneyTeam.ReportWin';
    const SERVICE_UPDATE        = 'Tourney.TourneyMatch.Update';
    const SERVICE_DELETE        = 'Tourney.TourneyMatch.Delete';
    /**
     * API svc name for unreporting the match
     * @var string
     */
    const SERVICE_UNREPORT      = 'Tourney.TourneyTeam.UnreportWin';
    /**
     * API svc name for saving game details in batch
     * @var string
     */
	const SERVICE_UPDATE_GAMES  = 'Tourney.TourneyMatchGame.ReportBatch';
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="Local cache settings">
    const CACHE_OBJECT_TYPE		= BBCache::TYPE_TOURNAMENT;
    const CACHE_TTL_LIST        = 10;
    const CACHE_TTL_LOAD        = 10;
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="Private properties and children arrays">
    /**
     * Keep a reference to the tournament that instantiated this class
     * @var BBTournament
     */
    private $tournament;

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
    private $round_format;

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
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="BBModel implementations">
    protected $id_property = 'tourney_match_id';
    protected $data_extraction_key = 'match_info';
    protected $read_only = array('team', 'opponent', 'draw', 'bracket');

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
    //</editor-fold>

    /**
     * This Match's ID, using BinaryBeast's naming convention
     * @var int
     */
    public $tourney_match_id;

    /**
     * Associate the provided {@link BBTournament} object as the parent tournament model object
     * 
     * @param BBTournament $tournament
     * @return void
     */
    public function init(BBTournament &$tournament) {
        $this->tournament = &$tournament;

        //Associate tournament as parent, so BBModel will flag child changes for us
        $this->parent = &$this->tournament;

		//Update any games we may have (necessary to allow directly loaded matches ($match = $bb->match(id) without calling init())
		foreach($this->games as &$game) {
            $game->init($this->tournament, $this);
        }
    }
    
    /**
     * Delete the match - <b>Only if {@link report()} hasn't been called yet!</b>
     *
     * If you wish to delete a match that has already been reported, please see {@link BBMatch::unreport()}
     * 
     * {@inheritdoc}
     */
    public function delete() {
        //Only continue if the match hasn't been reported
        if(!is_null($this->id)) {
            return $this->set_error('You cannot delete matches that have been reported, please see BBMatch::unreport()');
        }

        //Hand control over to BBModel
        return parent::delete();
    }

    /**
     * Overloads the BBModel save() so we can define additional arguments to send
     * 
     * {@inheritdoc}
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
     * Overload BBModel::__get so that when score and o_score are accessed, we can
     *  return the number of game_wins instead of the not-likely-to-be-used score and o_score values
     * 
     * {@inheritdoc}
     */
    public function &__get($name) {
        //If appropriate, return the number of individual game wins for score and o_score
        if($name == 'score' || $name == 'o_score') {
            //Only applicable a winner has been defined
            if($this->winner_set) {
                //Only possible if we have game details saved
                if(sizeof($this->games()) > 0) {
                    //A reference is expected
                    return $this->bb->ref(
                        //use get_game_wins for $team for 'score', and $opponent for 'o_score
                        $this->get_game_wins($name == 'score'
                            ? $this->team()
                            : $this->opponent()
                        )
                    );
                }
            }
        }

        //Fall back on the default {@link BBModel::__get()}
        return parent::__get($name);
    }

    /**
     * Overload {@link BBModel::__set()} so we can handle setting team ids, and draw manually
     *
     * Supports setting the following properties:
     * - tourney_team_id (calls @link set_winner())
     * - winner (calls @link set_winner())
     * - o_tourney_team_id (calls @link set_loser())
     * - loser (calls @link set_loser())
     * - draw (calls @link set_draw())
     *
     * @ignore
     * {@inheritdoc}
     */
    public function __set($name, $value) {
        //set_winner()
        if($name == 'tourney_team_id' || $name == 'winner') {
            return $this->set_winner($value);
        }
        //set_loser()
        if($name == 'o_tourney_team_id' || $name == 'loser') {
            return $this->set_loser($value);
        }
        //set_draw()
        if($name == 'draw') {
            //Can only used to ENABLE draw, can't be used to disable it
            if($value != false) {
                return $this->set_draw();
            }
            return;
        }
        parent::__set($name, $value);
    }

    /**
     * Overrides BBModel::reset() so we can define the $teams array for removing unsaved teams,
     *  and so we can unflag {@link $winner_set} if appropriate
     * 
     * {@inheritdoc}
     */
    public function reset() {
        //BBModel's default action first
        parent::reset();

        //For new matches, reset the winner if defined
        if(is_null($this->id)) $this->winner_set = false;

        //Now let BBModel remove any unsaved teams from $this->teams
        $this->remove_new_children($this->games());

        return true;
    }

    /**
     * Returns the BBRound containing the round format for this match
     * 
     * returns null if unable to determine the round format
     *
     * @return BBRound|null - null if unavailable
     */
    public function &round_format() {
        //Already set
        if(!is_null($this->round_format)) return $this->round_format;
        
        //If we have a value for round and bracket, grab the round from the tournament now
        if(isset($this->current_data['round']) && isset($this->current_data['bracket'])) {
            //The tournament's rounds array is keyed by "friendly" bracket, determine ours now
            $bracket    = BBHelper::get_bracket_label($this->current_data['bracket'], true);
            $round      = $this->current_data['round'];

            //Found it!
            $this->round_format = &$this->tournament->rounds->{$bracket}[$round];
        }

        //Failure!
        else {
            $this->set_error('Unable to figure out which round / bracket this match is in');
            return $this->bb->ref(null);
        }

        //Success!
        return $this->round_format;
    }

    /**
     * Returns an array of BBGames in this match
     * 
     * @return BBMatchGame[]
     */
    public function &games() {
		//Try loading first
		if(!is_null($this->id)) $this->load();
        return $this->games;
    }

    /**
     * Load the match details
     *
     * Overrides {@link BBModel::load} so we can specify certain
     *  arguments required by the API for the Tourney.TourneyMatch.Load service
     *
     * {@inheritdoc}
     */
    public function &load($id = null, $args = array(), $skip_cache = false) {
        //Let BBModal handle this, just pass it extra parameter
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
        $this->team = &$this->get_team('team', 'tourney_team_id');
        return $this->team;
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

        //Use the internal get_team method for this, using the opponent property
        $this->opponent = &$this->get_team('opponent', 'o_tourney_team_id');
        return $this->opponent;
    }
    /**
     * alias for {@link opponent()}
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
     * @return BBTeam|null   - null if input invalid
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
     * @ignore
     *
     * @param string $property      Which value to use for the team_id (team, opponent, tourney_team_id, o_tourney_team_id)
     * @param string $alt_property  An alternative property / key to use in case $property wasn't provided by the API
     * @return BBTournament|null
     */
    private function &get_team($property, $alt_property) {
		//Try loading first
		if(!is_null($this->id)) $this->load();

        //The team_id we'll be using with BBTournament::team - we'll complain later if it's still null
        $tourney_team_id = null;

        //Value for $property!
        if(isset($this->current_data[$property])) {
            //The $property is a valid object! extract the id integer
            if(is_object($this->current_data[$property])) {
                $tourney_team_id = $this->current_data[$property]->tourney_team_id;
            }
        }

        //$property failed, try the alternate
        if(is_null($tourney_team_id)) {
            //Found the alternate value
            if (isset($this->current_data[$alt_property])) {
                //We have a valid team_id!
                if(is_numeric($this->current_data[$alt_property])) {
                    $tourney_team_id = $this->current_data[$alt_property];
                }
            }
        }

        //Failure!
        if(is_null($tourney_team_id)) {
            $this->set_error('Match does not have a valid value for ' . $property . ' or ' . $alt_property . ', determining participants is impossible');
            return $this->bb->ref(null);
        }

        //Use BBTournament::team() to verify the value and to return a reference an existing child team object
        return $this->tournament->team($tourney_team_id);
    }

    /**
     * Fetch the {@link BBTeam} object of the winner of the match
     *
     * @return BBTeam|null|boolean
     * <ul>
     *  <li>{@link BBTeam} object: The winner of the match</li>
     *  <li><b>null</b>: No winner defined, see {@link set_winner()} or {@link set_loser()}</li>
     *  <li><b>false</b>: The match is a draw</li>
     * </ul>
     */
    public function &winner() {
        //Draw - return false
        if($this->is_draw()) return $this->bb->ref(false);

        //For new matches, only return the winner if it has been specifically set, using set_winner
        if(is_null($this->id)) {
            if($this->winner_set) return $this->team();

            //Unplayed match, without a winner set
            $this->set_error('Cannot retrieve the winner of unreported matches, try accessing $match->team and $match->team2(or $match->opponent)');
            return $this->bb->ref(null);
        }
        return $this->team();
    }
    /**
     * Fetch the {@link BBTeam} object of the loser of the match
     *
     * @return BBTeam|null|boolean
     * <ul>
     *  <li>{@link BBTeam} object: The loser of the match</li>
     *  <li><b>null</b>: No winner defined, see {@link set_winner()} or {@link set_loser()}</li>
     *  <li><b>false</b>: The match is a draw</li>
     * </ul>
     */
    public function &loser() {
        //Draw - return false
        if($this->is_draw()) return $this->bb->ref(false);

        //For new matches, only return the winner if it has been specifically set, using set_winner
        if(is_null($this->id)) {
            if($this->winner_set) return $this->opponent();

            //Unplayed match, without a winner set
            $this->set_error('Cannot retrieve the loser of unreported matches, try accessing $match->team and $match->team2(or $match->opponent)');
            return $this->bb->ref(null);
        }
        return $this->opponent();
    }
    /**
     * Returns a simple boolean to indicate whether or not
     *  this match was considered a draw
     * @return boolean
     */
    public function is_draw() {
        return $this->data['draw'] == true;
    }

    /**
     * Attempt to extract the 'games' array, then pass control
     * back to {@link BBModel::import_values()}
     *
     * {@inheritdoc}
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
     * Specify which team won the match
     *
     * <br />
     * There's a <b>full example</b> available in the {@link http://binarybeast.com/content/api/docs/php/class-BBMatch.html#example-define_winner documentation above}
     * 
     *
     * <br /><br />
     * <b>Warning:</b> you <b>CAN NOT</b> use this method to <b>change the outcome of an existing match!</b><br /><br />
     *
     * If <b>you need to change the winner</b>, please refer to {@link unreport()}
     *
     * <br /><br />
     *
     * @param BBTeam|integer|boolean|null $winner<br />
     * <b>The winning team</b><br />
     * <b>Acceptable values:</b><br />
     * <ul>
     *  <li>{@link BBTeam} object</li>
     *  <li>Team id <b>integer</b></li>
     *  <li><b>NULL:</b> To indicate a draw</li>
     *  <li><b>FALSE:</b> To indicate a draw</li>
     * </ul>
     *
	 * @param int $winner_score<br />
     * <b>Please use {@link BBMatch::game()} instead!</b><br /><br />
     * Quick way of defining the winner's score<br />
     * Only used if you don't define any {@link BBMatchGame} objects
     * 
	 * @param int $loser_score<br />
     * <b>Please use {@link BBMatch::game()} instead!</b><br /><br />
     * Quick way of defining the loser's score<br />
     * Only used if you don't define any {@link BBMatchGame} objects
     * 
     * @return boolean
     * <ul>
     *  <li>
     *      <b>False:</b> Either the <var>$winner</var> was invalid, or the match has already been reported<br />
     *      See {@link BinaryBeast::last_error} for details
     *  </li>
     *  <li>
     *      <b>True:</b> Winner successfully defined
     *  </li>
     * </ul>
     */
    public function set_winner($winner, $winner_score = null, $loser_score = null) {
        //Only appropriate for new matches
        if(!is_null($this->id)) return $this->set_error('Can\'t use set_winner to change the results of a match, you must unreport() first');

        //$winner = null|false means setting a draw, use set_draw()
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
        $this->set_new_data('tourney_team_id',      $this->team->id);
        $this->set_new_data('o_tourney_team_id',    $this->opponent->id);
        $this->set_new_data('draw', false);

        //Define score values
        if(!is_null($winner_score)) $this->set_new_data('score', $winner_score);
        if(!is_null($loser_score))  $this->set_new_data('o_score', $loser_score);

        return true;
    }
    /**
     * Define which team lost this match - uses set_win for the actual work, after "toggling" the team
     *
     * <br /><br />
     * Please review the documentation of {@link set_winner()} for more details,<br />
     * as the parameter list is identical
     * 
     * @param BBTeam|integer|boolean|null $loser<br />
     * <b>The losing team</b><br />
     * <b>Acceptable values:</b><br />
     * <ul>
     *  <li>{@link BBTeam} object</li>
     *  <li>Team id <b>integer</b></li>
     *  <li><b>NULL:</b> To indicate a draw</li>
     *  <li><b>FALSE:</b> To indicate a draw</li>
     * </ul>
     *
	 * @param int $winner_score<br />
     * See {@link set_winner()} for details
     *
	 * @param int $loser_score<br />
     * See {@link set_winner()} for details
     *
     * @return boolean
     */
    public function set_loser($loser, $winner_score = null, $loser_score = null) {
        if( !is_null($winner = $this->toggle_team($loser)) ) {
            return $this->set_winner($winner, $loser_score, $winner_score);
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

		//So report() doesn't complain about not having a winner
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
     * @return int		returns the id if successfully, false otherwise
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
         *  if it's not, it could be caused by a number of things - like being reported elsewhere, or the tournament advancing
         *  to the next stage etc
         */
        $tournament = &$this->tournament();
        if(!in_array($this, $tournament->open_matches())) {
            return $this->set_error('This match is no longer listed as an open match for this tournament, perhaps it was reported elsewhere, or the tournament has begun the next stage');
        }

        //We can't report the match if the round has unsaved changes, because the API may not process it correctly, as it may
        //have a different value for this round's best_of
        $format = &$this->round_format();
        if(!is_null($format)) {
            //Stop now - round has to be saved first
            if($format->changed) {
                return $this->set_error('The round for this match has unsaved changes, please save them first (either with $round->save(), $tournament->save_rounds(), or $tournament->save()');
            }

            //Strict - validate the winner has enough game wins first
            if($strict) {
                if(!$this->validate_winner_games($strict)) {
                    return $this->set_error('Winning team does not have enough game wins! This round requires at least ' . $format->best_of . ' game wins');
                }
            }
        }

		//Let BBModel handle this
		if( !($result = parent::save(false, array('tourney_id' => $this->tournament->id))) ) return false;

		//Report all of the game details
		if(!$this->save_games()) return $this->set_error('Error saving game details');

        //Wipe out all cached opponent / open match cache from the tournament
		$this->tournament->clear_match_cache();
        
        //Flag the teams for a reload (wins / lbwins etc may have changed), and reset opponent / match values etc, to force a fresh query next time they're accessed
        $this->team->flag_reload();
        $this->opponent->flag_reload();
        //
        $this->team->reset_opponents();
        $this->opponent->reset_opponents();

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
        if(!is_null($format = &$this->round_format())) {
            $winner = &$this->winner();
            $wins = $this->get_game_wins($winner);

            //In strict mode, he must have EXACTLY enough wins - otherwise we flag valid if he has at LEAST enough wins
            if($strict) return $wins == $format->wins_needed;
            if($wins >= $format->wins_needed) return true;

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
     * @return int|null
     *  null indicates that the $team provided was invalid
     */
    public function get_game_wins($team) {
        if(!($team = &$this->team_in_match($team))) {
            return null;
        }

        $wins = 0;
        foreach($this->games() as $game) {
            if($game->winner() == $team) {
                ++$wins;
            }
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
        //Derp - can't unreport if not reported yet
        if(is_null($this->id)) return $this->set_error('Can\'t unreport a match that hasn\'t been reported yet!');

        //Only possible from the tournament's current stage
        if(!BBHelper::bracket_matches_tournament_status($this->tournament(), $this->bracket)) {
            return $this->set_error('This match was played a previous stage of the touranment, and therefore can no longer be changed');
        }

        //If NOT from the group rounds, we must make sure that neither team has reported any wins after this match
        if($this->bracket !== 0) {
            $team1 = &$this->team();
            $team2 = &$this->opponent();
            if(is_null($team1->last_match())) return false;
            if(is_null($team2->last_match())) return false;

            if($this->team->last_match->id != $this->id) {
                return $this->set_error("You cannot unreport this match, because there are depedent matches that have been reported by team {$this->team->id} after this match, check \$match->team->last_match for details");
            }
            if($this->opponent->last_match->id != $this->id) {
                return $this->set_error("You cannot unreport this match, because there are depedent matches that have been reported by team {$this->opponent->id} after this match, check \$match->team2->last_match for details");
            }
        }

        //GOGOGO!
        $result = $this->call(self::SERVICE_UNREPORT, array(
            'tourney_team_id'   => $this->team->id,
            'o_tourney_team_id' => $this->opponent->id)
        );

        //Failure!
        if($result->result != BinaryBeast::RESULT_SUCCESS) return false;

        //Success! Simply set the id to null, we can leave the rest as-is
        $this->set_id(null);

        //Remove ids from each game
        foreach($this->games as &$game) $game->set_id(null);

        //Wipe out all cached opponent / open match cache from the tournament
		$this->tournament->clear_match_cache();
        
        //Flag the teams for a reload (wins / lbwins etc may have changed), and reset opponent / match values etc, to force a fresh query next time they're accessed
        $this->team->flag_reload();
        $this->opponent->flag_reload();
        //
        $this->team->reset_opponents();
        $this->opponent->reset_opponents();

        return true;
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
     * @param BBTeam|int        $winner
     * @param int               $match_winner_score     Optionally define the match winner's score for this game
     * @param int               $match_loser_score      Optionally define the match loser's score for this game
     * @return BBMatchGame|null
     *      Null if there are already enough games to satisfy the $round->best_of value
     */
    public function &game($winner = null, $match_winner_score = null, $match_loser_score = null) {
		//Make sure we have any existing games first
		if(!is_null($this->id)) $this->games();

        //Determine the next game_number, based on the number of games currently in the games array
        $game_number = sizeof($this->games);

        //Make sure game_number is within bounds of best_of (only if able to determine round format)
        if(!is_null($format = &$this->round_format())) {
            if($game_number >= $format->best_of) {
                $this->set_error("Attempted to set details for game " . ($game_number+1) . " in a best of {$format->best_of} series");
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
     *
     * @ignore
     * {@inheritdoc}
     */
    public function remove_child(BBModel &$child, $preserve = false) {
        if($child instanceof BBMatchGame) {
            if(!is_null($game = $this->get_child($child, $this->games())) ) {
                return $this->remove_child_from_list($game, $this->games(), $preserve);
            }
        }
        return false;
    }

    /**
     * Determines if the given team (Can provide either team_id integer or BBTeam object)
     *  is part of this match
     * 
     * If the team is in this match, the BBTeam object is returned
     *
     * If it's NOT part of this team, false is returned
     *
     * @param BBTeam|int $team
     * @return BBTeam|boolean
     *      <b>false</b> If not part of the match
     *      <b>{@link BBTeam} object</b> If it IS part of the match
     */
    public function &team_in_match($team) {

        //Use BBTournament::team to standardize the input and return any obvious errors (like not belonging to the tournament)
        if( is_null($team = &$this->tournament->team($team)) ) {
            return $this->bb->ref(false);
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