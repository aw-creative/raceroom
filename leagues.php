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
 //Choose Template
if (isset($_GET['Embed'])){
$template = $twig->loadTemplate('leaguesEmbed.phtml');
}else{
$template = $twig->loadTemplate('leagues.phtml');	
}
 //Start stuff
 
if (isset($_GET['LeagueID']))
{

$selectedLeague = $_GET['LeagueID'];	
$selectedLeague = $_GET['LeagueID'];
$LeagueServer = $database->select("sessions", "ServerID", ["AND" => ["LeagueID" => $selectedLeague, "TYPE" => 'Race']]);
$servers = $database->select("serverinfo", ["Server", "Time", "Track" , "ID" ],["ID" => $LeagueServer ],[ "ORDER" => "Time DESC"]);
	
foreach ($servers as $key => $value){
	$seconds = $value['Time']/1000;
	$servers[$key]['Date'] = date('l jS \of F Y H:i', $seconds);
	$servers[$key]['Winner'] = $database->get('sessionplayers',['PlayerID' , 'FullName' ],["AND" => ['ServerID' => $value['ID'], 'Position' => '1']] );
}

$LeagueInfo = $database->get("leagues", "*" ,["ID" => $selectedLeague]);


$rank = array();
$LeagueSessions = $database->select("sessions", "ID", ["AND" => ["LeagueID" => $selectedLeague, "TYPE" => 'Race']]);
$leagueplayers = $database->select("sessionplayers", "PlayerID", ["SessionID" => $LeagueSessions]);
//$result = array_unique($leagueplayers, SORT_REGULAR);

$allplayers = $database->select("players", ["ID", "FullName"],["ID" => $leagueplayers]);
foreach ($allplayers as $player){
                $p_id = $player["ID"];
                $p_name = $player["FullName"];
                $sessionplayers = $database->sum("sessionplayers", "Points",[ 
                        "AND" => [
                                "PlayerID" => $p_id , 
                                "SessionID" => $database->select("sessions", "ID", ["AND" => ["LeagueID" => $selectedLeague, "TYPE" => 'Race']]) ]]);
             // $totalPoints = (array_sum($sessionplayers));
                $p_array = array("points" => $sessionplayers , "ID" => $p_id);
                $rank[$p_name] = $p_array;

                }

arsort($rank);
$pI = 1;
	foreach ($rank as $key => $value){
			$rank[$key]['pos'] = ordinal($pI);
			$pI++;
	}
//Paging	
$pageNumber = 12;
$page = 1;
if (isset($_GET['p'])){
$page = $_GET['p'];
}
//if (count($rank) > 24){
//	$rank = array_chunk($rank,$pageNumber, true);
//	$rankings = array("servers" => $servers , "info" => $LeagueInfo, "rankings" => $rank[$page-1], "page" => $page, "numpages" => $rank); 
//}else{
	$rankings = array("servers" => $servers , "info" => $LeagueInfo, "rankings" => $rank); 
//}

 
 //display stuff
$template->display(array(
'leagueinfo' => $rankings,
'template' => $config['template'],
'profile' => $steamprofile, 
'pagetitle' => 'League',
'site_url' => $_SERVER['SERVER_NAME'],
'site_name' => $config['site_name'],
));
 
} 
 
 function formatSeconds($ms)
{
	return floor($ms / 60000) . ':' . floor(($ms % 60000) / 1000) . ':' . str_pad(floor($ms % 1000) , 3, '0', STR_PAD_LEFT);
}
	
function ordinal($number) {
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($number % 100) >= 11) && (($number%100) <= 13))
        return $number. 'th';
    else
        return $number. $ends[$number % 10];
}
?>