<?
///For handling output and templates, generally assuming use of http
/**
On the nature of templates
	. they should contain minimal logic, leaving most to control and tools

@note	although there is only expected to be one View instance per page, to


*/
class View{
	static $baseUrl;
	static $common = [];///< array of common view variables, like title, keywords, etc
	static $aliases;///< the aliases used when getting templates

	static function init(){
		self::$baseUrl = '//'.$_ENV['httpHost'].'/';
	}
	static function __callStatic($fn,$args){
		if(!self::$loaded){
			self::load();
		}
		return call_user_func_array(array('self',$fn),$args);
	}
	static $loaded = false;
	static function load(){
		//get template aliases
		if(!(self::$aliases = Cache::get('view.aliases'))){
			foreach($_ENV['view.aliasesFiles'] as $file){
				$extract = Files::inc($file,null,null,array('aliases'));
				self::$aliases = Arrays::merge(self::$aliases,$extract['aliases']);
			}
			Cache::set('view.aliases',self::$aliases);
		}
	}
	///keyed to local variable  withinside template as 'local'
	static $localVars = [];
	///used to get the content of a single template file
	/**
	@param	template	string path to template file relative to the templateFolder.  .php is appended to this path.
	@param	vars	variables to extract and make available to the template file
	@return	output from a template
	*/
	static protected function getTemplate($template,$vars=null){
		$vars['thisTemplate'] = $template;
		if(self::$localVars){
			$vars['local'] = self::$localVars;
		}

		ob_start();
		if(substr($template,-4) != '.php'){
			$template = $template.'.php';
		}
		Files::req($_ENV['templateFolder'].$template,null,$vars);
		$output = ob_get_clean();
		return $output;
	}

	static $showArgs;
	///used as the primary method to show a collection of templates.  @attention parameters are the same as the View::get function
	static protected function show(){
		self::$showArgs = func_get_args();
		Hook::run('viewPreShow',self::$showArgs);
		$output = call_user_func_array(array('self','get'),self::$showArgs);
		Hook::run('viewPostShow',$output,self::$showArgs);
		Hook::run('preHTTPMessageBody');
		echo $output['combine'];
	}

	///calls show then dies
	static protected function end(){
		call_user_func_array(array('self','show'),func_get_args());
		exit;
	}

	///used to get a collection of templates without displaying them
	/**
	@param	templates	the input or output form of View::parseTemplateString

		where subtemplates is the passed into get as $templates.

		 In the case of subtemplates, named ouput of each subtemplate along with the total previous output of the subtemplates is passed to the supertemplate.  The output of each subtemplate is passed by name in a $templates array, and the total output is available under the variable $input.
	@return output from the templates
	*/
	static protected function get($templates,$parseTemplates=true){
		if($parseTemplates){
			//this parses all levels, so only need to run on non-recursive call
			if(!is_array($templates)){
				$templates = self::parseTemplateString($templates);
			}
			$templates = self::parseAliases($templates);
			$templates = self::attachChildren($templates);
		}
		$return['collect'] = array();
		while($templates){
			$template = array_pop($templates);
			if(is_array($template)){
				if($template[2]){
					$got = self::get($template[2],false);
					if($template[0]){
						$output = self::getTemplate($template[0],array('templates'=>$got['collect'],'input'=>$got['combine']));
					}
					Arrays::addOnKey(($template[1] ? $template[1] : $template[0]),$output,$return['collect']);
				}else{
					$output = self::getTemplate($template[0]);
					Arrays::addOnKey(($template[1] ? $template[1] : $template[0]),$output,$return['collect']);
				}
			}else{
				$output = self::getTemplate($template);
				Arrays::addOnKey($template,$output,$return['collect']);
			}
			$return['combine'] .= $output;
		}

		return $return;
	}


