<?php

/**
 * Very simple non-editable class that hosts
 *  a few methods for fetching arrays of races that you can use
 *  while reporting games / matches
 * 
 * If the game your tournament uses does not have all of the races you need, please
 *      feel free to let us know, by sending an email to <code>contact@binarybeast.com</code>
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-02-11
 * @author Brandon Simmons
 */
class BBRace extends BBSimpleModel {
    const SERVICE_SEARCH    = 'Game.GameRace.Search';
    const SERVICE_LIST      = 'Game.GameRace.LoadList';

    //Cache setup (cache for 1 day)
    const CACHE_OBJECT_TYPE     = BBCache::TYPE_RACE;
    const CACHE_TTL_LIST        = 1440;

    /**
     * Returns an array of races configured for the given $game_code
     * 
     * If you want to define which race players used during games / matches, you'll need
     *  to know the race id - use this method to get them
     * 
     * Since BinaryBeast is awesome, you can also take advantage of the "race_icon" value returned
     *  in each game if you'd like to display an image of the race
     * 
     * @param string $game_code
     * @return array
     */
    public function game_list($game_code) {
        return $this->get_list(self::SERVICE_LIST, array('game_code' => $game_code), 'list');
    }
    /**
     * Alias for game_list, or search() if you define a filter
     * @param string $game_code
     * @param string $filter
     */
    public function load_list($game_code, $filter = null) {
        if(is_null($filter)) return $this->game_list($game_code);
        return $this->search($game_code, $filter);
    }
    /**
     * Returns an array of races used in the provided $game_code, after applying
     *      a filter against it
     * 
     * @param string $filter    Search filter
     * @return array
     */
    public function search($game_code, $filter) {
        return $this->get_list(self::SERVICE_SEARCH, array('game_code' => $game_code, 'filter' => $filter), 'list');
    }
}

?>