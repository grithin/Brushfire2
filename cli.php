<?
/**
Command line interface

Purpose: To use framework with non-web-adjunct scripts (ie, no 'control' routing)
Use:
	Include this file in main script file
	As normal, edit $_ENV before or after Apply any overlapping $_ENV changes after inclusion

What this does
	Sets various $_ENV
	Makes standard folders (storage folder, log folder)
	Adds autoloader, error handler

What is required
	Normal framework requirements: memcached
*/

//Only load if not already loaded
if(!class_exists('Control',false)){
	$initialEnv = $_ENV;///< all existing $_ENV values should override system defaults

	if(!$_ENV['projectFolder']){
		$includerPath = debug_backtrace()[0]['file'];
		$_ENV['projectFolder'] = realpath(dirname($includerPath)).'/';	}
	if(!$_ENV['systemFolder']){
		$_ENV['systemFolder'] = realpath(dirname(__FILE__)).'/';	}
	require_once $_ENV['systemFolder'].'config.php';
	//to avoid cache collision between cli scripts
	$_ENV['cache.default'] = ['type'=>'none','connection'=>[]];
	$_ENV = array_merge($_ENV,$initialEnv);

	$_ENV['inScript'] = true;
	$_ENV['doNotRoute'] = true;

	$ensureFolders = ['storageFolder','logFolder'];
	foreach($ensureFolders as $key){
		$folder = $_ENV[$key];
		if($folder && !is_dir($folder)){
			exec('mkdir -p '.$folder);	}	}

	require_once $_ENV['systemFolder'].'loader.php';
	Control::init();
}