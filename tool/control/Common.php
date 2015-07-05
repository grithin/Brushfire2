<?
namespace control; use \Control; use \Tool;
///Standard control functions
class Common{
	
	///Attempt to get id from user request
	/**
		Look for "id" in.  Look for integers in url path, starting from base outwards
	*/
	static function getId(){
		$id = abs(\Control::$in['id']);
		if(!$id){
			$tokens = Route::$tokens;
			krsort($tokens);
			foreach($tokens as $token){
				$id = abs($token);
				if($id){
					break;
				}
			}
		}
		
		if($id){
			\Control::$id = $id;
			return $id;
		}
	}
	static function reqId($failCallback=null){
		return self::primaryId($failCallback);
	}
	///setup .  Since this is a primaryId, error 
	static function primaryId($failCallback=null){
		$id = self::getId();
		if(!$id){
			if($failCallback){
				return call_user_func($failCallback);
			}
			self::error('Id not found');
		}
		return $id;
	}
	static function error($message){
		\Control::error($message);
		\Config::loadUserFiles($_ENV['errorPage'],null,null,array('error'=>$message));
		exit;
	}
	static function getOwner($item){
		if($item['user_id__owner']){
			return $item['user_id__owner'];
		}
		if($item['user_id']){
			return $item['user_id'];
		}
		if($item['user_id__creater']){
			return $item['user_id__creater'];
		}
		$keys = array_keys($item);
		foreach($keys as $key){
			if(preg_match('@user_id__@',$key)){
				return $item[$key];
			}
		}
	}
	static function req($path){
		$file = \Config::userFileLocation($path,'control').'.php';
		return \Files::req($file,array('control'));
	}
}
