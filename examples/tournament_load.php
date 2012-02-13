<?php

/* @var $bb BinaryBeast */



/**
 * Example BinaryBeast API script
 * 
 * How to retrieve information about a tournament
 *  
 */

//Include the start_brackets_seeded exmaple so we have a bracket to work with (and teams obviously)
require_once('tournament_start.php');

//This one is pretty simple
$result = $bb->tournament_load($tourney_id);

?>

<table>
    <?php foreach($result->tourney_info as $key => $value): ?>

        <tr>
            <th><?php echo $key; ?></th>
            <td><?php echo $value; ?></td>
        </tr>
    
    <?php endforeach; ?>
</table>