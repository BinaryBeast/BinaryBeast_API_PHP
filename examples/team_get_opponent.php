<?php

/* @var $bb BinaryBeast */



/**
 * Example BinaryBeast API script
 * 
 * How to determine a team's current opponent
 *  
 */

//Include the start_brackets_seeded exmaple so we have a bracket to work with (and teams obviously)
require_once('tournament_start.php');


/**
 * OK so we have $team_ids remember? (from adding_teams.php, which is included in start_brackets.php)
 * 
 * Let's just grab a random player, random in hopes that the team wil either have an opponent, or a freewin, so you can see them both
 */
$keys = array_keys($team_ids);
shuffle($keys);
$tourney_team_id = $keys[0];

//Save his name too for output
$display_name = $team_ids[$tourney_team_id];



/**
 * Evaluate the value of o_tourney_team_id to get the result
 *  o_tourney_team_id = -1      This indicates the team has been eliminated
 *  o_tourney_team_id = 0       This indicates the team currently has no opponent, he's waiting on another match to finish
 *  o_tourney_team_id > 0       We have a match!
 * 
 */
$result = $bb->team_get_opponent($tourney_team_id);


/**
 * We have an opponent! 
 */
if($result->o_tourney_team_id > 0)
{
    //Since we stored the team id's inserted in $team_id's, we can now use that to figure out team 4's current opponent
    $opponent_id = $result->o_tourney_team_id;
    $opponent = $team_ids[ $opponent_id ];

    echo $display_name . "'s current opponent is $opponent";
}

/**
 * No opponent yet, he's waiting 
 */
else if($result->o_tourney_team_id === 0)
{
    echo $display_name . " is currently waiting on a match to finish, he currently has no opponent";
}

/**
 * Eliminated! 
 * This won't happen obviously, but I'm drawing it here to show you an example
 */
else if($result->o_tourney_team_id == -1)
{
    //And from here, we can find out who eliminated him, by taking a look at the returned value 'victor'
    $winner = $team_ids[ $result->victor->tourney_team_id ];

    echo $display_name . " was eliminated by $winner!";
}


//On to the next test, let's have some space
echo ' (', $result->api_total_time, ' execution)<br /><br /><hr />';



?>