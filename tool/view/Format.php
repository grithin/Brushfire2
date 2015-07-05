<?
/**
All functions starting with '_' present the data inside spans to be parsed by javascript
*/
namespace view;
class Format{
	static function phone($value){
		if(strlen($value) == 10){
			$areacode = substr($value,0,3);
			$part1 = substr($value,3,3);
			$part2 = substr($value,6);
			return '('.$areacode.') '.$part1.'-'.$part2;
		}
	}
	static function _dateFormat($time,$format){
		return '<span data-timeFormat="'.$format.'">'.(new \Time($time))->unix.'</span>';
	}
	static function _date($time){
		return self::_dateFormat($time,'Y-m-d');
	}
	static function _datetime($time){
		return self::_dateFormat($time,'Y-m-d H:i:s');
	}
	static function _usaDate($time){
		return self::_dateFormat($time,'n/j/Y');
	}
	static function _usaDatetime($time){
		return self::_dateFormat($time,'F j, Y, g:i a');
	}
	static function _timeAgo($time,$options=[]){
		return '<span data-timeAgo="'.htmlspecialchars(json_encode($options)).'">'.(new \Time($time))->unix.'</span>';
	}
	
	
	static function date(&$value){
		if($value && \InputValidate::check('date',$value)){
			return (new \Time($value,$_ENV['timezone']))->setZone(\User::timezone())->date();
		}
	}
	static function datetime($value){
		if($value && \InputValidate::check('date',$value)){
			return (new \Time($value,$_ENV['timezone']))->setZone(\User::timezone())->datetime();
		}
	}
	static function usaDate($value){
		return (new \Time($value,$_ENV['timezone']))->format('n/j/Y',$timezone);
	}
	static function usaDatetime($value){
		return (new \Time($value,$_ENV['timezone']))->format('F j, Y, g:i a',$timezone);
	}
	static function conditionalBr2Nl($value){
		if(!preg_match('@<div|<p|<table@',$value)){
			$value = preg_replace('@<br ?/>@',"\n",$value);
		}
		return $value;
	}
	///breaks parts of text that go beyond the word size limit
	/**
	@param	limit	word size limit
	*/
	static function wordSize($text,$limit){
		///if previous part was wordsize, and there follows a non space character, split to wordsize
		while(preg_match('@((?>[^\s]{'.$limit.'}))([^\s])@',$text)){
			$text = preg_replace('@((?>[^\s]{'.$limit.'}))([^\s])@','$1 $2',$text,1);
		}
		return $text;
	}
	///limit text to some character limit, with handling for overage
	/**
	@param	options	[ellipsis:true|false	(whether to use ellipsis or span)]
	*/
	static function charLimit($text,$max,$options=[]){
		if(strlen($text) > $max){
			if($options['ellipsis']){
				return substr($text,0,$max-3).'...';	}
			return '<span class="shortened" title="'.htmlspecialchars($text).'">'.htmlspecialchars(substr($text,0,$max)).'</span>';	}
		return $text;
	}
	///takes some value and returns a dollar presentation if value is not null or '-'
	static function dollar($value,$default=null){
		return self::roundTo($value,2,$default,'$');
	}
	static function _dollar($value,$round=2){
		return '<span data-dollar>'.$value.'</span>';
	}
	///takes some value and returns a percentage presentation if value is not null or '-'
	static function percent($value,$round=2,$default=null){
		if($value === '-' || $value === null){
			$value = $value*100;
		}
		return self::roundTo($value,$round,$default,'','%');
	}
	static function roundTo($value,$round=2,$default='',$prefix='',$affix=''){
		if(($value === '-' || $value === null) && $default !== null){
			return $default;
		}else{
			return $prefix.number_format(round($value,$round),$round).$affix;
		}
	}
}
