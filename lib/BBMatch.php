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
    const SERVICE_CREATE = 'Tourney.TourneyMatch.Update';
    const SERVICE_UPDATE = 'Tourney.TourneyMatch.Update';
    const SERVICE_DELETE = 'Tourney.TourneyMatch.Delete';

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
     * Flag whether or not this match is played or unplayed (thus requiring report() to save)
     */
    private $played = false;

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
    );

    /**
     * Overloads the BBModel constructor so that we can check to see
     *      if $id was set, so we can set the local $played flag, which 
     *      tells us if this is an unplayed match (requiring report()), or an existing match
     * 
     * @param BinaryBeast $bb
     * @param mixed $data
     */
    function __construct(BinaryBeast &$bb, $data = null) {
        parent::__construct($bb, $data);

        //If the parent constructor found the ID this is an existing match
        $this->played = !is_null($this->id);
    }

    /**
     * Since PHP doens't allow overloading the constructor with a different paramater list,
     * we'll simply use a psuedo-constructor and call it init()
     * 
     * @param BBTournament  $tournament
     * @param int           $bracket
     * @return void
     */
    public function init(BBTournament &$tournament, $bracket) {
        $this->tournament = $tournament;

        //For new matches, import the bracket value from our
        if(!$this->played && !is_null($bracket)) {
            //Change both the default, and the preview
            
        }

        //Associate tournament as parent, so BBModel will flag child changes for us
        $this->parent = &$this->tournament;
    }

    /**
     * Returns the BBRound containing the round format for this match
     * 
     * returns null if unable to determine the round format
     * 
     * @return BBRound
     */
    protected function &round() {
        //Already set
        if(!is_null($this->round)) return $this->round;

        //If we have a value for round and bracket, grab the round from the tournament now
        if(isset($this->current_data['round']) && isset($this->current_data['bracket'])) {
            //The tournament's rounds array is keyed by "friendly" bracket, determine ours now
            $bracket = BBHelper::get_bracket_label($this->current_data['bracket'], true);
            $round  = $this->current_data['round'];

            //Found it!
            $this->round = $this->tournament->rounds->{$bracket}[$round];
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
    protected function &games() {
        return $this->games;
    }

    /**
     * Overrides BBModal::load because we need to change the argument
     * of get_round when requesting the data from BinaryBeast
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
        return parent::load($id, array_merge(array('get_round' => true), $args) );
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

        //Use the internal get_team method for this, using o_tourney_team_id property
        return $this->team = $this->get_team('tourney_team_id');
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
        if(!is_null($this->team)) return $this->team;
        
        //Use the internal get_team method for this, using o_tourney_team_id property
        return $this->opponent = $this->get_team('o_tourney_team_id');
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
        //Try to load it using tourney_team_id value
        if(!is_null($id = $this->current_data[$property])) {
            //let BBTournament::team() handle setting any errors, we can return it directly
            return $this->tournament->team($id);
        }

        //OH NOES!
        $this->set_error('Match does not have any value set for ' . $property . ', unable to figure out which teams are in this match');
        return $this->bb->ref(null);
    }

    /**
     * Return the BBTeam object of this match's winning team
     * 
     * returns null if the match hasn't been reported
     * 
     * @return BBTeam
     */
    public function &winner() {
        //Derp
        if(!$this->played) {
            //If they've defined a winner using set_winner, we'll use that
            if(!is_null($this->new_winner)) return $this->new_winner;

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
        //Derp
        if(!$this->played) {
            //If they've defined a winner using set_winner, we'll use that
            if(!is_null($this->new_winner)) return $this->new_winner;

            //Unplayed match, without a winner set
            $this->set_error('Cannot retrieve the winner of unreported matches, try accessing $match->team and $match->team2(or $match->opponent)');
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
    protected function import_values($data) {
        //Found it!
        if(isset($data->games)) {
            //Now loop through each game as instantiate a new BBMatchGame for it
            $this->games = array();
            foreach($data->games as &$game) {
                //Instantiate a new game, tell it to remember us, then store it in games[]
                $game = new BBMatchGame($this->bb, $game);
                $game->init($this->tournament, $this);
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
     * @param BBTeam|int $winner      tourney_team_id of the winner
     * @return boolean
     */
    public function set_winner($winner) {
        //Only appropriate for new matches
        if($this->played) {
            return $this->set_error('Can\'t use set_winner to change the results of a match, you must unreport() first');
        }

        //Use team_in_match to both make sure we have a BBTeam, and to make sure it's part of this match
        if(($winer = $this->team_in_match($winner)) == false) return false;

        //Figure out the loser_id
        $loser_id   = null;

        /**
         * Loser is o_tourney_team_id
         */
        if($winner->id == $this->current_data['tourney_team_id']) {
            $loser_id = $this->current_data['o_tourney_team_id'];
        }
        /**
         * Loser is tourney_team_id
         */
        else if($winner->id == $this->current_data['o_tourney_team_id']) {
            $loser_id = $this->current_data['tourney_team_id'];
        }

        //Get the BBTeam for opponent
        if(is_null($this->opponent = $this->tournament->team($loser_id)) ) return false;

        //new_loser is set, now save new_winner and return 
        $this->team   = $winner;
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
     * @return boolean
     */
    public function report() {
        //Already reported
        if($this->played) {
            return $this->set_error('This match has already been reported, please use save() if you wish to change the details');
        }

        //No winner defined
        if(is_null($this->new_winner)) {
            return $this->set_error('You must execute the set_winner($team_id) method to define which team won the match.  You can refer to $match->team and $match->opponents for participant details');
        }

        /**
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
         */
        
    }

    /**
     * Revert this match from the tournament
     * 
     * That means deleting the details, and removing the teams' progress in the tournament
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
     * Only used for reporting new matches
     * 
     * Can also be used to get BBMatchGames from existing matches, but there's the $match->games array for that
     * 
     * Returns a new BBMatchGame object you can use to configure more detailed results
     *  when you call report() | save()
     * 
     * Can be used for either new matches, or creating / updating games within existing matches
     * 
     * You can define which game in the series this is
     *      (ie for a BO3, you can report up to 3 matches, starting at 0)
     *      but by default, it will simply give you the next game in the series
     * 
     * Your game_number / number must be in bounds of the best_of setting for this round
     *      Check $match->round if you're not sure
     * 
     * Returns null if you attempt to create more games than this round's best_of
     * 
     * @param int $game_number
     * @return BBGame
     */
    public function &game($game_number = null) {
        //Determine the next game_number, based on played vs unplayed
        if(is_null($game_number)) {
            $game_number = sizeof($this->games);
        }

        //Make sure game_number is within bounds of best_of (only if able to determine round format)
        if(!is_null($round = $this->round())) {
            if($game_number > $round->best_of) {
                $this->set_error("Attempted to set details for game $game_number in a best of {$round->best_of} series");
                return $this->bb->ref(null);
            }
        }

        //Return existing BBMatchGame
        if(isset($this->games[$game_number])) {
            return $this->games[$game_number];
        }

        //Create a new one, initialize it
        $game = new BBMatchGame($this->bb);
        $game->init($this->tournament, $this);

        //Save it locally, and return a reference
        $this->games[$game_number] = $game;
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
        if( is_null($team = $this->tournament->team($team)) ) return $this->bb->ref(false);

        //Just match its id against both tourney_team_id and o_tourney_team_id
        if($team->id == $this->current_data['tourney_team_id'] || $this->current_data['o_tourney_team_id']) {
            return $team;
        }
        else return $this->bb->ref(false);
    }
}

?>