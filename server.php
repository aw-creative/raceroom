<?php

// Load Dependancies

require_once 'vendor/autoload.php';

require_once 'vendor/steamauth/steamauth.php';

$loggedin = false;
$steamprofile = array();

if (!isset($_SESSION['steamid']))
{
	$loggedin = false;
}
else
{
	include ('vendor/steamauth/userInfo.php');

	$loggedin = true;
}

// get the config file

require_once 'config/config..php';

// Set up the database

chdir(dirname(__FILE__));
$database = new medoo([

// required

'database_type' => 'mysql', 'database_name' => $config['db_name'], 'server' => $config['db_server'], 'username' => $config['db_username'], 'password' => $config['db_password'], 'charset' => 'utf8', ]);

// Load templating engine

$loader = new Twig_Loader_Filesystem($config['template']);
$twig = new Twig_Environment($loader, array(
	'debug' => true
));
$twig->addExtension(new Twig_Extension_Debug());

// Start stuff

if (isset($_GET['ID']))
{$selectedserver = $_GET['ID'];

if ($loggedin){
$playerID = $database->get('players','ID',['steamID' => $steamprofile['steamid']]);
$ServerFilter = $database->select('uleagueservers','LeagueID' ,['ServerID' => $selectedserver ]);
if (!empty($ServerFilter)){
$UleagueServers = $database->select('userleagues', '*' , [
"AND" => [
'OwnerID' => $playerID, 
"ID[!]" => $ServerFilter]]);
}else{
$UleagueServers = $database->select('userleagues', '*' , ['OwnerID' => $playerID, ]);
}


$steamprofile['PlayerID'] = $playerID;
}
	$Server = $database->get("serverinfo", "*", ["ID" => $selectedserver]);
	$Sessions = $database->select("sessions", "*", ["ServerID" => $Server['ID']]);
	$seconds = $Server['Time'] / 1000;
	$Server['Date'] = date('l jS \of F Y H:i', $seconds);
	$TrackImage = $Server['Track'];
	$Server['TrackImage'] = seoUrl($TrackImage);
	$PointsSystem = array(
		1 => 25,
		18,
		15,
		12,
		10,
		8,
		6,
		4,
		2,
		1
	);
	$LeagueInfo = " ";
	$raceChart = array();
	$maxlaps = 0;
	foreach($Sessions as $Session)
	{
		extract($Session, EXTR_PREFIX_ALL, "s");
		$Players = $database->select("sessionplayers", "*", ["SessionID" => $Session['ID']], ["ORDER" => "Position"]);
		if ($Session['Type'] == "Race")
		{
			$LeagueInfo = $database->get("Leagues", "LeagueName", ["ID" => $Session['LeagueID']]);
		}

		$counter = 0;
		$Points = 0;
		foreach($Players as $pkey => $Player)
		{
			extract($Player, EXTR_PREFIX_ALL, "p");
			if ($counter < 10)
			{
				$counter++;
				$Points = $PointsSystem[$counter];
				$Player['Points'] = $Points;
			}
			else
			{
				$Player['Points'] = 0;
			}

			if ($p_Position == 1)
			{
				$maxlaps = range(1, $database->count("racesessionlaps", ["AND" => ["PlayerID" => $p_PlayerID, "SessionID" => $s_ID]]));
			}
			
			$laptime = $p_BestLapTime;
			if ($p_BestLapTime < 0)
			{
				$laptime = 0;
			}
			else
			{
				$Player['BestLapTime'] = formatSeconds($laptime);
			}

			$racetime = $p_TotalTime;
			if ($p_TotalTime < 0)
			{
				$racetime = 0;
			}
			else
			{
				$Player['TotalTime'] = formatSeconds($racetime);
			}

			$laps = $database->select("racesessionlaps", "*", ["AND" => ["PlayerID" => $p_PlayerID, "SessionID" => $s_ID]]);
			$laptimes = $database->select("racesessionlaps", "Time", ["AND" => ["PlayerID" => $p_PlayerID, "SessionID" => $s_ID]]);
			if (count($laptimes) >= 1){
			$Player['Consistency'] = 100 - round(sd($laptimes)/1000 ,2);
		}
			$Pitstops = 0;
			$incTime = array(
				0 => 0
			);
			foreach($laps as $lkey => $lap)
			{
				if ($lap['Position'] == '0')
				{
					unset($laps[$lkey]);
					continue;
				}

				if ($lap['PitStopOccured'] == 1)
				{
					$Pitstops++;
				}

				$ltime = $lap['Time'];
				unset($laps[$lkey]['Time']);
				$laps[$lkey]['Time'] = formatSeconds($lap['Time']);
				if ($lkey >= 1)
				{
					$laps[$lkey]['difference'] = $laps[$lkey - 1]['difference'] + $lap['Time'];
				}
				else
				{
					$laps[$lkey]['difference'] = 0;
				}

				if ($lap['Position'] == '0')
				{
					unset($laps[$lkey]);
				}

				if ($Session['Type'] == 'Race')
				{
					$raceChart[$Player['FullName']]['laps'][] = $lap['Position'];
					
				}
			}

			$pDetail = $database->get("players", "*", ["ID" => $Player['PlayerID']]);
			$Player['FullName'] = $pDetail['FullName'];
			$Player['Username'] = $pDetail['Username'];
			$Player['Racesessionlaps'] = $laps;
			$Player['Pitstops'] = $Pitstops;
			$Session['Players'][] = $Player;
			$raceChart[$Player['FullName']]['color'] = random_color();
			if ($Session['Type'] == "Race")
			{
				$baselap = array();
				$difflap = array();
				$winlaps = $Session['Players'][0]['Racesessionlaps'];
				foreach($winlaps as $winlap)
				{
					$baselap[] = $winlap['difference'];
				}

				$mylaps = $Session['Players'][$pkey]['Racesessionlaps'];
				foreach($mylaps as $key => $mylap)
				{
					$difflap[] = ($mylap['difference'] - $baselap[$key]) / 1000;
				}

				$raceChart[$Player['FullName']]['dif'] = $difflap;
				$lapdetail = $Session['Players'][$pkey]['Racesessionlaps'];
				foreach($lapdetail as $difkey => $value)
				{
					$Session['Players'][$pkey]['Racesessionlaps'][$difkey]['dif'] = formatSeconds($difflap[$difkey]);
				}
			}
		}

		$Server['Sessions'][] = $Session;
		$Server['maxlaps'] = $maxlaps;
	}

	// Hence $largeArray has the largest

	$Server['chart'] = $raceChart;
	$template = $twig->loadTemplate('server.phtml');
	$template->display(array(
		'Server' => $Server,
		'template' => $config['template'],
		'site_url' => $_SERVER['SERVER_NAME'],
		'site_name' => $config['site_name'],
		'pagetitle' => $Server['Server'],
		'profile' => $steamprofile,
		'uleagueservers' => $UleagueServers,
	));
}

function formatSeconds($ms)
{
	return floor($ms / 60000) . ':' . floor(($ms % 60000) / 1000) . '.' . str_pad(floor($ms % 1000) , 3, '0', STR_PAD_LEFT);
}

function seoUrl($string)
{

	// Lower case everything

	$string = strtolower($string);

	// Make alphanumeric (removes all other characters)

	$string = preg_replace("/[^a-z0-9_\s-]/", "", $string);

	// Clean up multiple dashes or whitespaces

	$string = preg_replace("/[\s-]+/", " ", $string);

	// Convert whitespaces and underscore to dash

	$string = preg_replace("/[\s_]/", "-", $string);
	return $string;
}

function random_color_part()
{
	return str_pad(dechex(mt_rand(0, 255)) , 2, '0', STR_PAD_LEFT);
}

function random_color()
{
	return random_color_part() . random_color_part() . random_color_part();
}

function sd_square($x, $mean) { return pow($x - $mean,2); }

// Function to calculate standard deviation (uses sd_square)    
function sd($array) {
    
// square root of sum of squares devided by N-1
return sqrt(array_sum(array_map("sd_square", $array, array_fill(0,count($array), (array_sum($array) / count($array)) ) ) ) / (count($array)-1) );
}
?>