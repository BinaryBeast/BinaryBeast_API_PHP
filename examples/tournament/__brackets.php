<?php
/**
 * Creates an active double elimination bracket to keeps other examples DRY
 *
 * @filesource
 *
 * @package BinaryBeast
 * @subpackage Examples
 *
 * @version     1.0.0
 * @date        2013-04-13
 * @since       2013-04-13
 * @author      Brandon Simmons <contact@binarybeast.com>
 */

$path = str_replace('\\', '/', dirname(__FILE__)) . '/../../BinaryBeast.php';
require_once($path);
$bb = new BinaryBeast();
$bb->disable_ssl_verification();

/*
 * First - create a tournament with brackets
 */
$tournament = $bb->tournament();
$tournament->title = 'API Demo - Brackets';
$tournament->description = 'Simple API PHP Library demonstration - elimination only';
$tournament->elimination = BinaryBeast::ELIMINATION_DOUBLE;
//
for ($x = 0; $x < 16; $x++) {
    $team = $tournament->team();
    $team->confirm();
    $team->display_name = 'Demo Player ' . ($x + 1);
}
//
if (!$tournament->save()) {
    var_dump(array('Error saving tournament', 'errors' => $bb->error_history, 'results' => $bb->result_history));
    die();
}
if (!$tournament->start()) {
    var_dump(array('Error starting the brackets', 'errors' => $bb->error_history, 'results' => $bb->result_history));
    die();
}