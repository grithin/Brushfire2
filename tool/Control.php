<?
/**
Primary concepts
	Model of control (CLR): in > control > tool > control > return 
		From this, it is apparently a glue, and should be available everywhere
	
	For each page, there is a contexted control.  That is, this class and namespace \control represent the general control functions, where as context control is section and page specific.
	For each page, there are contexted tools.  These are a set of section and page specific tools, which server better in that context then they do outside in a general context, say directly in the /tools directory
		the class of contexted tools for any page will be assigned to control->lt (lt means local tool)
	For each page, there is one primary database.  It is possible other databases will be accessed, but it is fine to assume within the contexted tools or control related tools that Db::primary() is used
	
	The only apparent reason for having multiple control objects would be if a page were trying to reproduce the functionality of some other page, on top of doing other things.  But, this is such a rare occurrence that I am not coding for it.  It is best done, perhaps, by running another process

Control provides standardised message handling (errors and such)	
	Four types of messages are recognized:
		Errors: Things that prevent movement forward
		Warnings: Things tthat may later prevent movement forward
		Notices: Things to optmize movement forward
		Success: Indicator of movement forward



*/
class Control{	
	static $db;
	static $model;///< the data model for the site
	static $lt;///< the local tool contexted to the page request
	
	static $in;///< the potentially tool-changed input, made available to templates, controls and tools
	static $get;///< array of parsed get
	static $post;///< array of parsed post
	static $originalIn;///< an unmodified version of $in
	
	static $id;///< the primary id for the page item if there is one
	static $item;///< when a page is involving one item, use this variable to hold the data
	static $items;///< when a page is involving multiple items of the same type, use this variable to hold the data
	
	static $messages;///< system messages to display to the user
	static $value = [];///< the value of the return (usually for json responses)
	static $status = 1;///< 2 = error, 1 = success
	static $ajax = false;///< whether or not the request was an ajax one
	static function init(){
		//++ parse input {
		
		//++ Handle GET and POST variables{
		$in['get'] = $_SERVER['QUERY_STRING'];//we take it from here b/c php will replace characters like '.' and will ignore duplicate keys when forming $_GET
		//can cause script to hang (if no stdin), so don't run if in script unless configured to
		if(!$_ENV['inScript'] || $_ENV['scriptGetsStdin']){
			//multipart forms can either result in 1. input being blank or 2. including the upload.  In case 1, post vars can be taken from $_POST.  In case 2, need to avoid putting entire file in memory by parsing input
			if(substr($_SERVER['CONTENT_TYPE'],0,19) != 'multipart/form-data'){
				$in['post'] = file_get_contents('php://input');
				$in['post'] = $in['post'] ? $in['post'] : file_get_contents('php://stdin');
			}elseif($_POST){
				$in['post'] = http_build_query($_POST);
			}
		}
		if(substr($_SERVER['CONTENT_TYPE'],0,16) == 'application/json'){
			$in['post'] = json_decode($in['post'],true);
		}else{
			$in['post'] = Http::parseQuery($in['post'],$_ENV['pageInPHPStyle']);
		}
		if($in['post']['_json']){///in cases of file uploads, can't post in as application/json, so use _json param
			$in['post'] = json_decode($in['post']['_json'],true);
		}
		
		$in['get'] = Http::parseQuery($in['get'],$_ENV['pageInPHPStyle']);
		//it may be desired to use complex input which regular get syntax doesn't handle well, so have _json override
		if($in['get']['_json']){///in cases of file uploads, can't post in as application/json, so use _json param
			$in['get'] = json_decode($in['get']['_json'],true);
		}
		
		self::$get = $in['get'];
		self::$post = $in['post'];
		self::$in = Arrays::merge($in['get'],$in['post']);
		//++ }
		
		
		//since various functions may directly act on ->in, save the original for reference
		self::$originalIn = self::$in;
		
		//++ }
		
		//set time zone if specified
		if(self::$in['_setTimezone']){
			if(self::$in['_tzOffset']){
				\User::setTimezone(timezone_name_from_abbr("", -60*self::$in['_tzOffset'], self::$in['_dst']));
			}elseif(self::$in['_tz']){
				\User::setTimezone(self::$in['_tz']);
			}
		}
	
		
		if($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest'){
			self::$ajax = true;
		}
		
		//++ Handle premade system messages {
		//Cookied page message are intended to be shown on viewed pages, not ajax responses, so ignore on ajax
		if(!self::$ajax){
			//++ handle COOKIE messages {
			if($_COOKIE['_PageMessages']){
				do{
					$cookie = @unserialize($_COOKIE['_PageMessages']);
					if(is_array($cookie)){
						if(is_array($cookie['target'])){
							if(!array_diff($cookie['target'],Route::$tokens)){
								self::$messages = @unserialize($cookie['data']);
							}else{
								//not on right page, so break
								break;
							}
						}else{
							self::$messages = @unserialize($cookie['data']);
						}
					}
					Cookie::remove('_PageMessages');
				}while(false);
			}
			//++ }
		}
		//++ }
		//load db if configured
		if($_ENV['database']['default']){
			self::$db = Db::init($_ENV['projectName'],$_ENV['database']['default']);
			if(!$_ENV['database.noModel']){
				//load model
				require_once $_ENV['systemFolder'].'tool/ModelTrait.php';
				require_once $_ENV['projectFolder'].'tool/Model.php';
				self::$model = new Model(['forceRemake'=>$_ENV['forceRemakeModel'],'db'=>self::$db]);
			}
		}
	}
	/**
		Use local tool attributes if not found
	*/
	function __get($name){
		return self::$lt->$name;
	}
	
