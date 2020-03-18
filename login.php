<?php
// Load Dependancies
require_once 'vendor/autoload.php';
require_once 'vendor/steamauth/steamauth.php';
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

?>
<!DOCTYPE html>
<html>
<head>
    <title>page</title>
</head>
<body>
<?php
if(!isset($_SESSION['steamid'])) {

    echo "welcome guest! please login<br><br>";
    loginbutton(); //login button
    
}  else {
    include ('vendor/steamauth/userInfo.php');
	$searchstring = explode('/',$steamprofile['profileurl'],-1);
	$profileurl = $searchstring[4];
	$profilenames = explode(' ',$steamprofile['realname']);
	$profilenames[] = $profileurl;
	
	echo "<pre>";
	print_r($profilenames);
	echo "</pre>";
	$playerlist = $database->select('players', '*',["OR" => ['Username' => $profileurl , 'FullName' => $profilenames]]);
	echo "<pre>";
	print_r($playerlist);
	echo "</pre>";
    //Protected content
   echo "Welcome back " . $steamprofile['personaname'] ."</br>";
    echo "here is your avatar: </br>" . '<img src="'.$steamprofile['avatarfull'].'" title="" alt="" /><br>'; // Display their avatar!
    
    logoutbutton();
}    
?>  
</body>
</html>
<!--Version 3.1.1-->