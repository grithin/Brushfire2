<?
namespace cache;
class Redis extends \Cache{
	public $_success = true;
	function __construct($connectionInfo=null,$options=null){
		$this->prefix = $options['prefix'];
		$this->connectionInfo = $connectionInfo;
		if(!class_exists('Redis') || !$connectionInfo){
			$this->_success = false;
			return;
		}
		$this->under = new \Redis;
		call_user_func_array([$this->under,'connect'],$this->connectionInfo);
		if($_ENV['cache.db']){
			$this->under->select($_ENV['cache.db']);
		}
	}
	function touch(){
		$args = func_get_args();
		$args[0] = $this->prefix.$args[0];
		return call_user_func_array([$this->under,'touch'],$args);
	}
	function delete(){
		$args = func_get_args();
		$args[0] = $this->prefix.$args[0];
		return call_user_func_array([$this->under,'delete'],$args);
	}
	function get(){
		$args = func_get_args();
		$args[0] = $this->prefix.$args[0];
		return json_decode(call_user_func_array([$this->under,'get'],$args),true);
	}
	function set(){
		$args = func_get_args();
		$args[0] = $this->prefix.$args[0];
		$args[1] = json_encode($args[1]);
		return call_user_func_array([$this->under,'set'],$args);
	}
	
	///will cause watchedSet to return false on key if value modified between this call and watchedSet call
	function watch(){
		$args = func_get_args();
		$args[0] = $this->prefix.$args[0];
		return call_user_func_array([$this->under,'watch'],$args);
	}
	///set value only if not modified since watch call, then unwatch
	function watchedSet(){
		$args = func_get_args();
		$args[0] = $this->prefix.$args[0];
		$args[1] = json_encode($args[1]);
		$return = call_user_func_array([$this->under->multi(),'set'],$args)->exec();
		call_user_func_array([$this->under,'unwatch'],$args);
		return $return;
	}
	function unwatch(){
		$args = func_get_args();
		$args[0] = $this->prefix.$args[0];
		return call_user_func_array([$this->under,'unwatch'],$args);
	}
}