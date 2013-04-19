<?php
/**
 * Simple example demonstrating how you would embed brackets into your HTML, with some customized CSS properties
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