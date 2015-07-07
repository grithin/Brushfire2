<?
///put following into a control file and run it

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