<?php

/* @var $bb BinaryBeast */


/**
 * Example BinaryBeast API script
 * 
 * Start brackets (with sports/traditional seeding)
 */

//First of all, let's include some other examples so that we have the teams / format setup already
require_once('team_add.php');
require_once('tournament_update_rounds.php');

/**
 * First step: rank the teams 
 * 
 * What we need to is create an array of tourney_team_ids in order of rank
 * we'll simply copy $team_ids (see adding_teams.php to see how I came up with that)
 * 
 * To make sure we don't get the same ranks every time this example is run, we'll also randomize it
 */
$ranks = array_keys($team_ids);
shuffle($ranks);

//For debugging, we'll loop through and display the team ranks
foreach($ranks as $rank => $tourney_team_id)
{
    echo 'Rank ' . ($rank + 1) . ': ' . $team_ids[$tourney_team_id] . '<br />';
}
echo '<br /><hr />';


/**
 * Now that we have our ranks, all we have to do is start the tournament with the correct seeding pattern 
 * we'll use the constant BinaryBeast::SEEDING_SPORTS, though we could just say 'sports'
 */
$result = $bb->tournament_start($tourney_id, BinaryBeast::SEEDING_SPORTS, $ranks);

if($result->result != 200)
{
    var_dump($result);
    die('Error starting the brackets!');
}

echo 'Brackets started successfully!';