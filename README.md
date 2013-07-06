# BinaryBeast API PHP Library
#### Version 3.1.9 (2013-07-06)

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


### Version 3.1.9 (2013-07-06)
* Adds `BBMap::Load()`
	* Allows loading a map by map_id
* Adds support for the new tournament map_pool services
	* `$tournament->map_pool` to fetch the map pool
	* `$tournament->add_map()` to add a map
	* `$tournament->remove_map()` to remove a map    
	* 
### Version 3.1.8 (2013-07-01)
* Adds `$tournament->played_matches($freewins = false, $stream = false)`
	* Returns array of recently reported [BBMatch](lib/BBMatch.php) objects
		* Set `$stream = true` and it will only return each match result once 


### Version 3.1.7 (2013-06-19)
* Fixes a bug in BBTournament that was causing fatal PHP errors after saving a new tournament with teams already added


### Version 3.1.6 (2013-06-07)
* Restructured the way BBTeam stores and filters BBTeam arrays to resolve bugs caused by stale cached results
* Adds `$tournament->freewins` and `$tournament->freewins()` for returning freewin team objects

### Version 3.1.5 (2013-06-06)
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
*  

### Version 3.1.4 (2013-06-03)
* $tournament->hidden is now automatically automatically encoded and decoded as json


### Version Library 3.1.3 (2013-05-24)
* Adds `$bb->config` magic property
	* Returns the current configuration object (`BBConfiguration`)
* Adds `$bb->tournament->on_create('url here')` callback
	* Triggered when tournaments are created the account associated with the api_key
* Adds `BBCache::TTL_*` constants, simple TTL values for certain time periods
	* For example, `BBCache::TTL_WEEK` can be used to cache an API response for 1 week


See the [change log](CHANGELOG.md) for more

----------

# Full Documentation #

These were just a few quick examples, visit BinaryBeast.com for more examples, and full documentation of the library

### [http://binarybeast.com/content/api/docs/php/class-BinaryBeast.html](http://binarybeast.com/content/api/docs/php/class-BinaryBeast.html "Official Documentation")


----------


# Development / Issue Tracking #

Public development and issue tracking is available through our [public trello board](https://trello.com/board/public-development-board/516c69726403c70869000735) thatfor 
