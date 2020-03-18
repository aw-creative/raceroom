<?php

// Load Dependancies
require_once 'vendor/autoload.php';
require_once 'vendor/steamauth/steamauth.php';

$loggedin = false;


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

	
	// Logged in User Data
$steamprofile = array();
$selectedDriver = 0;
if(!isset($_SESSION['steamid'])) {

   $loggedin = false;
    
}  else {
    include ('vendor/steamauth/userInfo.php');
   $loggedin = true; 
	
if ($database->has('players',['steamID' => $steamprofile['steamid']])){

	   $selectedDriver = $database->get('players','ID',[ 'steamID' => $steamprofile['steamid']]);
	   
   }else{
  header( 'Location: ' . $config['site_url'] . '/register.php' );
 }
}

// End Logged in user Data	   
	   
// Load templating engine
$loader = new Twig_Loader_Filesystem($config['template']);
$twig = new Twig_Environment($loader, array(
 'debug' => true
)
 );
$twig->addExtension(new Twig_Extension_Debug());
 //Start stuff
 
$template = $twig->loadTemplate('driver.phtml');
if (isset($_GET['ID'])){
$selectedDriver = $_GET['ID'];
}
$driver = $database->get('players',['FullName', 'Username' ,'steamID' , 'avatar' , 'totalpoints', 'avatar' ,'registered'],['ID' => $selectedDriver]);
$racesessions = $database->select('sessions', 'ID' , ['Type' => 'Race']);
$qualsessions = $database->select('sessions', 'ID' , ['Type' => 'Qualify']);
$dsessions = $database->select('sessionplayers',['ID' , 'ServerID' , 'Car', 'SessionID', 'FinishStatus', "Position", "Points"],[ "AND" => ["PlayerID" => $selectedDriver , "SessionID" => $racesessions  ]]);
$dqsessions = $database->select('sessionplayers',['ID' , 'ServerID' , 'Car', 'SessionID', 'FinishStatus', "Position", "Points"],[ "AND" => ["PlayerID" => $selectedDriver , "SessionID" => $qualsessions, "Position" => '1' ]]);
$selsessions = array();
$allcars = array();
$points = $driver['totalpoints'];
$podiums = 0;
$wins = 0;
$dnf = 0;

foreach ($dsessions as $key => $v){
	$selsessions[] = $v['SessionID'];
	$allcars[] = $v['Car'];
	if ($v['Position'] <= 3){
		$podiums++;
	}
	if ($v['Position'] == 1){
		$wins++;
	}
	if ($v['FinishStatus'] == 'DidNotFinish'){
		$dnf++;
	}
	$serverinfo = $database->get('serverinfo' , ['ID' , 'Server' , 'Track'],[ 'ID' => $v['ServerID']]);
	$dsessions[$key]['ServerInfo'] = $serverinfo;
}
$c = array_count_values($allcars); 
$favcar = array_search(max($c), $c);
$dsessioninfo = $database->select('sessions','ID',[ "AND" =>["ID" => $selsessions , "Type" => "Race"]]);

$playersessions = $database->select('sessionplayers' , '*' ,[ "AND" => ['SessionID]' => $dsessioninfo, "PlayerID" => $selectedDriver], "ORDER" => 'ID  DESC']);
 foreach ($playersessions as $key => $value){
	 $serverinfo = $database->get('serverinfo' , ['ID' , 'Server' , 'Track'],[ 'ID' => $value['ServerID']]);
	 $playersessions[$key]['ServerInfo'] = $serverinfo;
 }

$racestarts = count($dsessioninfo);
$dqualy = count($dqsessions);



$driverData = array(
"driver" => $driver, 
"wins" => $wins ,
"pole" => $dqualy ,
"podiums" => $podiums ,
"racestarts" => $racestarts ,
"dnf" => $dnf ,
"favcar" => $favcar, 
'servers' => $playersessions);

//debugPrint($driverData);

$template->display(array(
'data' => $driverData,
"template" => $config['template'],
'profile' => $steamprofile,
'site_url' => $_SERVER['SERVER_NAME'],
'site_name' => $config['site_name'],
'pagetitle' => 'Home')
);

//}

function debugPrint($var){
echo "<pre>";
print_r($var);
echo "</pre>";
}



?>
