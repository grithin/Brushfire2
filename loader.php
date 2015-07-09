<?
/// logic unrelated to a specific request
/** @file */

#Tool, used by config
require_once $_ENV['systemFolder'].'tool/Tool.php';

#used by autoloader
require_once $_ENV['systemFolder'].'tool/Arrays.php';
require_once $_ENV['systemFolder'].'tool/Hook.php';
require_once $_ENV['systemFolder'].'tool/CommonTraits.php';
#Config setting
require_once $_ENV['systemFolder'].'tool/Config.php';
Config::init();

if($_ENV['debug']){
	require_once $_ENV['systemFolder'].'tool/Debug.php';
}

#Cache setup (used by autoloader,view,session)
require_once $_ENV['systemFolder'].'tool/Cache.php';
Cache::start(null,$_ENV['cache.default']['type'],$_ENV['cache.default']['connection'],['prefix'=>$_ENV['cache.default']['prefix']]);

#Autoloader
require_once $_ENV['systemFolder'].'tool/Autoload.php';
$autoload = Autoload::init(null,$_ENV['autoloadIncludes']);
spl_autoload_register(array($autoload,'cache'));
#composer autload
if(is_file($_ENV['composerFolder'].'autoload.php')){
	require_once $_ENV['composerFolder'].'autoload.php';
}

set_error_handler($_ENV['errorHandler'],$_ENV['errorsHandled']);
set_exception_handler($_ENV['exceptionHandler']);

if(!$_ENV['doNotRoute']){
	Config::loadUserFiles($_ENV['preRoute']);

	#pre session request handling; for file serving and such.
	require_once $_ENV['systemFolder'].'tool/control/Route.php';
	\control\Route::handle($_SERVER['REQUEST_URI']);
}