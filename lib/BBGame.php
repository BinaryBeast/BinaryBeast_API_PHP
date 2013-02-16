<?php

/**
 * Very simple non-editable class that hosts
 * a few methods for returning / searching through our
 * list of games
 * 
 * It's important to have these methods when creating touranments, you have the option 
 * of defining a game_code - and you can use this class to find the game_code for your game
 * 
 * If the game want to use is not in our database, send us an email to <code>contact@binarybeast.com</code>
 * and we'll be happy to add it for your
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-02-08
 * @author Brandon Simmons
 */
class BBGame extends BBSimpleModel {
    const SERVICE_SEARCH        = 'Game.GameSearch.Search';
    const SERVICE_LIST_POPULAR  = 'Game.GameSearch.Top';

    //Cache setup (cache for 1 day)
    const CACHE_OBJECT_TYPE     = BBCache::TYPE_GAME;
    const CACHE_TTL_LIST        = 1440;

    /**
     * Returns a list of games available on BinaryBeast that match the given $filter value
     * 
     * Necessary because in order to associate a particular game with a tournament,
     *      you need to know its game_code
     * 
     * The paramater for filtering can be either by game name, or game_code itself, 
     * so both "bw" and "brood" would no doubt return "StarCraft: BroodWar" as one of its results
     * 
     * @param string $filter    Search filter
     * @return array
     */
    public function search($filter) {
        //Don't even bother trying if filter is too short
        if(strlen($filter) < 2) return $this->set_error('"' . $filter . '" is too short, $filter must be at least 2 characters long');

        //Let get_list do the work
        return $this->get_list(self::SERVICE_SEARCH, array('game' => $filter), 'games');
    }

    /**
     * Returns a list of the currently most popular tournament at BinaryBeast
     * 
     * @param int $limit        (defaults to 10, can't exceed 100)
     * @return array
     */
    public function list_top($limit = 10) {
        //Let get_list do the work
        return $this->get_list(self::SERVICE_LIST_POPULAR, array('limit' => $limit), 'games');
    }
}

?>