<?php

// Load Dependancies
require_once 'vendor/autoload.php';
require_once 'vendor/steamauth/steamauth.php';

$loggedin = false;
$steamprofile = array();
$playerID = null;


//get the config file
$config = parse_ini_file('config/config.ini');
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

 //Start stuff
if(!isset($_SESSION['steamid'])) {

   $loggedin = false;
   $template = $twig->loadTemplate('leaguelist.phtml');
    
}  else {
   include ('vendor/steamauth/userInfo.php');
   $loggedin = true;
   
   
}    
if (isset($_GET['LeagueID']))
{
$template = $twig->loadTemplate('leagues.phtml');
$selectedLeague = $_GET['LeagueID'];
if ($loggedin) {
$playerID = $database->get('players','ID',['steamID' => $steamprofile['steamid']]);
}
$UleagueInfo = $database->get('userleagues', '*' , ['ID' => $selectedLeague]);
$UleagueServers = $database->select('uleagueservers', 'ServerID' , ['LeagueID' => $selectedLeague]);
$LeagueServer = $database->select("sessions", "ServerID", ["AND" => ["ServerID" => $UleagueServers, "TYPE" => 'Race']]);
$servers = $database->select("serverinfo", ["Server", "Time", "Track" , "ID" ],["ID" => $LeagueServer, "LIMIT" =>[0,12] ],[ "ORDER" => "Time DESC"]);
	
foreach ($servers as $key => $value){
	$seconds = $value['Time']/1000;
	$servers[$key]['Date'] = date('l jS \of F Y H:i', $seconds);
	$rsession = $database->select("sessions", "ID", ["AND" => ["ServerID" => $servers[$key]['ID'], "TYPE" => 'Race']]);
	$servers[$key]['Winner'] = $database->get('sessionplayers',['PlayerID' , 'FullName' ],["AND" => ['Position' => '1' , 'SessionID' => $rsession ]] );
}


$rank = array();
$LeagueSessions = $database->select("sessions", "ID", ["AND" => ["ServerID" => $UleagueServers, "TYPE" => 'Race']]);
if (!empty($LeagueSessions)){
$leagueplayers = $database->select("sessionplayers", "PlayerID", ["SessionID" => $LeagueSessions]);


$allplayers = $database->select("players", ["ID", "FullName"],["ID" => $leagueplayers]);
foreach ($allplayers as $player){
                $p_id = $player["ID"];
                $p_name = $player["FullName"];
                $sessionplayers = $database->sum("sessionplayers", "Points",[ 
                        "AND" => [
                                "PlayerID" => $p_id , 
                                "SessionID" => $database->select("sessions", "ID", ["AND" => ["ID" => $LeagueSessions, "TYPE" => 'Race']]) ]]);

                $p_array = array("points" => $sessionplayers , "ID" => $p_id);
                $rank[$p_name] = $p_array;

                }
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

	$rankings = array("servers" => $servers , "info" => $UleagueInfo, "rankings" => $rank); 


if (isset($UleagueInfo)){
if ($UleagueInfo['OwnerID'] == $playerID){
	$rankings['editable'] = true;
}
}
 //display stuff
$template->display(array(
'leagueinfo' => $rankings,
'template' => $config['template'],
'profile' => $steamprofile, 
'pagetitle' => $rankings['info']['LeagueName'],
'site_url' => $_SERVER['SERVER_NAME'],
'site_name' => $config['site_name'],
));

//display things for non logged in.
}else{
	if (isset($_GET['user'])){
		$playerID = $database->get('players','ID',['steamID' => $steamprofile['steamid']]);
		$rankings['servers'] = $database->select('userleagues' , "*" , ['OwnerID' => $playerID]);
$pagetitle = "Your Leagues"	;
	}else{
$rankings['servers'] = $database->select('userleagues' , "*"); 
$pagetitle = "All Leagues"	;
	}

$template = $twig->loadTemplate('leaguelist.phtml');

foreach ($rankings['servers'] as $rk => $rv){
	$rankings['servers'][$rk]['events'] =	$database->count('uleagueservers',['LeagueID' => $rv['ID']]);
}



 //display stuff
$template->display(array(
'leagueinfo' => $rankings,
'template' => $config['template'],
'profile' => $steamprofile, 
'pagetitle' => $pagetitle,
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