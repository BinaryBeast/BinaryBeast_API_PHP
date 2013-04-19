<?php
/**
 * Documentation of object and array format you can expect from certain method calls
 * 
 * 
 * @todo Testing todo list (tab)
 *  - Test 1
 *  - Test 2
 *  - Research docblock templates
 * 
 * @todo Testing todo list again (space)
 * - Test 1
 * - Test 2
 * - Research docblock templates
 * 
 * @package BinaryBeast
 * @subpackage ObjectStructure
 * 
 * @version 1.0.1
 * @date    2013-04-13
 * @author  Brandon Simmons <contact@binarybeast.com>
 */

/**
 * The data structure for elimination brackets
 *
 * This class is never used, it solely exists for documentation
 *
 * Documentation is for the return values of {@link BBTournament::brackets()}
 *
 *
 * #### Example Format .[#format]
 * <pre>
 *  $brackets = {
 *      'winners' => [
 *          //First round
 *          0 => [
 *              //First match in the first round
 *              0 => {@link BBMatchObject},
 *
 *              //Second match in the first round
 *              1 => {@link BBMatchObject},
 *          ],
 *          //Second round
 *          1 => [
 *              // ... and so on
 *          ]
 *      ],
 *
 *      'losers' => [
 *          //First round
 *          0 => [
 *              //First match in the first round
 *              0 => {@link BBMatchObject},
 *
 *              //Second match in the first round
 *              1 => {@link BBMatchObject},
 *          ],
 *          //Second round
 *          1 => [
 *              // ... and so on
 *          ]
 *      ],
 * }
 * </pre>
 *
 * Notice how the <b>vdoc</b> for code hinting in <var>/* @var $match BBMatch {@*}</var>
 * <code>
 * foreach($tournament->brackets->winners as $round => $matches) {
 *     echo '<h3>Round ' . ($round + 1) . '</h3>';
 *     foreach($matches as $i => $match) {
 *         echo '<h4>Match ' . ($i + 1) . '</h4>';
 *         /* @var $match BBMatchObject {@*}
 *         if(!is_null($match->team)) {
 *             echo $match->team->display_name;
 *             //Waiting on an opponent
 *             if(is_null($match->opponent)) {
 *                 echo ' - Waiting on an Opponent';
 *             }
 *             else {
 *                 echo ' vs. ' . $match->opponent->display_name;
 *                 //Print the winner name
 *                 if(!is_null($match->match)) {
 *                     if(!is_null($match->match->id)) {
 *                         echo ' (Winner: ' . $match->match->winner->display_name . ')';
 *                     }
 *                 }
 *             }
 *         }
 *         //Waiting on an opponent
 *         else if(!is_null($match->opponent)) {
 *                 echo $match->opponent->display_name . ' - Waiting on an Opponent <br />';
 *         }
 *     }
 * }
 * </code>
 *
 * @example examples/tournament/draw/brackets.php
 *
 * @package BinaryBeast
 * @subpackage Model_ObjectStructure
 *
 * @version 1.0.1
 * @date    2013-04-13
 * @author  Brandon Simmons <contact@binarybeast.com
 */
abstract class BBBracketsObject {
    /**#@+
     * Template for rounds in each group<br />
     *
     * Each round is an array of {@link BBMatchObject} objects<br />
     *
     * <br /><br />
     * <b>See an example script </b> in the {@link http://binarybeast.com/content/api/docs/php/class-BBBracketsObject.html#format class documentation}<br /><br />
     * <b>The format</b> is also defined in the {@link http://binarybeast.com/content/api/docs/php/class-BBBracketsObject.html#example class documentation}
     *
     * @var BBMatchObject[][]|round[]
     */
    /**
     * Array of rounds in the winners brackets<br /><br />
     */
    public $winners;

    /**
     * Array of rounds in the losers brackets<br /><br />
     */
    public $losers;

    /**
     * Array of rounds in the bronze bracket, aka the 3rd place decider<br /><br />
     */
    public $bronze;

    /**
     * Array of rounds in the finals bracket<br /><br />
     */
    public $finals;
    /**#@-*/

    /**
     * <b>Documentation Hack</b><br /><br />
     * An array of {@link BBMatchObject} in a round<br /><br />
     *
     * @var BBMatchObject[]
     */
    public static $round;
}



