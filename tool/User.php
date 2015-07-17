<?
/**
Most of the functions assume the application is on the current user.


TODO: GUI for adding groups, users, linking groups to users. linking permissions, adding permissions, editing users

$_ENV['user.signinUrl']

*/
class User{
	static $id;///< id of the logged in user
	static $db;///< db containing user tables
	/**
	@note adds json user_id
	*/
	static function init($db=null){
		self::$id = $_SESSION['user.id'];
		self::$db = $db ? $db : \Db::primary();

		if($_ENV['user.log'] || self::$db->tableExists('user_log_action')){
			\user\Log::init(self::$id,$this);
		}
		if(class_exists('\View',false)){
			\View::$json['user_id'] = self::$id;
		}

		if(!$_ENV['user.signinUrl']){
			$_ENV['user.signinUrl'] = '/user/signin';
		}
	}
	static function signin($userId,$name=null){
		self::$id = $userId;
		\Session::create();
		\Session::$other = array('user_id'=>$userId);
		$_SESSION['user.id'] = $userId;
		if($name){
			$_SESSION['user.name'] = $name;
		}

		if($_COOKIE['desiredUrl']){
			View::$json['redirect'] =  $_COOKIE['desiredUrl'];
			Cookie::remove('desiredUrl');
		}

		\Hook::run('userAction','signin');
	}
	static function signout(){
		\Hook::run('userAction','signout');
		session_destroy();
	}
	static function timezone(){
		return $_SESSION['timezone'];
	}
	///sets user timezone if it is valid
	static function setTimezone($timezone){
		if($timezone){
			try{
				(new \Time())->setZone($timezone);
			}catch(Exception $e){
				Debug::log($e);
				return;
			}
			$_SESSION['timezone'] = $timezone;
		}
	}
	static function id(){
		return self::$id;
	}
	///ensure user is logged in
	static function required(){
		if(!self::id()){
			\Cookie::set('desiredUrl',$_SERVER['REQUEST_URI']);
			\Http::redirect($_ENV['user.signinUrl']);
		}
	}
	static function passwordHash($password){
		if(!$_ENV['user.passwordSalt']){
			$_ENV['user.passwordSalt'] = '123';
		}
		//if salt is weak (ie "1", does not much deter password crackers.  So, force it to be strong
		$salt = sha1($_ENV['user.passwordSalt']);
		//salt makes hash dictionaries more difficult
		return sha1($password.$salt);
	}

//+	basic user and group permission handling{
	static function isAdmin(){
		if(isset($_SESSION['isAdmin'])){
			return $_SESSION['isAdmin'];
		}
		$_SESSION['isAdmin'] =  self::inGroup('admin');
		return $_SESSION['isAdmin'];
	}
	static $userGroups;
	static $groupPermissions;
	static $userPermissions;
	static function getUserGroups($userId=null){
		$userId = $userId ? $userId : User::id();
		if(!isset(self::$userGroups[$userId])){
			self::$userGroups[$userId] = self::$db->column('user_group_user',array('user_id'=>$userId),'user_group_id');
		}
		return self::$userGroups[$userId];
	}
	static $groupIds;///< list of group ids mapped to their names
	static function inGroup($name,$user=null){
		if(!self::$groupIds[$name]){
			self::$groupIds[$name] = self::$db->row('user_group',array('name'=>$name),'id');
		}
		$groups = self::getUserGroups($user);
		return in_array(self::$groupIds[$name],$groups);
	}
	///Check if user has a permission
	/**
	@param	permission	either int id or string name
	@param	userId	the user to check.  Defaults to current user
	*/
	static function hasPermission($permission,$userId=null){
		$userId = $userId ? $userId : User::id();
		if(!$userId){
			return false;	}

		//permission can be given as either the id or the name
		if(!Tool::isInt($permission)){
			$permission = self::getPermission($permission);
		}

		if(!isset(self::$userPermissions[$userId][$permission])){
			$groups = self::getUserGroups($userId);
			if($groups){
				//check the users groups to see if they give user the permission
				foreach($groups as $group){
					if(!isset(self::$groupPermissions[$group][$permission])){
						self::$groupPermissions[$group][$permission] = self::$db->row('user_group_permissionn',['user_permission_type_id'=>$permission],'1');
					}
					if(self::$groupPermissions[$group][$permission]){
						$hasPermission = true;
						break;
					}
				}
			}
			if(!$hasPermission){
				$hasPermission = Db::row('user_permission',array('user_id'=>$userId,'type_id'=>$permission),'1');
			}
			self::$userPermissions[$userId][$permission] = (bool)$hasPermission;
		}
		return self::$userPermissions[$userId][$permission];
	}
	static $permissions;
	static function getPermission($name){
		if(!self::$permissions[$name]){
			self::$permissions[$name] = Db::row('user_permission_type',array('name'=>$name),'id');
		}
		return self::$permissions[$name];
	}
	static function requirePermission($permission){
		if(!self::hasPermission($permission)){
			die('You are not authorized for: '.$permission);
		}
	}
	static $notAuthorizedNote = '';
	static function notAuthorized($note=null){
		if(Config::$x['notAuthorizedPage']){
			self::$notAuthorizedNote = $note;
			View::end(Config::$x['notAuthorizedPage']);
		}else{
			echo $note; exit;
		}
	}
//+	}
}
