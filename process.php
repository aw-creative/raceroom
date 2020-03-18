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
	
$playerID = $database->get('players','ID',['steamID' => $steamprofile['steamid']]);
//add a server to a league

if (!empty($_POST['uleague'])){
$newLeagueID = $database->insert('uleagueservers', ['LeagueID' => $_POST['uleague'] , 'ServerID' => $_POST['serverID']]);
echo "<h3>Added To League</h3>";
echo "<p><a class='green' href='/userleague/" . $_POST['uleague'] .".html'>Go to League</a></p>";

}



// update a league
if (!empty($_POST['LeagueID'])){
if (!empty($_POST['LeagueName'])){
$newLeague = $database->update("userleagues" , ['LeagueName' => strip_tags($_POST['LeagueName']), 'Description' => strip_tags($_POST['Description'])],['ID' => $_POST['LeagueID']]);
echo "<h3>League Updated</h3>";
echo "<p><a class='btn green' href='/userleague/" . $_POST['LeagueID'] .".html'>Reload</a></p>";
}else{
	echo "No Name Entered";
	}
}else{
//create a new userleague
if (!empty($_POST['LeagueName'])){
$newLeague = $database->insert("userleagues" , ['OwnerID' => $playerID, 'LeagueName' => strip_tags($_POST['LeagueName']), 'Description' => strip_tags($_POST['Description'])]);
echo "<h3>League Created</h3>";
echo "<p><a class='green' href='/userleague/" . $newLeague .".html'>Go to League</a></p>";
}else{
	echo "No Name Entered";
	}
}


?>