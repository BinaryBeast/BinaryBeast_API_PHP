<?php
/**
 * Documentation of object and array format you can expect from certain method calls
 * 
 * 
 * @todo Testing todo list (tab)
 *  - Test 1
 *  - Test 2
 *  - Research dockblock templates
 * 
 * @todo Testing todo list again (space)
 * - Test 1
 * - Test 2
 * - Research dockblock templates
 * 
 * @package BinaryBeast
 * @subpackage ObjectStructure
 * 
 * @version 1.0.0
 * @date 2013-04-02
 * @author Brandon Simmons <contact@binarybeast.com>
 */

/**
 * The data structure for elimination brackets
 * 
 * This class is never used, it soley exists for documentation
 * 
 * Documentation is for the return values of {@link BBTournament::brackets()}
 * 
 * 
 * #### Example Format .[#format]
 * <pre>
 *  $winners = [
 *      //First round
 *      0 => [
 *          //First match in the first round
 *          0 => {@link BBMatchObject},
 * 
 *          //Second match in the first round
 *          0 => {@link BBMatchObject},
 *      ], 
 *      //Second round 
 *      1 => [
 *          // ... and so on
 *      ]
 *  ];
 * </pre>
 * 
 * ### Example Script .[#example]
 * Notice how the <b>vdoc</b> for code hinting in <var>/* @var $match BBMatch {@*}</var>
 * <code>
 *          foreach($tournament->brackets->losers as $round => $matches) {
 *              echo '<h3>Round ' . ($round + 1) . '</h3>';
 *              foreach($matches as $i => $match) {
 *                  echo '<h4>Match ' . ($i + 1) . '</h4>';
 * 
 *                  /* @var $match BBMatchObject {@*}
 *                  if(!is_null($match->team)) {
 *                      echo $match->team->display_name;
 * 
 *                      //Waiting on an opponent
 *                      if(is_null($match->opponent)) {
 *                          echo ' - Waiting on an Opponent <br />';
 *                      }
 *                      else {
 *                          echo ' VS ' . $match->opponent->display_name;
 *                          
 *                          //Print the winner name
 *                          if(!is_null($match->match)) {
 *                              if(!is_null($match->match->id)) {
 *                                  echo ' (Winner: ' . $match->match->winner->display_name) . ')';
 *                              }
 *                          }
 * 
 *                          echo '<br />';
 *                      }
 *                  }
 *                  //Waiting on an opponent
 *                  else if(!is_null($match->opponent)) {
 *                          echo $match->opponent->display_name . ' - Waiting on an Opponent <br />';
 *                  }
 *              }
 *          }
 * </code>
 *
 * 
 * 
 * @package BinaryBeast
 * @subpackage Model_ObjectStructure
 */
abstract class BBBracketsObject {
    /**
     * Array of rounds in the winners brackets<br /><br />
     * Each element is an array of {@link BBMatchObject} objects<br /><br />
     * 
     * <br /><br />
     * <b>See an example script </b> in the {@link binarybeast.com/content/api/docs/php/class-BBBracketsObject.html#example class documentation}<br /><br />
     * <b>The format</b> is also defined in the {@link binarybeast.com/content/api/docs/php/class-BBBracketsObject.html#format class documentation}
     * 
     * @var BBMatchObject[][]|rounds[]
     */
    public $winners;

    /**
     * Array of rounds in the winners brackets<br /><br />
     * Each element is an array of {@link BBMatchObject} objects<br /><br />
     * 
     * <b>Double Elimination Only</b> ({@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#m$elimination BBTournament::elimination})<br />
     * 
     * <br /><br />
     * <b>See an example script </b> in the {@link binarybeast.com/content/api/docs/php/class-BBBracketsObject.html#example class documentation}<br /><br />
     * <b>The format</b> is also defined in the {@link binarybeast.com/content/api/docs/php/class-BBBracketsObject.html#format class documentation}
     * 
     * @var BBMatchObject[][]|rounds[]
     */
    public $losers;

    /**
     * Array of rounds in the bronze bracket, aka the 3rd place decider<br /><br />
     * Each element is an array of {@link BBMatchObject} objects<br /><br />
     * 
     * <b>Single Elimination Only</b> ({@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#m$elimination BBTournament::elimination})<br />
     * <b>Bronze Must Be Enabled</b> ({@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#m$bronze BBTournament::bronze})<br /><br />
     * 
     * <br /><br />
     * <b>See an example script </b> in the {@link binarybeast.com/content/api/docs/php/class-BBBracketsObject.html#example class documentation}<br /><br />
     * <b>The format</b> is also defined in the {@link binarybeast.com/content/api/docs/php/class-BBBracketsObject.html#format class documentation}
     * 
     * @var BBMatchObject[][]|rounds[]
     */
    public $bronze;

    /**
     * Array of rounds in the finals bracket<br /><br />
     * Each element is an array of {@link BBMatchObject} objects<br /><br />
     * 
     * <b>Double Elimination Only</b> ({@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#m$elimination BBTournament::elimination})<br />
     * 
     * <br /><br />
     * <b>See an example script </b> in the {@link binarybeast.com/content/api/docs/php/class-BBBracketsObject.html#example class documentation}<br /><br />
     * <b>The format</b> is also defined in the {@link binarybeast.com/content/api/docs/php/class-BBBracketsObject.html#format class documentation}
     * 
     * @var BBMatchObject[][]|rounds[]
     */
    public $finals;

    /**
     * <b>Documentation Hack</b><br /><br />
     * An array of rounds, each round is an array of {@link BBMatchObject}<br /><br />
     * 
     * @static
     * @var matches[]
     */
    public static $rounds;
    /**
     * <b>Documentation Hack</b><br /><br />
     * An array of {@link BBMatchObject} in a round<br /><br />
     * 
     * @static
     * @var BBMatchObject[]
     */
    public static $round;
}

/**
 * The data structure representing a single round within an elimination bracket
 * 
 * This class is never used, it soley exists for documentation
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
 */
abstract class BBMatchObject {
    //Nothing here - used for documentation only
}


?>