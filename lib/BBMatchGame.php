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

    //Unique ID for this object
    public $tourney_match_game_id;

    //So BBModal knows which property use as the unique id
    protected $id_property = 'tourney_match_game_id';

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
        //Map ID - you can find this value in $bb->list_maps([$filter = null]) (map_id)
        'map_id'                => null,
        //Optionally you can provide the map name instead of map_id
        'map'                   => null,
        //General description / notes on the match
        'notes'                 => null,
        //This will be updated soon to be more flexible, but for now - all this value serves as is as a URL to the replay of this match
        'replay'                => null,
    );
}

?>