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
 * @date 2013-02-04
 * @author Brandon Simmons
 */
class BBTeam extends BBModel {

    //Service names for the parent class to use for common tasks
    const SERVICE_LOAD   = 'Tourney.TourneyLoad.Team';
    const SERVICE_CREATE = 'Tourney.TourneyTeam.Insert';
    const SERVICE_UPDATE = 'Tourney.TourneyTeam.Update';
    const SERVICE_DELETE = 'Tourney.TourneyTeam.Delete';

    /**
     * Keep a reference to the tournament that instantiated this class
     * @var BBTournament
     */
    private $tournament = null;

    //This Team's ID, using BinaryBeast's naming convention
    public $tourney_team_id;

    //So BBModal knows which property use as the unique id
    protected $id_property = 'tourney_team_id';

    //Helps BBModal know how to extract the team data from the API result
    protected $data_extraction_key = 'team_info';

    /**
     * Default values for a new participant, also a useful reference for developers
     * @var array
     */
    protected $default_values = array(
        //The name displayed on the brackets for this participant
        'display_name'                          => 'New API Player',
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
     * @param array $players
     * @return void
     */
    public function init(BBTournament &$tournament) {
        $this->tournament   = $tournament;
    }

    /**
     * Overloaded so we can let our tournament know that
     * we now have unsaved changes
     * 
     * @see BBModel::__set()
     * 
     * @return void
     */
    function __set($name, $value) {
        //Notify the tournament
        $this->tournament->flag_child_changed($this);

        //Let the default method handle the rest
        parent::__set($name, $value);
    }

    /**
     * Overloaded so that we can let our tournament know that this
     * class no longer has any unsaved changes
     * 
     * @see BBModel::reset()
     * 
     * @return void
     */
    public function reset() {
        //Notify the tournament
        $this->tournament->unflag_child_changed($this);

        //Let the default method handle the rest
        parent::reset();
    }

    /**
     * Overloaded - you know the drill, let the tour know we're up to date
     * @see BBModel::sync_changes();
     * @param bool $skip_unflag     Allows the tournament to update all of the teams at once, and clear the array manually - it's better than calling unset and array_search for every single round!
     * @return void
     */
    public function sync_changes($skip_unflag = false) {
        //Notify the tournament we're up-to-date
        if(!$skip_unflag) $this->tournament->unflag_child_changed($this);

        //Let BBModel handle the rest
        parent::sync_changes();
    }
}

?>