<?php
/**
 * Simple example demonstrating how you would load a list of matches that still need to be reported
 * 
 * @filesource
 * 
 * @version 1.0.0
 * @date 2013-04-05
 * @author Brandon Simmons
 * 
 * 
 * @package BinaryBeast
 * @subpackage Examples
 */

require('../../../BinaryBeast.php');
$bb = new BinaryBeast();
$bb->disable_ssl_verification();

/*
 * First - create a tournament with brackets
 */
$tournament = $bb->tournament();
$tournament->title      = 'API Demo - Listing Open Matches';
$tournament->description = 'Simple API PHP Library demonstrating how to display a list of unplayed matches';
$tournament->elimination = BinaryBeast::ELIMINATION_DOUBLE;
//
for($x = 0; $x < 16; $x++) {
    $team = $tournament->team();
    $team->confirm();
    $team->display_name = 'Demo Player ' . ($x + 1);
}
//
if(!$tournament->save()) {
    var_dump(array('Error saving tournament', 'errors' => $bb->error_history));
    die();
}
if(!$tournament->start()) {
    var_dump(array('Error starting the brackets', 'errors' => $bb->error_history));
    die();
}

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
            ' in round ' . $match->round->round . '<br />';
    }
?>

<?php
    //Display option of deleting the example tournament
    require('../delete/delete.php');
?>