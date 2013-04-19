<?php
/**
 * Simple example demonstrating how to delete a tournament
 * 
 * (also used by other examples to allow deleting examples after you're done with them)
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

//May be set from another example
if(!isset($bb)) {
    require('../../../BinaryBeast.php');
    $bb = new BinaryBeast();
    $bb->disable_ssl_verification();
}

/**
 * Process the request
 */
if(isset($_POST['id'])) {
    $tournament = $bb->tournament($_POST['id']);
    if(!$tournament->delete()) {
        var_dump(array('Error deleting tournament', 'errors' => $bb->error_history));
        die();
    }
    else {
        die('<h1 style="color:red">Tournament (' . $_POST['id'] . ') deleted successfully!</h1>');
    }
}

/**
 * Create a tournament to delete (unless provided by another example)
 */
if(!isset($tournament)):
    require_once('../__brackets.php');
?>
    <h1>Tournament Brackets (<?php echo $tournament->id; ?>)</h1>

    <?php
        BBHelper::embed_tournament($tournament);
    ?>

<?php endif; ?>

<form action="/examples/tournament/delete/delete.php" method="post">
    <input type="hidden" name="id" value="<?php echo $tournament->id; ?>" />
    <fieldset>
        <legend>Delete the Tournament!</legend>
        <input type="submit" value="Delete" />
    </fieldset>
</form>