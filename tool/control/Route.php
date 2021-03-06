<?
namespace control; use \Debug;
///Used to handle requests by determining path, then determining controls
/**
See routes.sample.php for route rule information

Route Rules Logic
	All routes are optional
	Routes are discovered one level at a time, and previous routing rules affect the discovery of new routes
	if a matching rule is found, the Route rules loop starts over with the new path (unless option set to not do this)

	http://bobery.com/bob/bill/sue:
		control/routes.php
		control/bob/routes.php
		control/bob/bill/routes.php
		control/bob/bill/sue/routes.php


Controls Calling Logic:
	All controls are optional.  However, if the Route is still looping tokens (stop it by exiting or emptying $unparsedTokens) and the last token does not match a control, page not found returned

	http://bobery.com/bob/bill/sue:
		control/control.php
		control/bob.php || control/bob/control.php
		control/bob/bill.php || control/bob/bill/control.php
		control/bob/bill/sue.php || control/bob/bill/sue/control.php

File Routing
	If, for some reason, Route is given a request that has a urlProjectFileToken or a systemPublicFolder prefix, Route will send that file after determining the path through the Route Rules Logic

@note	if you get confused about what is going on with the rules, you can print out both self::$matchedRules and self::$ruleSets at just about any time
@note	dashes in paths will not work with namespacing.  Dashes in the last token will be handled by turning the name of the corresponding local tool into a lower camel cased name.
*/
class Route{
	static $stopRouting;///<stops more rules from being called; use within rule file
	static $tokens = array();///<an array of url path parts; rules can change this array
	static $realTokens = array();///<the original array of url path parts
	static $parsedTokens = array();///<used internally
	static $unparsedTokens = array();///<used internally
	static $matchedRules;///<list of rules that were matched
	static $path;///<the resulting url path
	static $currentToken;///<used internally; serves as the item compared on token compared rules
	///parses url, routes it, then calls off all the control until no more or told to stop
	static function handle($uri){
		self::parseRequest($uri);

		//url corresponds to public file directory, provide file
		if(self::$tokens[0] == $_ENV['urlProjectFileToken']){
			self::sendFile($_ENV['instancePublicFolder']);
		}elseif(self::$tokens[0] == $_ENV['urlSystemFileToken']){
			self::sendFile($_ENV['systemPublicFolder']);
		}

		self::routeRequest();

//+	load controls and section page{

		\Control::init();//we are now in the realm of dynamic pages
		\View::init(); //most pages on this web framework use the view

		//after this following line, self::$tokens has no more influence on routing.  Modify self::$unparsedTokens if you want modify control flow
		self::$unparsedTokens = self::$tokens;//blank token loads in control

		self::addLocalTool($_ENV['projectFolder'].'tool/local/');

		//control files included variables
		$incVars = self::$regexMatch;

		//get the section and page control
		if(is_file($_ENV['controlFolder'].'control.php')){
			include($_ENV['controlFolder'].'control.php');
		}

		self::rawLoad(self::$unparsedTokens,self::$parsedTokens,$incVars,self::$currentToken);
//+	}
	}

	///load a control path
	/**
	@param	start	<<will skip controls prior to start>>
	*/
	static function load($target,$start=''){
		$parsedTokens = [];
		$unparsedTokens = explode('/',$target);
		if($start){
			$parsedTokens = explode('/',$start);
			$unparsedTokens = array_diff($unparsedTokens,$parsedTokens);
		}
		self::rawLoad($unparsedTokens,$parsedTokens);
	}

