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


$maps = $bb->map->game_list('SC2');
foreach($maps as $map) {
    echo $map->map . ' (' . $map->map_id . ') <br />';
}

?>