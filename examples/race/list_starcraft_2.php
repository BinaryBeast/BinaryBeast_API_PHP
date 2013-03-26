<?php
/**
 * Example demonstrating how to load a list of maps available for StarCraft 2
 * 
 * @package BinaryBeast
 * @subpackage Examples
 */

require('../../BinaryBeast.php');
$bb = new BinaryBeast();
$bb->disable_ssl_verification();


$races = $bb->race->game_list('SC2');
foreach($races as $race) {
    echo '<img src="' . $race->race_icon . '" /> ' . $race->race . ' (' . $race->race_id . ') <br />';
}

?>