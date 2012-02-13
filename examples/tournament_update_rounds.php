<?php

/* @var $bb BinaryBeast */



/**
 * Example BinaryBeast API script
 * 
 * How to update Round Format
 * 
 * This includes Best Of, Maps, and Dates
 *  
 */


//First of all, let's include the create.php example so we have a new tourney_id with which to work
//it also instantiates $bb so we don't have to do that again in this script
require_once('tournament_create.php');


/**
 * There are 2 ways to do this
 * 
 * First: update a single round within a bracket
 * Second: batch update all rounds within a single bracket 
 */



/**
 * First method: update a single round
 * 
 * round 1 => BO3 Starting on Shakuras Plateau
 * round 2 => BO3 Starting on Metalopolis
 * round 3 => BO5 Starting on Xel'Naga Caverns
 */
$result = $bb->tournament_round_update($tourney_id, BinaryBeast::BRACKET_WINNERS, 0, 3, 'Shakuras Plateau');
echo 'Winners round 1: ' . $result->api_total_time . '<br />';
$result = $bb->tournament_round_update($tourney_id, BinaryBeast::BRACKET_WINNERS, 1, 3, 'Metalopolis');
echo 'Winners round 2: ' . $result->api_total_time . '<br />';
$result = $bb->tournament_round_update($tourney_id, BinaryBeast::BRACKET_WINNERS, 2, 5, "Xel'Naga Caverns");
echo 'Winners round 3: ' . $result->api_total_time . '<br />';


/**
 * Second example: batch update a single bracket
 * 
 * 
 * First, we compile the data into integer indexed arrays, in order of round
 * 
 * We can do a single call and update an entire bracket by passing arrays for each value
 */

//round 1: best of 1
//round 2: best of 3, etc
$best_ofs = array(1, 3, 3, 5);

//round 1: starts on Tal'Darim Altar
//round 2: starts on The Shattered Temple, etc
$maps     = array("Tal'Darim Altar", 'The Shattered Temple', "Xel'Naga Caverns", 'Metalopolis');

//Dates are just strings, there is no strict format
$dates    = array('Today', 'Tomorrow', 'canceled!');

//We use the BinaryBeast::BRACKET_LOSERS constant to identify which bracket to update
$result = $bb->tournament_round_update_batch($tourney_id, BinaryBeast::BRACKET_LOSERS, $best_ofs, $maps, $dates);
echo 'Losers (all rounds): ' . $result->api_total_time . '<br />';


//let's go ahead and update the finals format, we can just use a single round update
//Notice that it automatically corrects your invalid BO6 to BO7, as a BO series MUST NOT be evently divisible by 2
$bb->tournament_round_update($tourney_id, BinaryBeast::BRACKET_FINALS, 0, 6, 'Metalopolis');


//Let's move along, add a bit of seperation for the next test
echo '<br />Round format saved successfully!<br /><hr /><br />';

?>