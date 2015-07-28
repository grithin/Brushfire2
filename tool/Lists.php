<?
///general functions for dealing with lists in strings
class Lists{
	static function newlines($string){
		$lines = preg_split('@[\n\r]@',$string);
		$items = [];
		foreach($lines as $line){
			$line = trim($line);
			if($line){
				$items[] = $line;
			}
		}
		return $items;
	}
	//built insert values
	static function dbInsert($items){
		if(is_array($items[0])){
			die('not built');
		}else{
			return implode(',',array_map(function($item){return '(\''.addslashes($item).'\')';},$items));
		}
	}
}