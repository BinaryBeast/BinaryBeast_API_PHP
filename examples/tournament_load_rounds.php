<?php

/* @var $bb BinaryBeast */



/**
 * Example BinaryBeast API script
 * 
 * How to retrieve round format for a tournament
 *  
 */

//Include the start_brackets_seeded exmaple so we have a bracket to work with (and teams obviously)
require_once('tournament_start.php');

//This one is pretty simple, let's load the format for the entire tournament, so only need to provide the tourney_id
$result = $bb->tournament_load_round_format($tourney_id);

//Super lazy, maybe I'll format it into a table later
var_dump($result);

?>