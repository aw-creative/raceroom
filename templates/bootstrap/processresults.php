<?php
require_once 'vendor/autoload.php';

// Create DOM from URL or file

require_once 'config/config.php';
chdir(dirname(__FILE__));


$database = new medoo([
// required
'database_type' => 'mysql', 'database_name' => $config['db_name'], 'server' => $config['db_server'], 'username' => $config['db_username'], 'password' => $config['db_password'], 'charset' => 'utf8', ]);
$directory = $config['process_directory'];

if (!is_dir($directory))
{
	exit('Invalid diretory path');
}

function formatSeconds($ms)
{
	return floor($ms / 60000) . ':' . floor(($ms % 60000) / 1000) . ':' . str_pad(floor($ms % 1000) , 3, '0', STR_PAD_LEFT);
}

$files = array();
$PointsSystem = array(
	1 => '25',
	'18',
	'15',
	'12',
	'10',
	'8',
	'6',
	'4',
	'2',
	'1',
	'0'
);

foreach(scandir($directory) as $file)
{
	if ('.' === $file) continue;
	if ('..' === $file) continue;
	$files[] = $file;
	set_time_limit(6);
	$data = file_get_contents($directory . '/' . $file);
	$json = json_decode($data, true);
	if (isset($json['Server']))
	{
		echo "Valid JSON";
		$processdrivers = array();

		// ServerTimeConversion

		$sTest = $json['Time'];
		preg_match("/\(([^\)]*)\)/", $sTest, $aMatches);
		$servertime = $aMatches[1];

		 if ($database->has("serverinfo", ["AND" => ["Time" => $servertime], "Server" => $json['Server']])) {
			echo "Sessions for" . $json['Server'] . "Already Imported - Skipping </br>";
		 }
		 else {

		if ($database->has("leagues", ["LeagueName" => $json['Server']]))
		{
			echo "Adding to League";
			$leagueID = $database->get("leagues", "ID", ["LeagueName" => $json['Server']]);
		}
		else
		{
			echo "Creating New League";
			$leagueID = $database->insert("leagues", ["LeagueName" => $json['Server']]);
		}

		$database->insert("serverinfo", ["Server" => $json['Server'], "Time" => $servertime, "Experience" => $json['Experience'], "Difficulty" => $json['Difficulty'], "FuelUsage" => $json['FuelUsage'], "TireWear" => $json['TireWear'], "MechanicalDamage" => $json['MechanicalDamage'], "FlagRules" => $json['FlagRules'], "CutRules" => $json['CutRules'], "RaceSeriesFormat" => $json['RaceSeriesFormat'], "WreckerPrevention" => $json['WreckerPrevention'], "MandatoryPitstop" => $json['MandatoryPitstop'], "Track" => $json['Track']]);
		$newdatabase->id();
		$sessions = array();
		$sessions = $json['Sessions'];
		foreach($sessions as & $session)
		{
			$Ranked = false;
			echo "<hr> </br>";
			$p_count = count($session['Players']);
			$last_session_id = $database->insert("sessions", ["Type" => $session['Type'], "ServerID" => $last_server_id, "LeagueID" => $leagueID]);
			echo "Session ID is" . $last_session_id . "</br>";
			$players = array();
			$players = $session['Players'];
			
			
			
			$p_count = count($session['Players']);
			if ($p_count >= 4) $ranked = True;
			$counter = 0;
			$Points = '0';
			if ($session['Type'] == preg_match('/^Race/s', $file))
			{
				
			foreach ($players as $key => $value){
				$players[$key]['numlaps'] = count($value['RaceSessionLaps']);
					echo "Before" . $value['FullName'] . "laps" . $players[$key]['numlaps'] . $value['Position'] . "</br>";
					if ($value['FinishStatus'] == 'DidNotFinish'){ $players[$key]['Position'] = '0'; };
			}
			foreach ($players as $key => $row) {
				$volume[$key]  = $row['Position'];
				$edition[$key] = $row['numlaps'];
}
			array_multisort($edition, SORT_DESC, $volume, SORT_ASC, $players);
			
			foreach ($players as $key => $value){
				$players[$key]['Position'] = $key + 1;
					echo "After" . $key . $value['FullName'] . "laps" . $value['numlaps'] . $players[$key]['Position']  . "</br>";
			}
			
				if ($p_count >= 4)
				{
					$numlaps = count($players[0]['RaceSessionLaps']);
					if ($numlaps >= 5)
					{
						$Ranked = True;
						echo "Ranked Session";
						$database->update('serverinfo', ["ranked" => true], ["ID" => $last_server_id]);
					}
				}
				else
				{
					$Ranked = False;
					echo "Unranked Session";
				}

				foreach($players as & $player)
				{
					if ($counter < 11)
					{
						$counter++;
						if ($Ranked)
						{
							$Points = $PointsSystem[$counter];
							echo "This is a Ranked Race Session and points have been awarded </br>";
						}
						else
						{
							$Points = 0;
							echo "This is an Unranked Race Session and no points have been awarded </br>";
						}
					}

					if (empty($player['Username']))
					{
						echo "Player with no username found";
						$puser = $player['FullName'];
					}
					else
					{
						$puser = $player['Username'];
					}

					echo "<hr> </br>";
					if ($database->has("players", ["Username" => $puser]))
					{
						echo "User Already Exists. </br>";
						$last_player_id = $database->get("players", "ID", ["Username" => $puser]);
						echo $last_player_id . "</br>";
						$database->insert("sessionplayers", ["FullName" => $player['FullName'], "Username" => $puser, "Car" => $player['Car'], "Position" => $player['Position'], "BestLapTime" => $player['BestLapTime'], "TotalTime" => $player['TotalTime'], "FinishStatus" => $player['FinishStatus'], "SessionID" => $last_session_id, "PlayerID" => $last_player_id, "Points" => $Points, "Ranked" => $Ranked, "ServerID" => $last_server_id]);
						$processdrivers[] = $last_player_id;
					}
					else
					{
						echo "Adding New User" . $puser, "</br>";
						$last_player_id = $database->insert("players", ["FullName" => $player['FullName'], "Username" => $puser]);
						$database->insert("sessionplayers", ["FullName" => $player['FullName'], "Username" => $puser, "Car" => $player['Car'], "Position" => $player['Position'], "BestLapTime" => $player['BestLapTime'], "TotalTime" => $player['TotalTime'], "FinishStatus" => $player['FinishStatus'], "SessionID" => $last_session_id, "PlayerId" => $last_player_id, "Points" => $Points, "Ranked" => $Ranked, "ServerID" => $last_server_id]);
						$processdrivers[] = $last_player_id;
						echo $last_player_id . "</br>";
					}

					$laps = array();
					$laps = $player['RaceSessionLaps'];
					foreach($laps as $lap)
					{
						set_time_limit(1);
						$ltime = $lap['Time'];
						$database->insert("racesessionlaps", ["PlayerID" => $last_player_id, "SessionID" => $last_session_id, "Time" => $ltime, "Position" => $lap['Position'], "PitStopOccured" => $lap['PitStopOccured'], ]);
					}
				}
			}
			else
			{
				foreach($players as & $player)
				{
					if (empty($player['Username']))
					{
						echo "Player with no username found";
						$puser = $player['FullName'];
					}
					else
					{
						$puser = $player['Username'];
					}

					echo "<hr> </br>";
					if ($database->has("players", ["Username" => $puser]))
					{
						echo "User Already Exists. </br>";
						$last_player_id = $database->get("players", "ID", ["Username" => $puser]);
						echo $last_player_id . "</br>";
						$database->insert("sessionplayers", ["FullName" => $player['FullName'], "Username" => $puser, "Car" => $player['Car'], "Position" => $player['Position'], "BestLapTime" => $player['BestLapTime'], "TotalTime" => $player['TotalTime'], "FinishStatus" => $player['FinishStatus'], "SessionID" => $last_session_id, "PlayerID" => $last_player_id, "Ranked" => $Ranked, "ServerID" => $last_server_id]);
					}
					else
					{
						echo "Adding New User" . $player['FullName'], "</br>";
						$last_player_id = $database->insert("players", ["FullName" => $player['FullName'], "Username" => $puser, ]);
						$database->insert("sessionplayers", ["FullName" => $player['FullName'], "Username" => $puser, "Car" => $player['Car'], "Position" => $player['Position'], "BestLapTime" => $player['BestLapTime'], "TotalTime" => $player['TotalTime'], "FinishStatus" => $player['FinishStatus'], "SessionID" => $last_session_id, "PlayerID" => $last_player_id, "Ranked" => $Ranked, "ServerID" => $last_server_id]);
					}

					$laps = array();
					$laps = $player['RaceSessionLaps'];
					foreach($laps as $lap)
					{
						$ltime = formatSeconds($lap['Time']);
						$database->insert("racesessionlaps", ["PlayerID" => $last_player_id, "SessionID" => $last_session_id, "Time" => $ltime, "Position" => $lap['Position'], "PitStopOccured" => $lap['PitStopOccured']]);
					}
				}
			}
		}

			}

		print_r($processdrivers);
		echo "<hr>";
		foreach($processdrivers as $p)
		{
			$totalpoints = $database->sum('sessionplayers', 'Points', ['PlayerID' => $p]);
			$database->update('players', ['totalpoints' => $totalpoints], ['ID' => $p]);
		}

		rename($directory . '/' . $file, $config['destination_directory'] . $file);
	}
	else
	{
		$filename = $directory . '/' . $file;
		if (is_file($filename))
		{
			chmod($filename, 0777);
			if (unlink($filename))
			{
				echo 'This File is invalid - File deleted';
			}
			else
			{
				echo 'Cannot remove that file';
			}
		}
		else
		{
			echo 'File does not exist';
		}
	}
}

?>