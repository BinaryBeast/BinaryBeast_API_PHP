<?php
/**
 * Simple example demonstrating how you would load a list of matches that still need to be reported
 *
 * @global BBTournament $tournament
 *
 * @filesource
 * 
 * @version 1.0.1
 * @date    2013-04-13
 * @author  Brandon Simmons <contact@binarybeast.com>
 * 
 * 
 * @package BinaryBeast
 * @subpackage Examples
 */

require_once('../__brackets.php');
?>

<h1>Tournament Brackets (<?php echo $tournament->id; ?>)</h1>

<?php
    //Embed it 
    BBHelper::embed_tournament($tournament);

?>
<h1>Matches that need to be reported</h1>

<?php
    //Now list all of the open matches
    $matches = $tournament->open_matches();
    foreach($matches as $match) {
        echo $match->team->display_name .' vs ' . $match->team2->display_name .
            ' in round ' . $match->round . '<br />';
    }
?>

<?php
    //Display option of deleting the example tournament
    require('../delete/delete.php');
?>