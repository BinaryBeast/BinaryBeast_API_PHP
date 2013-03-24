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
 * 
 * @package BinaryBeast
 * @subpackage Examples
 */

require('../../../BinaryBeast.php');
$bb = new BinaryBeast();

/*
 * First - create a tournament with brackets
 */
$tournament = $bb->tournament();
$tournament->title              = 'API Demo - Embedding Brackets';
$tournament->description        = 'Simple API PHP Library demonstrating how to embed brackets';
$tournament->elimination        = BinaryBeast::ELIMINATION_DOUBLE;
$tournament->type_id            = BinaryBeast::TOURNEY_TYPE_CUP;
//8 groups, 4 teams from each - we should have a 32 man bracket after the groups
$tournament->group_count        = 8;
$tournament->teams_from_group   = 4;
//Need at least 5 participants per groups - to allow 4 to advance from each - so we need 40 teams
for($x = 0; $x < 40; $x++) {
    $team = $tournament->team();
    $team->confirm();
    $team->display_name = 'Demo Player ' . ($x + 1);
}
//
if(!$tournament->save()) {
    var_dump(array('Error saving tournament', 'errors' => $bb->error_history));
    die();
}
//Start groups
if(!$tournament->start()) {
    var_dump(array('Error starting the groups', 'errors' => $bb->error_history));
    die();
}
//Start brackets
if(!$tournament->start()) {
    var_dump(array('Error starting the brackets', 'errors' => $bb->error_history));
    die();
}

?>

<h1>Tournament Groups (<?php echo $tournament->id; ?>)</h1>
<?php
    /**
     * @todo build the iframe embedding helper
     */
?>
<h1>Tournament Groups (<?php echo $tournament->id; ?>)</h1>
<?php
    /**
     * @todo build the iframe embedding helper
     */
?>