	/**
		handling examples
			@current:
				1: ['@current']
				2: ['@standard',null,'!current']
				3: ['@standard',null,'currentPage']

				bare,,page,,,!children

				4: ['@standard',null,'currentPage']


		3 forms acceptable.

		Multiline:
			$templateString = '
				template
					subtemplate
						subsubtemplate
						subsubtemplate
				template';

		Single line:
			$templateString = 'template		subtemplate			subsubtemplate			subsubtemplate	template';
			#$templateString = 'template,,subtemplate,,,subsubtemplate,,,subsubtemplate,template';

		template forms
			normal form
				form: "file[:name]"
				Ex: blog/read
				Ex: blog/read:ViewBlog

				file is file path relative to templates directory base, excluding .php
				name is alphanumeric

			special form !real
				loads based on real path (not internal redirect)
			special form !current
				loads template at current route path based on \control\Route::$parsedTokens
			special form !children
				applies all following templates as subtemplates to previous template
			special form prefix @
				Used to represent an alias, though not parsed in this function.  Aliases are defined in View::aliases

		@return

		-	array with each element being a template
		-	an array of structured template arrays:
		@verbatim
array(
	array('templateFile','templateName',$subTemplates),
	array('templateFile2','templateName2',$subTemplates2),
)
		@endverbatim
	*/
	static function parseTemplateString($templateString){
		#single line, break into multiline
		if(!preg_match('/\n/',$templateString)){
			#ensure start template is at level 1
			$templateString = preg_replace('@^ *([^\s,])@',"\t".'$1',$templateString);
			#converge seperation
			$templateString = preg_replace('@,@',"\t",$templateString);
			#conform newline spacing
			$templateString = preg_replace('@\t+@',"\n".'$0',$templateString);
		}
		#remove start space and newlines
		$templateString = preg_replace('@^[ \n]*@','',$templateString);

		#remove excessive tabbing (items are now separated by newlines on all cases)
		preg_match('@^\t+@',$templateString,$match);
		if($match){
			$excessiveTabs =  str_repeat('\t',strlen($match[0]));
			$templateString = preg_replace('@(^|[^\t])'.$excessiveTabs.'@','$1',$templateString);
		}

		$templates = explode("\n",$templateString);
		$array = self::generateTemplatesArray($templates);

		#replace !current with current control to template location
		$array = Arrays::replaceAll('!current',implode('/',\control\Route::$parsedTokens),$array);
		$array = Arrays::replaceAll('!real',implode('/',\control\Route::$realTokens),$array);

		#set !children to work with parseAliases
		#$array = Arrays::replaceAllParents('!children','!children',$array,2);
		return $array;
	}
	///internal use for parseTemplateString
	static function generateTemplatesArray($templates,$depth=0,&$position=0){
		$templatesArray = array();
		$totalTemplates = count($templates);
		for(; $position < $totalTemplates; $position++){
			$template = $templates[$position];
			preg_match('@(\t*)([^\t]+$)@',$template,$match);
			$templateDepth = strlen($match[1]);
			$templateId = $match[2];

			#indicates sub templates, so add sub templates
			if($templateDepth > $depth){
				#add subtemplates to previous template
				$templatesArray[count($templatesArray) - 1][2] = self::generateTemplatesArray($templates,$templateDepth,$position);
			}
			#in too deep, return to parent
			elseif($depth > $templateDepth){
				return $templatesArray;
			}
			#in the same depth, add to array
			else{
				list($templateFile,$templateName) = explode(':',$templateId);
				$templatesArray[] = array($templateFile,$templateName);
			}
		}
		return $templatesArray;
	}
	///replaces aliases identified with @ALIAS_NAME in template array and defined in the alias file
	static protected function parseAliases(&$array){
		foreach($array as $i=>$v){
			//is aliased, find replacement
			$tree = &$array[$i];
			do{
				$replaced = false;
				if(substr($tree[0],0,1) == '@'){
					$newTree = self::replaceTemplateAlias(substr($tree[0],1),$tree[2]);
					array_splice($array,$i,1,$newTree);
					$replaced = true;
				}
				if(is_string($tree[2]) && substr($tree[2],0,1) == '@'){
					$tree[2] = self::replaceTemplateAlias(substr($tree[2],1));
					$replaced = true;
				}
				$tree = &$array[$i];
			} while($replaced);
			if(is_array($tree[2])){
				$tree[2] = self::parseAliases($tree[2]);
			}
		}
		unset($tree);

		return $array;
	}
	/// !children from aliases results in following tree being put into the !children
	static function attachChildren(&$array){
		$count = count($array);
		for($i=1;$i<$count;$i++){
			$found = false;
			$array[$i-1] = Arrays::replaceAllParents('!children',$array[$i],$array[$i-1],1,$found);
			if($found){
				//collapse tree
				array_splice($array,$i-1,2,[$array[$i-1]]);
			}
		}
		return $array;
	}
	///returns the tree stucture that get expects after replace 1 alias (the root)
	static protected function replaceTemplateAlias($alias,$children=null){
		$tree = self::$aliases[$alias];
		if(!$tree){
			Debug::toss('Could not find alias: '.$alias);
		}
		if(!is_array($tree)){
			$tree = self::parseTemplateString($tree);
		}

		if($children){
			$tree = Arrays::replaceAll('!children',$children,$tree);
		}

		return $tree;
	}

//+	css & js resource handling {