	///used internally for pointing at referenced token variables for use in control pages
	static function rawLoad(&$unparsedTokens,&$parsedTokens,&$incVars=null,&$currentToken=null){
		while($unparsedTokens){
			$loaded = false;
			$currentToken = array_shift($unparsedTokens);
			if($currentToken){//ignore blank tokens
				$parsedTokens[] = $currentToken;

				//++ load the control {
				$path = $_ENV['controlFolder'].implode('/',$parsedTokens);
				//if named file, load, otherwise load generic control in directory
				if(is_file($path.'.php')){
					$loaded = \Files::inc($path.'.php',null,$incVars);
				}elseif(is_file($path.'/control.php')){
					$loaded = \Files::inc($path.'/control.php',null,$incVars);
				}
				//++ }
			}
			//not loaded and was last token, page not found
			if($loaded === false && !$unparsedTokens){
				if(!$_ENV['inScript']){
					if(\Control::$ajax){
						\Control::error('Page not found');
						\View::endStdJson();
					}else{
						\View::end('@404');
					}
				}else{
					die("\n\nError\nControl Path not found.  Check your -p option\n\n");
				}
				if($_ENV['pageNotFound']){
					\Config::loadUserFiles($_ENV['pageNotFound'],'control',null,$incVars);
					exit;
				}else{
					Debug::toss('Request handler encountered unresolvable token at control level.'."\nCurrent token: ".$currentToken."\nTokens parsed".print_r($parsedTokens,true));
				}
			}
		}
	}

	///find the most specific tool
	private static function addLocalTool($base){
		$tokens = self::$tokens;
		while($tokens){
			if(is_file($base.implode('/',$tokens).'.php')){
				break;
			}
			array_pop($tokens);
			$tokens[] = 'local';
			if(is_file($base.implode('/',$tokens).'.php')){
				break;
			}
			array_pop($tokens);
		}
		\Control::addLocalTool($tokens);
	}
	///internal use. initial breaking apart of url
	private static function parseRequest($uri){
		self::$realTokens = explode('?',$uri,2);
		self::tokenise(self::$realTokens[0]);

		//urldecode tokens.  Note, this can make some things relying on domain path info for file path info insecure
		foreach(self::$tokens as &$token){
			$token = urldecode($token);
		}
		unset($token);

		//Potentially, the tokens will change according to routes, but the real ones may be referenced
		self::$realTokens = self::$tokens;
	}
	/// internal use. tokenises url
	/** splits url path on "/"
	@param	urlDir	str	path part of url string
	*/
	static $caselessPath;///<path, but cases removed
	private static function tokenise($urlDir){
		self::$tokens = \Tool::explode('/',$urlDir);
		self::$path = $urlDir;
		self::$caselessPath = strtolower($urlDir);
	}
	///for handling '[name]' style regex replacements
	static function regexReplacer($replacement,$matches){
		foreach($matches as $k=>$v){
			if(!is_int($k)){
				$replacement = str_replace('['.$k.']',$v,$replacement);
			}
		}
		return $replacement;
	}
	static $regexMatch=[];
	///internal use. Parses all current files and rules
	/** adds file and rules to ruleSets and parses all active rules in current file and former files
	@param	path	str	file location string
	*/
	private static function matchRules($path,&$rules){
		foreach($rules as $ruleKey=>&$rule){
			unset($matched);
			if(!isset($rule['flags'])){
				$flags = $rule[2] ? explode(',',$rule[2]) : array();
				$rule['flags'] = array_fill_keys(array_values($flags),true);

				//parse flags for determining match string
				if($rule['flags']['regex']){
					$rule['matcher'] = \Tool::pregDelimit($rule[0]);
					if($rule['flags']['caseless']){
						$rule['matcher'] .= 'i';
					}

				}else{
					if($rule['flags']['caseless']){
						$rule['matcher'] = strtolower($rule[0]);
					}else{
						$rule['matcher'] = $rule[0];
					}
				}
			}

			if($rule['flags']['caseless']){
				$subject = self::$caselessPath;
			}else{
				$subject = self::$path;
			}

			//test match
			if($rule['flags']['regex']){
				if(preg_match($rule['matcher'],$subject,self::$regexMatch)){
					$matched = true;
				}
			}else{
				if($rule['matcher'] == $subject){
					$matched = true;
				}
			}

			if($matched){
				self::$matchedRules[] = $rule;
				//++ apply replacement logic {
				if($rule['flags']['regex']){
					if(is_callable($rule[1])){
						$bound = new \Bound($rule[1],[$rule['matcher'],self::$path]);
					}else{
						$bound = new \Bound('\control\Route::regexReplacer',[$rule[1]]);
					}
					$replacement = preg_replace_callback($rule['matcher'],$bound,self::$path);
				}else{
					if(is_callable($rule[1])){
						$replacement = call_user_func($rule[1],$rule['matcher'],self::$path);
					}else{
						$replacement = $rule[1];
					}
				}

				//handle redirects
				if($rule['flags'][301]){
					$httpRedirect = 301;
				}
				if($rule['flags'][303]){
					$httpRedirect = 303;
				}
				if($rule['flags'][307]){
					$httpRedirect = 307;
				}
				if($httpRedirect){
					if($rule['flags']['params']){
						$replacement = \Http::appendsUrl(\Http::parseQuery($_SERVER['QUERY_STRING']),$replacement);
					}
					\Http::redirect($replacement,'head',$httpRedirect);
				}


				//remake url with replacement
				self::tokenise($replacement);
				self::$parsedTokens = [];
				self::$unparsedTokens = array_merge([''],self::$tokens);
				//++ }

				//++ apply parse flag {
				if($rule['flags']['once']){
					unset($rules[$ruleKey]);
				}elseif($rule['flags']['file:last']){
					unset(self::$ruleSets[$path]);
				}elseif($rule['flags']['loop:last']){
					self::$unparsedTokens = [];
				}
				//++ }

				return true;
			}
		} unset($rule);

		return false;
	}

