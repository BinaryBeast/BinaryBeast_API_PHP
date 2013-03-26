<?php
/**
 * Simple example demonstrating how to delete a tournament
 * 
 * (also used by other examples to allow deleting examples after you're done with them)
 * 
 * @package BinaryBeast
 * @subpackage Examples
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
 * Create a tournament to delete (unless provded by another example)
 */
if(!isset($tournament)):

    /*
     * First - create a tournament with brackets
     */
    $tournament = $bb->tournament();
    $tournament->title      = 'API Demo - Embedding Brackets';
    $tournament->description = 'Simple API PHP Library demonstrating how to delete a tournament';
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