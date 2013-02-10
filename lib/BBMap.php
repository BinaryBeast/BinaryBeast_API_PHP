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
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-02-08
 * @author Brandon Simmons
 */
class BBMap extends BBSimpleModel {
    const SERVICE_LIST      = 'Game.GameMap.LoadList';
    const SERVICE_SEARCH    = 'Game.GameMap.Search';

    /**
     * Returns a full list of all maps available within the given $game_code
     * 
     * If you take advantage of this service, you can start reporting
     *      match results using real maps, that are being track on BB's end
     * 
     * Please note: If you were kind enough to submit maps for us on the site, they won't
     *  show up here until we approve them
     * 
     * @param string $filter
     * @return array
     */
    public function game_list($game_code) {
        return $this->get_list(self::SERVICE_LIST, array('game_code' => $game_code), 'list');
    }

    /**
     * Return a filtered set of maps associated with the given game_code
     * 
     * @param string $filter
     * @param string $game_code
     * @return array
     */
    public function game_search($game_code, $filter) {
        return $this->get_list(self::SERVICE_SEARCH, array(
            'game_code' => $game_code,
            'filter' => $filter),
        'list');
    }
}

?>