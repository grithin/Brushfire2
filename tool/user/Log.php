<?
namespace user; use User;

//logging user actions
class Log{
	static $changeTypes = [
		'insert' => 1,1 => 'insert',
		'replace' => 2,2 => 'replace',
		'update' => 3,3 => 'update',
		'delete' => 4,4 => 'delete'];
	
	function init($user=null){
		//run user actions through the Log::action function
		\Hook::add('userAction',['\user\Log','action']);
	}
	
	static function lastQuery($userId=null){
		$method = User::$db->last['call'][0];
		$args = User::$db->last['call'][1];
		
		if(in_array($method,['insert','insertIgnore','insertUpdate','id'])){
			$type = 'insert';
			if($args[1]['id']){
				$where = $args[1]['id'];
			}else{
				$where = User::$db->under->lastInsertId();
			}
			self::tableChange($args[0],$args[1],$where,'insert');
		}elseif($method == 'replace'){
			self::tableChange($args[0],$args[1],$args[2],'replace');
		}elseif($method == 'update'){
			self::tableChange($args[0],$args[1],$args[2],'update');
		}elseif($method == 'delete'){
			self::tableChange($args[0],null,$args[1],'delete');
		}
	}
	
	
	/**
	@note so that the where column may be looked up on the where (wherein the where is use the id), the id is not jsoned when just the id
	*/
	static function tableChange($table,$change,$where,$type,$userId=null){
		$userId = $userId ? $userId : User::id();
		if(is_array($where) && count($where) == 1 && $where['id']){
			$where = $where['id'];
		}
		if(is_array($where)){
			$where = json_encode($where);
		}
		$change = $change ? json_encode($change) : null;
		$tableId = User::$db->id('table',array('name'=>$table));
		
		User::$db->insert('user_log_table_change',array(
			'user_id' => $userId,
			'ip' => \Http::ip(),
			'table_id' => $tableId,
			'where' => $where,
			'type_id' => self::$changeTypes[$type],
			'created' => new \Time(),
			'change' => $change
		));
	}
	static function action($name,$data=null,$userId=null){
		$userId = $userId ? $userId : User::id();
		$actionId = User::$db->id('user_log_action_type',array('name'=>$name));
		
		if(is_array($data) || (is_object($data) && !method_exists($data,'__toString'))){
			$data = json_encode($data);
		}
		
		User::$db->insert('user_log_action',array(
				'user_id' => $userId,
				'ip' => \Http::ip(),
				'type_id' => $actionId,
				'time' => new \Time,
				'data' => $data,
			));
	}
	static function actionId($name){
		return User::$db->id('user_log_action_type',array('name'=>$name));
	}
}
