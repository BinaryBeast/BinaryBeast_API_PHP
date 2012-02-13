<?php

/* @var $bb BinaryBeast */


/**
 * Example BinaryBeast API script
 * 
 * How to change a team's settings
 */

//First of all, let's include the create.php example so we have a new tourney_id with which to work
//it also instantiates $bb so we don't have to do that again in this script
require_once('tournament_create.php');


/**
 * 
 * First step is to create a team that we can edit 
 * 
 * We capture tourney_team_id from the returned response
 * 
 */
$result = $bb->team_insert($tourney_id, 'Tooth Man', array(
    'country_code'      => 'GBR',
    'network_display_name'      => 'characterCode.1234',
    'status'            => 0
));
$tourney_team_id = $result->tourney_team_id;




/**
 * Changing a team's information is easy enough
 *  
 * Let's move him to france and change his name
 */
$result = $bb->team_update($tourney_team_id, array(
    'display_name'      => 'Frog Man',
    'country_code'      => 'FRA' 
));


echo 'Team updated successfully (', $result->api_total_time, ')';