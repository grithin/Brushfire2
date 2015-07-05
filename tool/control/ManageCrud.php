<?
namespace control;
/*
Encapsulate logic determining which of the CRUD operations to apply based on
	-explicit input in the form of _update, _create, _upsert, _delete, _read keys
	-default to read if in command list

Object can be used as an array or as an object with attributes:
	value: (the return value from the command called)
	command: string (the command called)
	explicit: true|false (whether the command was requested by the post)

@note	Adds 'value' and 'command' keys to View::$json
*/
class ManageCrud extends \ArrayObject{
	public $commands = ['create','update','upsert','delete','read'];
	/**
	@param	options	[
		commands: commands to check for
		]
	*/
	function __construct($commands){
		$commands = isset($commands) ? \Arrays::toArray($commands) : $this->commands;
		
		$explicit = false;
		
		while(true){
			//see if the command was specified in the input
			foreach($commands as $potentialCommand){
				if(\Control::$in['_'.$potentialCommand]){
					$explicit = true;
					$command = $potentialCommand;
					break 2;
				}
			}
			
			//no specified action, try reading
			if(in_array('read',$commands) && method_exists(\Control::$lt,'read')){
				$command = 'read';
			}
			break;
		}
		
		if($command){
			
			$returned = \Control::$lt->$command();
			
			\View::$json['value'] = $returned;
			\View::$json['command'] = $command;
			
			return $this->exchangeArray(['value'=>&$returned,'command'=>$command,'explicit'=>$explicit]);
		}
		return $this->exchangeArray([]);
	}
	function __get($key){
		return $this->offsetGet($key);
	}
}