	///page css
	static $css = array();
	///page css put at the end after self::$css
	static $lastCss = array();
	///page js found at top of page
	static $topJs = array();
	///page js found at bottom of page
	static $bottomJs = array();
	///page js put at the end after self::$bottomJs
	static $lastJs = array();
	///used internally.
	/**
	@param	type	indicates whether tag is css, lastCss, js, or lastJs.  Optional prefix with "-" or "+" to temporarilty change the tagAddOrder ('-' operation becomes prepend instead of append)
		css
		lastCss
		js (alias for bottomJs)
		topJs
		BottomJs
		lastJs
	@param args	additional args taken as files.  Each file in the passed parameters has the following special syntax:
		-starts with http(s): no modding done
		-starts with "/": no modding done
		-starts with "inline:": file taken to be inline css or js.  Code is wrapped in tags before output.
		-starts with none of the above: file put in path /instanceToken/type/file; ex: "/public/css/main.css"
		-if array, consider first element the tag naming key and the second element the file; Used for ensuring only one tag item of a key, regardless of file, is included.
	@note	if you don't want to have some tag be unique (ie, you want to include the same js multiple times), don't use this function; instead, just set tag variable (like $bottomJs) directly
	*/
	static protected function addTags($type,$paths){
		$paths = (array)$paths;

		//handle order prefix
		if(in_array(substr($type,0,1),array('-','+'))){
			$originalTagAddOrder = $tagAddOrder;
			$tagAddOrder = substr($type,0,1);
			$type = substr($type,1);
		}

		//handle alias
		$type = $type == 'js' ? 'bottomJs' : $type;

		if(in_array($type,array('css','lastCss'))){
			$uniqueIn = array('css','lastCss');
			$folder = 'css';
		}else{
			$uniqueIn = array('topJs','bottomJs','lastJs');
			$folder = 'js';
		}

		if($paths){
			if($tagAddOrder == '-'){
				krsort($paths);
			}
			$typeTags =& self::$$type;
			foreach($paths as $file){
				//keyed (array) tags
				if(is_array($file)){
					$key = $file[0];
					$file = $file[1];
				}

				if(substr($file,0,7) == ('inline:')){
					$typeTags[] = $file;
				}else{
					//user is adding it, so assume css is at instance unless it starts with http or /
					if(substr($file,0,1) != '/' && !preg_match('@^http(s)?:@',$file)){
						$file = '/'.$_ENV['urlProjectFileToken'].'/'.$folder.'/'.$file;
					}
					if(!$key){
						//only load resource once, clear previous entry
						foreach($uniqueIn as $unique){
							Arrays::remove(self::$$unique,$file);
						}
						if($tagAddOrder == '-'){
							array_unshift($typeTags,$file);
						}else{
							$typeTags[] = $file;
						}
					}else{
						$typeTags[$key] = $file;
						unset($key);
					}
				}
			}
		}
		if($originalTagAddOrder){
			$tagAddOrder = $originalTagAddOrder;
		}
	}
	///used by getCss to turn css array into html string
	static function getCssString($array,$urlQuery=null){
		foreach($array as $file){
			if(preg_match('@^inline:@',$file)){
				$css[] = '<style type="text/css">'.substr($file,7).'</style>';
			}else{
				if($urlQuery){
					$file = Http::appendsUrl($urlQuery,$file);
				}
				$css[] = '<link rel="stylesheet" type="text/css" href="'.$file.'"/>';
			}
		}
		return implode("\n",$css);
	}
	///Outputs css style tags with self::$css
	/**
	@param	urlQuery	array	key=value array to add to the url query part; potentially used to force browser to refresh cached resources
	*/
	static protected function getCss($urlQuery=null){
		if(self::$css){
			$css = self::getCssString(self::$css,$urlQuery);
		}
		if(self::$lastCss){
			$css .= self::getCssString(self::$lastCss,$urlQuery);
		}
		return $css;
	}
	///used by getCss to turn css array into html string
	static function getJsString($array,$urlQuery=null){
		foreach($array as $file){
			//Intended to be used for plain script
			if(preg_match('@^inline:@',$file)){
				$js[] = '<script type="text/javascript">'.substr($file,7).'</script>';
			}else{
				if($urlQuery){
					$file = Http::appendsUrl($urlQuery,$file);
				}
				$js[] = '<script type="text/javascript" src="'.$file.'"></script>';
			}
		}
		return implode("\n",$js);
	}

