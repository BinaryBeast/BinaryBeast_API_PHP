<?php
/**
 * Simple example demonstrating how you would embed groups into your HTML
 * 
 * Important to note that by default, embedding a tournament will display the current phase of the tournament
 * 
 * So if your tournament is in the brackets stage in a groups to brackets tournament, the groups would NOT be displayed in the iframe
 * 
 * However by adding /groups to the iFrame src, you can request that the group specifically be displayed, regardless of status
 * 
 * Unlike groups.php, this example uses {@link BBTournament} instead of {@link BBHelper}
 * 
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

//Setup values for __groups.php
$group_count        = 8;
$teams_from_group   = 4;
$teams_count        = 40;

require_once('../__groups.php');
?>

<h1>Tournament Groups (<?php echo $tournament->id; ?>)</h1>
<?php
    $tournament->embed_groups();
?>
<h1>Tournament Brackets (<?php echo $tournament->id; ?>)</h1>
<?php
    $tournament->embed();
?>