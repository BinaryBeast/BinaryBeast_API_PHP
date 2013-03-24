<?php
/**
 * Example demonstrating how to load a list of games using a filter 
 * 
 * @package BinaryBeast
 * @subpackage Examples
 */

require('../../BinaryBeast.php');
$bb = new BinaryBeast();
$bb->disable_ssl_verification();


$games = $bb->game->search('star');
foreach($games as $game) {
    echo $game->game . ' (' . $game->game_code . ')<br />';
}

?>