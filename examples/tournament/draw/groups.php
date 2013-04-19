<?php
/**
 * Simple example demonstrating how to manually load group rounds
 *
 * @filesource
 *
 * @global BBTournament $tournament
 *
 * @package BinaryBeast
 * @subpackage Examples
 *
 * @version 1.0.1
 * @date    2013-04-13
 * @author  Brandon Simmons <contact@binarybeast.com>
 */

require_once('../__groups.php');
?>

<h1>Group A</h1>
<?php
foreach($tournament->groups->a as $round => $matches) {
    echo '<h3>Round ' . ($round + 1) . '</h3>';

    foreach($matches as $i => $match) {
        echo '<h4>Match ' . ($i + 1) . '</h4>';

        /* @var $match BBMatchObject */
        if(!is_null($match->team)) {
            echo $match->team->display_name;

            //Waiting on an opponent
            if(is_null($match->opponent)) {
                echo ' - Waiting on an Opponent';
            }
            else {
                echo ' vs. ' . $match->opponent->display_name;

                //Print the winner name
                if(!is_null($match->match)) {
                    if(!is_null($match->match->id)) {
                        echo ' (Winner: ' . $match->match->winner->display_name . ')';
                    }
                }

            }
        }
        //Waiting on an opponent
        else if(!is_null($match->opponent)) {
                echo $match->opponent->display_name . ' - Waiting on an Opponent <br />';
        }
    }
}
?>

<?php
    //Display option of deleting the example tournament
    require('../delete/delete.php');
?>