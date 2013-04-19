<?php

/**
 * The data structure for values returned from the BBMap services
 * 
 * This class is never used, it solely exists for documentation
 * 
 * @property-read int $map_id
 *  The unique map id integer<br />
 *  Used by {@link BBRound::map} and {@link BBMatchGame::map}
 * 
 * @property-read string $map
 *  The name of the map
 * 
 * @property-read string $game_code
 *  The game_code of the game this map belongs to<br />
 * 
 * @property-read boolean $approved
 *  Maps are only public once approved<br />
 *  There fore this value will always be true here
 * 
 * @property-read int $user_id
 *  The BinaryBeast user_id of the user that submitted the map
 * 
 * @property-read int $positions
 *  The number of starting positions on the map - if applicable
 * 
 * @property-read string $description
 *  Description of the map
 * 
 * @package BinaryBeast
 * @subpackage SimpleModel_ObjectStructure
 *
 * @version 1.0.1
 * @date    2013-04-13
 * @author  Brandon Simmons <contact@binarybeast.com
 */
abstract class BBMapObject {
    //Nothing here - used for documentation only
}

?>