<?php

// Load Dependancies
require_once 'vendor/autoload.php';
//get the config file
require_once 'config/config..php';
//Set up the database
chdir(dirname(__FILE__));
$database = new medoo([
// required
	'database_type' => 'mysql',
	'database_name' => $config['db_name'],
	'server' => $config['db_server'], 
	'username' => $config['db_username'], 
	'password' => $config['db_password'],
	'charset' => 'utf8', ]);

// Load templating engine
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, array(
 'debug' => true
)
 );

 //Start stuff
$template = $twig->loadTemplate('rankings.phtml');

chdir(dirname(__FILE__));
$database = new medoo([
'database_type' => 'mysql', 'database_name' => 'r3eresults', 'server' => 'localhost', 'username' => 'ucycjglp', 'password' => 'nZzf24^9', 'charset' => 'utf8', ]);
$rank = array();
$rankings = array();
$skipsession = 0;
$allplayers = $database->select("players", ["ID", "FullName"]);
foreach ($allplayers as $player){
		$p_id= $player["ID"];
		$p_name = $player["FullName"];
		$sessionplayers = $database->select("sessionplayers", "Points",[ "AND" => ["PlayerID" => $p_id , "Ranked" => true, "Points[>=]" => 1 ]]);
		$totalPoints = (array_sum($sessionplayers));
		if ($totalPoints == 0)continue;
		$p_average = $totalPoints / count($sessionplayers);
		$p_array = array("points" => $totalPoints, "average" => $p_average);
		$rank[$p_name] = $p_array;
		}


arsort($rank);
$rankings = array( "rank" => $rank); 
$template->display($rankings);
?>