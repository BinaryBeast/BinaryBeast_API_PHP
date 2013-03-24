<?php
/**
 * Simple example demonstrating how you would embed brackets into your HTML
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
$tournament->title      = 'API Demo - Embedding Brackets';
$tournament->description = 'Simple API PHP Library demonstrating how to embed brackets';
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
    /**
     * @todo build the iframe embedding helper
     */
?>