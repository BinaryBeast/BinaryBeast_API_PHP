<?php

/* @var $bb BinaryBeast */



/**
 * Example BinaryBeast API script
 * 
 * How to confirm team positions before starting the tournament
 * 
 * only teams that have status => 1 (aka confirmed) are included in the brackets
 * 
 * Note: If you call team_insert without defining a status, the team will automatically be confirmed
 */


//First of all, let's include the create.php example so we have a new tourney_id with which to work
//it also instantiates $bb so we don't have to do that again in this script
require('tournament_create.php');

/**
 * 
 * Check-in system
 * 
 */

//First step, is to add a team, but DONT auto-confirm him
$result = $bb->team_insert($tourney_id, 'check-in user', array(
    'status'        => 0,    //Status of 0 means that he is NOT confirmed - now that I think about it.. maybe I should added a few constants to BinaryBeast.. might make it a bit easier.. like BinaryBeast::STATUS_CONFIRMED = 1;
    'country_code' => 'NOR'  //and he may have a specific country flag as well, let's say he's from Norway
));

//Some point in the future, you open confirmations, and he confirms... here is what what we need to do to update the team on BinaryBeast's end
$result = $bb->team_confirm($result->tourney_team_id);

echo 'check-in user successfully confirmed (' . $result->api_total_time . ' execution)';

//We could also reverse this by calling this:
//$bb->team_unconfirm($result->tourney_team_id);