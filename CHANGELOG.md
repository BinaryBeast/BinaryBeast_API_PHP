
## BinaryBeast API PHP Library 3.1.9 (2013-07-06)
* Adds `BBMap::Load()`
	* Allows loading a map by map_id
* Adds support for the new tournament map_pool services
	* `$tournament->map_pool` to fetch the map pool
	* `$tournament->add_map()` to add a map
	* `$tournament->remove_map()` to remove a map    

	 
## BinaryBeast API PHP Library 3.1.8 (2013-07-01)
* Adds `$tournament->played_matches($freewins = false, $stream = false)`
	* Returns array of recently reported [BBMatch](lib/BBMatch.php) objects
		* Set `$stream = true` and it will only return each match result once

## BinaryBeast API PHP Library 3.1.7 (2013-06-19)
* Fixes a bug in BBTournament that was causing fatal PHP errors after saving a new tournament with teams already added

## BinaryBeast API PHP Library 3.1.6 (2013-06-07)
* Restructured the way BBTeam stores and filters BBTeam arrays to resolve bugs caused by stale cached results
* Adds `$tournament->freewins` and `$tournament->freewins()` for returning freewin team objects

## BinaryBeast API PHP Library 3.1.5 (2013-06-06)
* `result` table for api cache is now created as longtext
* `BBDev` now extracts data before dumping objects (to avoid fatal serialization errors)
* `save()` and `delete()` now clear list and id cache
	* Resolves issue of stale list results
* Model list results now honor custom classes defined in `BBConfiguration`
* New method: `$tournament->count()`
	* Returns the total number of tournaments created by your account
* `$team->notes` now automatically automatically encoded and decoded as json\
*  `$tournament->teams()` now returns FreeWins if you set the 3rd parameter (`$freewins`) to `true`
*  Adds `BBSimpleModel::SERVICE_LIST` for consistency

## BinaryBeast API PHP Library 3.1.4 (2013-06-03)
* $tournament->hidden is now automatically automatically encoded and decoded as json

## BinaryBeast API PHP Library 3.1.3 (2013-05-24)
* Adds `$bb->tournament->on_create('url here')` callback
	* Triggered when tournaments are created the account associated with the api_key
* Adds `$bb->config` magic property
	* Returns the current configuration object (`BBConfiguration`)
* Adds `BBCache::TTL_*` constants, simple TTL values for certain time periods
	* For example, `BBCache::TTL_WEEK` can be used to cache an API response for 1 week


## BinaryBeast API PHP Library 3.1.2 (2013-05-15)
Adds **Development Mode**

* Call `$bb->enable_dev_mode()` to enable
	* `$bb->disable_dev_mode()` to disable, as you may have guessed
* Automatically displays binarybeast-related errors

## BinaryBeast API PHP Library 3.1.1 (2013-05-15)
Replaced `get_called_class()` with `get_class($this)`

* This was done in order to make the library compatible with `PHP 5.2`

## BinaryBeast API PHP Library 3.1.0 (2013-05-05)
Removed some debugging output in BBModel that happened to have php 5.4-only syntax

## BinaryBeast API PHP Library 3.0.9 (2013-05-03) ##
Documented some fields that the API recently exposed to the public API

**Classes Affected:**

* [BBRound](lib/BBRound.php)
	* Adds `map_icon`, `map_icon`, and `game_code`
* [BBGame](lib/BBGame.php)
	* Notable additions: `genre`, `genre_abbreviation`, `parent_id`, and some new setting values
* [BBMap](lib/BBMap.php)
	* Adds `games`, an array of games associated with the map (includes games that inherit the map)
* [BBRace](lib/BBRace.php)
	* Adds `games`, an array of games associated with the race (includes games that inherit the race)


## BinaryBeast API PHP Library 3.0.8 (2013-04-26) ##
* Added support for loading and setting participant races in [BBTeam](lib/BBTeam.php)


## BinaryBeast API PHP Library 3.0.7 (2013-04-25) ##
* Fixed a bug that caused `BBMatch::$winner` to return NULL from existing match objects
	* 	The issue was actually in [BBModel::__get()](lib/BBModel.php)
* Added / Fixed some of the documentation


## BinaryBeast API PHP Library 3.0.6 (2013-04-19) ##

* Renamed BBMatch::$round and BBMatch::round()
    * `BBMatch::$round` property renamed to `BBMatch::$round_format`
    * `BBMatch::round()` method renamed to `BBMatch::round_format()`
* Added `BBMatch::$round` property
    * This is a simple integer telling you which round the match was played in
        * Previous you would have had to evaluate `$match->round->round` to see the round number

## BinaryBeast API PHP Library 3.0.5 (2013-04-05) ##

* Added custom callback support
	* Added callback registration methods to [BBTournament](lib/BBTournament.php)
* Added methods for fetching bracket/group data in [BBTournament](lib/BBTournament.php)
	* Added examples in for drawing [brackets](examples/tournament/draw/brackets.php) and [groups](examples/tournament/draw/groups.php)


## BinaryBeast API PHP Library 3.0.0 (2013-03-26) ##

* Completely re-built Object Oriented library
