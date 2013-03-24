<?php
/**
 * Simple example demonstrating how you would embed brackets into your HTML, with some customized CSS properties
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

<style>
    html, body {
        padding: 0;
        margin: 0;
        width: 100%;
    }
    .binarybeast.big {
        width: 1500px;
        height: 900px;
        display: block;
        margin: 20px 5px;
    }
</style>

<h1>Default (<?php echo $tournament->id; ?>)</h1>
<?php
    $tournament->embed();
?>

<h1>Big</h1>
<?php
    $tournament->embed(false, 800, 600, 'binarybeast big');
?>

<?php
    //Display option of deleting the example tournament
    require('../delete/delete.php');
?>