	///	Outputs js script tags with self::$topJs
	/**
	@param	urlQuery	array	key=value array to add to the url query part; potentially used to force browser to refresh cached resources
	*/
	static protected function getTopJs($urlQuery=null){
		if(self::$topJs){
			$js = self::getJsString(self::$topJs,$urlQuery);
		}
		return $js;
	}

	///	Outputs js script tags with self::$bottomJs and self::$lastJs
	/**
	@param	urlQuery	array	key=value array to add to the url query part; potentially used to force browser to refresh cached resources
	*/
	static protected function getBottomJs($urlQuery=null){
		if(self::$bottomJs){
			$js = self::getJsString(self::$bottomJs,$urlQuery);
		}
		if(self::$lastJs){
			$js .= self::getJsString(self::$lastJs,$urlQuery);
		}
		return $js;
	}
//+	}
//+	section handling {
	static $openSection = '';
	static $sections = [];
	//buffers following output and places it into keyed array.  Use getSection to get output
	/**
	if called with name:
		if no section open, open section
		if section open, place output into keyed array, close section, and then open new section
	if called without name
		if section open, put output into keyed array, close section

	@note If section already exists, will overwrite
	*/
	static protected function section($name=''){
		if(self::$openSection){
			self::$sections[self::$openSection] = ob_get_clean();
			self::$openSection = '';
		}
		if($name){
			self::$openSection = $name;
			ob_start();
		}
	}
	///gets the string collected under a section name.  If not name specified, returns the close the open section and return it
	static protected function getSection($name=null){
		if(!$name){
			if($name = self::$openSection){
				self::section();
				return self::getSection($name);
			}else{
				Debug::toss('Vieww::getSection called without section to get');
			}
		}
		return self::$sections[$name];
	}
//+	}
	///standard js object that gets turned into json for pages, ajax, or api.  Various information loaded into it on calling this
	static $json = null;
	static $mappedKeys = ['messages','id','status','value'];
	///prints the self::$json into the tp.json object.  Requires the previous declaration of tp js object on the page
	static protected function getStdJson($full=true){
		if($full){
			if(Control::$in){
				self::$json['in'] = Control::$in;
			}
			self::$json['route']['parsed'] = \control\Route::$parsedTokens;
		}

		foreach(self::$mappedKeys as $key){
			if(!self::$json[$key]){
				self::$json[$key] = Control::$$key;
			}
		}
		self::$json['timezone'] = \User::timezone();

		Hook::run('stdJson');
		return Tool::json_encode(self::$json);
	}
	///end the script with stdJson
	static protected function endStdJson(){
		self::endJson(self::getStdJson(false),false);
	}
	///end script with xml
	static function endXml($content){
		Hook::run('preHTTPMessageBody');
		header('Content-type: text/xml; charset=utf-8');
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo $content; exit;
	}
	///end script with json
	static function endJson($content,$encode=true){
		Hook::run('preHTTPMessageBody');
		header('Content-type: application/json');
		if($encode){
			echo Tool::json_encode($content);
		}else{
			echo $content;
		}
		exit;
	}

