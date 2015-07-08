<?
namespace cache;
class Memcached extends \Cache{
	public $_success = true;
	function __construct($connectionInfo=null,$options=null){
		$this->prefix = $options['prefix'];
		\Debug::out($this->prefix);
		$this->connectionInfo = $connectionInfo;
		if(!class_exists('Memcached') || !$connectionInfo){
			$this->_success = false;
			return;
		}
		$this->under = new \Memcached;
		$this->under->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
		if(!is_array(current($this->connectionInfo))){
			$this->connectionInfo = array($this->connectionInfo);
		}
		foreach($this->connectionInfo as $v){
			if(!$this->under->addserver($v[0],$v[1],$v[2])){
				\Debug::toss('Failed to add server "'.$name.'"',__CLASS__.'Exception');
			}
		}
	}
	protected function touch(){
		$args = func_get_args();
		$args[0] = $this->prefix.$args[0];
		return call_user_func_array([$this->under,'touch'],$args);
	}
	protected function delete(){
		$args = func_get_args();
		$args[0] = $this->prefix.$args[0];
		return call_user_func_array([$this->under,'delete'],$args);
	}
	protected function get(){
		$args = func_get_args();
		$args[0] = $this->prefix.$args[0];
		return call_user_func_array([$this->under,'get'],$args);
	}
	protected function set(){
		#\Debug::toss('fuck this');
		$args = func_get_args();
		$args[0] = $this->prefix.$args[0];
		return call_user_func_array([$this->under,'set'],$args);
	}
	
	public $casToken;
	protected function lastCasToken(){
		return $this->casToken;
	}
	///@note casToken not set if key not in cache
	protected function casGet($name){
		$got = $this->under->get($name,null,$this->casToken);
	}
	protected function getAndCasToken($name){
		$casToken = 0.0;
		$got = $this->under->get($name,null,$casToken);
		if($casToken == 0.0){
			$this->under->set($name,0);
			$got = $this->under->get($name,null,$casToken);
		}
		return [$got,$casToken];
	}
}