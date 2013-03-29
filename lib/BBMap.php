<?php

/**
 * Map listing / search simple model
 * 
 * Provides services for searching for maps available to a game (see {@link BBGame})
 * 
 * 
 * <br /><br />
 * The map_id values returned can be used to set {@link http://binarybeast.com/content/api/docs/php/class-BBRound.html#m$map BBRound::$map}, <br />
 * and to define which map each {@link http://binarybeast.com/content/api/docs/php/class-BBMatchGame.html#m$map BBMatchGame::$map} was was played on within a {@link BBMatch}
 * 
 * --------
 * The following examples assume <var>$bb</var> is an instance of {@link BinaryBeast}
 * <br />
 * 
 * 
 * ### Example: List all StarCraft 2 maps
 * 
 * <b>Note: </b>You'll need to know the GameCode - you can get it from {@link BBGame}
 * 
 * <code>
 *  $games = $bb->map->game_list('SC2');
 *  foreach($games as $game) {
 *      echo $game->game . ' (' . $game->game_code . ')<br />';
 *  }
 * </code>
 * <b>Result:</b>
 * <pre>
 *  Abyssal Caverns (125) 
 *  Abyssal City (614) 
 *  Agria Valley (647) 
 *  Akilon Flats (639)
 *  Antiga Shipyard (71) 
 *  Arid Plateau (211) 
 *  Atlantis Spaceship (337) 
 *  Backwater Gulch (72) 
 *  Blistering Sands (225) 
 *  Cloud Kingdom (243) 
 *  Condemned Ridge (513) 
 *  Crossfire (68) 
 * ...
 * </pre>
 * 
 * 
 * ### Example: Search for a map
 * 
 * Let's try to find the map_id of Antiga Shipyard for StarCraft 2
 * <code>
 *  $maps = $bb->map->game_search('SC2', 'Antiga Shipyard');
 *  foreach($maps as $map) {
 *      echo $map->map . ' (' . $map->map_id . ') <br />';
 *  }
 * </code>
 * <b>Result:</b>
 * <pre>
 *  Antiga Shipyard (71) 
 *  GSL Antiga Shipyard (207) 
 *  IPL3 Antiga Shipyard (133) 
 *  MLG Antiga Shipyard (54) 
 *  TSL4 Antiga Shipyard (499) 
 *  WCS Antiga Shipyard (485) 
 * </pre>
 * 
 * 
 * ### Missing Maps
 * 
 * If there are maps missing, you can submit them for approval on BinaryBeast
 * 
 * Maps are submitted per-game however, so for example if you wanted to add a map for QuakeLive, use
 * {@link http://binarybeast.com/game/create_map/QL}
 * 
 * 
 * 
 * 
 * @package BinaryBeast
 * @subpackage SimpleModel
 * 
 * 
 * @version 3.0.1
 * @date 2013-03-28
 * @author Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
class BBMap extends BBSimpleModel {
    const SERVICE_LIST      = 'Game.GameMap.LoadList';
    const SERVICE_SEARCH    = 'Game.GameMap.Search';
    
    //Cache setup (1 day cache)
    const CACHE_OBJECT_TYPE      = BBCache::TYPE_MAP;
    const CACHE_TTL_LIST         = 1440;

    /**
     * Returns a full list of all maps available within the given $game_code
     * 
     * If you take advantage of this service, you can start reporting
     *      match results using real maps, that are being track on BB's end
     * 
     * Please note: If you were kind enough to submit maps for us on the site, they won't
     *  show up here until we approve them
     * 
     * @param string $game_code
     * @param string $filter
     * @return BBMapObject[]
     */
    public function game_list($game_code) {
        return $this->get_list(self::SERVICE_LIST, array('game_code' => $game_code), 'list');
    }
    /**
     * Alias for game_list, or search() if you define a filter
     * @param string $game_code
     * @param string $filter
     * @return BBMapObject[]
     */
    public function load_list($game_code, $filter = null) {
        if(is_null($filter)) return $this->game_list($game_code);
        return $this->search($game_code, $filter);
    }
    /**
     * Return a filtered set of maps associated with the given game_code
     * 
     * @param string $game_code
     * @param string $filter
     * @return BBMapObject[]
     */
    public function game_search($game_code, $filter) {
        return $this->get_list(self::SERVICE_SEARCH, array(
            'game_code' => $game_code,
            'filter' => $filter),
        'list');
    }
}

/**
 * The data structure for values returned from the BBMap services
 * 
 * This class is never used, it soley exists for documentation
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
 */
abstract class BBMapObject {
    //Nothing here - used for documentation only
}

?>