	//it appears the browser parses once, then operating system, leading to the need to double escape the file name.  Use double quotes to encapsulate name
	static function escapeFilename($name){
		return Tool::slashEscape(Tool::slashEscape($name));
	}
	///send an actual file on the system via http protocol
	static function sendFile($path,$saveAs=null,$exit=true){
		//Might potentially remove ".." from path, but it has already been removed by the time the request gets here by server or browser.  Still removing for precaution
		$path = Files::removeRelative($path);
		if(is_file($path)){
			$mime = Files::mime($path);

			header('Content-Type: '.$mime);
			if($saveAs){
				header('Content-Description: File Transfer');
				if(strlen($saveAs) > 1){
					$fileName = $saveAs;
				}else{
					$fileName = array_pop(explode('/',$path));
				}
				header('Content-Disposition: attachment; filename="'.self::escapeFilename($fileName).'"');
			}

			echo file_get_contents($path);
		}elseif($_ENV['resourceNotFound']){
			Config::loadUserFiles($_ENV['resourceNotFound'],'control');
		}else{
			Debug::toss('Request handler encountered unresolvable file.  Searched at '.$path);
		}
		if($exit){
			exit;
		}
	}
	//simple standard logic to generate page title
	static function pageTitle(){
		return Tool::capitalize(Tool::camelToSeparater(implode(' ',\control\Route::$parsedTokens),' '));
	}
	static function contentHeaders($mime=null,$filename=null){
		if($mime){
			header('Content-Type: '.$mime);
		}
		if($filename){
			header('Content-Description: File Transfer');
			header('Content-Disposition: attachment; filename="'.self::escapeFilename($filename).'"');
		}
	}
	///create a url from a path
	/**
	@param	path	path and include parameters, but will be individually overwritten by passed $params arg
	@param	params	params to append to the url
	@examples
		relative paths
			::url('bob')
				page bob is loaded in current directory
			::url('/bob')
				the file bob at the root is shown
		query parts
			::url('?bob=sue')
				bob=sue is the only query
			::url(null,['bob'=>'sue'])
				bob=sue is appended to existing query
			::url('/bob?bill=sue',['bob'=>'sue'])
				bill=sue and bob=sue are the only query parts
			::url('',['bob'=>'sue'])
				bob=sue is the only query part (the blank '' is a present path, which relatively resolves to current file and removes existing query)
	*/
	protected function url($path='',$params=null){
		$path = Http::appendsUrl($params,$path);
		$relativeTo = self::$baseUrl.substr($_SERVER['REQUEST_URI'],1);//request uri always starts with '/'
		return Http::absoluteUrl($path,$relativeTo);
	}
}

///alias for htmlspecialchars
function hsc(){
	return call_user_func_array('htmlspecialchars',func_get_args());
}