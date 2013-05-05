<?php

/**
 * The data structure for values returned from the BBGame services
 * 
 * This class is never used, it solely exists for documentation
 * 
 * @property-read string $game
 * The game title
 * 
 * @property-read string $game_code
 * The unique identifier for this game<br />
 * Used by {@link BBTournament::game_code} to associate a tournament with a specific game
 *
 * @property-read string|null $parent_id
 * Game game_code of the parent game
 * Used by expansiosn to define which game they are expanding
 *
 * @property-read string|null $default_child
 * For games with multiple expansions, this defines the newest / latest
 *  child / expansion that should be used
 *
 * For example for StarCraft 2, the default expansion would be Heart of the Swarm
 *
 * @property-read boolean $force_child
 * If true, it means this game should not be used directly,
 * You should use child games
 *
 * An example would be StarCraft 2, you should use WoL or HotS
 *
 * @property boolean $inherit_maps
 * Specifies if this game inherits maps from its parent game
 *
 * @property boolean $inherit_races
 * Specifies if this game inherits races from its parent game
 *
 * @property boolean $inherit_networks
 * Specifies if this game inherits networks from its parent game
 *
 * @property boolean $inherit_platforms
 * Specifies if this game inherits platforms from its parent game
 *
 * @property boolean $inherit_regions
 * Specifies if this game inherits network-regions from its parent game
 *
 * @property int|null $network_id
 * Default network id
 *
 * @property string|null $network
 * Name of the default network
 *
 * @property-read string|null $description
 * Full game description
 *
 * @property-read int $creation_count
 * Total number of times an event has been created for this game
 * 
 * @property-read string|null $genre
 * Game style
 * Example: Real-Time Strategy
 *
 * @property-read int|null $genre_id
 * The genre's ID
 *
 * @property-read string|null $genre_abbreviation
 * Short form of <var>$genre</var>
 *  examples are RTS, FPS, MMORPG, etc
 *
 * @property boolean $maps
 * Game setting: does this game allow / support a list of maps?
 *
 * @property boolean $races
 * Game setting: does this game allow / support a list of races?
 *
 * @property boolean $divisions
 * Game setting: does this game allow / support a divisions?
 *
 * @property-read string $race_label
 * If applicable, this game may have races / factions associated with it<br />
 * the race_label simply indicates how the races referred to - whether they are races, or characters, factions, etc
 *
 * @property-read string $map_label
 * How maps are referred to in this game
 * <b>Default:</b> 'Map'
 * Needed for games that may refer to a map as a 'Stage' for example
 *
 * @property-read string $game_icon
 *  The URL of the 20x20 icon hosted on BinaryBeast.com for this game
 * 
 * 
 * @package BinaryBeast
 * @subpackage SimpleModel_ObjectStructure
 *
 * @version 1.0.3
 * @date    2013-05-05
 * @author  Brandon Simmons <contact@binarybeast.com
 */
abstract class BBGameObject {
    //Nothing here - used for documentation only
}