	static $ruleSets;///<files containing rules that have been included

	///internal use. Gets files and then applies rules for routing
	private static function routeRequest(){
		self::$unparsedTokens = array_merge([''],self::$tokens);

		while(self::$unparsedTokens && !self::$stopRouting){
			self::$currentToken = array_shift(self::$unparsedTokens);
			if(self::$currentToken){
				self::$parsedTokens[] = self::$currentToken;
			}

			$path = $_ENV['controlFolder'].implode('/',self::$parsedTokens);
			if(!isset(self::$ruleSets[$path])){
				self::$ruleSets[$path] = (array)\Files::inc($path.'/routes.php',null,null,['rules'])['rules'];
			}
			if(!self::$ruleSets[$path]){
				continue;
			}
			//note, on match, matehRules resets unparsedTokens (having the effect of loopiing matchRules over again)
			self::matchRules($path,self::$ruleSets[$path]);
		}

		self::$parsedTokens = [];
	}
	///internal use. Gets a file based on next token in the unparsedTokens variable
	private static function getTokenFile($defaultName,$globalize=null,$extract=null){
		$path = $_ENV['controlFolder'].implode('/',self::$parsedTokens);
		//if path not directory, possibly is file
		if(!is_dir($path)){
			$file = $path.'.php';
		}else{
			$file = $path.'/'.$defaultName.'.php';
		}
		return \Files::inc($file,$globalize,null,$extract);
	}
	///internal use. attempts to find non php file and send it to the browser
	private static function sendFile($base){
		array_shift(self::$tokens);
		$filePath = escapeshellcmd(implode('/',self::$tokens));
		if($filePath == 'index.php'){
			\Config::loadUserFiles($_ENV['pageNotFound'],'control',array('page'));
		}
		$path = $base.$filePath;
		if($_ENV['downloadParamIndicator']){
			$saveAs = $_GET[$_ENV['downloadParamIndicator']] ? $_GET[$_ENV['downloadParamIndicator']] : $_POST[$_ENV['downloadParamIndicator']];
		}
		\View::sendFile($path,$saveAs);
	}
	static function currentPath(){
		return '/'.implode('/',self::$parsedTokens).'/';
	}
}
