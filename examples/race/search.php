<?php
/**
 * Example demonstrating how to search for a specific race - let's search for 'Zer' in StarCraft 2
 * 
 * We should get one back: Zerg
 * 
 * @package BinaryBeast
 * @subpackage Examples
 */

require('../../BinaryBeast.php');
$bb = new BinaryBeast();
$bb->disable_ssl_verification();


$races = $bb->race->game_search('SC2', 'Ze');
foreach($races as $race) {
    echo '<img src="' . $race->race_icon . '" /> ' . $race->race . ' (' . $race->race_id . ') <br />';
}

?>