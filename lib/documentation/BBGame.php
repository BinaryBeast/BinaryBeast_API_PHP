<?php

/**
 * The data structure for values returned from the BBGame services
 * 
 * This class is never used, it soley exists for documentation
 * 
 * @property-read string $game
 *  The game title
 * 
 * @property-read string $game_code
 *  The unique identifier for this game<br />
 *  Used by {@link BBTournament::game_code} to associate a tournament with a specific game
 * 
 * @property-read string $game_style
 *  If available, the type of game this is<br />
 *  examples are RTS, FPS, MMORPG, etc
 * 
 * @property-read string $race_label
 *  If applicable, this game may have races / factions associated with it<br />
 *  the race_label simply indicates how the races referred to - whether they are races, or characters, factions, etc
 * 
 * @property-read string $game_icon
 *  The URL of the 20x20 icon hosted on BinaryBeast.com for this game
 * 
 * 
 * @package BinaryBeast
 * @subpackage SimpleModel_ObjectStructure
 */
abstract class BBGameObject {
    //Nothing here - used for documentation only
}

?>