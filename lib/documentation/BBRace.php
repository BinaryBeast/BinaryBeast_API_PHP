<?php


/**
 * The data structure for values returned from the BBGame services
 * 
 * This class is never used, it solely exists for documentation
 * 
 * @property-read int $race_id
 *  The unique race id integer<br />
 *  Used by {@link BBMatchGame::race} and {@link BBMatchGame::o_race}
 * 
 * @property-read string $race
 *  The name of the race
 * 
 * @property-read string $race_icon
 *  The URL of the icon hosted by BinaryBeast if available
 *
 * @property-read string[] $games
 *  Array of game codes of the games allowed to use this map
 * 
 * 
 * @package BinaryBeast
 * @subpackage SimpleModel_ObjectStructure
 *
 * @version 1.0.2
 * @date    2013-05-03
 * @author  Brandon Simmons <contact@binarybeast.com
 */
abstract class BBRaceObject {
    //Nothing here - used for documentation only
}

?>