<?php

// Load Dependancies
require_once 'vendor/autoload.php';
require_once 'vendor/steamauth/steamauth.php';

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

// Load templating engine
$loader = new Twig_Loader_Filesystem($config['template']);
$twig = new Twig_Environment($loader, array(
 'debug' => true
)
 );

 //Start stuff

$template = $twig->loadTemplate('search.phtml');

if(isset($_GET['search'])){ 
if ($_GET['search'] == 'all'){
	$servers = $database->select("serverinfo", ["Server", "Time", "Track" , "ID" ]);
	$searchdb = array();
$searchresults = array();
	foreach ($servers as $key => $server){
		$seconds = $server['Time']/1000;
		$servers[$key]['Date'] = $server['Date'] = date('l jS \of F Y H:i', $seconds);
 }
	$drivers = array();
}else{
$searchfor = $_GET['search'];
$servers = $database->select("serverinfo", ["Server", "Time", "Track" , "ID" ],[
"OR" => ["Server[~]" => $searchfor, "Track[~]" => $searchfor,],
"ORDER" => "Time DESC"]);

$searchdb = array();
$searchresults = array();
	foreach ($servers as $key => $server){
		$seconds = $server['Time']/1000;
		$servers[$key]['Date'] = $server['Date'] = date('l jS \of F Y H:i', $seconds);
 }
$drivers = $database->select("players", ["Fullname", "ID" ],[ "OR" => ["FullName[~]" => $searchfor, "Username[~]" => $searchfor]]);
}
 } 
	  else{
		$searchresults = array();
		$drivers = array();
}



$template->display(array(
'serverdata' => $servers, 
'driverData' => $drivers, 
'template' => $config['template'],
'site_url' => $_SERVER['SERVER_NAME'],
'site_name' => $config['site_name'],
'pagetitle' => 'Search',
'profile' => $steamprofile, 
));




function formatSeconds($ms)
{
	return floor($ms / 60000) . ':' . floor(($ms % 60000) / 1000) . ':' . str_pad(floor($ms % 1000) , 3, '0', STR_PAD_LEFT);
}
?>