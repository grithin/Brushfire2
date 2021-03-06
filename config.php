<?
///for use by system in things such as combined log
$_ENV['projectName'] = $_ENV['projectName'] ? $_ENV['projectName'] : 'BrushFire Project Name';
///@note $_SERVER['HTTP_HOST'] is dependent upon request HTTP headers.  These can be fabricated (ex, mod /etc/hosts).  Consequently, it is wise to overwrite this default with manually entered domain
$_ENV['httpHost'] = $_ENV['httpHost'] ? $_ENV['httpHost'] : $_SERVER['HTTP_HOST'];

//+	Path configs{
//where the framework lies on your system
#$_ENV['systemFolder'] = '/www/brushfire/v9/versioned/';
//system public web resources
$_ENV['systemPublicFolder'] = $_ENV['systemFolder'].'public/';
//project public web resources
$_ENV['projectPublicFolder'] = $_ENV['projectFolder'].'public/';

///path to template folder (in case you want to use some common templates folder)
$_ENV['templateFolder'] = $_ENV['projectFolder'].'template/';
///path to storage folder
$_ENV['storageFolder'] = $_ENV['projectFolder'].'storage/';
///path to the control files
$_ENV['controlFolder'] = $_ENV['projectFolder'].'control/';
///path to extra config
$_ENV['configFolder'] = $_ENV['projectFolder'].'apt/config/';
//+	}

//+	Log configs {
///log location
/** path is relative to project folder*/
$_ENV['logFolder'] = $_ENV['storageFolder'].'log/';
///if the file isn't specified, log file will be datetime named in the log folder
$_ENV['logFile'] = $_ENV['logFolder'].'log';
///Max log size.  If you want only one error to show at a time, set this to 0
$_ENV['maxLogSize'] = '1mb';
//+	}


//+	Route config {
///what file to call when page was not found; relative to instance/control/
$_ENV['pageNotFound'] = '404page.php';
///what file to call when resource was not found; relative to instance/control/
#$_ENV['resourceNotFound'] = '404page.php';
$_ENV['resourceNotFound'] = '';
///the starting url path token that indicates that the system should look in the project public directory
$_ENV['urlProjectFileToken'] = 'public';
///the starting url path token that indicates that the system should look in the system public directory
$_ENV['urlSystemFileToken'] = 'brushfirePublic';
///A parameter in the post or get that indicates a file is supposed to be downloaded instead of served.  Works for non-parse public directory files.  Additionally, serves to name the file if the param value is more than one character.
$_ENV['downloadParamIndicator'] = 'download';
//+	}

//+	Error config {
///custom error handler.  Defaults to system error handler.
$_ENV['errorHandler'] = 'Debug::handleError';
///custom error handler.  Defaults to system error handler.
$_ENV['exceptionHandler'] = 'Debug::handleException';
///type of errors handled
$_ENV['errorsHandled'] = E_ALL & ~ E_NOTICE & ~ E_STRICT;
///For errors while !inScript, allow for function to handle
#$_ENV['errorViewHandler'] = function($err){ die($error,$errorId);};
///Determines the level of detail provided in the error message, 0-3
$_ENV['errorDetail'] = 2;
///Display errors
$_ENV['displayErrors'] = true;
///regex match on file for stack parts to exclude from error stack output
$_ENV['errorStackExclude'] = [];#['@^system:@'];
///will load Debug class on loader
$_ENV['debug'] = true;

//+	}
//+	Session config {
///date in the past after which inactive sessions should be considered expired
$_ENV['sessionExpiry'] = '-1 day';
///for cache based sessions
$_ENV['sessionCacheLength'] = '86400';
///folder to keep the file sessions if file sessions are used.  Php must be able to write to the folder.
$_ENV['sessionFolder'] = $_ENV['storageFolder'].'session/';
///determines whether to use the database for session data
//$_ENV['sessionUseDb'] = true;
///determines which table in the database to use for session data
$_ENV['sessionDbTable'] = 'session';
-///the time at which the cookie is set to expire.  0 for while browser open, other was passed into Time
-$_ENV['sessionCookieExpiry'] = '+1 year';
-///cookie expiry refresh probability; the denominator that an existing session cookie will be updated with the current sessionCookieExpiry on page load.  0, false, null = don't refresh
-$_ENV['sessionCookieExpiryRefresh'] = 100;
//probability is probability/divisor
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
//+	}

//+	Caching {
///connection array
#$_ENV['cache.default'] = ['type'=>'memcache','connection'=>['127.0.0.1','11211'],'prefix'=>$_ENV['httpHost']];
if(!$_ENV['cache.default']){
	$_ENV['cache.default'] = ['type'=>'redis','connection'=>['127.0.0.1',6379, 1, NULL, 100],'prefix'=>$_ENV['httpHost'].'.'];
}
///db of cache to connect to, if any
//$_ENV['cache.db']
//+ }
//+	Encryption config {
///the cipher to use for the framework encyption class
$_ENV['cryptCipher'] = MCRYPT_RIJNDAEL_128;
///the cipher mode to use for the framework encyption class
$_ENV['cryptMode'] = MCRYPT_MODE_ECB;
///the cipher key to use for the framework encyption class.  Clearly, the safest thing would be to keep it as the default!
$_ENV['cryptKey'] = $_ENV['projectName'];
//+	}

//+	Autoload config {
///a list of directories to search recursively.  See Doc:Autoload Includes
$_ENV['autoloadIncludes'] = array(
		'default'=>array(
			$_ENV['systemFolder'].'tool/',
			array($_ENV['projectFolder'].'tool/',array('moveDown'=>true,'stopFolders'=>['section','module'])),),
		'\local'=>array($_ENV['projectFolder'].'tool/local/')
	);
$_ENV['composerFolder'] = $_ENV['projectFolder'].'vendor/';
//+	}

//+	Display related config {
///used for @name like shortcuts in View::get template array.  See example in system/view/aliases.php
$_ENV['view.aliasesFiles'] = [$_ENV['templateFolder'].'brushfire/aliases.php',$_ENV['templateFolder'].'aliases.php'];
$_ENV['view.staticServer'] = '//common.deemit.com/';
//+	}

//+	CRUD related config {
//what to call when CRUD model encounters a bad id
$_ENV['CRUDbadIdCallback'] = 'badId';
//+	}

//+	Misc config {
///email unique identifier;
/**when sending an email, you have to generate a message id.  To prevent collisions, this id will be used in addition to some random string*/
$_ENV['emailUniqueId'] = $_ENV['projectName'].'-mail';

///cookie default options for use by Cookie class
$_ENV['cookieDefaultOptions'] = array(
		'expire' => 0,
		'path'	=> '/',
		'domain' => null,
		'secure' => null,
		'httpsonly'=> null
	);

///time zone (db, internal functions)
$_ENV['timezone'] = 'UTC';

///whether to parse input using php '[]' special syntax
$_ENV['pageInPHPStyle'] = true;
///user not authorized page
$_ENV['notAuthorizedPage'] = 'standardPage,,,notAuthorized';
///for finding first IP outside network on HTTP_X_FORWARDED_FOR
$_ENV['loadBalancerIps'] = array();

$_ENV['startTime'] = microtime(true);
//+	}