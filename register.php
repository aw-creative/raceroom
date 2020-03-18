<?php

// Load Dependancies
require_once 'vendor/autoload.php';
require_once 'vendor/steamauth/steamauth.php';

$loggedin = false;
$steamprofile = array();
if(!isset($_SESSION['steamid'])) {

   $loggedin = false;
   header( 'Location: ' . $config['site_url'] . '/?login' );
    
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

$error = true;
if ($database->has('players',[ "AND" => ['steamID' => $steamprofile['steamid'], 'registered' => '1']])){
header( 'Location: ' . $config['site_url'] . 'drivers.php' );
}
if (isset($_POST['submit'])){
	echo "Form Submitted";
	echo $_POST["driverName"];
		if ($database->has('players',['FullName' => $_POST["driverName"]])){
	$lastupdateID = $database->update('players',['steamID' => $steamprofile['steamid'],'avatar' => $steamprofile['avatarfull'], 'registered' => true],['FullName' => $_POST["driverName"]]);
	echo $lastupdateID;
	header( 'Location: ' . $config['site_url'] . 'drivers.php' );
}else{
$error = true;
}
}
	
	

// Load templating engine
$loader = new Twig_Loader_Filesystem($config['template']);
$twig = new Twig_Environment($loader, array(
 'debug' => true
)
 );
$twig->addExtension(new Twig_Extension_Debug());
 //Start stuff
$template = $twig->loadTemplate('register.phtml');
$template->display(array(
'error' => $error, 
'info' => "thing", 
'profile' => $steamprofile,
'template' => $config['template'],
'drivers' => $database->select('players' , ['FullName', 'Username'],['registered' => 0])
));
	
?>
