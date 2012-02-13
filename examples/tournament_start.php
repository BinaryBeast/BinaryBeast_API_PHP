<?php

/* @var $bb BinaryBeast */


/**
 * Example BinaryBeast API script
 * 
 * Start brackets (with random seeds);
 */

//First of all, let's include some other examples so that we have the teams / format setup already
require_once('team_add.php');
require_once('tournament_update_rounds.php');


//Easiest example evar
//we could define the seeding as random (BinaryBeast::SEEDING_RANDOM), but it's the default value theres'e no need
$result = $bb->tournament_start($tourney_id);

if($result->result != 200)
{
    var_dump($result);
    die('Error starting the brackets!');
}