# BinaryBeast API PHP Library - v3.0.1
#### Version 3.0.0 (2013-03-27)

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

require('BinaryBeast.com');
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
	
require('BinaryBeast.com');
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



# Full Documentation #

These were just a few quick examples, visit BinaryBeast.com for more examples, and full documentation of the library

### [http://binarybeast.com/content/api/docs/php/class-BinaryBeast.html](http://binarybeast.com/content/api/docs/php/class-BinaryBeast.html "Official Documentation")
