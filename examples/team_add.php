<?php

/* @var $bb BinaryBeast */


/**
 * Example BinaryBeast API script
 * 
 * How to add players/teams
 *  
 */


//First of all, let's include the create.php example so we have a new tourney_id with which to work
//it also instantiates $bb so we don't have to do that again in this script
require_once('tournament_create.php');


//let's just create an array we can loop through to start adding teams
$teams = array('Team 1', 'Team 2', 'Team 0b11', 'Team 03',  'Team 0x4');

//Init an array to store the returned team id's
$team_ids = array();

/**
 * I'm doing a small trick here...
 * 
 * Let's say we may want to add the ability to re-sync our local data with binarybeast's.. download each tournament and team from our account
 * 
 * Well, we'll lose local user associations with any teams right?
 * Let's hack $notes to store our local user id, so we if resync, we can json_decode the team notes and figure out which local user it belongs to
 * 
 * obviously this is done outside the loop so it's not really unique to a specific team, this is purely academic
 */
$notes = json_encode(array('user_id' => 'OVER 9,000!'));




//Simply loop through our teams array and add each to our tournament
foreach($teams as $display_name)
{
    /**
     * Available options:
     *      string country_code
     *      int    status               (0 = unconfirmed, 1 = confirmed, -1 = banned)
     *      string notes                Notes on the team - this can also be used possibly to store a team's remote userid for your own website
     *      array  players              If the TeamMode is > 1, you can provide a list of players to add to this team, by CSV (Player1,,Player2,,Player3)
     *      string network_name         If the game you've chosen for the tournament has a network configured (like sc2 = bnet 2, sc2eu = bnet europe), you can provide their in-game name here
     */
    $result = $bb->team_insert($tourney_id, $display_name, array(
        'country_code'  => 'JPN',
        'notes'         => $notes
    ));

    //OH NOES!
    if($result->result != 200)
    {
        var_dump($result);
        die('Error inseting team ' . $display_name);
    }

    //Let's store an array of inserted teams, so we can match up
    //team id's with the actual names later on
    //Success! ok so 
    $team_ids[ $result->tourney_team_id ] = $display_name;

    echo $display_name . ' added, his tourney_team_id is: ' . $result->tourney_team_id . ' (' . $result->api_total_time . ' execution) <br />';
}

//Let's move along, add a bit of seperation for the next test
echo '<br /><hr /><br />';