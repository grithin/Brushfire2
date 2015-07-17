<?
///put following into a control file and run it
///@important	if you don't manually set the  $_ENV['httpHost'], the script run will not know what host to use and cache prefix will  not match a site

//remake model
if(\Control::$db){
	new Model(['forceRemake'=>true,'db'=>\Control::$db]);
}
//clear view alias cache
Cache::delete('view.aliases');

//handle cached autoloader
$cached = Cache::get('_autoloadCached');
if($cached){
	foreach($cached as $class){
		echo 'Clearing autoload class: '.$class."\n";
		Cache::delete('_autoload.'.$class);
	}
	Cache::delete('_autoloadCached');
}