	function addLocalTool($tokens){
		if(!$tokens){
			$class = 'stdClass';
		}else{
			Files::incOnce($_ENV['projectFolder'].'tool/local/'.implode('/',$tokens).'.php');
			$class = '\\local\\'.implode('\\',$tokens);
			if(strpos($class,'-') !== false){
				$class = Tool::toCamel($class,false,'-');
			}
		}
		self::addLocalClass($class);
	}
	function addLocalClass($class){
		self::$lt = new $class;
		self::$lt->control = $this;
		self::$lt->in =& self::$in;
		self::$lt->messages =& self::$messages;
		if(!self::$lt->db){
			self::$lt->db =& self::$db;
			self::$lt->model =& self::$model;
		}
	}
	///adds message of type error.  See self::message
	function error($message,$fields=null,$options=[]){
		self::$status = 2;
		self::message($message,$fields,'error',$options);	}
	///adds message of type success.  See self::message
	function success($message=null,$fields=null,$options=[]){
		if(!$message){
			$message = View::pageTitle().' successful';
		}
		self::message($message,$fields,'success',$options);	}
	///add message of type notice.  See self::message
	function notice($message,$fields=null,$options=[]){
		self::message($message,$fields,'notice',$options);	}
	///add message of type warning.  See self::message
	function warning($message,$fields=null,$options=[]){
		self::message($message,$fields,'warning',$options);	}
	
	static $additionalOptions = [];
	///adds a system message (normally for use in standard JSON return object)
	/**
	@param	fields	sting field, or array of string fields
	*/
	function message($message,$fields,$type,$options=[]){
		$fields  = (array)$fields;
		$message = array('type'=>$type,'fields'=>$fields,'content'=>(string)$message);
		$options = array_merge(self::$additionalOptions,(array)$options);
		if($options){
			$message = Arrays::merge($message,$options);
		}
		self::$messages[] = $message;
	}
	function fieldsErrors($fields){
		return self::getMessages('error',(array)$fields);
	}
	///returns errors
	function errors(){
		return self::getMessages('error');
	}
	///checks if there was an error
	function hasError(){
		if(self::$status == 2){
			return true;
		}
	}
	///get messages based on name
	function getMessages($type=null,$name=null){
		$messages = array();
		foreach((array)self::$messages as $message){
			if($type && $type != $message['type']){
				continue;
			}elseif($fields && array_intersect($fields,$message['fields'])){
				continue;	}
			$messages[] = $message;	}
		return $messages;
	}
	
