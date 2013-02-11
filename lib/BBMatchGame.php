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
        //General description / notes on the match
        'notes'                 => null,
        //This will be updated soon to be more flexible, but for now - all this value serves as is as a URL to the replay of this match
        'replay'                => null,
    );

    /**
     * winner's id is read-only, developers shoudl use set_winner to change it
     */
    protected $read_only = array('tourney_team_id');

    /**
     * Since PHP doens't allow overloading the constructor with a different paramater list,
     * we'll simply use a psuedo-constructor and call it init()
     * 
     * @param BBTournament  $tournament
     * @param BBMatch       $match
     * @return void
     */
    public function init(BBTournament &$tournament, BBMatch $match) {
        $this->tournament   = $tournament;
        $this->match        = $match;

        //Let BBModel know who our parent is, so that changes are automatically flagged in BBMatch
        $this->parent = &$this->match;
    }

    /**
     * Returns the BBTeam object of the winner of this game
     * 
     * @return BBTeam
     */
    public function &winner() {
        //Already cached
        if(!is_null($this->winner)) return $this->winner;

        //No winner defined
        if(is_null($id = $this->data['tourney_team_id'])) {
            $this->set_error('No tourney_team_id defined in this game, unable to retrieve winner\'s BBTeam');
            return $this->bb->ref(null);
        }

        //Try to load from the tournament, return directly since null is returned if the id is invalid anyway
        $this->winner = $this->tournament->team($id);
        return $this->winner;
    }

    /**
     * Returns the BBTeam object of this game's loser
     * 
     * @return BBTeam
     */
    public function &loser() {
        //Already cached
        if(!is_null($this->loser)) return $this->loser;

        //We need to know the winner first, so if that's unavailable then stop now
        if( is_null($winner = $this->winner()) ) {
            return $winner;
        }

        /**
         * Game's only record winners for some reason (why, I don't remember)
         * So we must deduce the loser by comparing this game's winner to the match's winner
         * 
         * Go ahead and use the match's loser, we'll be calling loser() anyway
         *  and then change it if we determine otherwise
         */
        $this->loser = $this->match->winner();
        if($winner == $this->match->winner()) {
            $this->loser = $this->match->loser();
        }

        //Return a reference to the cache
        return $this->loser;
    }

    /**
     * Defines which team won this game
     * 
     * Must be a team from within the match
     * 
     * You can provide either the team's integer id, or the BBTeam object
     * 
     * @param BBTeam|int        The winning team
     * returns false if provided team is invalid
     */
    public function set_winner($winner) {

        //Use the matche's team_in_match to give us the BBTeam, and to verify that it's actually in the match
        if(($winner = $this->match->team_in_match($winner)) == false) {
            return $this->set_error('Invalid team selected for this game\'s winner');
        }

        /**
         * Let's store this as the new winner, and then set the loser to null, so next time it's accessed 
         *      the correct team will be returned, and not a cached value
         */
        $this->winner = $winner;
        $this->loser = null;

        //Flag changes, and let parents know
        $this->changed = true;
        $this->match->flag_child_changed($this);

        //Success!
        return true;
    }
}

?>