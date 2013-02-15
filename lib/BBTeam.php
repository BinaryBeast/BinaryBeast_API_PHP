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

    //Cache setup (cache for 10 minutes)
    const CACHE_OBJECT_ID       = BBCache::TYPE_TEAM;
    const CACHE_TTL_LIST        = 10;
    const CACHE_TTL_LOAD        = 10;

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
     * If removed from the parent, this object will become an orphan without hope of recovery
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
        $this->tournament = $tournament;

        //Set parent so BBModel will auto flag changes
        $this->parent = &$this->tournament;
    }

    /**
     * Delete this team!!!!!!!
     * If a new unsaved team, this method removes itself from the tournament
     * 
     * 
     * WARNING - DANGEROUS SERVICE METHOD!
     * 
     * There is no undoing this method if it works
     * 
     * 
     * 
     * However that being said, it's necessary for unsaved new teams too, so we can remove
     *      them from the tournament save queue
     * 
     * @return boolean
     */
    public function delete() {

        /**
         * For a new unsaved team, all we have to do is remove it
         *  from the tournament, and then flag this object as an orphan
         */
        if(is_null($this->id)) {
            $this->tournament->remove_team($this);
        }

        /**
         * 
         * 
         * 
         * 
         * 
         * 
         * 
         * 
         * Build the API logic
         * 
         * 
         * 
         * 
         */
    }

    /**
     * This method is used by BBTournament to set our tourney_team_id 
     * for new teams
     * 
     * @param int $tourney_team_id
     */
    public function set_tourney_team_id($tourney_team_id) {
        if(is_null($this->tourney_team_id)) {
            $this->set_id($tourney_team_id);
        }
    }

    /**
     * Returns the BBTeam object of this team's current opponent
     * Null if he has no opponent waiting
     * 
     * IMPORTANT NOTE: If this tournament is configured to use group_rounds,
     *      there may be several opponents currently waiting to play agains this team,
     * 
     * If that's the case, then the this method will simply return the first one found
     * 
     * @return BBTeam (null if no opponent available)
     */
    public function &opponent() {
        //Already figured it out
        if(!is_null($this->opponent)) return $this->opponent;

        //Tournament is not active, can't possibly have an opponent - derp
        if(!BBHelper::tournament_is_active($this->tournament)) {
            return $this->bb->ref(
                $this->set_error('Tournament is not even active yet, impossible to determine a team\'s current opponent!')
            );
        }

        /**
         * For round robin, we use opponents() to load a list of opponents left to play first,
         *   then return the first one in the array
         */
        if(BBHelper::tournament_in_group_rounds($this->tournament)) {
            /**
             * 
             * 
             * 
             * 
             * 
             * TODO: This
             * 
             * 
             * 
             * 
             */
        }

        /**
         * If tournament currently has brackets, all we have to do is ask the API to send us
         * the tourney_team_id of our current opponent
         */
        if(BBHelper::tournament_in_brackets($this->tournament)) {
            bb_debug('here BBTeam::opponent() - after tour in brackets');
        }
        
    }
}

?>
