<?php

/**
 * Very simple non-editable class that hosts
 * a few methods for returning / searching through our
 * list of maps
 * 
 * It's important to have these methods when setting up the format for tournaments, and when
 * reporting matches - becuase those services allow you to define a <code>map_id</code>, and here
 * is where you can find those ids
 * 
 * Please note that all services in thie class are executed within the context
 *      of the game_code you provide, you can't load a global list of maps, each game has its
 *      own list of maps
 * 
 * 
 * @package BinaryBeast
 * @subpackage SimpleModel
 * 
 * 
 * @version 3.0.0
 * @date 2013-03-17
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