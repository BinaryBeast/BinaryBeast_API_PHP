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
 * @version 1.0.2
 * @date    2013-04-24
 * @since   2013-04-13
 * @author  Brandon Simmons <contact@binarybeast.com>
 */

$group_count = 2;
require_once('../__groups.php');

//User the shared __report example script to randomly report 3 matches
require_once('../__report.php');

?>
<h1><a href="<?php echo $tournament->url; ?>" target="_blank"><?php echo $tournament->title . "($tournament->id)"; ?></a> - Group Rounds Matches and Results</h1>

<?php
//Loop through and display the matches / results of each group
foreach($tournament->groups as $group => $rounds): ?>
    <h2>Group <?php echo $group; ?></h2>

    <?php
    //Loop through each round for this group
    foreach($rounds as $round => $matches) {
        echo '<h3>Round ' . ($round + 1) . '</h3>';

        //Loop through each match in the round
        foreach($matches as $i => $match) {
            /* @var $match BBMatchObject */

            echo '<h4>Match ' . ($i + 1) . '</h4>';

            //Print out the first team's name
            echo $match->team->display_name;

            //Determine how to print the second half of the match description, based on if the match has been reported
            $out = null;

            //This team has a 'bye' / freewin
            if(is_null($match->opponent)) {
                $out = ' - <b>Bye / Freewin</b>';
            }

            //If we have match details, display the results
            else if(!is_null($match->match)) {

                //Instead of display "team vs opponent", display "team 2:1 opponent" indicating the result and score
                if(!is_null($match->match->id)) {
                    //First team won - display score:o_score
                    if ($match->match->winner == $match->team) {
                        $out = ' <b>' . $match->match->score . ':' . $match->match->o_score . '</b> ';
                    }

                    //Second team won - display o_score:score
                    else {
                        $out = ' <b>' . $match->match->o_score . ':' . $match->match->score . '</b> ';
                    }

                    $out .= $match->opponent->display_name;
                }
            }

            //Unplayed match - generic "player vs player" output
            if(is_null($out)) {
                $out = ' vs. ' . $match->opponent->display_name;
            }

            //Print the result, either player $x:$y player, or player vs player
            echo "$out<br />";
        }
    }

endforeach;

//Display option of deleting the example tournament
require('../delete/delete.php');

?>