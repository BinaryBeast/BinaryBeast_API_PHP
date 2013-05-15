# BinaryBeast API PHP Library
#### Version 3.1.2 (2013-05-14)

----------



This library provides functionality for easily and quickly accessing the BinaryBeast API Services

Unlike the previous versions, the 3.0.0+ version of this library is now [Object-oriented](http://en.wikipedia.org/wiki/Object_oriented)


# Before you get started #

There are two things you need to do before you start developing:


##### 1) Get your API Key

You can find your api_key from your user settings at binarybeast.com <http://binarybeast.com/user/settings/api>


##### 2) Configure the values in lib/BBConfiguration.php

The values in [BBConfiguration.php](lib/BBConfiguration.php) allow you to define your **api_key**, setup **local response caching**, and even **extend model classes**

**Note:** This will not affect users currently supplying an api\_key to the [BinaryBeast](BinaryBeast.php), constructor still accepts an api_key value


### Backwards Compatability

Thanks to [BBLegacy.php](lib/BBLegacy.php), your code will NOT break if you upgrade to the latest library version


### Response Caching

In order to cut down on the number of API calls your application needs to make, this library provides integrated response caching through the [BBCache](lib/BBCache.php) class

However in order to take advantage of this, you must define your database connection details in [BBConfiguration](lib/BBConfiguration.php)


#### API Request Logs

You can view a full log of your recent API activity, including what arguments were sent, and how the API responded here: <http://binarybeast.com/user/settings/api_history>

# Quick Examples #


#### Example: Create a Tournament ####

```php

require('BinaryBeast.php');
$bb = new BinaryBeast();

$tournament = $bb->tournament();
$tournament->title = 'My new tournament!';
if($tournament->save()) {
	echo '<a href="' . $tournament->url . '">Tournament Created Successfully!</a>';
}
else {
	var_dump($bb->last_error);
}
```

### Example: Fetch and Report Open Matches

```php
	
require('BinaryBeast.php');
$bb = new BinaryBeast();

$tournament = $bb->tournament('my_tournament_id');
if(sizeof($tournament->open_matches()) > 0) {
	$match = $tournament->open_matches[0];
	$match->set_winner($match->team2());
	if($match->report()) {
		echo $match->winner() . ' defeated ' . $match->loser() . ' in match ' . $match->id;
	}
	else {
		var_dump($bb->last_error);
	}
}
```


### Example: Embed Group Rounds
##### Using [BBHelper](lib/BBHelper.php)

```php

require('BinaryBeast.php');
$bb = new BinaryBeast();

BBHelper::embed_tournament_groups('my_tournament_id');

```

### Example: Embed Brackets
##### Using [BBTournament](lib/BBTournament.php)


```php

require('BinaryBeast.php');
$bb = new BinaryBeast();

$tournament = $bb->tournament('my_tournament_id');
$tournament->embed();

```


### Example: Load a List of StarCraft 2 Maps

```php

$maps = $bb->map->game_list('SC2');
foreach($maps as $map) {
    echo $map->map . ' (' . $map->map_id . ') <br />';
}

```


### Example: Get StarCraft 2 Race IDs

```php

$races = $bb->race->game_list('SC2');
foreach($races as $race) {
   	echo '<img src="' . $race->race_icon . '" /> ' . $race->race . ' (' . $race->race_id . ') <br />';
}

```


### Extending the Library

Coming soon


----------


## Recent Changes

### Version 3.1.2 (2013-05-15)
Adds **Development Mode**

* Call `$bb->enable_dev_mode()` to enable
	* `$bb->disable_dev_mode()` to disable, as you may have guessed
* Automatically displays binarybeast-related errors

### Version 3.1.1 (2013-05-15)
Replaced `get_called_class()` with `get_class($this)`

* This was done in order to make the library compatible with `PHP 5.2`

### Version 3.1.0 (2013-05-05)
Removed some debugging output in BBModel that happened to have php 5.4-only syntax


### Version 3.0.9 (2013-05-03)
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


### Version 3.0.8 (2013-04-26)
* Added support for loading and setting participant races in [BBTeam](lib/BBTeam.php)


### Version 3.0.7 (2013-04-25)
* Fixed a bug that caused `BBMatch::$winner` to return NULL from existing match objects
	* 	The issue was actually in [BBModel::__get()](lib/BBModel.php)
* Added / Fixed some of the documentation


### Version 3.0.6 (2013-04-19)
* Renamed BBMatch::$round and BBMatch::round()
    * `BBMatch::$round` property renamed to `BBMatch::$round_format`
    * `BBMatch::round()` method renamed to `BBMatch::round_format()`
* Added `BBMatch::$round` property
    * This is a simple integer telling you which round the match was played in
        * Previous you would have had to evaluate `$match->round->round` to see the round number

### Version 3.0.5 (2013-04-05)
* Added custom callback support
	* Added callback registration methods to [BBTournament](lib/BBTournament.php)
* Added methods for fetching bracket/group data in [BBTournament](lib/BBTournament.php)
	* Added examples in for drawing [brackets](examples/tournament/draw/brackets.php) and [groups](examples/tournament/draw/groups.php)

----------

# Full Documentation #

These were just a few quick examples, visit BinaryBeast.com for more examples, and full documentation of the library

### [http://binarybeast.com/content/api/docs/php/class-BinaryBeast.html](http://binarybeast.com/content/api/docs/php/class-BinaryBeast.html "Official Documentation")


----------


# Development / Issue Tracking #

Public development and issue tracking is available through our [public trello board](https://trello.com/board/public-development-board/516c69726403c70869000735) thatfor 