/**
 * The data structure for group rounds data
 *
 * This class is never used, it solely exists for documentation
 *
 * Documentation is for the return values of {@link BBTournament::groups()}
 *
 *
 * #### Example Format .[#format]
 * <pre>
 *  $groups = {
 *      //Group A
 *      a => [
 *          //First round
 *          0 => [
 *              //First match in the first round
 *              0 => {@link BBMatchObject},
 *
 *              //Second match in the first round
 *              1 => {@link BBMatchObject},
 *          ],
 *          //Second round
 *          1 => [
 *              // ... and so on
 *          ]
 *      ],
 *      //Group B
 *      b => [
 *          //..etc
 *      ]
 * }
 * </pre>
 *
 * ### Example Script .[#example]
 * There is a full example available in {@link examples/tournaments/draw/groups.php}
 *
 * Notice how the <b>vdoc</b> for code hinting in <var>/* @var $match BBMatch {@*}</var>
 * <code>
 *  foreach($tournament->groups->a as $round => $matches) {
 *      echo '<h3>Round ' . ($round + 1) . '</h3>';
 *
 *      foreach($matches as $i => $match) {
 *          echo '<h4>Match ' . ($i + 1) . '</h4>';
 *
 *          /* @var $match BBMatchObject {@*}
 *          if (!is_null($match->team)) {
 *              echo $match->team->display_name;
 *
 *              //Waiting on an opponent
 *              if (is_null($match->opponent)) {
 *                  echo ' - Waiting on an Opponent';
 *              }
 *              else {
 *                  echo ' vs. ' . $match->opponent->display_name;
 *
 *                  //Print the winner name
 *                  if (!is_null($match->match)) {
 *                      if (!is_null($match->match->id)) {
 *                          echo ' (Winner: ' . $match->match->winner->display_name . ')';
 *                      }
 *                  }
 *              }
 *          }
 *          //Waiting on an opponent
 *          else if (!is_null($match->opponent)) {
 *              echo $match->opponent->display_name . ' - Waiting on an Opponent <br />';
 *          }
 *      }
 * }
 * </code>
 *
 * @example examples/tournament/draw/groups.php
 *
 * @package BinaryBeast
 * @subpackage Model_ObjectStructure
 *
 * @version 1.0.1
 * @date    2013-04-13
 * @author  Brandon Simmons <contact@binarybeast.com
 */
abstract class BBGroupsObject {

    /**#@+
     * Template for rounds in each group<br />
     *
     * Each round is an array of {@link BBMatchObject} objects<br />
     *
     * <br /><br />
     * <b>See an example script </b> in the {@link http://binarybeast.com/content/api/docs/php/class-BBGroupsObject.html#format class documentation}<br /><br />
     * <b>The format</b> is also defined in the {@link http://binarybeast.com/content/api/docs/php/class-BBGroupsObject.html#format class documentation}
     *
     * @var BBMatchObject[][]|round[]
     */
    /**
     * Array of rounds in group A
     */
    public $a;
    /**
     * Array of rounds in group B
     */
    public $b;
    /**
     * Array of rounds in group C
     */
    public $c;
    /**
     * Array of rounds in group D
     */
    public $d;
    /**
     * Array of rounds in group E
     */
    public $e;
    /**
     * Array of rounds in group F
     */
    public $f;
    /**
     * Array of rounds in group G
     */
    public $g;
    /**
     * Array of rounds in group H
     */
    public $h;
    /**
     * Array of rounds in group I
     */
    public $i;
    /**
     * Array of rounds in group J
     */
    public $j;
    /**
     * Array of rounds in group K
     */
    public $k;
    /**
     * Array of rounds in group L
     */
    public $l;
    /**
     * Array of rounds in group M
     */
    public $m;
    /**
     * Array of rounds in group N
     */
    public $n;
    /**
     * Array of rounds in group O
     */
    public $o;
    /**
     * Array of rounds in group P
     */
    public $p;
    /**
     * Array of rounds in group Q
     */
    public $q;
    /**
     * Array of rounds in group R
     */
    public $r;
    /**
     * Array of rounds in group S
     */
    public $s;
    /**
     * Array of rounds in group T
     */
    public $t;
    /**
     * Array of rounds in group U
     */
    public $u;
    /**
     * Array of rounds in group V
     */
    public $v;
    /**
     * Array of rounds in group W
     */
    public $w;
    /**
     * Array of rounds in group X
     */
    public $x;
    /**
     * Array of rounds in group Y
     */
    public $y;
    /**
     * Array of rounds in group Z
     */
    public $z;
    /**#@-*/

    /**
     * <b>Documentation Hack</b><br /><br />
     * An array of {@link BBMatchObject} in a round<br /><br />
     *
     * @var BBMatchObject[]
     */
    public static $round;
}


/**
 * The data structure representing a single round within an elimination bracket
 * 
 * This class is never used, it solely exists for documentation
 * 
 * Documentation is for the return values of {@link BBTournament::brackets()} and {@link BBTournament::groups()}
 * 
 * @property-read BBTeam $team
 * The first team in the match, currently facing {@link $opponent}<br /><br/>
 * If <b>null</b>, it means that this position hasn't been filled by a team yet
 * 
 * @property-read BBTeam $opponent
 * The second team in the match, currently facing {@link $team}<br /><br/>
 * If <b>null</b>, it means that this position hasn't been filled by a team yet
 * 
 * @property-read BBMatch $match
 * The BBMatch object that represents the result of this match<br /><br />
 * If <b>null</b>, it means that this either {@link $team} or {@link $opponent} are null<br /><br />
 * If this match has not yet been played, a BBMatch object is set representing the unplayed match, so that you can easily report it
 * 
 * @package BinaryBeast
 * @subpackage Model_ObjectStructure
 *
 * @version 1.0.1
 * @date    2013-04-13
 * @author  Brandon Simmons <contact@binarybeast.com
 */
abstract class BBMatchObject {
    //Nothing here - used for documentation only
}


?>