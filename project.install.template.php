<?
/**
Should be placed in project folder.
Expects apt/config.php
Will install composer and issue composer update
Expects there to be a control/script/install in the project folder
*/
if(!is_file('composer.phar')){
	passthru('php -r "readfile(\'https://getcomposer.org/installer\');" | php');
}
passthru('php composer.phar update');

//load config
require_once (__DIR__.'/apt/config.php');

//creaate the various used folders
exec('mkdir -p -m 777 '.$_ENV['storageFolder']);
exec('mkdir -p -m 777 '.$_ENV['logFolder']);
exec('mkdir -p -m 777 '.$_ENV['sessionFolder']);
//link up brushfire default template and control folders
if(!is_dir($_ENV['projectFolder'].'template/brushfire')){
	echo "\nLinking framework templates\n";
	exec('ln -s '.$_ENV['systemFolder'].'template '.$_ENV['projectFolder'].'template/brushfire');
}
if(!is_dir($_ENV['projectFolder'].'control/brushfire')){
	echo "\nLinking framework controls\n";
	exec('ln -s '.$_ENV['systemFolder'].'control '.$_ENV['projectFolder'].'control/brushfire');
}


//call project install script
if(is_file($_ENV['projectFolder'].'control/scrilt/install')){
	passthru('php public/index.php -p script/install');
}