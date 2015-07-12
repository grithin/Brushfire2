<?
namespace view;
//since there is integration with display-page level activity, this is a tool of the view
class SortPage{
	/**
	The use of the sort and page functions can vary in how the result is to interact with the rest of the 
		logic and the UI, so the result present provides as much information as might be necessary
	*/
	static function sort($allowed,$sort=null,$default=null,$quoteColumns=true){
		if(!$sort){
			$sort = \Control::$in['_sort'];
		}
		$sorts = explode(',',$sort);
		foreach($sorts as $sort){
			$order = substr($sort,0,1);
			if($order == '-'){
				$order = ' DESC';
				$field = substr($sort,1);
			}else{
				if($order == '+'){
					$field = substr($sort,1);
				}else{
					$field = $sort;
				}
				$order = ' ASC';
			}
			if(in_array($field,$allowed)){
				if($quoteColumns){
					$field = \Db::quoteIdentity($field);
				}
				$orders[]  = $field.$order;
				$usedSorts[] = $sort;
			}
		}
		if(!$orders){
			if($default){
				return self::sort($allowed,$default,null,$quoteColumns);
			}
		}
		return array(
				'sql' => ($orders ? ' ORDER BY '.implode(', ',$orders).' ' : ' '),
				'sorts' => $orders,
				'sort' => ($usedSorts ? implode(',',$usedSorts) : [])
			);
	}
	/**
	@param	options	[
		per: # per page
		page: current page (starts at 1)
		max: max results to look for in count
		
	@note, options param is returns in the  info key, so may be useful to pass in 'sort' key
	*/
	static function page($sql,$options=[]){
		$options['per'] = $options['per'] ? $options['per'] : 50;
		if($options['page'] === null){
			$options['page'] = (int)\Control::$in['_page'];
		}
		
		$options['page']--;
		
		$options['page'] = $options['page'] > 0 ? $options['page'] : 0;
		
		$offset = $options['per'] * $options['page'];
		$sql .= "\nLIMIT ".$offset.', '.$options['per'];
		list($count,$rows) = \Db::countAndRows(($options['max'] ? $options['max'] + 1 : null),$sql);
		$top = $count;
		if($options['max'] && $count > $options['max']){
			$top = $options['max'];
		}
		
		$options['page']++;
		
		$options['pages'] = ceil($top/$options['per']);
		$options['top'] = $top;
		$options['count'] = $count;
		
		
		return ['rows' => $rows,
			'info' => $options];
	}
	static function pagingAttribute($paging=null){
		if(!$paging){
			$paging = self::$lastPaging;
		}
		return ' data-paging="'.$paging['info']['pages'].'" ';
	}
}
