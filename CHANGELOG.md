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