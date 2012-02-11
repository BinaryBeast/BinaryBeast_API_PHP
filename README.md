# BinaryBeast.php v2.65
### <http://binarybeast.com/api/info>

`BinaryBeast.php` is a PHP class written to ease development requirements for integrating the `binarybeast.com` API into your PHP application


It determines which methods to use to communicate with the BinaryBeast services, and which methods to use to parse the result, by testing the capabilities of your PHP installation

Here's a quick tutorial:

### How to use in your PHP project

Copy `BinaryBeast.php` to your project directory, and require it

require('BinaryBeast.php');

The next step is to instantiate it.. the constructor takes one paramater: your `api_key`

$bb = new BinaryBeast('my_api_key_here');

You can find your api_key from your user settings at binarybeast.com <http://binarybeast.com/user/settings/api>

Another useful page to note is the API History page in your user settings.  It lists your recent API requests, with anything posted, and exactly how it responded
<http://binarybeast.com/user/settings/api_history>



Here's a list of wrapper methods currently available

### tournament_create($options)
$options is an associate array, the available options are: 

string title
string description
int    public
string game_code            	(SC2, BW, QL examples, @see <http://wiki.binarybeast.com/index.php?title=API_PHP:_game_search>)
int    type_id              	(0 = elimination brackets, 1 = group rounds to elimination brackets, also the BinaryBeast.php class has constants to help with this)
int    elimination          	(1 = single, 2 = double
int    max_teams
int    team_mode            	(id est 1 = 1v1, 2 = 2v2)
int    group_count
int    teams_from_group
date   date_start		YYYY-MM-DD HH:SS
string location
array  teams			You may automatically add players, with a simpl indexed array of player names
int    return_data          	(0 = TourneyID and URL only, 1 = List of team id's inserted (from teams array), 2 = team id's and full tourney info dump)

### tournament_update($tourney_id, $options)

$tourney_id obviously... $options, you have the same options available as tourneament_create


### tournament_delete($tourney_id)

### tournament_start($tourney_id, $seeding = 'random, $teams = null)

$seeding can be 'random', 'traditional', 'balanced', or 'manual'

If chooseing anything other than random, you pass in an indexed array of tourney_team_id's to $teams

For manual, the $teams will be the exact order of teams in the brackets

For traditional and balanced, $teams must be the list of teams in order of rank, index 0 being the top ranked player/team

### tournament_round_update($tourney_id, $bracket, $round = 0, $best_of = 1, $map = null, $date = null)

Update the format of a single round in the tournament

$bracket: ie 0 = groups, 1 = winners (there are class constants for these values


### tournament_round_update_batch($tourney_id, $bracket, $best_ofs = array(), $maps = array(), $dates = array())

Just like tournament_round_update.. except it effects an entire bracket in one go (this is the suggested method if you're doing anything but editing a single round)

the only difference is $best_ofs, $maps, and $dates are arrays

for example... $best_ofs[0] = 3; $maps[0] = "Xel'naga Caverns', $dates[0] = '2012-12-31';, will tell binaryBeast that the first round of the bracket will be a Best of 7 on Xel'Naga Caverns at the end of the world


### tournament_list($filter, $limit = 30, $private = true)

List your touranments.. you can set a filter

By default it loads all of your tournaments, even the private ones

But for example if you want to draw a list of your tournaments on the home page of your website, but you don't want private tournaments to show up... instead of parsing through them manually and checking ->public == 1, you can simply pass $private => false when calling the service
