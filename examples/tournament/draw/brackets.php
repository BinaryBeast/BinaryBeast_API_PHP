<?php
/**
 * Simple example demonstrating how to manually load brackets
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

require('../__brackets.php');
?>

<h1>Winners Bracket (<?php echo $tournament->id; ?>)</h1>

<?php
foreach($tournament->brackets->winners as $round => $matches) {
    echo '<div class="round"><h3>Round ' . ($round + 1) . '</h3>';

    foreach($matches as $i => $match) {
        echo '<div class="match"><h4>Match ' . ($i + 1) . '</h4>';

        /* @var $match BBMatchObject */
        if(!is_null($match->team)) {
            echo '<span class="player">' . $match->team->display_name . '</span>';

            //Waiting on an opponent
            if(is_null($match->opponent)) {
                echo ' - <span class="empty">Waiting on an Opponent</div>';
            }
            else {
                echo ' <span class="vs">vs.</span> <span class="player">' . $match->opponent->display_name . '</span>';

                //Print the winner name
                if(!is_null($match->match)) {
                    if(!is_null($match->match->id)) {
                        echo ' Winner: <span class="winner">(Winner: ' . $match->match->winner->display_name . ')';
                    }
                }

                echo '<br />';
            }
        }
        //Waiting on an opponent
        else if(!is_null($match->opponent)) {
                echo $match->opponent->display_name . ' - Waiting on an Opponent <br />';
        }

        // end div.match
        echo '</div>';
    }
    // end div.round
    echo '</div>';
}
?>

<?php
    //Display option of deleting the example tournament
    require('../delete/delete.php');
?>