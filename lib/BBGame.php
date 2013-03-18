<?php

/**
 * === Game Loader ===
 * 
 * Simple API service wrapper for loading / searching for games
 * 
 * It's important to have these methods when creating touranments, you have the option 
 *  of defining a game_code - and you can use this class to find the game_code for your game
 * 
 * If the game want to use is not in our database, send us an email to <contact@binarybeast.com>
 * and we'll be happy to add it for your
 * 
 * @package BinaryBeast
 * @subpackage SimpleModel
 * 
 * @version 3.0.0
 * @date 2013-03-17
 * @author Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
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
     * @return BBGameObject[]
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
     * @return BBGameObject[]
     */
    public function list_top($limit = 10) {
        //Let get_list do the work
        return $this->get_list(self::SERVICE_LIST_POPULAR, array('limit' => $limit), 'games');
    }
}

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
 *  examples are RTS, FPS, MMORPS, etc
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