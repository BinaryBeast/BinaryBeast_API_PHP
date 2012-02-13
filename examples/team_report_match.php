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
 * First Step: find a match
 * 
 * To kill two birds etc, I'm goign to use a service that returns a list of open matches, and we'll just grab one of those
 */
$result = $bb->tournament_get_open_matches($tourney_id);

//The winner, we'll assign it while looping through the open matches
$tourney_team_id = 0;
$display_name = null;
//
$o_tourney_team_id = 0;
$o_display_name = null;

echo '<h3>Open Matches: (queried in ', $result->api_total_time, ')</h3>';
foreach($result->matches as $match)
{
    //I don't care which match report, just save the two players / names
    $tourney_team_id    = $match->team->tourney_team_id;
    $display_name       = $match->team->display_name;
    //
    $o_tourney_team_id  = $match->opponent->tourney_team_id;
    $o_display_name     = $match->opponent->display_name;
    
    echo "$display_name vs $o_display_name<br />";
}
echo '<br /><br />';

/**
 *Report the win, but NOTE: we're keeping track of $tourney_match_id, we'll use it in another example script (report_match_details.php) 
 */
$result = $bb->team_report_win($tourney_id, $tourney_team_id);

//Should be fairly straight forward...
if($result->result == 200)
{
    echo "$display_name defeated $o_display_name";

    //IMPORTANT: remember the match_id for the next example (report_match_details.php);
    $tourney_match_id = $result->tourney_match_id;
}
else
{
    var_dump($result);
    die("Error reporting $display_name's win");
}

echo " ({$result->api_total_time})<br /><br /><hr />";

?>