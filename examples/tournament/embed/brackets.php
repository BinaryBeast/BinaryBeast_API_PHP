<?php
/**
 * Simple example demonstrating how you would embed brackets into your HTML
 * 
 * Review __groups.php to see how the tournament was created
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

require_once('../__brackets.php');
?>

<h1>Tournament Brackets (<?php echo $tournament->id; ?>)</h1>

<?php
    BBHelper::embed_tournament($tournament);
?>

<?php
    //Display option of deleting the example tournament
    require('../delete/delete.php');
?>