<?php
/**
 * Randomly reports 3 open matches of the global BBTournament object
 *
 * @filesource
 *
 * @global BBTournament $tournament
 *
 * @package BinaryBeast
 * @subpackage Examples
 *
 * @version 1.0.0
 * @date    2013-04-25
 * @since   2013-04-25
 * @author  Brandon Simmons <contact@binarybeast.com>
 */

//Change all rounds to BO3
foreach ($tournament->rounds as $bracket) {
    foreach ($bracket as $round_format) {
        $round_format->best_of = 3;
    }
}

//Save the round format update
if (!$tournament->save()) {
    var_dump([
        'message' => 'Error saving round format!',
        'last_error' => $bb->last_error
    ]);
}

//Let's go ahead and report 3 open matches randomly
for ($x = 0; $x < 3; $x++) {
    $key = array_rand($tournament->open_matches());
    $match = $tournament->open_matches[$key];

    if (!$match->set_winner($match->team())) {
        var_dump(['Error defining the match winner', 'last_error' => $bb->last_error]);
        die();
    }
    $match->notes = 'Randomly reported match!';

    //2:1 result
    $match->game();
    $match->game($match->loser());
    $match->game();
}

//Report the matches
if(!$tournament->save_matches()) {
    var_dump(['Error reporting the match!', 'error_history' => $bb->last_error]);
    die();
}