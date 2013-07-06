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
 * @version 3.0.3
 * @date    2013-07-05
 * @since   2013-02-08
 * @author  Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
class BBMap extends BBSimpleModel {
    /**
     * Service name for searching listing game maps
     * @var string
     */
    const SERVICE_LIST      = 'Game.GameMap.LoadList';
    /**
     * Service name for loading a single map
     * @var string
     */
    const SERVICE_LOAD      = 'Game.GameMap.Load';
    /**
     * Service name for searching searching for game maps
     * @var string
     */
    const SERVICE_SEARCH    = 'Game.GameMap.Search';

    const CACHE_OBJECT_TYPE      = BBCache::TYPE_MAP;
    const CACHE_TTL_LIST         = 1440;
    const CACHE_TTL_LOAD         = 1440;

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
        return $this->game_search($game_code, $filter);
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

    /**
     * Load map details
     *
     * @since 2013-07-05
     *
     * @param int $map_id
     *
     * @return BBMapObject|null
     * - false if the map_id is invalid
     */
    public function load($map_id) {
        $result = $this->call(self::SERVICE_LOAD, array('map_id' => $map_id),
            self::CACHE_TTL_LOAD, self::CACHE_OBJECT_TYPE, $map_id
        );

        //Invalid map_id
        if($result->result != 200) {
            return null;
        }

        //Success!
        return $result->info;
    }
}