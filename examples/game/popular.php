<?php
/**
 * Example demonstrating how to load a list of popular games using the BBGame simple model
 * 
 * @package BinaryBeast
 * @subpackage Examples
 */

require('../../BinaryBeast.php');
$bb = new BinaryBeast();
$bb->disable_ssl_verification();


$games = $bb->game->list_top(15);
foreach($games as $game) {
    echo $game->game . ' (' . $game->game_code . ')<br />';
}

?>