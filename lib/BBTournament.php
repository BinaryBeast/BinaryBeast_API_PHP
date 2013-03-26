<?php

/**
 * Model object for a BinaryBeast Tournament
 * 
 * It can be used to <b>create</b>, <b>manipulate</b>, <b>delete</b>, and <b>list</b> BinaryBeast tournaments
 * 
 * 
 * ### Quick Examples and Tutorials ###
 * 
 * The following examples assume <var>$bb</var> is an instance of {@link BinaryBeast}
 * 
 * 
 * ## Create a New Tournament
 * 
 * Let's run through a quick example of how to create a new tournament using the {@link BinaryBeast::touranment()} factory method
 * 
 * Be sure to check out the available properties ({@see m#agicProperties}), they won't be covered in the example
 * 
 * The code below will create a 1v1 single elimination tournament, with the bronze / 3rd place round enabled
 * <code>
 *  $tournament                 = $bb->tournament();
 *  $tournament->title          = 'Hello world!';
 *  $tournament->elimination    = BinaryBeast::ELIMINATION_SINGLE;
 *  $tournament->bronze         = true;
 *  if(!$tournament->save()) {
 *      var_dump($bb->last_error);
 *  }
 * </code>
 * 
 * 
 * ### Configure Round Format
 * 
 * By round format, I mean the best_of value for each round in a bracket
 * 
 * Assume <var>$tournament</var> is a double elimination tournament
 * 
 * The example below will set ALL rounds in the winners' bracket to best of 3, and the finals with best of 5:
 * <code>
 *  foreach($tournament->rounds->winners as $round) {
 *      $round->best_of = 3;
 *  }
 *  $tournament->rounds->finals->best_of = 5;
 * </code>
 * 
 * Now we can use either {@link BBTournament::save_rounds()} or {@link BBTournament::save()} to submit the changes:
 * 
 * <b>Using {@link BBTournament::save_rounds()}</b>
 * <code>
 *  if(!$tournament->save_rounds()) {
 *      var_dump($tournament->error());
 *  }
 * </code>
 * <b>Using {@link BBTournament::save()}</b>
 * <code>
 *  if(!$tournament->save()) {
 *      var_dump($tournament->error());
 *  }
 * </code>
 * 
 * 
 * ### Add Teams to the Tournament
 * 
 * There are two ways to do it, but the optimal method is to use {@link BinaryBeast::team()}
 * 
 * You COULD use {@link BinaryBeast::team()}, then call {@link BBTeam::init()}, but doing it through
 * the tournament is faster and more error proof
 * 
 * 
 * Note: If {@link BinaryBeast::team()} returns <var>null</var>, check {@link BinaryBeast::last_error} for an explanation
 * 
 * The example below assumes <var>$tournament</var> is elimination tournament and hasn't started yet
 * 
 * Please see {@link BBTeam} documentation for a list of attributes and methods available in instances of BBTeam
 * 
 * <b>Add 8 confirmed teams
 * <code>
 *  for($x = 0; $x < 8; $x++) {
 *      $team = $tournament->team();
 *      $team->confirm();
 *      $team->display_name = 'New Confirmed Team ' . ($x + 1);
 *  }
 * </code>
 * <b>Add 3 unconfirmed teams</b>
 * <code>
 *  for($x = 0; $x < 3; $x++) {
 *      $team = $tournament->team();
 *      $team->unconfirm();
 *      $team->display_name = 'New Unonfirmed Team ' . ($x + 1);
 *  }
 * </code>
 * 
 * There are 3 ways to save a new team:
 * 
 * <b>1) Using {@link BBTournament::save()}</b>
 * <code>
 *  if(!$tournament->save()) {
 *      var_dump($bb->last_error);
 *  }
 * </code>
 * <b>2) Using {@link BBTournament::save_teams()}</b>
 * <code>
 *  if(!$tournament->save_teams()) {
 *      var_dump($bb->last_error);
 *  }
 * </code>
 * <b>3) Using {@link BBTeam::save()}</b>
 * 
 * <b>Caution: </b> this method requires that the tournament already be saved!
 * <code>
 *  foreach($tournament->teams as $team) {
 *      if(!$team->save()) {
 *          var_dump($bb->last_error);
 *      }
 *  }
 * </code>
 * 
 * 
 * ### Reporting Matches
 * 
 * First step in reporting matches, is to use {@link BBTournament::open_matches()}
 * to get a list of matches that need to be reported
 * 
 * {@link BBTournament::open_matches()} returns an array of {@link BBMatch} instances
 * 
 * Once you have a match, first call {@link BBMatch::set_winner()} to determine the winner, and then
 * you can call {@link BBTournament::save()}, {@link BBTournament::save_matches()}, or {@link BBMatch::report()}
 * to save it
 * 
 * Note: You can also use the "magic" property <var>$tournament->open_matches</var> to refer to the array directly
 * 
 * <b>Example using {@link BBTournament::save_matches()}</b>
 * <code>
 *  $match = $tournament->open_matches[0];
 *  $winner = $match->team2();
 *  $match->set_winner($winner);
 *  if(!$tournament->save_matches()) {
 *      var_dump($bb->last_error);
 *  }
 * </code>
 * <b>Example using {@link BBMatch::report()}</b>
 * <code>
 *  $match = $tournament->open_matches[0];
 *  $winner = $match->team2();
 *  $match->set_winner($winner);
 *  if(!$match->report()) {
 *      var_dump($bb->last_error);
 *  }
 * </code>
 * 
 * 
 * ### Starting Group Rounds
 * 
 * <b>Note: </b>Unfortunately for the moment, this library is limited to "random" group seeding
 * 
 * To start the group rounds, first you need an inactive tournament with {@link BBTournament::type_id} set to 1 
 * 
 * The example assumes you have a valid tournament with type_id = 1, and enough active teams to fill {@link BBTournament::group_count}
 * 
 * You can use the {@link BBHelper} class to verify that the tournament is ready
 * {@link BBHelper::tournament_can_start} will return either (bool)true, or a string error
 * <code>
 *  if(($error = BBHelper::tournament_can_start($tournament) !== true) {
 *      var_dump(array('start_error' => $error));
 *  }
 * </code>
 * 
 * <b>Example: start the group rounds</b>
 * <code>
 *  if(!$tournament->start()) {
 *      var_dump($bb->last_error);
 *  }
 * </code>
 * 
 * We can now even use BBHepler to verify it's now in group rounds using {@link BBHelper::tournament_in_group_rounds}
 * <code>
 *  if(!BBHelper::tournament_in_group_rounds($tournament)) {
 *      var_dump(array('Active-Groups expected...', 'tournament_status' => $tournament->status));
 *  }
 * </code>
 * 
 * Easy enough!
 * 
 * 
 * ### Starting Brackets - Random
 * 
 * Starting brackets with random positions is very simple
 * 
 * <b>Note: </b>You don't have to worry about freewins / bye's, they will added automatically
 * 
 * 
 * <b>Example: start bracket with random seeding</b>
 * The default seeding method is 'random', so all we have to do is call start(), very easy
 * <code>
 *  if(!$tournament->start()) {
 *      var_dump($bb->last_error);
 *  }
 * </code>
 * 
 * ### Starting Brackets - Manual
 * 
 * You also have the option of starting brackets, and manually defining all of the starting positions
 * 
 * 
 * To start with specific starting positions, you have to do two things:
 * 
 *  1) Set the first argument of save() to 'manual', or {@link BinaryBeast::SEEDING_MANUAL}
 * 
 *  2) Provide an array of either {@link BBTeam} instances, or tourney_team_id integers, in the exact order you want them to appear in the brackets
 * 
 * 
 * I won't go into too much detail here on how to setup $order, it's already documented: {@link BBTournament::start()}
 * 
 * 
 * <b>Note:</b> To define a freewin, just use the (int) 0 to indicate a freewin in that position
 * 
 * <b>First step: </b>Setup an array of teams in order
 * 
 * Let's assume we have 7 players, <var>$team1</var>, <var>$team2</var>, ..., <var>$team7</var>, and one freewin
 * <code>
 *  $order = array($team7, $team2, $team5, $team1, 0, $team3, $team4, $team6);
 * </code>
 * 
 * <b>Final step: </b>Execute {@link BBTournament::start()}:
 * <code>
 *  if(!$tournament->start(BinaryBeast::SEEDING_MANUAL, $order)) {
 *      var_dump($bb->error);
 *  }
 * </code>
 * 
 * Success!  Just for ease of mind, we can verify a few things:
 * 
 * <b>Use BBHelper to verify the tournament status:</b>
 * <code>
 *  if(!BBHelper::tournament_in_brackets($tournament)) {
 *      var_dump(array('Expected brackets!, 'found' => $tournament->status));
 *  }
 * </code>
 * 
 * <b>We can also verify position values</b>
 * Note: if <var>$team1</var> was NOT saved using a reference (aka <var>$team</var> = <var>&$tournament->team()</var>),
 * you may have to call {@link BBTeam::reload} to insure it has the latest values
 * <code>
 *  if($team7->position != 0) {
 *      var_dump(array('Team 7 position exptected to be 7!', 'position' => $team7->position));
 *  }
 *  if($team2->position != 1) {
 *      var_dump(array('Team 2 position exptected to be 7!', 'position' => $team7->position));
 *  }
 * </code>
 * etc etc...
 * 
 * 
 * ### Starting Brackets - Seeded
 * 
 * 'sports' and 'balanced' seeding work very similary, it's not within the scope
 * of this documentation to explain the difference
 * 
 * They work much like 'manual' - but instead of defining initial positions,
 * you're defining team rank
 * 
 * So if you want the top 3 ranks to be <var>$team7</var>, <var>$team3</var>, <var>$team1</var>, and randomize the rest,
 * just provide an array with those 3 teams, and it will fill in the rest with random
 * ranks
 * 
 * See start()'s documentation for more details: {@link BBTournament::start()}
 * 
 * <b>Example: Setup ranks only for top 3 teams, randomize the rest, and start with 'sports' seeding:</b>
 * <code>
 *  $order = array($team7, $team3, $team1);
 *  if(!$tournament->start(BinaryBeast::SEEDING_SPORTS, $order)) {
 *      var_dump($bb->last_error);
 *  }
 * </code>
 * 
 * ### Resetting brackets or Groups
 * 
 * If you need to reset the brackets or groups, it's very simple:
 * <code>
 *  if(!$tournament->reopen()) {
 *      var_dump($bb->last_error());
 *  }
 * </code>
 * 
 * 
 * ### Deleting the tournament
 * 
 * Be <b>VERY</b> careful with this! there's no going back!!
 * 
 * You can delete the tournament and all of its children however, with one quick
 * potentially catostrphically mistaken swoop:
 * <code>
 *  if(!$tournament->delete()) {
 *      var_dump($bb->last_error);
 *  }
 * </code>
 * 
 * ### Getting Teams 
 * 
 * Getting a list of teams within a tournament is simple, and there are a few ways to do it
 * 
 * You can use {@link BBTournament::teams()} to give you a simple list of EVERY team in the tournament, even
 * new ones you haven't saved yet
 * 
 * <b>Example: teams():</b>
 * <code>
 *  $teams = $tournament->teams();
 *  echo sizeof($teams) . ' teams found in the tournament';
 *  foreach($teams as $team) {
 *      //$team is a BBTeam instance, do whatever you want with it now
 *  }
 * </code>
 * 
 * You can also get team lists filtered by status
 * 
 * <b>Example: list only confirmed teams</b>
 * <code>
 *  $confirmed_teams = $tournament->confirmed_teams();
 *  echo sizeof($confirmed_teams) . ' teams have been confirmed in the tournament';
 * </code>
 * 
 * The same is true for banned, and unconfirmed
 * 
 * <b>Example: list only unconfirmed teams</b>
 * <code>
 *  $unconfirmed_teams = $tournament->unconfirmed_teams();
 *  echo sizeof($unconfirmed_teams) . ' teams have been unconfirmed in the tournament';
 * </code>
 * <b>Example: list only banned teams</b>
 * <code>
 *  $banned_teams = $tournament->banned_teams();
 *  echo sizeof($banned_teams) . ' teams have been banned from the tournament';
 * </code>
 * 
 * ### Listing
 * 
 * <b>Example: Load list of tournaments created by your account:</b>
 * 
 * Includes any tournaments you've marked as private {@link BBTournament::public},
 * as defined in the 3rd paramater
 * <code>
 *  $tournaments = $bb->tournament->list_my(null, 100, true);
 *  foreach($tournaments as $tournament) {
 *      echo '<a href="/my/path/to/viewing/a/tournament?id=' . $tournament->id . '">' . $tournament->title . '</a>';
 *  }
 * </code>
 * 
 * <b>Example: Load a filtered list of your tournaments, using the keyword 'starleague':</b>
 * 
 * Note: since we didn't define the 3rd paramater, private tournaments will NOT be included {@link BBTournament::public}
 * <code>
 *  $tournaments = $bb->tournament->list_my('starleague');
 *  foreach($tournaments as $tournament) {
 *      echo '<a href="/my/path/to/viewing/a/tournament?id=' . $tournament->id . '">' . $tournament->title . '</a>';
 *  }
 * </code>
 * 
 * <b>Example: Load a list of the most popular tournaments on BinaryBeast right now:</b>
 * <code>
 *  $tournaments = $bb->tournament->list_popular();
 *  foreach($tournaments as $tournament) {
 *      echo '<a href="' . $tournament->url . '">' . $tournament->title . '</a>';
 *  }
 * </code>
 * 
 * <b>Example: Load a list of the most popular Quake Live tournaments on BinaryBeast right now:</b>
 * <code>
 *  $tournaments = $bb->tournament->list_popular('QL');
 *  foreach($tournaments as $tournament) {
 *      echo '<a href="' . $tournament->url . '">' . $tournament->title . '</a>';
 *  }
 * </code>
 * 
 * 
 * ### Embedding your Tournament
 * 
 * There are two ways to do this
 * 
 * - Using {@link BBHelper::embed_brackets()} and {@link BBHelper::embed_groups()}
 * - Using {@link BBTournament::embed()}
 * 
 * 
 * <b>Example - Brackets - Using BBHelper</b>
 * 
 * Review the documentation in {@link BBHelper::embed_tournament()} to see the possible arguments
 * <code>
 *  BBHelper::embed_brackets($tournament);
 * </code>
 * 
 * 
 * <b>Example - Groups - Using BBHelper</b>
 * 
 * Review the documentation in {@link BBHelper::embed_tournament_groups()} to see the possible arguments
 * <code>
 *  BBHelper::embed_groups($tournament);
 * </code>
 * 
 * <b>Example - Groups - Using BBTournament</b>
 * 
 * Review the documentation in {@link BBTournament::embed_groups()} to see the possible arguments
 * <code>
 *  $tournament->embed_groups();
 * </code>
 * 
 * <b>Example - Brackets - Using BBTournament</b>
 * 
 * Review the documentation in {@link BBTournament::embed()} to see the possible arguments
 * <code>
 *  $tournament->embed();
 * </code>
 * 
 * 
 * 
 * 
 * ### More...
 * 
 * Those are the basics, but there's a lot more to it - feel free to look through
 * the documtnation to see what else is available
 * 
 * 
 * You should also review the documentation for {@link BBTeam}, {@link BBRound}, {@link BBMatch}, and {@link BBMatchGame}
 * 
 * 
 * 
 * 
 * 
 * 
 * @property boolean $title
 *      Tournament title
 * 
 * @property boolean $public
 * <b>Default: true</b><br />
 * If true, this tournament can be found in the public lists + search
 * 
 * @property-read string $url
 *  The URL to this tournament hosted on BinaryBeast.com
 * 
 * @property-read string $status
 * <b>Read Only</b><br />
 * The current tournament status<br />
 * <b>Building:</b> New tournament, still accepting signups, not accepting player-confirmations<br />
 * <b>Confirmations:</b> Getting ready to start, still accepting signups, now accepting player-confirmations<br />
 * <b>Active:</b> Brackets<br />
 * <b>Active-Groups:</b> Round robin group rounds<br />
 * <b>Active-Brackets:</b> Brackets after group-rounds <br />
 * <b>Complete:</b> Final match has been reported<br />
 * 
 * @property-read int $live_stream_count
 * <b>Read Only</b><br />
 * Number of streams associated with this tournament that are currently streaming
 * 
 * @property-read string $game
 * <b>Read Only</b><br />
 * Name of this tournament's game, determined from {@link BBTournament::$game_code}
 * 
 * @property-read string $network
 * <b>Read Only</b><br />
 * Name of the network for this game's tournament
 * 
 * @property string $game_code
 * <b>Default: HotS (StarCraft 2: Heart of the Swarm)</b><br />
 * Unique game identifier, <br />
 * Use {@link BBGame::search()} to search through our games list, and get the game_code values
 * 
 * @property int $type_id
 * <b>Default: 0 (Elimination Brackets)</b><br />
 * Tournament type - defines the stages of the tournament<br />
 * Use {@link BinaryBeast::TOURNEY_TYPE_BRACKETS} and {@link BinaryBeast::ELIMINATION_SINGLE}<br />
 * <b>warning: you cannot change this setting after the tournament has started, you'll have to {@link BBTournament::reopen()} first</b>
 * 
 * @property int $elimination
 * <b>Default: 1 (Single Elimination)</b><br />
 * Elimination mode, single or double, simply 1 for single 2 for double<br />
 * but you can also use {@link BinaryBeast::ELIMINATION_SINGLE} and {@link BinaryBeast::ELIMINATION_DOUBLE}
 * 
 * @property boolean $bronze
 * <b>Default: false</b><br />
 * <b>Single Elimination Brackets Only</b><br />
 * Set this true to enable the bronze bracket<br />
 * aka the 3rd place decider
 * 
 * @property int $team_mode
 * <b>Default: 1 (1v1)</b><br />
 *  Number of players per team<br />
 *  1 = a normal 1v1<br />
 *  Anything else indicates this is a team-based game<br />
 * 
 * @property int $group_count
 * <b>Default: 1</b><br />
 * <b>Group Rounds Only</b><br />
 * Number of groups to setup when starting round robin group rounds
 * 
 * @property int $teams_from_group
 * <b>Default: 2</b><br />
 * <b>Group Rounds Only</b><br />
 * Number of participants from each group that advance to the brackets
 * 
 * @property string $location
 *      Generic description of where players should meet / coordinate (ie a bnet channel)
 * 
 * @property int $max_teams
 * <b>Default: 32</b><br />
 * Maximum number of participants allowed to confirm their positions
 * 
 * 
 * @property int $replay_uploads
 * <b>Default: 1 (Optional)</b><br />
 * Replay upload mode<br />
 * {@link BinaryBeast::REPLAY_UPLOADS_OPTIONAL}<br />
 * {@link BinaryBeast::REPLAY_UPLOADS_DISABLED}<br />
 * {@link BinaryBeast::REPLAY_UPLOADS_MANDATORY}<br />
 * 
 * @property int $replay_downloads
 * <b>Default: 1 (Enabled)</b><br />
 * Replay download mode<br />
 * {@link BinaryBeast::REPLAY_DOWNLOADS_ENABLED}<br />
 * {@link BinaryBeast::REPLAY_DOWNLOADS_DISABLED}<br />
 * {@link BinaryBeast::REPLAY_DOWNLOADS_POST_COMPLETE}<br />
 * 
 * @property string $description
 * Generic description of the tournament<br />
 * Plain text only - no html allowed
 * 
 * @property string $hidden
 * Special hidden (as you may have guessed) values that you can use to store custom data<br />
 * The recommended use of this field, is to store a json_encoded string that contains your custom data
 * 
 * @property string $player_password
 * <b>Strongly recommended</b><br />
 * If set, players are required to provide this password before allowed to join<br />
 * Use {@link BBTournament::generate_player_password()}
 * 
 * @property BBTeam[] $teams
 * <b>Alias for {@link BBTournament::teams()}</b><br />
 * An array of teams in this tournament
 * 
 * @property BBTeam[] $confirmed_teams
 * <b>Alias for {@link BBTournament::confirmed_teams()}</b><br />
 * An array of confirmed teams in this tournament
 * 
 * @property BBTeam[] $unconfirmed_teams
 * <b>Alias for {@link BBTournament::unconfirmed_teams()}</b><br />
 * An array of unconfirmed teams in this tournament
 * 
 * @property BBTeam[] $banned_teams
 * <b>Alias for {@link BBTournament::banned_teams()}</b><br />
 * An array of banned teams in this tournament
 * 
 * @property BBRoundObject $rounds
 * <b>Alias for {@link BBTournament::rounds()}</b><br />
 * An object containing arrays of BBRound objects <br />
 *  each array is keyed by the the simple bracket label:<br />
 *  groups, winners, losers, bronze, finals<br />
 * 
 * @property BBMatch[] $open_matches
 * <b>Alias for {@link BBTournament::open_matches()}</b><br />
 * An array of matches in this tournament that still need to be reported
 * 
 * 
 * @package BinaryBeast
 * @subpackage Model
 * 
 * @version 3.0.0
 * @date 2013-03-17
 * @author Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
class BBTournament extends BBModel {

    /* Model services / Tournament manipulation **/
    const SERVICE_LOAD                      = 'Tourney.TourneyLoad.Info';
    const SERVICE_CREATE                    = 'Tourney.TourneyCreate.Create';
    const SERVICE_UPDATE                    = 'Tourney.TourneyUpdate.Settings';
    const SERVICE_DELETE                    = 'Tourney.TourneyDelete.Delete';
    const SERVICE_START                     = 'Tourney.TourneyStart.Start';
    const SERVICE_REOPEN                    = 'Tourney.TourneyReopen.Reopen';
    const SERVICE_ALLOW_CONFIRMATIONS       = 'Tourney.TourneySetStatus.Confirmation';
    const SERVICE_DISALLOW_CONFIRMATIONS    = 'Tourney.TourneySetStatus.Building';
    /* Listing / searching **/
    const SERVICE_LIST                      = 'Tourney.TourneyList.Creator';
    const SERVICE_LIST_POPULAR              = 'Tourney.TourneyList.Popular';
    const SERVICE_LIST_OPEN_MATCHES         = 'Tourney.TourneyLoad.OpenMatches';
    /* Child listing / manipulation**/
    const SERVICE_LOAD_MATCH                = 'Tourney.TourneyLoad.Match';
    const SERVICE_LOAD_TEAM_PAIR_MATCH      = 'Tourney.TourneyMatch.LoadTeamPair';
    const SERVICE_LOAD_TEAMS                = 'Tourney.TourneyLoad.Teams';
    const SERVICE_LOAD_ROUNDS               = 'Tourney.TourneyLoad.Rounds';
    const SERVICE_UPDATE_ROUNDS             = 'Tourney.TourneyRound.BatchUpdate';
    const SERVICE_UPDATE_TEAMS              = 'Tourney.TourneyTeam.BatchUpdate';

    /*
     * Caching settings
     */
    const CACHE_OBJECT_TYPE				= BBCache::TYPE_TOURNAMENT;
    /**
     * Cache the tournament info for 60 minutes
     * @var int
     */
	const CACHE_TTL_LOAD				= 60;
    /**
     * Cache list results for 60 minutes
     * @var int
     */
    const CACHE_TTL_LIST				= 60;
    /**
     * Cache team list results for 30 minutes
     * @var int
     */
    const CACHE_TTL_TEAMS				= 30;
    /**
     * Cache match load results for 10 minutes
     * @var int
     */
    const CACHE_TTL_MATCH				= 10;
    /**
     * Cache round format listing results for 60 minutes
     * @var int
     */
    const CACHE_TTL_ROUNDS				= 60;
    /**
     * Cache queries for unplayed / open matches for 20 minutes
     * @var int
     */
    const CACHE_TTL_LIST_OPEN_MATCHES	= 20;


    /*
     * Callbacks
     * @todo Implement these callbacks
     */


    /**
     * Callback event id for when a match has been reported
     * @var int
     */
    const CALLBACK_MATCH_REPORTED = 6;
    /**
     * Callback event id for when a match has been unreported
     * @var int
     */
    const CALLBACK_MATCH_UNREPORTED = 7;
    /**
     * Callback event id for when the tournament is deleted
     * @var int
     */
    const CALLBACK_DELETED = 8;
    /**
     * Callback event id for when the group rounds begin
     * @var int
     */
    const CALLBACK_GROUPS_STARTED = 1;
    /**
     * Callback event id for when the brackets begin
     * @var int
     */
    const CALLBACK_BRACKETS_STARTED = 2;
    /**
     * Callback event id for when the final match is reported, and the tournament finishes
     * @var int
     */
    const CALLBACK_COMPLETE = 3;
    /**
     * Callback event id for when a team is added to the tournament
     * @var int
     */
    const CALLBACK_TEAM_ADDED = 4;
    /**
     * Callback event id for when a team is removed from the tournament
     * @var int
     */
    const CALLBACK_TEAM_REMOVED = 4;

    /**
     * Array of participants within this tournament
     * @var BBTeam[]
     */
    private $teams;

    /**
     * Object containing format for each round
     * Keyed by bracket name, each of which is an array of BBRound objects
     * @var BBRoundObject
     */
    private $rounds;

    /**
     * This tournament's ID, using BinaryBeast's naming convention
     * @var string
     */
    public $tourney_id;
    /**
     * The tournament id in a standardized naming convention
     * @var string
     */
    public $id;

    /**
     * Cache results from loading open matches from the API
     * @var BBMatch[]
     */
    private $open_matches;

    /**
     * The name BinaryBeast API uses for this object's unique ID - used by {@link BBModel::load()} when determining the local <var>$id</var>
     * @var string
     */
    protected $id_property = 'tourney_id';

    /**
     * The key to used for extracting data from the API result
     * @var string
     */
    protected $data_extraction_key = 'tourney_info';

    /**
     * Default values for a new tournament
     * @var array
     */
    protected $default_values = array(
        'title'             => 'PHP API Test',
        'public'            => true,
        'game_code'         => 'HotS',
        'type_id'           => BinaryBeast::TOURNEY_TYPE_BRACKETS,
        'elimination'       => BinaryBeast::ELIMINATION_SINGLE,
        'bronze'            => false,
        'team_mode'         => 1,
        'group_count'       => 0,
        'teams_from_group'  => 2,
        'location'          => null,
        'max_teams'         => 32,
        'replay_uploads'    => BinaryBeast::REPLAY_UPLOADS_OPTIONAL,
        'replay_downloads'  => BinaryBeast::REPLAY_DOWNLOADS_ENABLED,
        'description'       => '',
        'hidden'            => null,
        'player_password'   => null,
    );

    /**
     * Read only properties
     * @var array
     */
    protected $read_only = array('status', 'tourney_id');

    /**
     * Overload BBModel::__set so we can prevent setting the type_id of an active tournament
     * 
     * The API would have prevented it, but this way it doesn't even update $data, so developers don't have to 
     *  wait to save() to realize the type_id didn't change
     * 
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        if(strtolower($name) == 'type_id') {
            if(BBHelper::tournament_is_active($this)) return;
        }

        parent::__set($name, $value);
    }

    /**
     * Returns an array of players/teams/participants within this tournament
     * 
     * This method takes advantage of BBModel's __get, which allows us to emulate public values that we can
     * intercept access attempts, so we can execute an API request to get the values first
     * 
     * WARNING:  Be careful when evaluating the result of this method, if you try to evaluate the result
     * as a boolean, you may be surprised when an empty array is returned making it look like the service failed
     * 
     * If you want to check for errors with this method, make sure you do it like this:
     *      if($tournament->teams() === false) {
     *          OH NO!!!
     *      }
     * 
     * @param boolean   $ids set true to return array of ids only
     * @param array     $args   any additonal arguments to send with the API call
     * 
     * @return BBTeam[]|null
     *      Null is returned if there was an error with the API request
     */
    public function &teams($ids = false, $args = array()) {

        //Already instantiated
        if(!is_null($this->teams)) {
            //Requested an array of ids
            if($ids) {
                $teams = array();
                foreach($this->teams as $x => &$team) {
                    $teams[$x] = $team->id;
                }
                return $teams;
            }

            return $this->teams;
        }
        
        //Load from the API
        $result = $this->call(self::SERVICE_LOAD_TEAMS, array_merge($args, array(
                'tourney_id' => $this->tourney_id,
                'full' => true
            )),
            self::CACHE_TTL_TEAMS, self::CACHE_OBJECT_TYPE, $this->id
        );

        //Fail!
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
            return $this->bb->ref(null);
        }

		//Cast each returned team as a BBTeam, and initalize
        $this->teams = array();
		foreach($result->teams as $team) {
            $team = $this->bb->team($team);
            $team->init($this, false);
            $this->teams[] = $team;
        }

		//Success!
		return $this->teams;
    }

    /**
     * Returns an array of confirmed teams in this tournament
     * 
     * @param boolean   $ids        set true to return array of ids only
     * @return BBTeam[]
     */
    public function &confirmed_teams($ids = false) {
        return $this->filter_teams_by_status($ids, 1);
    }
    /**
     * Returns an array of unconfirmed teams in this tournament
     * 
     * @param boolean   $ids        set true to return array of ids only
     * @return BBTeam[]
     */
    public function &unconfirmed_teams($ids = false) {
        return $this->filter_teams_by_status($ids, 0);
    }
    /**
     * Returns an array of banned teams in this tournament
     * 
     * @param boolean   $ids        set true to return array of ids only
     * @return BBTeam[]
     */
    public function &banned_teams($ids = false) {
        return $this->filter_teams_by_status($ids, -1);
    }
    
    /**
     * Used by confirmed|banned|unconfirmed_teams to return an array of teams from $teams() that have a matching
     *  $status value

     * @param boolean   $ids        Return just ids if true
     * @param int       $status     Status value to match
     *      Null to return ALL teams
     * @return BBTeam[]
     */
    private function &filter_teams_by_status($ids, $status) {
        //Use teams() to guarantee up to date values, and so we can return false if there are errors set by it
        if(is_null($teams = &$this->teams())) return $this->bb->ref(false);

        //Initialize the output
        $filtered = array();

        //Simply loop through and return teams with matching $status
        foreach($teams as &$team) {
            if($team->status == $status || is_null($status)) {
                $filtered[] = $ids ? $team->id : $team;
            }
        }

        //Qapla!
        return $filtered;
    }
    /**
     * Used internally after a major change to refresh our list of teams, in hopes of
     *  getting changed data
     * 
     * Refresh our teams array, and deletes our cache of open_matches
     * 
     * @return void
     */
    private function on_major_update() {
        //Clear ALL cache for this specific tournament
        $this->clear_id_cache();

        //Flag reloads for any existing teams, in case any stray references exist after removing child classes
        if(is_array($this->teams)) {
            foreach($this->teams as &$team) {
                $team->flag_reload();
            }
        }

        //GOGOGO!
        $this->rounds = null;
        $this->teams = null;
        $this->teams();
        $this->open_matches = null;
    }
    
    /**
     * Delete any cached API results for team opponents, and open match listing 
     * 
     * Also resets the local open_matches() array, so next time it's accessed, it will
     *  be set by a fresh API call
     * 
     * @return boolean
     */
    public function clear_match_cache() {
        //derp
        if(is_null($this->id)) return false;

        //If reporting multiple matches, we want to hold off on this until all matches have been reported
        if($this->iterating) return false;

        //Delete the cache for open matches for this tournament ID, and teams (since wins / lbwins etc have changed)
        $this->clear_id_cache(array(self::SERVICE_LIST_OPEN_MATCHES, self::SERVICE_LOAD_TEAMS));
        
        //Delete ALL team-opponent/match/elimination cache, unfortunately there's no simple way to limit it to this tournament
        $this->clear_service_cache(array(
            BBTeam::SERVICE_LIST_OPPONENTS,
            BBTeam::SERVICE_GET_LAST_MATCH,
            BBTeam::SERVICE_GET_OPPONENT,
        ));

        //Clear the open_matches array, to force a fresh query next time it's accessed
        $this->open_matches = null;

        return true;
    }

    /**
     * Returns an object containing the format for each round within this tournament, in the following layout:
     * {winners => [0 => {best_of=>3, map_id=>1234, wins_needed=>2, map=>Shakuras}, 1 =>{...}...], losers =>... finals =>... bronze =>... groups=>...}
     * 
     * This method takes advantage of BBModel's __get, which allows us to emulate public values that we can
     * intercept access attempts, so we can execute an API request to get the values first
     * 
     * 
     * Note: for new tournaments, you'll want to save() the tournament before attempting to setup the round format
     * 
     * @return BBRoundObject|boolean      False if it fails for any reason
     */
    public function &rounds() {
        //Already instantiated
        if(!is_null($this->rounds)) return $this->rounds;

        //New tournaments must be saved before we can start saving child data
        if(is_null($this->tourney_id)) {
            return $this->bb->ref(
                $this->set_error('Please execute save() before manipulating rounds or teams')
            );
        }

        //Ask the API for the rounds.  By default, this service returns every an array with a value for each bracket
        $result = $this->call(self::SERVICE_LOAD_ROUNDS, array('tourney_id' => $this->tourney_id), self::CACHE_TTL_ROUNDS, self::CACHE_OBJECT_TYPE, $this->id);

        //Error - return false and save the result 
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
            return $this->bb->ref($this->set_error($result));
        }

		//Initialize the rounds object, use BBHelper to give us the available brackets for this tournament
		$this->rounds = (object)BBHelper::get_available_brackets($this, true, true);

        //Initialize each returned round, and store it into local propeties
        foreach($result->rounds as $bracket => &$rounds) {
            //key each round by the round label
            $bracket_label = BBHelper::get_bracket_label($bracket, true);

			//Only import rounds for relevent brackets
			if(!isset($this->rounds->$bracket_label)) continue;

            //Save each new BBRound after initializing it
            foreach($rounds as $round => &$format) {
                $new_round = $this->bb->round($format);
                $new_round->init($this, $bracket, $round);
				$this->rounds->{$bracket_label}[] = $new_round;
            }
        }

        //Success! return the result
        return $this->rounds;
    }

    /**
     * Save the tournament - overloads BBModel::save() so we can 
     * check to see if we need to save rounds too
     * 
     * @param boolean   $return_result      ignored
     * @param array     $child_args         ignored
     * @return string       The id of this tournament to indicate a successful save, false if something failed
     */
    public function save($return_result = false, $child_args = null) {

        //Save settings first
        $result = $this->save_settings(false);

        //Oh noes!! save failed, so return false - developer should check the value of $bb->last_error
        if(!$result) return false;

		//Now save any new/changed teams and rounds
		if(!$this->save_rounds())	return false;
		if(!$this->save_teams())	return false;
		if(!$this->save_matches())	return false;

		//clear ALL cache for this tournament id
		$this->clear_id_cache();

        //Success! Return the original save() result (if this tour is new, it'll give us the tourney_id)
        return $result;
    }
    /**
     * Save the tournament without updating any children
     * 
     * @param boolean $settings_only - true by default
     */
    public function save_settings($settings_only = true) {
        //If saving a new touranment, pass control to save_new
        if(is_null($this->id)) {
            return $this->save_new($settings_only);
        }

        //Let BBModel::save handle it, but request the return so that we can import the new tourney_info
        if(is_array($result = parent::save(true, array('dump' => true))) ) {
            $this->import_values($result);
        }
        else if($result === false) return false;

        //Success!
        return $this->id;
    }
    /**
     * If creating a new tournament, we want to make sure that when the data is sent
     * to the API, that we request return_data => 2, therefore asking BinaryBeast to send
     * back a full TourneyInfo object for us to import all values that may have been
     * generated on BB's end
     * 
     * @param boolean $settings_only        false by default - can be used to skip saving children (BBTeams)
     * 
     * @return string       The new tourney_id for this object
     */
    private function save_new($settings_only = false) {
        //return_data => 2 asks BinaryBeast to include a full tourney_info dump in its response
        $args = array('return_data' => 2);

        //If any teams have been added, include them too - the API can handle it
        if(!$settings_only) {
            $changed_teams = $this->get_changed_children('BBTeam');
            $teams = array();
            if(sizeof($changed_teams) > 0) {
                foreach($changed_teams as $team) $teams[] = $team->data;
            }
            if(sizeof($teams) > 0) $args['teams'] = $teams;
        }

        //Let BBModel handle it from here - but ask for the api respnose to be returned instead of a generic boolean
        if(!$result = parent::save(true, $args)) return false;

        //OH NOES!
        if($result->result !== 200) return false;

        /**
         * Import the new data, and save the ID
         */
        $this->import_values($result);
        $this->set_id($result->tourney_id);

        //Use the api's returned array of teams to update to give each of our new teams its team_id
        if(!$settings_only) {
            if(isset($result->teams)) {
                if(is_array($result->teams)) {
                    if(sizeof($result->teams) > 0) {
                        $this->iterating = true;
                        foreach($result->teams as $x => $team) {
                            $result = $this->teams[$x]->import_values($team);
                            $this->teams[$x]->set_id($team->tourney_team_id);
                        }
                        $this->iterating = false;
                    }
                }
            }

            //Reset count of changed children
            $this->reset_changed_children('BBTeam');
        }

        //Success!
        return $this->id;
    }

    /**
     * Update all rounds that have had any values changed in one go
     * 
     * You can either call this directly (if for some reason you don't yet want touranmetn changes saved)
     * or call save(), which will save EVERYTHING, including tour data, teams, and rounds
     * 
     * @return boolean
     */
    public function save_rounds() {

        //New tournaments must be saved before saving teams
        if(is_null($this->id)) return $this->set_error('Can\t save teams before saving the tournament!');

        //Get a list of changed rounds
        $rounds = &$this->get_changed_children('BBRound');
		
        //Nothing has changed, just return true
        if(sizeof($rounds) == 0) return true;

        //Compile values into the format expected by the API - one array for each value, keyed by bracket, indexed by round
        $format = array();

        foreach($rounds as &$round) {
            //Bracket value initailized?
            if(!isset($format[$round->bracket])) $format[$round->bracket] = array(
                'maps'      => array(),
                'map_ids'   => array(),
                'dates'     => array(),
                'best_ofs'  => array(),
            );

            //Add it to the queue!
            $format[$round->bracket]['maps'][$round->round]         = $round->map;
            $format[$round->bracket]['map_ids'][$round->round]      = $round->map_id;
            $format[$round->bracket]['dates'][$round->round]        = $round->date;
            $format[$round->bracket]['best_ofs'][$round->round]     = $round->best_of;
        }

        //Loop through the results, and call the API once for each bracket with any values in it
        foreach($format as $bracket => &$bracket_rounds) {
            //Determine the arguments for this bracket
            $args = array_merge($bracket_rounds, array(
                'tourney_id'    => $this->tourney_id,
                'bracket'       => $bracket,
            ));

            //GOGOGO! store the result each time
            $result = $this->call(self::SERVICE_UPDATE_ROUNDS, $args);

            //OH NOES!
            if($result->result != BinaryBeast::RESULT_SUCCESS) {
                return $this->set_error($result);
            }
        }

        /**
         * If we've gotten this far, that means everything updated!!
         * Last step is to reset each round and list of changed rounds
         * 
         * We waited to do this because we wouldn't want to clear the queue
         * before insuring that we submitted the changes successfully
         */
        foreach($rounds as &$round) {
            $round->sync_changes();
        }

        //Reset our list of changed rounds, and return true, yay!
        $this->reset_changed_children('BBRound');
        return true;
    }
    /**
     * Overrides BBModel::reset() so we can define the $teams array for removing unsaved teams
     */
    public function reset() {
        //BBModel's default action first
        parent::reset();

        //Now let BBModel remove any unsaved teams from $this->teams
        $this->remove_new_children($this->teams);
    }
    
    /**
     * Update all teams/participants etc that have had any values changed in one go
     * 
     * You can either call this directly (if for some reason you don't yet want touranmetn changes saved)
     * or call save(), which will save EVERYTHING, including tour data, teams, and rounds
     * 
     * @return boolean
     */
    public function save_teams() {
		
        //New tournaments must be saved before saving teams
        if(is_null($this->id)) return $this->set_error('Can\t save teams before saving the tournament!');

        //Get a list of changed teams
        $teams = &$this->get_changed_children('BBTeam');

        //Nothing has changed, just return true
        if(sizeof($teams) == 0) return true;

        /**
         * Using the array of tracked / changed teams, let's compile
         *  a couple arrays of team values to send to the API
         * 
         * Initialize the two team arrays: new + update
         */
        $update_teams = array();
        $new_teams = array();

        /**
         * GOGOGO!
         */
        foreach($teams as &$team) {
            /**
             * New team - get all default + new values and add to $new_teams
             */
            if(is_null($team->id)) {
                $new_teams[] = $team->data;
            }
            /**
             * Existing team - get only values that are new, and key by team id
             */
            else {
                $update_teams[$team->tourney_team_id] = $team->get_changed_values();
            }
        }

        //Send the compiled arrays to the API for batch update/insert
        $result = $this->call(self::SERVICE_UPDATE_TEAMS, array(
            'tourney_id'        => $this->id,
            'teams'             => $update_teams,
            'new_teams'         => $new_teams
        ));
        //Oh noes!
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
            return $this->set_error($result);
        }

        /**
         * Tell all team instances to synchronize their settings
         */
        $new_id_index = 0;
		$this->iterating = true;
        foreach($teams as &$team) {

            //Tell the tournament to merge all unsaved changed into $data
            $team->sync_changes();

            /**
             * For new tournaments, make sure they get the new team_id
             */
            if(is_null($team->id)) {
                $team->set_id($result->team_ids[$new_id_index]);
                ++$new_id_index;
            }
        }
		$this->iterating = false;

        //Clear the list of teams with changes
        $this->reset_changed_children('BBTeam');

        //Success!
        return true;
    }
	/**
	 * Submit changes to any matches that have pending changes
	 * 
     * @param boolean $report       true by default - you can set this to false to ONLY save existing matches, and not to report()
     *  and now ones
     * 
	 * @return boolean
	 */
	public function save_matches($report = true) {
		$matches = &$this->get_changed_children('BBMatch');

        //Set the iteration flag to avoid the open_matches list being reset before we're done reporting all the matches
        $this->iterating = true;

        //Remember if any matches were actually reported, so that we can reset open_matches after we finish looping through each match
        $reported = false;

        //Simply call save() on each match
		foreach($matches as &$match) {
            //So we can set $reported
            $new = $match->is_new();

            if(!$report) {
                if(is_null($match->id)) {
                    continue;
                }
            }
            $match->save();

            //If reporting the match, set $reported flag
            if(!$report) {
                if($new) {
                    $reported = !$match->is_new();
                }
            }
        }

        //No longer iterating
        $this->iterating = false;

        //If we reported any matches, execute clear_match_cache now
        if($report) $this->clear_match_cache();

		return true;
	}

    /**
     * Load a list of tournaments created by the user of the current api_key
     * 
     * Note: each tournament is actually instantiated as a new BBTournament class, so you 
     * can update / delete them in iterations etc etc
     * 
     * @param string $filter        Optionally search through your tournaments using a simple filter
     * @param int    $limit         Number of results returned
     * @param bool   $private       false by default - set this to true if you want your private tournaments included
     * @return BBTournament[]
     */
    public function list_my($filter = null, $limit = 30, $private = true) {
        return $this->get_list(self::SERVICE_LIST, array(
            'filter'    => $filter,
            'page_size' => $limit,
            'private'   => $private,
        ), 'list', 'BBTournament');
    }

    /**
     * Returns a list of popular tournaments
     * 
     * However since this service is loading public tournaments, that means we likely
     *      won't have access to edit any of them
     * 
     * Therefore, all tournaments returned by this service are simple data objects
     * 
     * @param string $game_code
     *      You have the option of limiting the tournaments returned by context of a specific game,
     *      In otherwords for example game_code QL will ONLY return the most popular games that are using
     *      Quake Live
     * @param int $limit            Defaults to 10, cannot exceed 100
     * @return BBTournament[]
     */
    public function list_popular($game_code = null, $limit = 10) {
        return $this->get_list(self::SERVICE_LIST_POPULAR, array(
            'game_code'     => $game_code,
            'limit'         => $limit
        ), 'list');
    }

    /**
     * Remove a child class from this team - like a BBTeam
     * 
     * For BBTeam, we pass it thorugh $team() to avoid any mistakes caused by reloading / changed data, we base the 
     *  search purely on tourney_team_id
     * 
     * @param BBModel $child
     * @param BBModel[] $children
     */
    public function remove_child(BBModel &$child, $preserve = true) {
        if($child instanceof BBTeam) {
            //Rely on team() to insure that even if changed, that we at LEAST get the correct reference using team_id
            if(!is_null($team = &$this->team($child))) {
                return $this->remove_child_from_list($team, $this->teams(), $preserve);
            }
            return false;
        }
        if($child instanceof BBMatch) {
            //Like team(), we use match() to standardize, in case the input has changed from our cached version
            if(!is_null($match = &$this->match($child))) {
                return $this->remove_child_from_list($child, $this->open_matches(), $preserve);
            }
            return false;
        }
    }

    /**
     * This method can be used to generate a random password that 
     * people would be required to provide before joining your tournament
     * 
     * It's a nice way to insure that only YOU can add playesr through your API
     * 
     * Warning: if you enable auto_save, any exiting values that you've already set
     * will also be sent to the API as update values
     * 
     * @param bool $auto_save       Automatically save the new value? 
     * @return $this->save(); if auto_save, null otherwise
     */
    public function generate_player_password($auto_save = true) {
        $this->player_password = uniqid();
        if($auto_save) return $this->save();
    }
    
    /**
     * Add a BBTeam object to this tournament
     * 
     * The team must NOT have an ID, and must not
     *  have a tournament associated with it yet, 
     *  (unless that tournament is this tournament)
     * 
     * @param BBTeam $team
     * @return int          The team's new index in $this->teams()
     *      Can return false, so you may want to check $add_team === false
     */
    public function add_team(BBTeam $team) {
        //We can't add new players to an active-tournament
        if(BBHelper::tournament_is_active($this)) {
            return $this->bb->ref($this->set_error('You cannot add players to active tournaments!!'));
        }

        /**
         * Derp - already part of this tournament, just return true
         * @todo this needs to return the index of the existing team
         */
        if(in_array($team, $this->teams())) return true;

        //Team already has an ID
        if(!is_null($team->id)) return $this->set_error('That team already has a tourney_team_id, it cannot be added to another tournament');

        //Team already has a tournament - make sure it's THIS tournament
        if(!is_null($tournament = &$team->tournament())) {
            if($tournament != $this) {
                return $this->set_error('Team already belongs to another tournament, it cannot be added to this one');
            }
        }

        //At this point we can proceed, so associate this tournament with the team
        else $team->init($this, false);

        //add it to the list!
        $key = sizeof($this->teams);
        $this->teams[$key] = $team;

        //Flag changes
        $this->flag_child_changed($this->teams[$key]);

        //Success! return the key/index
        return $key;
    }

    /**
     * Either returns a new BBTeam to add to the tournament, or returns a reference to an existing one
     * 
     * This method will return a value of false if the tournament is already active!
     * 
     * The secondary use of this method is to validate a team argument value, allowing either
     *      the team's id or the team object itself to be used
     *      this method will convert the id to a BBTeam if necessary, and verify that
     *      it belongs to this tournament
     * 
     * A cool tip: this method can actually be used to pre-define a list of players in a new tournaemnt, before
     *      you even create it :)
     * 
     * Note: ALL this method does is return a new BBTeam! it does NOT send it to the API and save it!
     * 
     * However, it is automatically added to this tournament's save queue, so as soon as you $tour->save(), it will be added
     * If you want to avoid this, you must call $team->delete()
     * 
     * Assuming $team = $bb->player|participant; or $team = $bb->team|player|participant(); or $team = $bb->new_team|new_player|new_participant()
     * Next, configure the team: $team->display_name = 'blah'; $team->country_code = 'NOR'; etc...
     * 
     * Now there are three ways to actually save the team and send it to the API:
     * 1) $team->save()
     * 2) $bb->save_teams();
     * 3) $bb->save();
     * 
     * 
     * 
     * The secondary use of this method is for retrieving a reference to the BBTeam object of
     *      team that you know the ID of, just pass in the tourney_team_id
     * 
     * 
     * 
     * @param int $id       Optionally attempt to retrieve a reference to the BBTeam of an existing team
     * 
     * @return BBTeam|null       Returns null if invalid
     */
    public function &team($id = null) {

        //If validating an input, make sure the teams array is initalized
		if(!is_null($this->id)) {
			if($this->teams() === false) {
				return $this->bb->ref(null);
			}
		}
        //If it's a new object, allow devs to add teams before save(), so we need to make sure $teams is initialized
		else if(is_null($this->teams)) $this->teams = array();

        /**
         * Team() can be used to validate a team input, and we rely on 
         *  BBModel::get_child for that
         */
        if(!is_null($id)) {
            return $this->get_child($id, $this->teams);
        }

        //We can't add new players to an active-tournament
        if(BBHelper::tournament_is_active($this)) {
            $this->set_error('You cannot add players to active tournaments!!');
            return $this->bb->ref(null);
        }

        //Instantiate, and associate it with this tournament
        $team = $this->bb->team();
        $team->init($this, false);

        //use add_team to add it to $teams - it will return the key so we can return a direct reference
        if(($key = $this->add_team($team)) === false) return $this->bb->ref(null);

        //Success! return a reference directly from the teams array
        return $this->teams[$key];
    }

    /**
     * If you plan to allow players to join externaly from BinaryBeast.com, 
     *  you can use this method to allow them to start confirming their positions
     * 
     * Note that only confirmed players are included in the brackets / groups once the tournament starts
     * 
     * @return boolean
     */
    public function enable_player_confirmations() {
        //Make sure we're loaded first
        if(!$this->load()) return false;

        //Not even going to bother validating, let the API handle that
        $result = $this->call(self::SERVICE_ALLOW_CONFIRMATIONS, array('tourney_id' => $this->id));

        //Uh oh - likely an active tournament already
        if(!$result) return false;

        //Success - update local status value
        $this->set_current_data('status', 'Confirmation');
        return true;
    }
    /**
     * Disable participant's option of confirming their positions
     * 
     * Note that only confirmed players are included in the brackets / groups once the tournament starts
     * 
     * @return boolean
     */
    public function disable_player_confirmations() {
        //Make sure we're loaded first
        if(!$this->load()) return false;

        //Not even going to bother validating, let the API handle that
        $result = $this->call(self::SERVICE_DISALLOW_CONFIRMATIONS, array('tourney_id' => $this->id));

        //Uh oh - likely an active tournament already
        if(!$result) return false;

        //Success - update local status value
        $this->set_current_data('status', 'Building');
        return true;
    }

    /**
     * DANGER DANGER DANGER DANGER DANGER
     * DANGER!
     * 
     * This method will actually revert the tournament to its previous stage,
     *      So if you have brackets or groups, ALL progress in them will be lost forever
     * 
     * 
     * So BE VERY CAREFUL AND BE CERTAIN YOU WANT TO USE THIS
     * 
     * 
     * @return boolean      Simple result boolean, false on error true on success 
     */
    public function reopen() {
        //Can only reopen an active tournament
        if(!BBHelper::tournament_is_active($this)) {
            return $this->set_error('Tournament is not currently actve (current status is ' . $this->status . ')');
        }

        //Don't reopen if there are unsaved changes
        if($this->changed) {
            return $this->set_error(
                'Tournament currently has unsaved changes, you must save doing something as drastic as starting the tournament'
            );
        }

        /**
         * Who you gonna call??
         */
        $result = $this->call(self::SERVICE_REOPEN, array('tourney_id' => $this->id));

        //Uh oh
        if($result->result != BinaryBeast::RESULT_SUCCESS) return false;

        /**
         * Reopened successfully
         * update local status (sent by api), and reload teams
         */
        $this->set_current_data('status', $result->status);

        //Reload all data that would likely change from a major update like this (open matches, teams)
        $this->on_major_update();

        //Success!
        return true;
    }

    /**
     * Start the tournament!!
     * 
     * This method will move the tournament into the next stage
     *      Depending on type_id and current status, the next stage could be Active, Active-Groups, or Active-Brackets
     * 
     * 
     * If you try to call this method while the tournament has unsaved changes, it will return false, you must save changes before starting the tournament
     * 
     * 
     * The reason we go through so much time validating the input, is BinaryBeast actually allows you to be flexible about defining team orders, which in
     *      My experience so far is NOT a good thing and will likely change - but for now, we'll enforce a string input, and make sure that all valid teams and ONLY
     *      valid teams are sent
     * 
     * Unfortunatley due to uncertainty in inevitable changes to the way Groups are seeding through the API... this method currently does not support
     *      manually seeeding groups, it will only allow randomized groups
     * 
     *      This will change soon through, as soon as the API group seeding service can get it's sh*t together
     * 
     * Seeding brackets however is ezpz - just give us an array of either team id's, or team models either in order of bracket position, or seed ranking
     * 
     * 
     * @param string $seeding       'random' by default
     *      How to arrange the team matches
     *      'random' (Groups + Brackets)        =>      Randomized positions
     *      'manual' (Groups + Brackets)        =>      Arranges the teams in the order you provide in $order
     *      'sports' (Brackets only)            =>      This is the more traditional seeding more organizers are likely used to,
     *              @see link to example here
     *              Top seeds are given the greatest advantage, lowest seeds the lowest
     *              So for a 16 man bracket, seed 1 players against 32 first, 2 against 31, 3 against 30... 8 against 9 etc
     *      'balanced' (brackets only)          =>      BinaryBeast's own in-house algorithm for arranged team seeds, we felt 'Sports' was a bit too inbalanced,
     *              @see link to example here
     *              Unlike sports where the difference in seed is dynamic, favoring top seeds, in Balanced the seed different is the same for every match, basically top seed + $players_count/2
     *              For a 16 man bracket, seed 1 is against 9, 2 against 10, 3 against 11, 4 against 12.. 8 against 16 etc
     * 
     * 
     * @param BBTeam[]|int[] $order          If any seeding method other than 'random', use this value
     *      to define either the team arrangements, or team seeds
     * 
     *      You may either provide an array of BBTeam objects, or an array of tourney_team_id integer values
     *          Example of BBTeam array: $order = [BBTeam (seed|position 1), BBTeam (seed|position 2)...]
     *          Example of ids array:    $order = [1234 (seed|position 1), 1235 (seed|position2)...]
     * 
     *      For 'Manual', the match arrangements will be determined by the order of teams in $order, 
     *          So for [1234, 1235, 1236, 1237]... the bracket will look like this:
     *          1234
     *          1235
     *          --
     *          1236
     *          1237
     * 
     *      For Sports and Balanced, the match arranagements are determined by an seed pairing algorithm,
     *          and each team's seed is equal to his position in $order
     * 
     *      Please note that you may ommit teams from the $order if you wish, and they will be
     *          randomly added to the end of the $order - which could be useful if you'd like to define
     *          only the top $x seeds, and randomize the rest
     * 
     *      If you want to define freewin positions (for example when manually arranging the matches)...
     *          Use an integer value of 0 in $order to indicate a FreeWin
     * 
     * @return boolean
     */
    public function start($seeding = 'random', $order = null) {
        //Use BBHelper to run verify that this touranment is ready to start
        if(is_string($error = BBHelper::tournament_can_start($this))) {
            return $this->set_error($error);
        }

        //Make sure the seeding type is valid, use BBHelper - returned value of null indicates invalid seeding type
        if(is_null($seeding = BBHelper::validate_seeding_type($this, $seeding))) {
            return $this->set_error("$seeding is not a valid seeding method value! Valid values: 'random', 'manual', 'balanced', 'sports' for brackets, 'random' and 'manual' for groups");
        }

        //Initialize the real $teams value we send to the API - $order is just temporary
        $teams = array();

        /**
         * If we need an order or teams / seeds, we need to make sure that 
         *      all confirmed teams are provided, and nothing more
         */
        if($seeding != 'random') {
            /**
             * Will be supported in the future, however for now we don't allow
             *      seeding groups with this class
             */
            if(BBHelper::get_next_tournament_stage($this) == 'Active-Groups') {
                return $this->set_error('Unfortunately for the time being, seeding groups has been disabled.  It will be supported in the future.  However for now, only "random" is supported for group rounds');
            }
            

            /**
             * First grab a list of teams that need to be included
             * 
             * Any teams not specifically provided in $order will be random
             *  added to the end
             */
            $confirmed_teams = $this->confirmed_teams(true);

            //Start looping through each team provided, adding it to $teams only if it's in $confirmed_teams
            foreach($order as &$team) {
                //If this is an actual BBTeam object, all we want is its id
                if($team instanceof BBTeam) $team = $team->id;

                //Now make sure that this team is supposed to be here
                if(!in_array($team, $confirmed_teams) && intval($team) !== 0) {
                    return $this->set_error("Team {$team} is a valid tourney_team_id of any team in this tournament, please include only valid team ids, or 0's to indicate a FreeWin");
                }

                /**
                 * Valid team! Now we need to do two things:
                 *  1) Remove the team from confirmed_teams (so we can randomize + add any remaining teams after we're finished)
                 *  2) Add its tourney_team_id to $teams, which is the actual value sent to BinaryBeast
                 */
                $teams[] = $team;
                unset($confirmed_teams[array_search($team, $confirmed_teams)]);
            }

            /*
             * If there are any teams left over, randomize them and add them to the end of the teams array
             */
            if(sizeof($confirmed_teams) > 0) {
                shuffle($teams);
                array_push($teams, $confirmed_teams);
            }
        }
        //For random tournaments, just send null for $order
        else $order = null;

        //GOGOGO!
        $result = $this->call(self::SERVICE_START, array(
            'tourney_id'        => $this->id,
            'seeding'           => $seeding,
            'teams'             => $teams
        ));

        //oh noes!
        if($result->result !== BinaryBeast::RESULT_SUCCESS) {
            return false;
        }

        /**
         * Started successfully!
         * Now we update our status value, and reload the teams arary
         * 
         * Conveniently the API actually sends back the new status, so we'll use that to update our local values
         */
        $this->set_current_data('status', $result->status);

        //Reload all data that would likely change from a major update like this (open matches, teams)
        $this->on_major_update();

        //Success!
        return true;
    }

    /**
     * Returns an array of matches that still need to be reported
     * 
     * Each item in the array will be an instance of BBMatch, which you can use 
     *      to submit the results
     * 
     * @param boolean $force_reload
     *      False by default
     *      Enable this to force querying for a fresh list from the API
     *      Warning: this does NOT mean that cached results will be cleared, that must be done separately
     * 
     * @return array
     */
    public function &open_matches($force_reload = false) {
        //Already cached
        if(!is_null($this->open_matches) && !$force_reload) return $this->open_matches;

        //Inactive tournament
        if(!BBHelper::tournament_is_active($this)) {
            $this->set_error('cannot load open matches of an inactive tournament, start() it first');
            return $this->bb->ref(null);
        }

        //Ask the api
        $result = $this->call(self::SERVICE_LIST_OPEN_MATCHES, array('tourney_id' => $this->id), self::CACHE_TTL_LIST_OPEN_MATCHES, self::CACHE_OBJECT_TYPE, $this->id);
        if(!$result) return $this->bb->ref(false);

        //Cast each match into BBMatch, and call init() so it knows which tournament it belongs to
        foreach($result->matches as $match) {
            $match = $this->bb->match($match);
            $match->init($this);
            $this->open_matches[] = $match;
        }

        //Success!
        return $this->open_matches;
    }
	/**
     * Can be used 2 ways:
     *  1) Returns the open BBMatch object where the $team1 and $team2 meet
     *      it will try to return an unreported BBMatch from the open_matches array
     * 
     *  2) Verifies that the provided BBMatch object is valid / part of this tournament
     *      In which case, we return the valid most up-to-date object instance possible, even if the only thing that matches
     *      from your BBMatch, is the id
     * 
	 * 
	 * @param int|BBTeam|BBMatch        $team1
	 * @param int|BBTeam                $team2
	 * 
	 * @return BBMatch|null
	 */
	public function &match($team1 = null, $team2 = null) {
        //If given BBMatch, verify or return null
        if($team1 instanceof BBMatch) {
            //if unreported - check it against our open_matches list
            if($team1->is_new()) {
                return $this->get_child($team1, $this->open_matches());
            }

            //If reported, simply load a fresh copy from the API and return
            else {
                //Cast as a BBMatch
                $match = $this->bb->match($team1->id);
                $match->init($this);
                if($match->tourney_id != $this->id) {
                    return $this->bb->ref(null);
                }

                //success!
                return $match;
            }
        }

        //If team1 is a number, and team 2 is null, treat team1 as a tourney_match_id
        if(is_numeric($team1) && is_null($team2)) {
            $match = $this->bb->match($team1);
            $match->init($this);
            return $match->load();
        }

        //Let open_match() take over
        return $this->open_match($team1, $team2);
	}

	/**
     * Used to either verify that the given BBMatch is in this tournametns' open_match list, or to 
     *  fetch the open match that contains the provided $team1 and $team2
     * 
	 * 
	 * @param int|BBTeam|BBMatch        $team1
	 * @param int|BBTeam|null           $team2
     *      Can be null if the first argument is an instance of BBMatch
	 * 
	 * @return BBMatch|null
	 */
    public function &open_match($team1, $team2 = null) {
        //If given a match, match it against our open_matches array
        if($team1 instanceof BBMatch) {
            return $this->get_child($team1, $this->open_matches());
        }

        //Try to find the match with these two teams
        foreach($this->open_matches() as $match) {
            if($match->team_in_match($team1) && $match->team_in_match($team2) ) {
                return $match;
            }
        }

        //Failure!
        return $this->bb->ref(null);
    }
    
    /**
     * Prints outs the iframe HTML needed to embed a tournament
     * 
     * Convenience wrapper for {@link BBHelper::embed_tournament()}
     * 
     * @param boolean $groups
     *  By default if a tournament with rounds has progressed to the brackets, the groups will not be displayed
     *  however if you set this to true, the group rounds will be displayed instead
     * @param int|string $width
     *  Optionally define the width of the iframe
     *  Can either by a string (IE '100%', or a number (100 => becomes '100px')
     * @param int|string $height
     *  Optionally define the height of the iframe
     *  Can either by a string (IE '100%', or a number (100 => becomes '100px')
     * @param string $class
     *  A class name to apply to the iframe
     *  Defaults to 'binarybeast'
     * 
     * @return boolean
     *  prints out the html directly
     *  returns false if there was an error (like unable to determine the tournament id)
     */
    public function embed($groups = false, $width = 800, $height = 600, $class = 'binarybeast') {
        return BBHelper::embed_tournament($this, $groups, $width, $height, $class);
    }
    
    /**
     * Prints outs the iframe HTML needed to embed a tournament - forcing group rounds, even if tournament
     *  has progress to brackets
     * 
     * Convenience wrapper for {@link BBHelper::embed_tournament()}
     * 
     * @param int|string $width
     *  Optionally define the width of the iframe
     *  Can either by a string (IE '100%', or a number (100 => becomes '100px')
     * @param int|string $height
     *  Optionally define the height of the iframe
     *  Can either by a string (IE '100%', or a number (100 => becomes '100px')
     * @param string $class
     *  A class name to apply to the iframe
     *  Defaults to 'binarybeast'
     * 
     * @return boolean
     *  prints out the html directly
     *  returns false if there was an error (like unable to determine the tournament id)
     */
    public function embed_groups($width = 800, $height = 600, $class = 'binarybeast') {
        return $this->embed(true, $width, $height, $class);
    }
    
}

?>