	static $validationFields;///< the [field=>value,...] reference to array of fields being validated
	static $validatedFields;///< array of fields keys => array validations made against.  Reset on each validate call
	/**
	@param	rules	[field=>rules,field=>rules,...]	see applyFieldRules for rules format
	@param	env	[
		in:[field=>value]	(defaults to self::$in
		tool:toolObj	(defaults to self::$lt)
		errorHandler: callable	(defaults to [$this,'error'])]
		
	@note	don't call it within itself
	@return	false if error, else true
	*/
	function validate($rules,$env=[],$options=null){
		//++ set up defaults {
		if(!$env['in']){
			$env['in'] = &self::$in;
		}
		$env['tool'] = $env['tool'] ? $env['tool'] : self::$lt;
		$env['errorHandler'] = $env['errorHandler'] ? $env['errorHandler'] : ['self','error'];
		//++ }
		
		//hooks such as converting fields to strings, or adding a CSRF token validater rule
		Hook::run('preValidate',$rules,$env,$options);
		
		//set to allow validater callbacks to use the context of the other fields
		self::$validationFields = &$env['in'];
		//reset validatedFields
		self::$validatedFields = [];
		
		foreach($rules as $field=>$ruleSet){
			unset($fieldValue, $byReference);
			$fieldValue = null;
			if(isset($env['in'][$field])){
				$fieldValue = &$env['in'][$field];
				$byReference = true;	}
			
			$result = self::applyFieldRules($field, $fieldValue, $ruleSet, $env);
			
			if($result['hasError']){
				$env['hasError'] = true;}
			//since there was no fields[field], yet the surrogate was manipulated, set the fields[field] to the surrogate
			if(!$byReference && $fieldValue){
				$env['in'][$field] = $fieldValue;	}
			//rule had a !!, indicating stop all validation
			if($result['break']){
				break;	}	}
		return !$env['hasError'];
	}
	
