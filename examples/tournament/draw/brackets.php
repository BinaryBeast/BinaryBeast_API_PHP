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
 * @date    2013-04-25
 * @since   2013-04-13
 * @author  Brandon Simmons <contact@binarybeast.com>
 */

require_once('../__brackets.php');

//User the shared __report example script to automatically report a couple of matches
require_once('../__report.php');

?>
    <h1><a href="<?php echo $tournament->url; ?>"
           target="_blank"><?php echo $tournament->title . "($tournament->id)"; ?></a>
        - Bracket Matches and Results
    </h1>

<?php

//Loop through and display the matches / results of each group
foreach ($tournament->brackets as $bracket => $rounds): ?>
    <h2><?php echo ucfirst($bracket); ?></h2>

    <?php
    //Loop through each round for this group
    foreach ($rounds as $round => $matches) {
        echo '<h3>Round ' . ($round + 1) . '</h3>';

        //Loop through each match in the round
        foreach ($matches as $i => $match) {
            /* @var $match BBMatchObject */

            echo '<h4>Match ' . ($i + 1) . '</h4>';

            //Is there a team in the first position?
            if(!is_null($match->team)) {
                echo $match->team->display_name;

                //Do we have an opponent?
                if(!is_null($match->opponent)) {
                    //Determine how to print the second half of the match description, based on if the match has been reported
                    $out = null;

                    //If we have match details, display the results
                    if(!is_null($match->match)) {

                        //Instead of display "team vs opponent", display "team 2:1 opponent" indicating the result and score
                        if (!is_null($match->match->id)) {
                            //First team won - display score:o_score
                            if ($match->match->winner == $match->team) {
                                $out = ' <b>' . $match->match->score . ':' . $match->match->o_score . '</b> ';
                            } //Second team won - display o_score:score
                            else {
                                $out = ' <b>' . $match->match->o_score . ':' . $match->match->score . '</b> ';
                            }

                            $out .= $match->opponent->display_name;
                        }
                    }

                    //Unplayed match - generic "player vs player" output
                    if (is_null($out)) {
                        $out = ' vs. ' . $match->opponent->display_name;
                    }

                    //Print the result, either player $x:$y player, or player vs player
                    echo "$out<br />";
                }

                //Waiting on an opponent
                else {
                    echo ' - Waiting on an opponent<br />';
                }
            }

            //Player in position 2?
            else if(!is_null($match->opponent)) {
                //We'd only get to this point if the first position was null, so we know he is waiting on an opponent
                echo $match->team->display_name . ' - Waiting on an opponent<br />';
            }
        }
    }

endforeach;

//Display option of deleting the example tournament
require('../delete/delete.php');

?>