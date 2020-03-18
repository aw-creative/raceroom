<?php
require_once 'vendor/autoload.php';

// Create DOM from URL or file

require_once 'config/config..php';

chdir(dirname(__FILE__));
$database = new medoo([
// required
	'database_type' => 'mysql',
	'database_name' => $config['db_name'],
	'server' => $config['db_server'], 
	'username' => $config['db_username'], 
	'password' => $config['db_password'],
	'charset' => 'utf8', ]);

$processdrivers = $database->select('players', 'ID');

foreach ($processdrivers as $p){
			$totalpoints = $database->sum('sessionplayers','Points',['PlayerID' => $p]);
			$database->update('players',['totalpoints' => $totalpoints],['ID' => $p]);
			echo "Points Calculated </br>";
			}
?>