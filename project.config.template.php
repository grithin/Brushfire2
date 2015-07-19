<?
$_ENV['projectName'] = 'Brushfire2 Project';
$_ENV['projectFolder'] = realpath(dirname(__FILE__).'/../').'/';
#$_ENV['httpHost'] = '__FILL__';//will default to $_SERVER[HTTP_HOST], but you should set this since cli won't have a http host (cache prefix uses http host)

$_ENV['systemFolder'] = '[[systemFolder]]';
$_ENV['serverType'] = 'main';
/* Uncomment to have access to the database
$_ENV['database']['default'] = array(
		'user'=>'root',
		'password'=>'',
		'database'=>'__FILL__',
		'host'=>'localhost',
		'driver'=>'mysql');
#*/

require $_ENV['systemFolder'].'config.php';

//don't show some framework related backtrace related info on errors
$_ENV['errorStackExclude'] = ['@(tool/CommonTraits)|(tool/Db.php)@'];

//will prevent the use of the model cache, and force remake
$_ENV['forceRemakeModel'] = true;