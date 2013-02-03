<?php


global $doc;
//$doc->ThrowError('beginning of BBRound.php');

/**
 * This class represents a single match result withint a tournament
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-02-02
 * @author Brandon Simmons
 */
class BBRound extends BBModel {

    //Service names for the parent class to use for common tasks
    //const SERVICE_LOAD   = 'Tourney.TourneyLoad.Info'; //This class is instantiated manually, we don't call the load service from here
    const SERVICE_CREATE = 'Tourney.TourneyRound.Update';
    const SERVICE_UPDATE = 'Tourney.TourneyRound.Update';
    const SERVICE_DELETE = 'Tourney.TourneyRound.Delete';

    /**
     * Keep a reference to the tournament that instantiated this class
     * @var BBTournament
     */
    private $tournament = null;

    /**
     * Set by the parent, each round is associated with a single bracket
     * @var int
     */
    private $bracket;
    /**
     * Set by the parent, each round is associated with a single round within as bracket
     * @var int
     */
    private $round;

    /**
     * Default values for a new tournament
     * @see BinaryBeast::update()
     * @var array
     */
    protected $default_values = array(
        //int:      Best of value for this round (ie best of 1 (1), or best of 3 (3)
        'best_of'           => 1,

        //int:      id of the map you'd like to use for this round (use $bb->list_maps([filter]) for map ids and names)
        //Note: You cannot use maps id's from other games, the map you provide MUST be associated with this tournament's game
        'map_id'            => null,

        //string: Optionally you can define the actual map name instead of the map_id - if you happen to spell it the same, BB will associate it with the correct map_id
        'map'               => null,

        //string: For the moment this is a simple unformated string, it does not even validate the format.  This should reflect when this particular round should be played
        'date'              => null,
    );

    /**
     * Since PHP doens't allow overloading the constructor with a different paramater list,
     * we'll simply use a psuedo-constructor and call it init()
     * 
     * @param BBTournament $tournament
     * @param int $bracket
     * @param int $round
     * @return void
     */
    public function init(BBTournament &$tournament, $bracket, $round) {
        $this->tournament   = $tournament;
        $this->bracket      = $bracket;
        $this->round        = $round;
    }

    /**
     * Overloaded so we can let BBTournament know that something has changed, so that
     * when the tournament is saved, Rounds wil be updated as well
     */
    function __set($name, $value) {
        $this->tournament->flag_rounds_changed();
        parent::__set($name, $value);
    }
}

?>