<?php

/* @var $bb BinaryBeast */



/**
 * Example BinaryBeast API script
 * 
 * How to report individual game details within a match (Winner of each game, maps, etc)
 *  
 */

/**
 * First, let's include the round_format example
 * It will create a tournament, then setup BO3 for the first 2 rounds 
 * This will allow us to report game details for a match that has more than 1 game in it (as BO1 is default unless we declare otherwise)
 */
require_once('tournament_update_rounds.php');

//Include the report_match example.. which will start a bracket and report a win
//it also gives us access to $tourney_match_id.. the match_id of the match it reported (as you might have guessed)
require_once('team_report_match.php');



/**
 * Unfortunately the report_match method does not support game details, so we have to make a second call
 * 
 * The arguments work like this: $winners is an array indexed by game order, it should contain the tourney_team_id of the winner of that specific game
 * so for $winners = array($team_id1, $team_id2, $team_id1) means that the winner of the match won the 1st and 3rd games
 * 
 * $maps too should be an indexed array in order that the games were played
 * ie $maps = array('map 1', 'map 2', 'map3') if a BO3 and all 3games played, first map is map 1, etc
 * 
 * For $scores and $o_scores, they too should be in order of the games played, and they should reflect the score of the winner of that specific game
 * ie $scores = array(1, 1, 1) is the likely array for a game liek starcraft, where 1 would be the score for a win.. so for $o_scores you'd say something like:
 * ie $o_scores = array(0, 0, 0) where the loser of each game got a score of 0
 */

//Array pointing to the winner of each match, $tourney_team_id wins first game, $o_tourney_team_id wins second, and $tourney_team_id takes the 3rd
$winners = array($tourney_team_id, $o_tourney_team_id, $tourney_team_id);

//Even though this is StarCraft 2 and we don't need scores, I'm reporting just so you can see the result
$scores = array(15, 9001, 9);
//The loser built zealots vs carriers game 2.. so he's l337 * -1
$o_scores = array(3, -1337, 8);

//Let's define the map each game was played on
//if you spell it correclty and we happen to have your map, an image will be drawn for it (see the 3rd map)
$maps = array('First Map', 'Second Map', 'Metalopolis');

//We're all set, now we just make the service call
$result = $bb->match_report_games($tourney_match_id, $winners, $scores, $o_scores, $maps);


if($result->result == 200) echo 'Details saved successfully! (<a href="http://binarybeast.com/tourney/load_match/', $tourney_match_id, '" target="_blank">View</a>) (', $result->api_total_time, ')';

echo '<br /><br /><hr />';

?>