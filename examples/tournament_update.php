<?php

/* @var $bb BinaryBeast */



/**
 * Example BinaryBeast API script
 * 
 * How to edit a tournament
 *  
 */

//First of all, let's include the create.php example so we have a new tourney_id with which to work
//it also instantiates $bb so we don't have to do that again in this script
require_once('tournament_create.php');




/**
 *
 * Update the tournament!
 *
 * Only one argument, an associative array of options 
 * 
 * here are the available options:
 *   string $title
 *   string $description
 *   int    $public
 *   string $game_code            	SC2: StarCraft 2, BW: StarCraft BroodWar, QL: Quake Live ... you can search through our games using $bb->game_search('filter'))
 *   int    $type_id              	(0 = elimination brackets, 1 = group rounds to elimination brackets, also the BinaryBeast.php class has constants to help with this)
 *   int    $elimination          	(1 = single, 2 = double
 *   int    $max_teams
 *   int    $team_mode            	(id est 1 = 1v1, 2 = 2v2)
 *   int    $group_count
 *   int    $teams_from_group
 *   date   $date_start                  YYYY-MM-DD HH:SS
 *   string $location                    Simple description of where players should meet to play their matches, or where the event takes place
 */

//Let's say all we want to do is change from double elimination to single elimination
$result = $bb->tournament_update($tourney_id, array(
    'elimination' => BinaryBeast::ELIMINATION_SINGLE
));

if($result->result != 200)
{
    echo 'Error updating tournament!';
    var_dump($result);
    die();
}

echo 'Tournament updated successfully! (', $result->api_total_time, ')';

?>