	static $currentField = '';///< current field being parseds
	/**
	If validation fails, the exception of InputException is expected, where the exception message turns into the error message put into the error part of the control $messages
	If the exception code is 2, the exception message will not be added to the control messages, but the control status will be set to error (2)
	
	
	@param	rules	string or array	
		Rules can be an array of rules, or a string separated by "," for each rule.  
		Each rule can be a string or arrays
		As a string, the rule should be in one of the following forms:
				"f.name|param1;param2" indicates InputFilter method
				"v.name|param1;param2" indicates InputValidate function
				"g.name|param1;param2" indicates global scoped function
				"class.name|param1,param2,param3" indicates static method "name: of class "class" 
				"l.name|param1,param2,param3" Local tool method
				'd.name|param1,..." indicates data model handler method
		As an array, the rule function part (type:method) is the first element, and the parameters to the function part are the following elements.
		
		The fn part can be prefixed with "!" to break on error with no more rules for that field should be applied
		The fn part can be prefixed with "!!" to break on error with no more rules for any field should be applied
		The fn part can be prefixed with "?" to indicate the validation is optional, and not to throw an error (useful when combined with '!' => '?!v.filled,email')
		The fn part can be prefixed with "~" to indicate if the validation does not fail, then there was an error
		The fn part can be prefixed with "&" to indicate code should break if there were any previous errors on that field
		The fn part can be prefixed with "&&" to indicate code should break if there were any previous errors on any field in the validate run
		
		
		If array, first part of rule is taken as string with the behavior above without parameters and the second part is taken as the parameters; useful for parameters that include commas or semicolons or which aren't strings
		
		Examples for rules:
			'f.trim,v.email'
			'CustomValidation.method|param2,param3'
			['f.trim',['v.regex','PATTERN']]
			['f.trim',[['!',['InputValidate','regex']],'PATTERN']]
	
	@return	
	*/
	function applyFieldRules($field, &$value, $rules, &$env){
		$fieldError = false;
		
		self::$currentField = $field;
		
		$rules = Arrays::toArray($rules);
		for($i=0;$i<count($rules);$i++){
			$rule = $rules[$i];
			unset($prefixOptions);
			$params = array(&$value);
			
			if(is_array($rule)){
				$callback = array_shift($rule);
				if(is_array($callback)){
					list($prefixOptions) = self::rulePrefixOptions($callback[0]);
					$callback = $callback[1];	}
				$paramsAdditional = &$rule;
			}else{
				list($callback,$paramsAdditional) = explode('|',$rule);
				
				if($paramsAdditional){
					$paramsAdditional = explode(';',$paramsAdditional);	}	}
			///merge field value param with the user provided params
			if($paramsAdditional){
				Arrays::mergeInto($params,$paramsAdditional);	}
			if(!$prefixOptions){
				list($prefixOptions,$callback) = self::rulePrefixOptions($callback);	}
			if($prefixOptions['continuity'] && $fieldError){
				break;	}
			if($prefixOptions['fullContinuity'] && ($fieldError || $env['hasError'])){
				break;	}
			
			$callback = self::ruleCallable($callback,$env);
			if(!is_callable($callback)){
				Debug::toss('Rule not callable: '.var_export($rule,true));	}
			try{
				/**
				Thoughts on the principles of the validation callback parameters
					1. The field name is not included, b/c it is rare that the validation cares about the field name.  
						If it does, the validation is often a custom function meant specifically for that field.  
						And, if it is not such a custom function, the field name can be passed manually by doing so in formulating the rules
						But, just for the hell of it, there is Control::$currentField
					2. Sometimes a field is validated in the context of other fields.  It becomes necessary that the validator can access these other fields.
						Passing in the fields, however, means the majority of functions that don't care would have to change their parameters to account for this useless parameter.
						Now, it can be expected the validate function will not be called within itself, so the static Control::$validationFields should accomodate
				*/
				call_user_func_array($callback,$params);
				
				if($prefixOptions['not']){
					$prefixOptions['not'] = false;
					Debug::toss('{_FIELD_} Failed to fail a notted rule: '.var_export($rule,true),'InputException');
				}
			}catch(InputException $e){
				//this is considered a pass
				if($prefixOptions['not']){
					continue;
				}
				//add error to messages
				if(!$prefixOptions['ignoreError']){
					$fieldError = true;
					if($e->getCode() == 2){
						//set status to error, but don't add message
						self::$status = 2;
					}else{
						call_user_func_array($env['errorHandler'],[$e->getMessage(),$field,$errorOptions,$e]);
					}
				}
				//full break will break out of all fields
				if($prefixOptions['fullBreak']){
					return ['hasError'=>$fieldError,'break'=>true];
				}
				//break will stop validators for this one field
				if($prefixOptions['break']){
					break;
				}
			}
			self::$validatedFields[$field][] = $callback;
		}
		return ['hasError'=>$fieldError];
	}
	///check if a field has been valiated with a certain validation.
	/**
	@param	validation	the non-shortened validation callback form
	*/
	function fieldValidated($field,$validation,$validatedFields=null){
		$validatedFields = $validatedFields ? $validatedFields : self::$validatedFields;
		$validations = $validatedFields[$field];
		if($validations){
			foreach($validations as $v){
				if($v == $validation){
					return true;
				}
			}
		}
	}
	function ruleCallable($callback,&$env){
		if(is_string($callback)){
			list($type,$method) = explode('.',$callback,2);
			if(!$method){
				$method = $type;
				unset($type);
			}
		}else{
			return $callback;
		}
		
		if(!$callback){
			Debug::toss('Failed to provide callback for input handler');
		}
		switch($type){
			case 'f':
				return ['Filter',$method];
			break;
			case 'v':
				return ['Validate',$method];
			break;
			case 'l':
				return [$env['tool'],$method];
			break;
			case 'g':
				$method;
			break;
			default:
				if($type){
					return [$type,$method];
				}
				return $callback;
			break;
		}
	}
	function rulePrefixOptions($string){
		//used in combination with !, like ?! for fields that, if not empty, should be validated, otherwise, ignored.
		for($length = strlen($string), $i=0;	$i<$length;	$i++){
			switch($string[$i]){
				case '&':
					if($string[$i + 1] == '&'){
						$i++;
						$options['fullContinuity'] = true;
					}else{
						$options['continuity'] = true;
					}
					break;
				case '?':
					$options['ignoreError'] = true;
					break;
				case '!':
					if($string[$i + 1] == '!'){
						$i++;
						$options['fullBreak'] = true;
					}else{
						$options['break'] = true;
					}
					break;
				case '~':
					$options['not'] = true;
					break;
				default:
					break 2;
			}
		}
		return  [$options,substr($string,$i)];
	}
	///puts messages in cookie for next pageload
	function saveMessages($targetPage=null){
		$cookie['data'] = serialize(self::$messages);
		if($targetPage){
			$cookie['target'] = $targetPage;
		}
		
		Cookie::set('_PageMessages',serialize($cookie));
	}
	//saves messages before redirect
	function redirect($path=null){
		self::saveMessages();
		Http::redirect($path);
	}
}
