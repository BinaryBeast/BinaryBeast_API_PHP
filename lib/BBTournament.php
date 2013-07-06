<?php

/**
 * Model object for a BinaryBeast Tournament
 *
 * It can be used to <b>create</b>, <b>manipulate</b>, <b>delete</b>, and <b>list</b> BinaryBeast tournaments
 *
 *
 * > Content
 * > ******
 * >
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#tutorials Tutorials}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#methods Methods}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#properties Properties}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#magicProperties Magic Properties}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#constants Constants}
 * >
 * >
 * > Examples and Tutorials  .[#tutorials]
 * > ******
 * >
 * > All examples assume <var>$bb</var> is an instance of {@link BinaryBeast}
 * >
 * > <b>Tutorials:</b>
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#listing Listing Tournaments}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#create Create a New Tournament}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#round-format Configure Round Format}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#adding-teams Adding Teams}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#list-teams Listing Teams}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#start-groups Starting Group Rounds}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#start-brackets-random Starting Brackets - Random}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#start-brackets-manual Starting Brackets - Manual}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#start-brackets-seeded Starting Brackets - Seeded}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#open-matches Loading / Listing Unplayed Matches}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#reporting Reporting Matches}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#report-batch Reporting Multiple Matches}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#embedding Embedding your Tournament}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#callbacks Callbacks}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#drawing Accessing Groups/Brackets Data}
 * > > - {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#deleting Deleting the Tournament}
 * >
 *
 *
 * ## Listing Tournaments .[#listing]
 *
 * <b>Example: Load list of tournaments created by your account:</b>
 *
 * Includes any tournaments you've marked as private {@link BBTournament::public},
 * as defined in the 3rd parameter
 * <code>
 *  $tournaments = $bb->tournament->list_my(null, 300, true);
 *  foreach($tournaments as $tournament) {
 *      echo '<a href="/my/path/to/viewing/a/tournament?id=' . $tournament->id . '">' . $tournament->title . '</a>';
 *  }
 * </code>
 *
 * <b>Example: Load a filtered list of your tournaments, using the keyword 'starleague':</b>
 *
 * Note: since we didn't define the 3rd parameter, private tournaments will NOT be included {@link BBTournament::public}
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
 * ## Create a New Tournament .[#create]
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
 * ## Configure Round Format .[#round-format]
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
 * ## Add Teams to the Tournament .[#adding-teams]
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
 * ## Listing Teams .[#list-teams]
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
 * ## Starting Group Rounds .[#start-groups]
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
 * ## Starting Brackets - Random  .[#start-brackets-random]
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
 * ## Starting Brackets - Manual  .[#start-brackets-manual]
 * 
 * You also have the option of starting brackets, and manually defining all of the starting positions
 * 
 * 
 * To start with specific starting positions, you have to do two things:<br />
 * - Set the first argument of save() to 'manual', or {@link BinaryBeast::SEEDING_MANUAL}
 * - Provide an array of either {@link BBTeam} objects or team id integers in the exact order you want them to appear in the brackets
 *
 * <br />
 * <b>Note:</b> To define a freewin, just use a value of <b>(int) 0</b> in that position
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
 *      var_dump(array('Team 7 position expected to be 7!', 'position' => $team7->position));
 *  }
 *  if($team2->position != 1) {
 *      var_dump(array('Team 2 position expected to be 7!', 'position' => $team7->position));
 *  }
 * </code>
 * etc etc...
 * 
 * 
 * ## Starting Brackets - Seeded  .[#start-brackets-seeded]
 * 
 * 'sports' and 'balanced' seeding work very similarly, it's not within the scope
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
 * ## Resetting brackets or Groups .[#resetting]
 * 
 * If you need to reset the brackets or groups, it's very simple:
 * <code>
 *  if(!$tournament->reopen()) {
 *      var_dump($bb->last_error());
 *  }
 * </code>
 *
 *
 * ## Loading / Listing Unplayed Matches .[#open-matches]
 *
 * We can use the {@link open_matches()} method, and the magic {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#m$open_matches $open_matches} property to find matches that need to be reported
 *
 * <b>Example: </b> Using {@link open_matches()}
 * <code>
 *  $matches = $tournament->open_matches();
 *  foreach($matches as $match) {
 *      echo $match->team->display_name . ' vs ' . $match->team2->display_name . ' in round ' . $match->round_format->round . '<br />';
 *  }
 * </code>
 *
 *
 * <br /><br />
 * You can use the magic {@link $open_matches} property to directly fetch one of the matches
 *
 * <b>Example: </b> Using the magic {@link $open_matches} property
 * <code>
 *  $match = $tournament->open_matches[0];
 *      echo $match->team->display_name . ' vs ' . $match->team2->display_name . ' in round ' . ($match->round + 1) . '<br />';
 * </code>
 *
 *
 * ## Reporting Matches .[#reporting]
 *
 *
 * The previous example showed us how to get unplayed {@link BBMatch} objects
 *
 * So assuming <var>$tournament</var> is an active tournament with brackets... first step:
 * <code>
 *  $match = $tournament->open_matches[0];
 * </code>
 *
 *
 * Please refer to the documentation of {@link BBMatch} for more options, but here's a quick example:
 *
 * Key points in the example:
 * - {@link BBMatch::team()} to grab the first team
 * - {@link BBMatch::set_winner()} to define the first team as the winner
 * - {@link BBMatch::report()} to save / report the match
 *
 * <b>Example: </b>Report a simple 1:0 match:
 * <code>
 *  $match->set_winner($match->team());
 *  if(!$match->report()) {
 *      var_dump($bb->last_error);
 *  }
 *  echo $match->winner->display_name . ' defeated ' . $match->loser->display_name . ' in match ' . $match->id;
 * </code>
 *
 *
 * ## Reporting Multiple Matches .[#report-batch]
 *
 * The previous example demonstrated the use of {@link BBMatch::report()}, now let's look how to report multiple matches simultaneously
 *
 * <br />
 * Firstly, go through all the open matches and define a winner so we have multiple matches to report
 *
 * <code>
 *  foreach($tournament->open_matches as $match) {
 *      $match->set_winner($match->team());
 *  }
 * </code>
 *
 *
 * <br /><br />
 * Now we can either use {@link BBTournament::save_matches()}, or {@link BBTournament::save()}
 *
 * <b>Using {@link BBTournament::save_matches()}:</b>
 * <code>
 *  if(!$tournament->save_matches()) {
 *      var_dump($bb->error_history);
 *  }
 * </code>
 *
 *
 * <br />
 * <b>Using {@link BBTournament::save()}:</b>
 * <code>
 *  if(!$tournament->save()) {
 *      var_dump($bb->error_history);
 *  }
 * </code>
 *
 *
 * ## Embedding your Tournament .[#embedding]
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
 * ## Callbacks .[#callbacks]
 * 
 * Thanks to the {@link BBCallback} library class, you can register URLs for BinaryBeast to call whenever a certain event is triggered
 * 
 * Here is a list of callbacks currently available for a tournament:
 * - {@link on_create()}: Triggered when a new tournament is created
 * - {@link on_change()}: Very generic, triggered by just about everything
 * - {@link on_complete()}: The final match is reported
 * - {@link on_delete()}: Tournament has been deleted
 * - {@link on_delete()}: Tournament has been deleted
 * - {@link on_match_reported()}: A new match result has been reported
 * - {@link on_match_unreported()}: A match result has been unreported / deleted
 * - {@link on_settings()}: A setting has changed (like the title, max_teams etc)
 * - {@link on_start_brackets()}: Brackets have been generated
 * - {@link on_start_groups()}: Group rounds have begun
 * - {@link on_team_added()}: A new team has been added
 * - {@link on_team_removed()}: A new team has been removed / deleted
 * - {@link on_team_status()}: A new team's status has changed (Confirmed / Banned etc)
 * 
 * <br />
 * 
 * 
 * ## Example: Register a Callback
 * 
 * Let's register a simple callback with {@link on_change()}:
 * <code>
 *	$id = $tournament->on_change('http://yoursite.com/tournament/handle_callback.php');
 *	echo 'Callback registered succesfully! Callback ID: ' . $id;
 * </code>
 * 
 * 
 * ## Example: Handling a Callback
 * 
 * Check out the documentation in {@link http://binarybeast.com/content/api/docs/php/class-BBCallback.html#handling BBCallback} for instructions on handling callbacks
 * 
 * 
 * ## Example: Listing Registered Callbacks
 * 
 * Use {@link BBModel::list_callbacks()} to determine what callbacks have been registered with your tournament
 * 
 * <br /><br />
 * {@link BBCallbackObject} documents the format you can expect this method to return
 * 
 * <br /><br />
 * <code>
 *	foreach($tournament->list_callbacks() as $callback) {
 *		echo $callback->id . ': (' . $callback->url . ') Triggered by: ' . $callback->event_description . '<br />';
 *	}
 * </code>
 * 
 * 
 *
 * 
 * ## Example: Delete (Unregister) a Callback
 * 
 * Again, refer to the documentation in {@link http://binarybeast.com/content/api/docs/php/class-BBCallback.html#deleting BBCallback} for this
 *
 *
 *
 *
 * ## Accessing Groups/Brackets Data .[#drawing]
 *
 * You have the option of directly accessing the raw data from the brackets and group rounds themselves
 *
 * <br />
 * There are two methods / properties available for this:
 * - {@link brackets()} / {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#m$brackets BBTournament::$brackets}
 * - {@link groups()} / {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#m$groups BBTournament::$groups}
 *
 * <br /><br />
 * <b>Examples and Documentation</b> are available below:
 * - <b>Groups: </b>{@link BBGroupsObject}
 * - <b>Brackets: </b>{@link BBBracketsObject}
 *
 *
 * ## Deleting the Tournament .[#deleting]
 *
 * Be <b>VERY</b> careful with this! there's no going back!!
 *
 * You can delete the tournament and all of its children however, with one quick
 * potentially catastrophically mistaken swoop:
 * <code>
 *  if(!$tournament->delete()) {
 *      var_dump($bb->last_error);
 *  }
 * </code>
 * 
 * 
 * ## More...
 * 
 * Those are the basics, but there's a lot more to it - feel free to look through
 * the documentation to see what else is available
 * 
 *
 * ## Next Step
 *
 * Your next step should be to review documentation for the following models, each one which are children of a BBTournament:<br />
 * - {@link BBRound}
 * - {@link BBTeam}
 * - {@link BBMatch}
 * - {@link BBMatchGame}
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
 * @property string $date_start
 * YYYY-MM-DD HH:SS ({@link http://en.wikipedia.org/wiki/ISO_8601})
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
 * @property mixed $hidden
 * Special hidden (as you may have guessed) values that you can use to store custom data<br />
 * The recommended use of this field, is to store a json_encoded string that contains your custom data
 * <b>Note:</b> Can be set to an object / array, and it will be saved as a json_string,<br />
 * and the encoding/decoding is <b>handled automatically</b> when the object is loaded and saved
 *
 * 
 * @property string $player_password
 * <b>Strongly recommended</b><br />
 * If set, players are required to provide this password before allowed to join<br />
 * Use {@link BBTournament::generate_player_password()}
 *
 * @property BBMapObject[] $map_pool
 * Array of approved maps in the map pool
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
 * @property BBTeam[] $freewins
 * <b>Alias for {@link BBTournament::freewins()}</b><br />
 * An array of freewin teams
 *
 * @property int $teams_confirmed_count
 * Number of confirmed teams in the tournament
 *
 * @property int $teams_joined_count
 * Number of teams in the tournament, regardless of status
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
 * @property-read BBBracketsObject $brackets
 * <b>Alias for {@link brackets()}</b><br />
 * The data object containing elimination bracket matches and results
 *
 * @property-read BBGroupsObject $groups
 * <b>Alias for {@link groups()}</b><br />
 * The data object containing group rounds matches and results
 *
 * @todo consider allowing developers to set values for any custom properties, and save them in $notes
 * @todo consider allowing developers to set values for any custom properties, and save them in $notes
 *
 * @todo Instead of flagging reloads when the race is changed, import the new values (may require update to the svc itself)
 *
 * @package BinaryBeast
 * @subpackage Model
 * 
 * @version 3.1.5
 * @date    2013-07-05
 * @since   2012-09-17
 * @author  Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
class BBTournament extends BBModel {

    //<editor-fold defaultstate="collapsed" desc="API svc name constants">
    const SERVICE_LOAD                      = 'Tourney.TourneyLoad.Info';
    const SERVICE_CREATE                    = 'Tourney.TourneyCreate.Create';
    const SERVICE_UPDATE                    = 'Tourney.TourneyUpdate.Settings';
    const SERVICE_DELETE                    = 'Tourney.TourneyDelete.Delete';
    /**
     * API svc name for fetching a tournament count
     * @var string
     */
    const SERVICE_COUNT                     = 'Tourney.TourneyList.Count';
    /**
     * API svc name for starting a tournament
     * @var string
     */
    const SERVICE_START                     = 'Tourney.TourneyStart.Start';
    /**
     * API svc name for resetting brackets/ groups
     * @var string
     */
    const SERVICE_REOPEN                    = 'Tourney.TourneyReopen.Reopen';
    /**
     * API svc name for allowing participant confirmations
     * @var string
     */
    const SERVICE_ALLOW_CONFIRMATIONS       = 'Tourney.TourneySetStatus.Confirmation';
    /**
     * API svc name for disabling participant confirmations
     * @var string
     */
    const SERVICE_DISALLOW_CONFIRMATIONS    = 'Tourney.TourneySetStatus.Building';
    //
    /**
     * API svc name for loading a list of tournaments
     * @var string
     */
    const SERVICE_LIST                      = 'Tourney.TourneyList.Creator';
    /**
     * API svc name for loading a list of <b>popular</b> tournaments
     * @var string
     */
    const SERVICE_LIST_POPULAR              = 'Tourney.TourneyList.Popular';
    /**
     * API svc name for loading a list of unplayed matches
     * @var string
     */
    const SERVICE_LIST_OPEN_MATCHES         = 'Tourney.TourneyLoad.OpenMatches';
    /**
     * API svc name for loading / streaming a list of played matches
     * @var string
     */
    const SERVICE_STREAM_MATCHES         = 'Tourney.TourneyMatch.Stream';
    /**
     * API svc name for loading a single match
     * @var string
     */
    const SERVICE_LOAD_MATCH                = 'Tourney.TourneyLoad.Match';
    /**
     * API svc name for loading match details, given the participants
     * @var string
     */
    const SERVICE_LOAD_TEAM_PAIR_MATCH      = 'Tourney.TourneyMatch.LoadTeamPair';
    /**
     * API svc name for listing the participants in the tournament
     * @var string
     */
    const SERVICE_LOAD_TEAMS                = 'Tourney.TourneyLoad.Teams';
    /**
     * API svc name for performing a batch update of teams
     * @var string
     */
    const SERVICE_UPDATE_TEAMS = 'Tourney.TourneyTeam.BatchUpdate';
    //
    /**
     * API svc name for listing the round format in the tournament
     * @var string
     */
    const SERVICE_LOAD_ROUNDS               = 'Tourney.TourneyLoad.Rounds';
    /**
     * API svc name for performing a batch update of round format
     * @var string
     */
    const SERVICE_UPDATE_ROUNDS             = 'Tourney.TourneyRound.BatchUpdate';
    //
    /**
     * API svc name for loading the raw data of matchups and results in the brackets
     * @var string
     */
    const SERVICE_LOAD_BRACKETS             = 'Tourney.TourneyDraw.Brackets';
    /**
     * API svc name for loading the raw data of matchups and results in the group rounds
     * @var string
     */
    const SERVICE_LOAD_GROUPS               = 'Tourney.TourneyDraw.Groups';
    /**
     * API svc name used to remove a map from the map_pool
     * @var string
     */
    const SERVICE_REMOVE_MAP               = 'Tourney.TourneyMapPool.Delete';
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="Local Cache Settings">
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
    /**
     * Cache the Groups/Brackets drawing services
     * @var int
     */
    const CACHE_TTL_DRAW                = 30;
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="Callback event_id constants">
    /**
     * Callback event id for when a new tournament is created
     * @var int
     */
    const CALLBACK_CREATED = 12;
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
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="Private properties and child arrays">
    /**
     * Array of participants within this tournament
     * @var BBTeam[]
     */
    private $teams;
    /**
     * Array of filtered team arrays, necessary for returning results by reference
     */
    private $filtered_teams = array();

    /**
     * Object containing format for each round
     * Keyed by bracket name, each of which is an array of BBRound objects
     * @var BBRoundObject
     */
    private $rounds;

    /**
     * Cache results from loading open matches from the API
     * @var BBMatch[]
     */
    private $open_matches;

    /**
     * Result of calling the TourneyDraw.Brackets service
     * @var BBBracketsObject
     */
    private $brackets;

    /**
     * Result of calling the TourneyDraw.Groups service
     * @var BBGroupsObject
     */
    private $groups;

    /**
     * Map pool objects
     * @var BBMapObject[]
     */
    private $map_pool;
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="BBModel implementations">
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
        'map_pool'          => array()
    );
    protected $read_only = array('status', 'tourney_id');
    protected $id_property = 'tourney_id';
    protected $data_extraction_key = 'tourney_info';
    //</editor-fold>

    /**
     * This tournament's ID, using BinaryBeast's naming convention
     * @var string
     */
    public $tourney_id;

    /**
     * Overloads {@link BBModel::__set()} so we can prevent setting the type_id of an active tournament
     *
     * @ignore
     * {@inheritdoc}
     */
    public function __set($name, $value) {
        if(strtolower($name) == 'type_id') {
            if(BBHelper::tournament_is_active($this)) return;
        }

        parent::__set($name, $value);
    }

    /**
     * Overloads {@link BBModel::import_values} so we can handle 'hidden'
     *
     * {@inheritdoc}
     */
    public function import_values($data) {
        //Let BBModel handle default functionality
        parent::import_values($data);

        //json 'hidden' custom values
        if(array_key_exists('hidden', $this->data)) {
            $hidden = $this->data['hidden'];
            if(is_string($hidden)) {
                if(!is_null($hidden = json_decode($hidden))) {
                    $this->set_current_data('hidden', $hidden);
                }
            }
        }
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
     * @param array     $args   any additional arguments to send with the API call
     *
     * @param boolean   $freewins
     * <b>Default: </b>false
     * Set true to include fake FreeWins placeholder teams
     * 
     * @return BBTeam[]|null
     *      Null is returned if there was an error with the API request
     */
    public function &teams($ids = false, $args = array(), $freewins = false) {
        //Already instantiated
        if(!is_null($this->teams)) {
            //Return directly
            if(!$ids && $freewins) {
                return $this->teams;
            }

            //Generate a filter key, so we filter the results and still return by reference
            $filter_key = 'teams' . ($ids?'_ids':'') . ($freewins?'_freewins':'');

            //Populate the filter
            $this->filtered_teams[$filter_key] = array();

            //Loop through the full list, stored in $teams
            foreach($this->teams as &$team) {
                //Filter out freewins
                if(!$freewins) {
                    //This is a freewin, skip to the next iteration
                    $lower = strtolower($team->display_name);
                    if($lower == 'freewin' || $lower == 'bye') {
                        continue;
                    }
                }

                //id only
                if($ids) {
                    $this->filtered_teams[$filter_key][] = $team->id;
                }
                else {
                    $this->filtered_teams[$filter_key][] = &$team;
                }
            }

            //Success!
            if($ids) var_dump(array('key' => $filter_key, 'filtered_teams' => $this->filtered_teams, 'result' => $this->filtered_teams[$filter_key]));
            return $this->filtered_teams[$filter_key];
        }

        //Load from the API
        if(!is_array($args)) $args = array();
        $result = $this->call(self::SERVICE_LOAD_TEAMS, array_merge($args, array(
                'tourney_id' => $this->tourney_id,
                'full' => true,
                'freewins' => true
            )),
            self::CACHE_TTL_TEAMS, self::CACHE_OBJECT_TYPE, $this->id
        );

        //Fail!
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
            return $this->bb->ref(null);
        }

		//Instantiate the results as BBTeam model objects
        $this->teams = array();
		foreach($result->teams as $team) {
            //Instantiate, Initialize, and store it!
            $team = $this->bb->team($team);
            $team->init($this, false);
            $this->teams[] = $team;
        }

		//Success! - now run again so we can apply the filters
		return $this->teams($ids, null, $freewins);
    }

    /**
     * Returns an array of confirmed teams in this tournament
     * 
     * @param boolean   $ids        set true to return array of ids only
     * @param boolean $freewins
     * @return BBTeam[]
     */
    public function &confirmed_teams($ids = false, $freewins = false) {
        return $this->filter_teams_by_status($ids, 1, $freewins);
    }
    /**
     * Returns an array of unconfirmed teams in this tournament
     * 
     * @param boolean   $ids        set true to return array of ids only
     * @param boolean $freewins
     * @return BBTeam[]
     */
    public function &unconfirmed_teams($ids = false, $freewins = false) {
        return $this->filter_teams_by_status($ids, 0, $freewins);
    }
    /**
     * Returns an array of banned teams in this tournament
     * 
     * @param boolean   $ids        set true to return array of ids only
     * @param boolean $freewins
     * @return BBTeam[]
     */
    public function &banned_teams($ids = false, $freewins = false) {
        return $this->filter_teams_by_status($ids, -1, $freewins);
    }
    /**
     * Returns an array of freewin teams in this tournament
     *
     * @param boolean   $ids
     * set true to return array of ids only
     *
     * @return BBTeam[]
     */
    public function &freewins($ids = false) {
        //Use teams() to guarantee up to date values, and so we can return false if there are errors set by it
        if(is_null($teams = &$this->teams(false, array(), true))) return $this->bb->ref(false);

        //Generate a filter key, so we filter the results and still return by reference
        $filter_key = 'freewins';
        $this->filtered_teams[$filter_key] = array();

        //Loop through the raw list and populate the filter
        foreach($teams as &$team) {
            //Populate the filtered results if the name matches
            $lower = strtolower($team->display_name);
            if($lower == 'freewin' || $lower == 'bye') {
                $this->filtered_teams[$filter_key][] = &$team;
            }
        }

        //Qapla!
        return $this->filtered_teams[$filter_key];
    }
    
    /**
     * Used by confirmed|banned|unconfirmed_teams to return an array of teams from $teams() that have a matching
     *  $status value

     * @param boolean   $ids        Return just ids if true
     * @param int       $status     Status value to match
     *      Null to return ALL teams
     * @param boolean $freewins
     * @return BBTeam[]
     */
    private function &filter_teams_by_status($ids, $status, $freewins) {
        //Use teams() to guarantee up to date values, and so we can return false if there are errors set by it
        if(is_null($teams = &$this->teams(false, array(), $freewins))) return $this->bb->ref(false);

        //Generate a filter key, so we filter the results and still return by reference
        $filter_key = 'status_' . $status . ($ids?'_ids':'') . ($freewins?'_freewins':'');
        $this->filtered_teams[$filter_key] = array();

        //Loop through the raw list and populate the filtered array
        foreach($teams as &$team) {
            //Populate the filtered results with teams that match the provided $status
            if($team->status == $status || is_null($status)) {
                $this->filtered_teams[$filter_key][] = &$team;
            }
        }

        //Qapla!
        return $this->filtered_teams[$filter_key];
    }
    /**
     * Used internally after a major change to refresh our list of teams, in hopes of
     *  getting changed data
     * 
     * Refresh our teams array, and deletes our cache of open_matches
     *
     * @ignore
     * @return void
     */
    private function handle_major_update() {
        //Clear ALL cache for this specific tournament
        $this->clear_id_cache();

        //Flag reloads for any existing teams, in case any stray references exist after removing child classes
        if(is_array($this->teams(false, array(), true))) {
            foreach($this->teams as &$team) {
                if($team instanceof BBTeam) {
                    $team->flag_reload();
                }
            }
        }

        //GOGOGO!
        $this->rounds           = null;
        $this->teams            = null;
        $this->filtered_teams   = null;
        $this->brackets         = null;
        $this->groups           = null;
        //$this->teams();
        $this->open_matches     = null;
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

        //Delete the cache for open matches and drawing for this tournament ID, and teams (since wins / lbwins etc have changed)
        $this->clear_id_cache(array(
            self::SERVICE_LIST_OPEN_MATCHES,
            self::SERVICE_LOAD_TEAMS,
            self::SERVICE_LOAD_BRACKETS,
            self::SERVICE_LOAD_GROUPS
        ));

        //Delete ALL team-opponent/match/elimination cache, unfortunately there's no simple way to limit it to this tournament
        $this->clear_service_cache(array(
            BBTeam::SERVICE_LIST_OPPONENTS,
            BBTeam::SERVICE_GET_LAST_MATCH,
            BBTeam::SERVICE_GET_OPPONENT,
        ));

        //Clear the open_matches array, to force a fresh query next time it's accessed
        $this->open_matches = null;

        //Delete drawing results, forcing a fresh query next time they're accessed
        $this->groups = null;
        $this->brackets = null;

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
        if(is_null($this->id)) {
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

        //Initialize each returned round, and store it into local properties
        foreach($result->rounds as $bracket => &$rounds) {
            //key each round by the round label
            $bracket_label = BBHelper::get_bracket_label($bracket, true);

			//Only import rounds for relevant brackets
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
     * Save the tournament<br />
     *
     * Overloaded for special argument requirements,
     * and for saving children
     *
     * {@inheritdoc}
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
     * @return string|boolean
     * Either returns the tournament id string, or false to indicate an error
     */
    public function save_settings($settings_only = true) {
        //json notes
        $hidden = null;
        if(array_key_exists('hidden', $this->new_data)) {
            $hidden = $this->new_data['hidden'];
            if(is_object($hidden) || is_array($hidden)) {
                $this->new_data['hidden'] = json_encode($hidden);
            }
        }

        //Send only map_id values for the map pool
        $this->new_data['map_pool'] = $this->map_ids();

        //If saving a new tournament, pass control to save_new
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

        /**
         * Teams to insert
         * @var BBTeam[]
         */
        $new_teams = array();

        /**
         * Raw team data for new teams
         * @var array[]
         */
        $teams_data = array();

        //If any teams have been added, include them too - the API can handle it
        if(!$settings_only) {
            /** @var BBTeam[] $new_teams */
            $new_teams = $this->get_changed_children('BBTeam');

            //Extract data arrays for each new team
            if( !empty($new_teams) ) {
                foreach($new_teams as &$team) {
                    $teams_data[] = $team->data;
                }

                //Add the new teams to the API arguments
                $args['teams'] = $teams_data;
            }
        }

        //Let BBModel handle it from here - but ask for the api response to be returned instead of a generic boolean
        if(!$result = parent::save(true, $args)) return false;

        //OH NOES!
        if($result->result !== 200) return false;

        //Clear list and count api cache
        $this->clear_object_service_cache(array(
            self::SERVICE_COUNT, self::SERVICE_LIST, self::SERVICE_LIST_POPULAR
        ));

        /**
         * Import the new data, and save the ID
         */
        $this->import_values($result);
        $this->set_id($result->tourney_id);

        /**
         * Update new teams with their new ids
         */
        if(!empty($new_teams)) {
            if(!empty($result->teams)) {
                /**
                 * Iterate through each "changed" team (they will all be new, since this is a new tournament),
                 *  and apply the new tourney_team_id
                 */
                foreach($new_teams as $x => &$team) {
                    //apply the entire "data" array as if it were an import
                    $team->import_values( $teams_data[$x] );

                    //Apply the new team id
                    $team->set_id( $result->teams[$x] );
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
     * Revert all changes<br />
     * Overrides {@link BBModel::reset()} so we can define the $teams array for removing unsaved teams
     * {@inheritdoc}
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
     * You can either call this directly (if for some reason you don't yet want tournaments changes saved)
     * or call save(), which will save EVERYTHING, including tour data, teams, and rounds
     * 
     * @return boolean
     */
    public function save_teams() {
        //New tournaments must be saved before saving teams
        if(is_null($this->id)) return $this->set_error('Can\t save teams before saving the tournament!');

        /**
         * List of teams with changes
         * @var BBTeam[]
         */
        $teams = &$this->get_changed_children('BBTeam');

        /**
         * Array of team object references that expect new ids
         * @var BBTeam[]
         */
        $team_ids = array();

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
                $team_ids[] = &$team;
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
         * Import new team ids
         */
        foreach($team_ids as $x => &$team) {
            $team->set_id( $result->team_ids[$x] );
        }

        //Set the iteration flag, which prevents BBTeam::sync_changes from flagging the tournament as changed
        $this->iterating = true;

        /**
         * Synchronize team changes
         */
        foreach($teams as &$team) {
            /** @var BBTeam $team */

            //Flag a reload if the race changed
            $flag_reload = array_key_exists('race', $team->get_changed_values());

            //Tell the team to merge all unsaved changed into $data
            $team->sync_changes();

            //Flag reload if the race was set
            if($flag_reload) {
                $team->flag_reload();
            }
        }

        //Clear the list of teams with changes, and reset the iteration flag
        $this->iterating = false;
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
        if($reported) $this->clear_match_cache();

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
        /**
         * Paginate to satisfy $limit
         */
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
     * For BBTeam, we pass it through $team() to avoid any mistakes caused by reloading / changed data, we base the
     *  search purely on tourney_team_id
     *
     * @ignore
     * {@inheritdoc}
     */
    public function remove_child(BBModel &$child, $preserve = true) {
        if($child instanceof BBTeam) {
            //Rely on team() to insure that even if changed, that we at LEAST get the correct reference using team_id
            if(!is_null($team = &$this->team($child))) {
                return $this->remove_child_from_list($team, $this->teams, $preserve);
            }
            return false;
        }
        if($child instanceof BBMatch) {
            //Like team(), we use match() to standardize, in case the input has changed from our cached version
            if(!is_null($match = &$this->open_match($child))) {
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
     * @return string|bool|null
     * <ul>
     *  <li><b>String: </b> The result of {@link save()}, returned if <var>$auto_save</var> is true</li>
     *  <li><b>boolean: </b> The result of {@link save()}, returned if <var>$auto_save</var> is true</li>
     *  <li><b>null: </b> if <var>$auto_save</var> is false, nothing is returned</li>
     * </ul>
     */
    public function generate_player_password($auto_save = true) {
        $this->player_password = uniqid();
        if($auto_save) return $this->save();
    }

    /**
     * Associate an existing BBTeam object with this tournament<br />
     *
     * <b>Requirements:</b><br />
     * - The team must NOT have an id yet
     * - The team must NOT have a tournament associated with it yet
     *
     * @param BBTeam $team
     * @return int|boolean
     * The team's new index in $this->teams()<br /><br />
     * <b>false returns: </b> evaluate as <var>$tournament->add_team($team) === false</var>,<br />
     *  because the index returned <b>may be zero</b>, which equates to false
     */
    public function add_team(BBTeam $team) {
        //We can't add new players to an active-tournament
        if(BBHelper::tournament_is_active($this)) {
            return $this->bb->ref($this->set_error('You cannot add players to active tournaments!!'));
        }

        //Team already exists, simply return its index within teams()
        if( ($index = array_search($team, $this->teams(false, null, true))) !== false ) {
            return $index;
        }

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
     * Create a new team, or validate an existing one<br /><br />
     *
     *
     * <b>Validating an Existing Team</b><br />
     * To verify that a team belongs to this tournament, you can provide the following: <br />
     * - {@link BBTeam::id} integer
     * - {@link BBTeam} object
     *
     * <br />
     * <b>Create a New Team</b><br />
     * If using this method to create a new team, simply don't povide any arguments
     *
     * <br /><br />
     * <b>Pro Tip: </b><br />
     * You can use this method to pre-define a list of participants in a new tournament,<br />
     * even before you call {@link save()}.<br /><br />
     *
     * The API accepts a list of teams to import with the same service used to create it, <br />
     * which cuts down on API requests, is faster, and more efficient<br /><br />
     *
     * <b>Saving your new Team</b><br />
     * This method does NOT save the team automatically, but you can save the new team with any of the following methods:<br />
     * - {@link BBTournament::save()}
     * - {@link BBTournament::save_teams()}
     * - {@link BBTeam::save()}
     *
     *
     * 
     * @param int|BBTeam $id
     * <br /><b>Optional</b><br />
     * <b>Default: </b>null<br /><br />
     * </b>Acceptable values:</b><br />
     * <ul>
     *  <li>{@link BBTeam::id} integer</li>
     *  <li>{@link BBTeam} object</li>
     * </ul>
     *
     * @return BBTeam|null
     * <ul>
     *  <li>{@link BBTeam}: The new team, or the verified team based on your input</li>
     *  <li><b>Null: </b> Either the tournament has started, or your team object/id was invalid</li>
     * </ul>
     */
    public function &team($id = null) {

        //If validating an input, make sure the teams array is initialized
		if(!is_null($this->id)) {
			if($this->teams() === false) {
				return $this->bb->ref(null);
			}
		}
        //If it's a new object, allow developers to add teams before save(), so we need to make sure $teams is initialized
		else if(is_null($this->teams)) {
            $this->teams = array();
        }

        /**
         * Team() can be used to validate a team input, and we rely on
         *  BBModel::get_child for that
         */
        if( !is_null($id) ) {
            //Try $teams, then $freewins
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
     * If you plan to allow players to join externally from BinaryBeast.com,
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
     * <b>DANGER DANGER!!!!</b> Irreversibly reset the current stage
     *
     * <br /><br />
     * Reverts the tournament to the previous stage if active
     *
     * <br /><br />
     * ### All results will be lost!! .{color:red}
     *
     * This means all <b>match result</b> details, <b>seed positions</b>, <b>group scores</b>, etc, will be <b>deleted</b>
     *
     * @return boolean
     */
    public function reopen() {
        //Can only reopen an active tournament
        if(!BBHelper::tournament_is_active($this)) {
            return $this->set_error('Tournament is not currently active (current status is ' . $this->status . ')');
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
        $this->handle_major_update();

        //Success!
        return true;
    }

    /**
     * Start the tournament!!
     *
     * Begin the next phase of the tournament<br /><br />
     *
     * if <var>$type_id</var> is set to {@link BinaryBeast::TOURNEY_TYPE_CUP} ( (int) 1 ),<br />
     * group rounds will be started the first time you call it, elimination brackets the second time<br />
     *
     * <br /><br />
     * <b>Warning: </b> You can't start with unsaved changes, call {@link save()} if you've changed anything first
     *
     * <br /><br />
     * ***Seeding Methods:***<br />
     *
     * - 'random' (group rounds):   {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#start-groups Example} .{target:_blank}
     * - 'random' (brackets):       {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#start-brackets-random Example} .{target:_blank}
     * - 'manual' (brackets):       {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#start-brackets-manual Example} .{target:_blank}
     * - 'sports' (brackets):       {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#start-brackets-seeded Example} .{target:_blank}
     * - 'balanced' (brackets):         {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#start-brackets-seeded Example} .{target:_blank}
     *
     * <br /><br />
     * There are examples available on {@link http://wiki.binarybeast.com/index.php?title=Seeding BinaryBeast.com} outlining the differences between each method
     *
     *
     * @param string $seeding
     * <br /><b>Default: </b> 'random'
     *
     * <br />
     * <b>Acceptable Values:</b><br />
     * <ul>
     *  <li>'random' ({@link BinaryBeast::SEEDING_RANDOM})</li>
     *  <li>'manual' ({@link BinaryBeast::SEEDING_MANUAL})</li>
     *  <li>'sports' ({@link BinaryBeast::SEEDING_SPORTS})</li>
     *  <li>'balanced' ({@link BinaryBeast::SEEDING_BALANCED})</li>
     * </ul>
     *
     * @param BBTeam[]|int[] $order
     * <br /><b>Optional</b><br />
     * Only used for seeding methods <b>other than 'random'</b>
     *
     * <br /><br />
     * The <b>format</b> expected can be found in the following examples:
     * <ul>
     *  <li>'manual': {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#start-brackets-manual Example}</li>
     *  <li>'sports': {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#start-brackets-seeded Example}</li>
     *  <li>'balanced': {@link http://binarybeast.com/content/api/docs/php/class-BBTournament.html#start-brackets-seeded Example}</li>
     * </ul>
     *
     * @return boolean
     */
    public function start($seeding = 'random', $order = null) {
        //Use BBHelper to run verify that this tournament is ready to start
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
            $confirmed_teams = $this->confirmed_teams(true, true);

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

            /**
             * Randomly append remaining teams
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
         * Now we update our status value, and reload the teams array
         * 
         * Conveniently the API actually sends back the new status, so we'll use that to update our local values
         */
        $this->set_current_data('status', $result->status);

        //Reload all data that would likely change from a major update like this (open matches, teams)
        $this->handle_major_update();

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
     * @return BBMatch[]|null|boolean
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
        foreach($result->matches as $match_data) {
            //Instantiate and initialize
            $match = $this->bb->match($match_data);
            $match->init($this);

            //queue it up!
            $this->open_matches[] = $match;
        }

        //Success!
        return $this->open_matches;
    }

    /**
     * Returns a list of played matches using the
     *  tournament stream service
     *
     * @since 2013-07-01
     *
     * @param boolean $freewins
     * <b>Default:</b> false
     * Set true to include matches against freewins
     *
     * @param boolean $stream
     * <b>Default:</b> false
     * If set to true, BinaryBeast remembers the last match
     *  streamed to you, and only sends matches reported since that
     *  last match each time you call this
     *
     * In other words each time you call this with $stream, you will only
     *  get match results you have not seen yet
     *
     * @return BBMatch[]|null
     */
    public function played_matches($freewins = false, $stream = false) {
        //Derp
        if(is_null($this->id)) {
            return null;
        }

        //Compile arguments
        $args = array(
            'tourney_id'    => $this->id,
            'games'         => true,
            'freewins'      => $freewins == true,
            'all'           => $stream == false
        );

        //Disable cache for streaming
        if($stream) {
            $cache_ttl      = null;
            $cache_type     = null;
            $cache_id       = null;
        }

        //Standard caching for non-stream results
        else {
            $cache_ttl  = self::CACHE_TTL_LIST;
            $cache_type = self::CACHE_OBJECT_TYPE;
            $cache_id   = $this->id;
        }

        //GOGOGO!
        $result = $this->call(self::SERVICE_STREAM_MATCHES, $args, $cache_ttl, $cache_type, $cache_id);
        if(!$result) return $this->bb->ref(false);

        //Init the output
        $matches = array();

        //Cast each match into BBMatch, and call init() so it knows which tournament it belongs to
        foreach($result->matches as $match_data) {
            //Instantiate and initialize
            $match = $this->bb->match($match_data);
            $match->init($this);

            //queue it up!
            $matches[] = $match;
        }

        //Success!
        return $matches;
    }

	/**
     * Create a new match, or validate an existing one<br /><br />
     *
     * <br /><br />
     * Can be used 2 ways:<br /><br />
     *  <b>1)</b> Returns the open BBMatch object where the $team1 and $team2 meet,<br />
     *      it will try to return an unreported BBMatch from the open_matches array
     *
     * <br />
     *  <b>2)</b> Verifies that the provided BBMatch object is valid / part of this tournament
     *      In which case, we return the valid most up-to-date object instance possible, even if the only thing that matches
     *      from your BBMatch, is the id
     * 
	 * 
	 * @param int|BBTeam|BBMatch        $team1
     * <b>Possible values:</b>
     * - The <b>integer</b> ID the either team in the match
     * - The {@link BBTeam} object of either team in the match
     * - A {@link BBMatch} object - to verify that it exists and to fetch the latest and created instance, stored in our open matches array
     *  
	 * @param int|BBTeam                $team2
     * <b>Possible values:</b>
     * - The <b>integer</b> ID the either team in the match (can't be the same as <var>$team1</var>)
     * - The {@link BBTeam} object of either team in the match (can't be the same as <var>$team1</var>)
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
     * Used to either verify that the given BBMatch is in this tournaments' open_match list, or to
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
        if(!is_null($team1) && !is_null($team2)) {
            foreach($this->open_matches() as $key => $match) {
                if($match->team_in_match($team1) && $match->team_in_match($team2) ) {
                    return $this->open_matches[$key];
                }
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

    //<editor-fold defaulstate="collapsed" desc="Callbacks">
    /**
	 * Used by event-specific methods (like on_change and on_complete), to register callbacks
	 *	while handling errors in a DRY manner
	 */
	private function register_callback($event_id, $url, $action = 'post', $recurrent = true, $args = null, $trigger_id = null) {
        //Default to current tournament id unless otherwise specified
        if(is_null($trigger_id)) {
            //Can't register callbacks if the tournament doesn't exist yet
            if(is_null($this->id)) {
                return $this->set_error('Cannot register callbacks for the tournament before it exists on BinaryBeast - call save() first');
            }
            $trigger_id = $this->id;
        }

		//Register through the BBCallback class
		return $this->bb->callback->register($event_id, $trigger_id, $url, $action, $recurrent, $args);
	}

    /**
   	 * Register a callback / hook, triggered when a new tournament is created
   	 *
   	 * Review the {@link BBCallback} documentation for examples of how to handle a callback
   	 *
   	 * @param string $url
   	 *	URL called by BinaryBeast when the event is triggered
     *
     * @param int|null
     *  <b>Default:</b> The account associated with the current api_key<br />
     *  Which user to receive notifications of new tournaments
   	 *
   	 * @param string $action
   	 *	How to call your <var>$url</var><br />
   	 *	Must be <b>post</b> or <b>get</b>
   	 *
   	 * @param boolean $recurrent
   	 * True by default - if false, the callback is deleted after the first time it is triggered
   	 *
   	 * @param array $args
   	 *	Optionally array of custom arguments you'd like BinaryBeast to send when it calls your <var>$url</var>
   	 *
   	 * @return int|boolean
   	 *	Returns the callback id, false if there was an error registering the callback
   	 */
   	public function on_create($url, $user_id = null, $action = 'post', $recurrent = true, $args = null) {
        //Fetch the current user id so we can use it as
        if(is_null($user_id)) {
            //Call the api login service so we can fetch the user id - cache for a week
            $result = $this->call(BinaryBeast::SERVICE_API_KEY_LOGIN, array('api_key' => $this->bb->config->api_key),
                BBCache::TTL_WEEK);

            //Can't continue without the user id
            if($result->result != BinaryBeast::RESULT_SUCCESS) {
                return $this->set_error('Unable to determine the account\'s user_id, which is mandatory fo registering callbacks for this type of event');
            }

            //Extract the user id
            $user_id = $result->player_data->user_id;
        }

   		return $this->register_callback(BBCallback::EVENT_TOURNAMENT_CREATED, $url, $action, $recurrent, $args, $user_id);
   	}

	/**
	 * Register a callback / hook, triggered anytime this tournament changes
	 * 
	 * Review the {@link BBCallback} documentation for examples of how to handle a callback
	 * 
	 * @param string $url
	 *	URL called by BinaryBeast when the event is triggered
	 * 
	 * @param string $action
	 *	How to call your <var>$url</var><br />
	 *	Must be <b>post</b> or <b>get</b>
	 * 
	 * @param boolean $recurrent
	 * True by default - if false, the callback is deleted after the first time it is triggered	
	 * 
	 * @param array $args
	 *	Optionally array of custom arguments you'd like BinaryBeast to send when it calls your <var>$url</var>
	 * 
	 * @return int|boolean
	 *	Returns the callback id, false if there was an error registering the callback
	 */
	public function on_change($url, $action = 'post', $recurrent = true, $args = null) {
		return $this->register_callback(BBCallback::EVENT_TOURNAMENT_CHANGED, $url, $action, $recurrent, $args);
	}


	/**
	 * Register a callback / hook, triggered when group rounds begin
	 * 
	 * For documentation, please review {@link on_change()}, as this method is used in an identical manner
	 * 
	 * @param string $url
	 * @param string $action
	 * @param boolean $recurrent
	 * @param array $args
	 * @return int|boolean
	 */
	public function on_start_groups($url, $action = 'post', $recurrent = true, $args = null) {
		return $this->register_callback(BBCallback::EVENT_TOURNAMENT_START_GROUPS, $url, $action, $recurrent, $args);
	}


	/**
	 * Register a callback / hook, triggered when group rounds begin
	 * 
	 * For documentation, please review {@link on_change()}, as this method is used in an identical manner
	 * 
	 * @param string $url
	 * @param string $action
	 * @param boolean $recurrent
	 * @param array $args
	 * @return int|boolean
	 */
	public function on_start_brackets($url, $action = 'post', $recurrent = true, $args = null) {
		return $this->register_callback(BBCallback::EVENT_TOURNAMENT_START_BRACKETS, $url, $action, $recurrent, $args);
	}


	/**
	 * Register a callback / hook, triggered when the tournament finishes (aka, the final match is reported)
	 *
	 * Please review the callback documentation here in {@link BBCallback::EVENT_TOURNAMENT_COMPLETE}
	 *
	 *
	 * For documentation, please review {@link on_change()}, as this method is used in an identical manner
	 *
	 * @param string $url
	 * @param string $action
	 * @param boolean $recurrent
	 * @param array $args
	 * @return int|boolean
	 */
	public function on_complete($url, $action = 'post', $recurrent = true, $args = null) {
		return $this->register_callback(BBCallback::EVENT_TOURNAMENT_COMPLETE, $url, $action, $recurrent, $args);
	}

	/**
	 * Register a callback / hook, triggered when a team is added to the tournament
	 *
	 * Please review the callback documentation here in {@link BBCallback::EVENT_TOURNAMENT_TEAM_ADDED}
	 *
	 *
	 * For documentation, please review {@link on_change()}, as this method is used in an identical manner
	 *
	 * @param string $url
	 * @param string $action
	 * @param boolean $recurrent
	 * @param array $args
	 * @return int|boolean
	 */
	public function on_team_added($url, $action = 'post', $recurrent = true, $args = null) {
		return $this->register_callback(BBCallback::EVENT_TOURNAMENT_TEAM_ADDED, $url, $action, $recurrent, $args);
	}

	/**
	 * Register a callback / hook, triggered when a team is removed to the tournament
	 *
	 * Please review the callback documentation here in {@link BBCallback::EVENT_TOURNAMENT_TEAM_REMOVED}
	 *
	 *
	 * For documentation, please review {@link on_change()}, as this method is used in an identical manner
	 *
	 * @param string $url
	 * @param string $action
	 * @param boolean $recurrent
	 * @param array $args
	 * @return int|boolean
	 */
	public function on_team_removed($url, $action = 'post', $recurrent = true, $args = null) {
		return $this->register_callback(BBCallback::EVENT_TOURNAMENT_TEAM_REMOVED, $url, $action, $recurrent, $args);
	}

	/**
	 * Register a callback / hook, triggered when a match result is reported
	 *
	 * Please review the callback documentation here in {@link BBCallback::EVENT_TOURNAMENT_MATCH_REPORTED}
	 *
	 *
	 * For documentation, please review {@link on_change()}, as this method is used in an identical manner
	 *
	 * @param string $url
	 * @param string $action
	 * @param boolean $recurrent
	 * @param array $args
	 * @return int|boolean
	 */
	public function on_match_reported($url, $action = 'post', $recurrent = true, $args = null) {
		return $this->register_callback(BBCallback::EVENT_TOURNAMENT_MATCH_REPORTED, $url, $action, $recurrent, $args);
	}

	/**
	 * Register a callback / hook, triggered when a match result is unreported / deleted
	 *
	 * Please review the callback documentation here in {@link BBCallback::EVENT_TOURNAMENT_MATCH_UNREPORTED}
	 *
	 *
	 * For documentation, please review {@link on_change()}, as this method is used in an identical manner
	 *
	 * @param string $url
	 * @param string $action
	 * @param boolean $recurrent
	 * @param array $args
	 * @return int|boolean
	 */
	public function on_match_unreported($url, $action = 'post', $recurrent = true, $args = null) {
		return $this->register_callback(BBCallback::EVENT_TOURNAMENT_MATCH_UNREPORTED, $url, $action, $recurrent, $args);
	}

	/**
	 * Register a callback / hook, triggered when the tournament is deleted
	 *
	 * Please review the callback documentation here in {@link BBCallback::EVENT_TOURNAMENT_DELETED}
	 *
	 *
	 * For documentation, please review {@link on_change()}, as this method is used in an identical manner
	 *
	 * @param string $url
	 * @param string $action
	 * @param boolean $recurrent
	 * @param array $args
	 * @return int|boolean
	 */
	public function on_delete($url, $action = 'post', $recurrent = true, $args = null) {
		return $this->register_callback(BBCallback::EVENT_TOURNAMENT_DELETED, $url, $action, $recurrent, $args);
	}

	/**
	 * Register a callback / hook, triggered when the any team in the tournament has a status change (ie unconfirmed->confirmed etc)
	 *
	 * Please review the callback documentation here in {@link BBCallback::EVENT_TOURNAMENT_TEAM_STATUS_CHANGED}
	 *
	 *
	 * For documentation, please review {@link on_change()}, as this method is used in an identical manner
	 *
	 * @param string $url
	 * @param string $action
	 * @param boolean $recurrent
	 * @param array $args
	 * @return int|boolean
	 */
	public function on_team_status($url, $action = 'post', $recurrent = true, $args = null) {
		return $this->register_callback(BBCallback::EVENT_TOURNAMENT_TEAM_STATUS_CHANGED, $url, $action, $recurrent, $args);
	}

	/**
	 * Register a callback / hook, triggered when any of the settings change (title, max_teams, etc)
	 * 
	 * Please review the callback documentation here in {@link BBCallback::EVENT_TOURNAMENT_SETTINGS_CHANGED}
	 * 
	 * 
	 * For documentation, please review {@link on_change()}, as this method is used in an identical manner
	 * 
	 * @param string $url
	 * @param string $action
	 * @param boolean $recurrent
	 * @param array $args
	 * @return int|boolean
	 */
	public function on_settings($url, $action = 'post', $recurrent = true, $args = null) {
		return $this->register_callback(BBCallback::EVENT_TOURNAMENT_SETTINGS_CHANGED, $url, $action, $recurrent, $args);
	}
    //</editor-fold>
    
    /**
     * Fetch the data object that determines the elimination bracket participants and results
     * 
     * @todo implement the $bracket filter
     * 
     * @param int|null $bracket
     * <br /><b>Optional</b><br />
     * Specify a single bracket to return<br />
     * 
     * @return BBBracketsObject|boolean
     * <br />Return values:<br />
     * <ul>
     *  <li>{@link BBBracketsObject} - default return value</li>
     *  <li>{@link BBMatchObject}[][] - if a <var>$bracket</var> integer was provided, only the array of rounds for that bracket is returned</li>
     *  <li><b>False</b> returned if tournament has no brackets / error communicating with the API</li>
     */
    public function &brackets($bracket = null) {
        //Already set
        if(!is_null($this->brackets)) return $this->brackets;

        //Failure
        if(!BBHelper::tournament_has_brackets($this)) {
            return $this->bb->ref(
                $this->set_error('This tournament does not have any brackets to load!')
            );
        }

        //GOGOGO!
        $result = $this->call(self::SERVICE_LOAD_BRACKETS, array('tourney_id' => $this->id),
                self::CACHE_TTL_DRAW, self::CACHE_OBJECT_TYPE, $this->id);

        //Failure!
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
            $this->set_error('Error returned from the API when calling the bracket drawing service, please refer to $bb->result_history for details');
            return $this->bb->ref(false);
        }

        /*
         * Success!
         * 
         * Now pass each returned match through process_draw_match,
         * which will attempt to convert the values into Model objects
         */
        $this->brackets = new stdClass();
        foreach($result->brackets as $bracket => $rounds) {
            //the API's response is keyed by bracket integer, let's translate for convenience of developers
            $bracket = BBHelper::get_bracket_label($bracket, true);
			$this->brackets->{$bracket} = array();
            foreach($rounds as $round => $matches) {
                $this->brackets->{$bracket}[$round] = array();
                foreach($matches as $match) {
					$this->brackets->{$bracket}[$round][] = $this->process_drawn_match($match);
                }
            }
        }

        //Success! now return the result
        return $this->brackets;
    }

    /**
     * Fetch the data object that determines the group round matchups / fixtures and results directly
     * 
     * @return BBGroupsObject|boolean
     * Returns false if tournament has no groups to draw
     */
    public function &groups() {
        //Already set
        if(!is_null($this->brackets)) return $this->brackets;

        //Failure
        if(!BBHelper::tournament_has_group_rounds($this)) {
            $this->set_error('This tournament does not have any groups to load!');
            return $this->bb->ref(false);
        }

        //GOGOGO!
        $result = $this->call(self::SERVICE_LOAD_GROUPS, array('tourney_id' => $this->id),
                self::CACHE_TTL_DRAW, self::CACHE_OBJECT_TYPE, $this->id);

        //Failure!
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
            $this->set_error('Error returned from the API when calling the bracket drawing service, please refer to $bb->result_history for details');
            return $this->bb->ref(false);
        }

        /*
         * Success!
         * 
         * Now pass each returned match through process_draw_match,
         * which will attempt to convert the values into Model objects,
         * save the result in brackets, keyed by the group label
         */
        $this->groups = new stdClass();
        foreach($result->fixtures as $group => $rounds) {
			$group = strtolower($group);
			$this->groups->{$group} = array();
            foreach($rounds as $round => $matches) {
				$this->groups->{$group}[$round] = array();
                foreach($matches as $match) {
                    $this->groups->{$group}[$round][] = $this->process_drawn_match($match);
                }
            }
        }

        //Success! now return the result
        return $this->groups;
    }
    
    
    /**
     * Takes match objects from the API "draw" services, and instantiates models wherever possible
     * 
     * Replaces by reference
     * 
     * @param object $match_object
     * @return object
     */
    private function process_drawn_match($match_object) {
        /*
         * Convert participants into {@link BBTeam} models
         * 
         * We'll use {@link team()} for this.. because if team() can't find an existing model for us..
         * then something has gone terribly wrong
         */
        if(!is_null($match_object->team)) {
            $match_object->team = $this->team($match_object->team->tourney_team_id);
        }
        if(!is_null($match_object->opponent)) {
            $match_object->opponent = $this->team($match_object->opponent->tourney_team_id);
        }

		//Cast as a BBMatch model object
        if(!is_null($match_object->match)) {
            $match = $this->bb->match($match_object->match);
            $match->init($this);
            $match_object->match = $match;
        }

        //Create a new unplayed / open match object, so this match can easily be reported directly
        else {
            if(!is_null($match_object->team) && !is_null($match_object->opponent)) {
                //open_match can handle the conversion for us, which guarantees that we only create a new BBMatch object if the match is truly open / unplayed
                $match_object->match = $this->open_match($match_object->team, $match_object->opponent);
            }
        }

        //Create null values for unset properties, for consistency with the {@link BBMatchObject} "schema"
        if(!isset($match_object->team))     $match_object->team = null;
        if(!isset($match_object->opponent)) $match_object->opponent = null;
        if(!isset($match_object->match))    $match_object->match = null;

        //Success!
        return $match_object;
    }

    /**
     * Fetch a count of tournaments, optionally
     *  using a filter term
     *
     * @param null $filter
     * @param bool $private
     *
     * @return int
     */
    public function count($filter = null, $private = true) {
        $result = $this->bb->call(self::SERVICE_COUNT, array(
            'filter'    => $filter,
            'private'   => $private
        ), self::CACHE_TTL_LIST, self::CACHE_OBJECT_TYPE);

        //Failure! Set an error and return 0
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
            $this->set_error($result->result . ' result code returned from tournament count');
            return 0;
        }

        //Success!
        return $result->count;
    }

    /**
     * Add a map to the map pool
     *
     * @since 2013-07-05
     *
     * @param int|BBMapObject $map
     * Must be either the map_id integer, or the map object
     *
     * @return BBMapObject|boolean
     * - returns the map details if valid
     * - returns false if map_id invalid
     */
    public function add_map($map) {
        //Extract the map_id
        if( ($map_id = $this->get_map_id($map)) === false) {
            return false;
        }

        //Load the existing pool
        $pool = $this->__get('map_pool');

        //Make sure it hasn't been added yet
        foreach($pool as $pool_map) {
            //Already added, simply return the existing map
            if($pool_map->map_id == $map_id) {
                return $pool_map;
            }
        }

        //Load the map object, complain if invalid
        if( is_null($map = $this->bb->map->load($map_id)) ) {
            return $this->set_error('Invalid map_id');
        }

        //Add and flag changes
        $pool[] = $map;
        $this->set_new_data('map_pool', $pool);

        //Success!
        return $map;
    }

    /**
     * Remove a map from the map pool
     *
     * @since 2013-07-05
     *
     * @param int|object $map
     * Must be either the map_id integer, or the map object
     *
     * @return boolean
     */
    public function remove_map($map) {
        //Extract the map_id
        if( ($map_id = $this->get_map_id($map)) === false) {
            return false;
        }

        //Find the map within the pool
        $pool = $this->__get('map_pool');

        //Find the map
        foreach($pool as $x => $pool_map) {
            //Found it!
            if($pool_map->map_id == $map_id) {

                //Remove locally
                unset( $pool[$x] );
                $pool = array_values($pool);
                $this->set_current_data('map_pool', $pool);

                //Remove remotely
                $result = $this->bb->call(self::SERVICE_REMOVE_MAP, array('id' => $pool_map->id));
                if($result->result == 200) {
                    //Clear cache
                    $this->clear_id_cache();

                    //Success!
                    return true;
                }

                //Fail
                return $this->set_error('Removed locally but unable to remove from BinaryBeast');
            }
        }

        //Not part of the pool
        return $this->set_error('Map not found in the current map_pool');
    }

    /**
     * Returns an array of the map_ids for each map in $map_pool
     *
     * @since 2013-07-05
     *
     * @return int[]
     */
    public function map_ids() {
        $pool = $this->__get('map_pool');
        $ids = array();

        foreach($pool as $map) {
            $ids[] = $map->map_id;
        }

        return $ids;
    }

    /**
     * Used by add_map and remove_map to
     *  allow providing either map objects or map_ids in a DRY manner
     *
     * @since 2013-07-05
     *
     * @param int|BBMapObject $map
     *
     * @return int|boolean
     */
    private function get_map_id($map) {
        //Given a map_id
        if(is_numeric($map)) {
            return $map;
        }
        //Extract the id
        else if(is_object($map)) {
            if(!empty($map->map_id)) {
                return $map->map_id;
            }
        }

        //Invalid
        return $this->set_error('Invalid value for $map, must be either a map_id integer, or a map object containing the map_id');
    }
}