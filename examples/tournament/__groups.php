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

/**
 * Allow examples to override the default of 1 group
 * @global int $group_count
 */
if (!isset($group_count)) $group_count = 1;

/**
 * Allow examples to override the default of 2 groups advancing from each group
 * @global int $teams_from_group
 */
if (!isset($teams_from_group)) $teams_from_group = 2;

/**
 * Allow examples to override the default of 6 teams created
 * @global int $teams_from_group
 */
if (!isset($teams_count)) $teams_count = 6;

/**
 * Allow examples to override the default double elimination mode
 * @global int $elimination
 */
if (!isset($elimination)) $elimination = 2;


/*
 * First - create a tournament with groups
 */
$tournament = $bb->tournament();
$tournament->title = 'API Demo - With Groups Groups';
$tournament->description = 'Simple API PHP Library demonstration - with group rounds';
$tournament->elimination = $elimination;
$tournament->type_id = BinaryBeast::TOURNEY_TYPE_CUP;

//Only need 1 group
$tournament->group_count        = $group_count;
$tournament->teams_from_group   = $teams_from_group;

//Group of $teams
for ($x = 0; $x < $teams_count; $x++) {
    $team = $tournament->team();
    $team->confirm();
    $team->display_name = 'Demo Player ' . ($x + 1);
}
//
if (!$tournament->save()) {
    var_dump(array('Error saving tournament', 'errors' => $bb->error_history));
    die();
}
//Start groups
if (!$tournament->start()) {
    var_dump(array('Error starting the groups', 'errors' => $bb->error_history));
    die();
}
