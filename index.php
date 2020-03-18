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
$twig->addExtension(new Twig_Extension_Debug());
 //Start stuff

 //genrate some stats
 $tnumplayers = $database->count('players');
 $tnumraces = $database->count('sessions',['Type' => 'Race']);
 $tnumlaps = $database->count('racesessionlaps');
 $tnumleagues = $database->count('leagues');
 $tnumPointsScored =  array_sum($database->select('sessionplayers', 'points'));
 $allcars = $database->select('sessionplayers', 'car');
 $weektime = time() - 7*24*60*60;
 $lastweek = round($weektime) * 1000;
 $serverslastweek = $database->count('serverinfo',['time[>]' =>  $lastweek]);
	$c = array_count_values($allcars); 
	$tfavcar = array_search(max($c), $c);
	$drivers = $database->select( "players" , ['ID' , 'FullName' , 'totalpoints'],[ "ORDER" => 'totalpoints DESC', 'LIMIT' => '10' ]);
	$topdrivers = array();
	
//Create Stats Array 
 $stats = array(
 'players' => $tnumplayers, 
 'races' => $tnumraces, 
 'laps' => $tnumlaps, 
 'leagues' => $tnumleagues , 
 'points' => $tnumPointsScored , 
 'car' => $tfavcar , 
 'lastweekservers' =>   $serverslastweek , 
 'topdrivers' => $drivers , 
);
 

$template = $twig->loadTemplate('index.phtml');

$rservers = $database->select("serverinfo", ["Server", "Time", "Track" , "ID" , "ranked" ],["ranked" => true , "ORDER" => "Time DESC", "LIMIT" => "10"]);
$uservers = $database->select("serverinfo", ["Server", "Time", "Track" , "ID" , "ranked" ],["ranked" => false , "ORDER" => "Time DESC", "LIMIT" => "10"]);

$rsearchdb = array();
$usearchdb = array();

$searchresults = array();
$searchresults['Title'] = "Most Recent Servers";
$searchresults['Stats'] = $stats;

foreach ($rservers as $server){
		extract($server, EXTR_PREFIX_ALL, "svr");
		$seconds = $svr_Time/1000;
		$rsearchdb['Name'] = $svr_Server;
		$rsearchdb['Day'] = date(' jS', $seconds);
		$rsearchdb['Month'] = date('M', $seconds);
		$rsearchdb['Year'] = date('Y', $seconds);
		$rsearchdb['Time'] = date('H:i', $seconds);
		$rsearchdb['Track'] = $svr_Track;
		$rsearchdb['ID'] = $svr_ID;
		$rsearchdb['ranked'] = $svr_ranked;
		$searchresults['RankedServers'] [] = $rsearchdb;
 }
 foreach ($uservers as $server){
		extract($server, EXTR_PREFIX_ALL, "svr");
		$seconds = $svr_Time/1000;
		$usearchdb['Name'] = $svr_Server;
		$usearchdb['Day'] = date(' jS', $seconds);
		$usearchdb['Month'] = date('M', $seconds);
		$usearchdb['Year'] = date('Y', $seconds);
		$usearchdb['Time'] = date('H:i', $seconds);
		$usearchdb['Track'] = $svr_Track;
		$usearchdb['ID'] = $svr_ID;
		$usearchdb['ranked'] = $svr_ranked;
		$searchresults['UnrankedServers'] [] = $usearchdb;
 }
$template->display(array(
'template' => $config['template'],
'profile' => $steamprofile, 
'stats' => $stats ,
'data' => $searchresults,
'site_url' => $_SERVER['SERVER_NAME'],
'site_name' => $config['site_name'],
'pagetitle' => 'Home'
));


//Funtions 

function formatSeconds($ms)
{
	return floor($ms / 60000) . ':' . floor(($ms % 60000) / 1000) . ':' . str_pad(floor($ms % 1000) , 3, '0', STR_PAD_LEFT);
}
function seoUrl($string) {
    //Lower case everything
    $string = strtolower($string);
    //Make alphanumeric (removes all other characters)
    $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
    //Clean up multiple dashes or whitespaces
    $string = preg_replace("/[\s-]+/", " ", $string);
    //Convert whitespaces and underscore to dash
    $string = preg_replace("/[\s_]/", "-", $string);
    return $string;
}
function my_sort($a,$b)
{
if ($a==$b) return 0;
  return ($b['Points']<$a['Points'])?-1:1;
}
?>
