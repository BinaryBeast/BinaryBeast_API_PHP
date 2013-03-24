<?php

/**
 * Country searching / listing simple model
 * 
 * 
 * You'll need this class to find game codes For {@link BBTournament::$game_code}
 * 
 * 
 * Examples assume <var>$bb</var> is an instance of {@link BinaryBeast}
 * 
 * 
 * 
 * ### Example: Search for a game
 * 
 * <b>Example - search for games that contain the word 'star'</b>
 * <code>
 *  $games = $bb->game->search('star');
 *  foreach($games as $game) {
 *      echo $game->game . ' (' . $game->game_code . ')<br />';
 *  }
 * </code>
 * <b>Result:</b>
 * <pre>
 *  StarCraft 2 (SC2)
 *  StarCraft 2: Heart of the Swarm (HotS)
 *  StarCraft: BroodWar (BW)
 *  StarCraft 2 - Europe (SC2EU)
 *  StarCraft 2 - North America (SC2US)
 *  StarCraft 2 - Asia (SC2SEA)
 *  Star Wars Jedi Knight III (JDK3)
 *  Star Wars Jedi Knight II: Jedi Outcast (JDK2)
 * </pre>
 * 
 * 
 * ### Example: List popular games
 * 
 * <b>Example - List the top 15 games on BinaryBeast right now</b>
 * <code>
 *  $games = $bb->game->list_top(15);
 *  foreach($games as $game) {
 *      echo $game->game . ' (' . $game->game_code . ')<br />';
 *  }
 * </code>
 * <b>Result:</b>
 * <pre>
 *  StarCraft 2 (SC2)
 *  League of Legends (LoL)
 *  StarCraft 2: Heart of the Swarm (HotS)
 *  StarCraft: BroodWar (BW)
 *  Counter-Strike 1.6 (CS16)
 *  Warcraft 3: DotA (DotA)
 *  DotA 2 (DOTA2)
 *  Call of Duty: Modern Warfare 3 (MW3)
 *  FIFA 12 (FIFA12)
 *  FIFA 13 (FIFA13)
 *  Counter-Strike: Source (CSS)
 *  Warcraft 3: Frozen Throne (War3FT)
 *  Counter-Strike: Global Offensive (CSGO)
 *  Call of Duty 4 (CoD4)
 *  Call of Duty: Black Ops 2 (CoDBO2)
 * </pre>
 * 
 * 
 * 
 * 
 * 
 * If the game want to use is not in our database, send us an email to <contact@binarybeast.com>
 * and we'll be happy to add it for your
 * 
 * 
 * 
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