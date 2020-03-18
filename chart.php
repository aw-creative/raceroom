<?php

// Load Dependancies
require_once 'vendor/autoload.php';
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
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, array(
 'debug' => true
)
 );
$template = $twig->loadTemplate('chart.phtml');

$Server = array();

$template->display($Server);


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

?>
