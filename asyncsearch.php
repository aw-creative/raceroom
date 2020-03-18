<?php
// Load Dependancies
require_once 'vendor/autoload.php';

$loggedin = false;
$steamprofile = array();

if(!isset($_SESSION['steamid'])) {

   $loggedin = false;
    
}  else {
    include ('vendor/steamauth/userInfo.php');

   $loggedin = true;
   
}  

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

if (isset($_GET['term'])){

$drivers = $database->select('players' , 'FullName' ,[
"OR" => [
"FullName[~]" => $_GET['term'],
"Username[~]" => $_GET['term']
],  
"AND" => ['registered' => 0]
]
);

echo json_encode($drivers);
}


?>