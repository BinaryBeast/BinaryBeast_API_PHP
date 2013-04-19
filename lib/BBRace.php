<?php

/**
 * Race searching / listing simple model
 * 
 * 
 * <br />
 * You'll need the <var>$race_id</var> values from this class, if you want to specify a race for each participant when reporting match results
 * 
 * - {@link http://binarybeast.com/content/api/docs/php/class-BBMatchGame.html#m$race BBMatchGame::$race}
 * - {@link http://binarybeast.com/content/api/docs/php/class-BBMatchGame.html#m$race BBMatchGame::$o_race}
 * 
 * 
 * <br /><br />
 * The following examples assume <var>$bb</var> is an instance of {@link BinaryBeast}
 * <br />
 * 
 * 
 * 
 * ### Example: List all races for StarCraft 2
 * 
 * <b>Example - search for games that contain the word 'star'</b>
 * <code>
 *  $races = $bb->race->game_list('SC2');
 *  foreach($races as $race) {
 *      echo '<img src="' . $race->race_icon . '" /> ' . $race->race . ' (' . $race->race_id . ') <br />';
 *  }
 * </code>
 * <b>Result:</b>
 * 
 * [* http://binarybeast.com/img/races/SC2/3.png *] Protoss (3)<br />
 * [* http://binarybeast.com/img/races/SC2/7.png *] Random (7)<br />
 * [* http://binarybeast.com/img/races/SC2/2.png *] Terran (2)<br />
 * [* http://binarybeast.com/img/races/SC2/1.png *] Zerg (1)<br />
 *
 * 
 * ### Example: Search for the Zerg race in StarCraft 2
 * 
 * <code>
 *  $races = $bb->race->game_search('SC2', 'Ze');
 *  foreach($races as $race) {
 *      echo '<img src="' . $race->race_icon . '" /> ' . $race->race . ' (' . $race->race_id . ') <br />';
 *  }
 * </code>
 * <b>Result:</b>
 * 
 * [* http://binarybeast.com/img/races/SC2/1.png *] Zerg (1)
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
 * 
 * @package BinaryBeast
 * @subpackage SimpleModel
 * 
 * @version 3.0.2
 * @date    2013-04-13
 * @author  Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
class BBRace extends BBSimpleModel {
    /**
     * API svc name for searching for game races
     * @var string
     */
    const SERVICE_SEARCH    = 'Game.GameRace.Search';
    /**
     * API svc name for loading game races
     * @var string
     */
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
     * @return BBRaceObject[]
     */
    public function game_list($game_code) {
        return $this->get_list(self::SERVICE_LIST, array('game_code' => $game_code), 'list');
    }
    /**
     * Alias for game_list, or search() if you define a filter
     * 
     * @param string $game_code
     * @param string $filter
     * @return BBRaceObject[]
     */
    public function load_list($game_code, $filter = null) {
        if(is_null($filter)) return $this->game_list($game_code);
        return $this->game_search($game_code, $filter);
    }
    /**
     * Returns an array of races used in the provided $game_code, after applying
     *      a filter against it
     *
     * @param string $game_code
     * @param string $filter    Search filter
     * @return BBRaceObject[]
     */
    public function game_search($game_code, $filter) {
        return $this->get_list(self::SERVICE_SEARCH, array('game_code' => $game_code, 'filter' => $filter), 'list');
    }
}

?>