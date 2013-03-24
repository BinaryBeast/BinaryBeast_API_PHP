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


$maps = $bb->map->game_search('SC2', 'Antiga Shipyard');
foreach($maps as $map) {
    echo $map->map . ' (' . $map->map_id . ') <br />';
}

?>