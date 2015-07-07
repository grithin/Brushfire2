<?
/*
updateMany:
	can update multiple items where the unique key is included in the update item object
	in = {items:[item,item]}
	sm's will show with 'itemOffset" attribute
updateOne:
	can update one also using the item object to find existing (will error on matching multiple items)
	in = {item:{}}
readOne:
	

*/
class ModelApi{
	static $table;
	function standardHandling($allowed=[], $table=null, &$in=null){
		if($in === null){
			$in = &Control::$in;
		}
		$in['type'] = $in['type'] ? $in['type'] : 'readOne';
		if(!$table){
			$table = end(\control\Route::$parsedTokens);
		}
		
		if($allowed && !in_array($in['type'],$allowed)){
			\Control::error('Operation not allowed');
			\View::endStdJson();
		}
		
		
		
		switch($in['type']){
			case 'readOne':
				\View::$json['value'] = self::readOne($table,$in);
			break;
			case 'readMany':
				\View::$json['value'] = self::readMany($table,$in);
			break;
			case 'createOne':
				$insert = self::createOne($table,$in);
				\View::$json['value'] =  $insert['id'];
			break;
			case 'updateOne':
				\View::$json['value'] = (bool)self::updateOne($table,$in);
			break;
			case 'createMany':
				$ids = self::createMany($table,$in);
				\View::$json['value'] =  $ids;
			break;
			case 'updateMany':
				\View::$json['value'] = self::updateMany($table,$in);
			break;
			case 'deleteOne':
				\View::$json['value'] = self::delete($table,$in);
			break;
			case 'deleteMany':
				\View::$json['value'] = self::deleteMany($table,$in);
			break;
			
			default:
				\Control::error('No matched _type');
			break;
		}
		\View::endStdJson();
	}
	function deleteMany($scope,$options=[]){
		foreach($options['wheres'] as $k=>&$where){
			\Control::$additionalOptions['itemOffset'] = $k;
			$deleteOptions['where'] = &$where;
			$counts[] = self::delete($scope,$deleteOptions);
		}
		unset(\Control::$additionalOptions['itemOffset']);
		return $counts;
	}
	function createMany($scope,$options=[]){
		foreach($options['items'] as $k=>&$item){
			\Control::$additionalOptions['itemOffset'] = $k;
			$itemOptions['item'] = &$item;
			$insert = self::createOne($scope,$itemOptions);
			$ids[] = $insert['id'];
		}
		unset(\Control::$additionalOptions['itemOffset']);
		return $ids;
	}
	function updateMany($scope,$options=[]){
		$ups=[];
		foreach($options['items'] as $k=>$item){
			\Control::$additionalOptions['itemOffset'] = $k;
			$options['item'] = $item;
			$ups[] = (bool)self::updateOne($scope,$options);
		}
		unset(\Control::$additionalOptions['itemOffset']);
		return $ups;
	}
	function createOne($scope,$options=[]){ 
		$options['type'] = 'create';
		return self::upsert($scope,$options);	}
	function updateOne($scope,$options=[]){ 
		$options['type'] = 'update';
		return self::upsert($scope,$options);	}
	function upsert($scope,$options=[]){
		if($options['item']){
			$in = &$options['item'];
		}else{
			$in = &Control::$in;
		}
		//++ ensure present foreign keys match {
		if(\Control::$model[$scope]['links']['dc']){
			$linkColumns = self::prefixFields(array_keys(\Control::$model[$scope]['links']['dc']),$scope);
			$linkedColumns = array_intersect($linkColumns,array_keys($in));
			if($linkedColumns){
				foreach($linkedColumns as $linkedColumn){
					$dc = explode('.',$linkedColumn)[1];
					if($in[$linkedColumn]){
						$link = \Control::$model[$scope]['links']['dc'][$dc];
						$fcFullName = $link['ft'].'.'.$link['fc'];
						$itemExists = self::readOne($link['ft'],['select'=>'1','where'=>[$fcFullName=>$in[$linkedColumn]]]);
						if(!$itemExists){
							\Control::error('Referencing column points to nothing: {_FIELD_}: ',$linkedColumn);
							return false;
						}
					}else{
						$info = \Control::$model[$scope]['columns'][$dc];
						if(!$info['nullable']){
							if($info['default'] === null){
								\Control::error('Missing referencing column: {_FIELD_}',$linkedColumn);
								return false;
							}
						}else{
							unset($in[$linkedColumn]);
						}
					}
				}
			}
		}
		//++ }
		
		//++ allow resolution for up to one link deep {
		$possibleListedFields = self::possibleFields($scope,2);
		$listedFields = array_intersect(array_keys($in), $possibleListedFields);
		$possibleFields = self::possibleFields($scope,1);
		$fields = array_intersect($listedFields, $possibleFields);
		$referenceFields = array_diff($listedFields,$fields);
		if($referenceFields){
			foreach($referenceFields as $field){
				$parts = explode('.',$field);
				$link = \Control::$model[$scope]['links']['dc'][$parts[0]];
				$references[$parts[0]]['link'] = $link;
				$references[$parts[0]]['where'][$parts[1]] = $in[$field];
			}
			//since a reference may have multiple columns, need a separate loop
			foreach($references as $dc=>$reference){
				$rows = self::readMany($reference['link']['ft'],['where'=>$reference['where'],
					'select'=>'id',
					'page'=>0,
					'limit'=>2]);
				if(count($rows) > 1){
					\Control::error('Too many matched reference rows using: '.var_export($reference,1));
					return false;
				}elseif(!$rows){
					\Control::error('Reference has no matches: '.var_export($reference,1));
					return false;
				}else{
					$in[$dc]  = $rows[0]['id'];
					if(!in_array($dc,$fields)){
						$fields[] = $dc;
					}
				}
			}
		}
		//++ }
		
		//++ run 'upsert' validaters {
		if(\Control::$model[$scope]['validaters']['upsert']){
			if(!Control::validate(\Control::$model[$scope]['validaters']['upsert'],['in'=>&$in])){
				return false;
			}
		}
		
		//placed after custom rules since custom rules may format or formulate fields
		if(!self::upsertValidate($scope,$fields,$in)){
			return false;
		}
		//++ }
		
		//++ check for unique row fields in the input {
		foreach(\Control::$model[$scope]['keys'] as $key=>$keyFields){
			$notKey = false;
			foreach($keyFields as $field){
				$keyFieldList[] = $field;
				if(!isset($in[$field])){
					$notKey = true;
					break;
				}
			}
			if(!$notKey){
				$matchedKeys[] = $keyFields;
			}
		}
		
		//++ }
		//++ check matched unique rows against database {
		if($matchedKeys){
			foreach($matchedKeys as $key=>$fields){
				$match = \Db::row($scope,Arrays::extract($matchedKeys[0],$in));
				if($match){
					break;	}	}
			$lastMatchedKey = $lastMatchedKey = $key;
			if($match){
				if($options['subtype'] == 'ignore'){
					return true;
				}elseif($options['type'] == 'create' && !$options['subtype']){
					//create should not match any records
					Control::error('{_FIELD_} match(es) an existing record',$fields);
					return false;	}
				else {
					//prefix fields
					foreach($match as $k=>$v){
						$match[$k] = $v;
					}
					//if update make sure matched keys don't match other records besides the one being updated
					for($i = $lastMatchedKey; $i < count($matchedKeys); $i++){
						$fields = $matchedKeys[$i];
						foreach($fields as $field){
							if($match[$field] != $in[$field]){// note the != will  cause things like '0' != 0 to not error
								//a unique key is being changed, see if there is a collision
								if(\Db::row($scope,Arrays::extract($fields,$in),1)){
									Control::error('{_FIELD_} match(es) an existing record',$fields);
									return false;	}	}	}	}	}	}	}
		//++ }
		
		if(!$match && $options['type'] == 'update'){
			Control::error('No record found',$fields);
			return false;
		}
		
		$options['possibleFields'] = $possibleFields;
		$options['scope'] = $scope;
		
		if($match && !$options['subtype']){//update
			if(\Control::$model[$scope]['validaters']['update']){
				if(!Control::validate(\Control::$model[$scope]['validaters']['update'],['in'=>&$in])){
					return false;
				}
			}
			$options['matchedKeys'] = $matchedKeys;
			$options['lastMatchedKey'] = $lastMatchedKey;
			$options['in'] = &$in;
			
			return self::doUpdate($options);
		}else{//create
			if(\Control::$model[$scope]['validaters']['create']){
				if(!Control::validate(\Control::$model[$scope]['validaters']['create'],['in'=>&$in])){
					return false;
				}
			}
			if(!self::createValidate($scope,$in)){
				return false;
			}
			$options['in'] = &$in;
			return self::doCreate($options);
		}
	}
	
	static function descopeFields($scopedFields){
		foreach($scopedFields as $k=>$v){
			$fields[array_pop(explode('.',$k))] = $v;
		}
		return $fields;
	}
	///extract fields from input and do insert on table
	function doCreate($options){
		//filters may have added in some fields, re-get
		$options['select'] = array_intersect($options['possibleFields'],array_keys($options['in']));
		$upsert = self::descopeFields(Arrays::extract($options['select'],$options['in']));
		if(\Control::$model[$options['scope']]['columns']['created']){
			$upsert['created'] = new Time('now',$_ENV['timezone']);
		}
		
		if($options['createCallback']){
			return call_user_func_array($options['createCallback'],[&$upsert,&$options]);
		}
		if($options['subtype']){
			if($options['subtype'] == 'ignore'){
				//even though there is a return on ignore flag, the fields causing a collision can be set after that flag return
				return Db::insertIgnore($options['scope'],$upsert);
			}elseif($options['subtype'] == 'update'){
				return Db::insertUpdate($options['scope'],$upsert);
			}elseif($options['subtype'] == 'replace'){
				return Db::replace($options['scope'],$upsert);
			}
		}
		$options['id'] = \Db::insert($options['scope'],$upsert);
		return $options;
	}
	
	///extract fields from input and update based on matchedKeys
	function  doUpdate($options){
		//filters may have added in some fields, reget
		$options['select'] = array_intersect($options['possibleFields'],array_keys($options['in']));
		$upsert = self::descopeFields(Arrays::extract($options['select'],$options['in']));
		if(\Control::$model[$options['scope']]['columns']['updated']){
			$upsert['updated'] = new Time('now',$_ENV['timezone']);
		}
		if($options['updateCallback']){
			return call_user_func_array($options['updateCallback'],[&$upsert,&$options]);
		}
		Db::update($options['scope'],$upsert,Arrays::extract($options['matchedKeys'][$options['lastMatchedKey']],$options['in']));
		return $options;
	}
	
	///check each present field against general validation
	function upsertValidate($table,$fields,&$in){
		$validaters = [];
		foreach($fields as $field){
			$validaters[$field] = self::generalFieldValidaters($field,\Control::$model->field($field,$table));
		}
		return Control::validate($validaters,['in'=>&$in]);
	}
	///check for non null fields.  Make sure auto increment field is not set
	function createValidate($table,&$in){
		foreach(\Control::$model[$table]['columns'] as $field=>$info){
			if($info['autoIncrement']){
				$validaters[$field][] = 'f.remove';
			}elseif(!$info['nullable'] && $info['default'] === null){
				/**
				not nullable + default = null interpretted to mean field must be filled.  
					Consequently,	to allow for empty text fields, the column must be nullable, 
						and since (
							text columns can not have '' defaults, text fields, 
							and '' inputs are set to null on nullable fields
						) empty text fields should be handle as nulls
				*/
				//ignore special fields
				if(
					($field == 'created' || $field == 'updated') &&
					($info['type'] == 'datetime' || $info['type'] == 'date')
				){
					continue;
				}
				
				$validaters[$field][] = '!v.filled';
			}
		}
		if($validaters){
			return Control::validate($validaters,['in'=>&$in]);
		}else{
			return true;
		}
	}
	
	
	function generalFieldValidaters($fullName,$info){
		$field = explode('.',$fullName)[1];
		$validaters = [];
		
		//speical handling for 'created' and 'updated'
		if(
			($field == 'created' || $field == 'updated') &&
			($info['type'] == 'datetime' || $info['type'] == 'date')
		){
			return [];
		}
		
		$validaters[] = 'f.toString';
		if(!$info['nullable'] && !$info['autoIncrement']){
			if($info['default'] === null){
				//column must be present
				$validaters[] = '!v.exists';
			}else{
				//there's a default, when missing, set to default
				$validaters[] = ['f.unsetMissing'];
				$validaters[] = ['?!v.filled'];
			}
		}else{
			//for nullable columns, empty inputs (0 character strings) are null
			$validaters[] = array('f.toDefault',null);
			
			//column may not be present.  Only validate if present
			$validaters[] = '?!v.filled';
		}
		switch($info['type']){
			case 'datetime':
			case 'timestamp':
				$validaters[] = '!v.date';
				$validaters[] = 'f.datetime';
			break;
			case 'date':
				$validaters[] = '!v.date';
				$validaters[] = 'f.toDate';
			break;
			case 'text':
				if($info['limit']){
					$validaters[] = '!v.lengthRange|0;'.$info['limit'];
				}
			break;
			case 'int':
				if($info['limit'] == 1){//boolean value
					$validaters[] = 'f.toBool';
					$validaters[] = 'f.toInt';
				}else{
					$validaters[] = 'f.trim';
					$validaters[] = '!v.isInteger';
				}
			break;
			case 'decimal':
			case 'float':
				$validaters[] = 'f.trim';
				$validaters[] = '!v.isFloat';
			break;
		}
		
		return $validaters;
	}
	///returns fields prefixed with scope.  Useful since $fields can be a string (otherwise array_map would be used)
	static function prefixFields($fields,$scope){
		$fields = Arrays::toArray($fields);
		$prefixedFields = [];
		foreach($fields as $field){
			$prefixedFields[] = $scope.'.'.$field;
		}
		return $prefixedFields;
	}
	function quoteField($field){
		$parts = explode('.',$field);
		$name = array_pop($parts);
		return \Db::quoteIdentity(implode('.',$parts),false).'.'.\Db::quoteIdentity($name);
	}
	///get  the select part of sql, fully naming the fiels (so that "table.name" is perserved)
	function sqlSelect($fields){
		foreach($fields as $field){
			$selectPart[] = self::quoteField($field).' '.\Db::quote($field);
		}
		return implode(', ',$selectPart);
	}
	/// get the "select x,y,z from table x left join y"
	/**
	uses relative names and the model to determine joins
	primary scopes names are not prefixed
	linked names are scoped as follows:
		comany_id.name
	*/
	function select($table,$fields){
		$fields = Arrays::toArray($fields);
		
		$info = \Control::$model[$table];
		$linkedTables = [];
		
		$joins = [];
		foreach($fields as $field){
			$selectPart[] = self::quoteField($table.'.'.$field).' '.\Db::quote($field);
			$joins = array_merge($joins,self::makeJoins($field,$table));
		}
		
		$from = \Db::quoteIdentity($table);
		if($joins){
			$from .= "\n\tleft join ".implode("\n\tleft join ",$joins);
		}
		return 'select '.implode(', ',$selectPart)."\nfrom ".$from;
	}
	
	///create the "table on column = column" for joins
	function makeJoins($field,$current){
		$parts = explode('.',$field);
		array_pop($parts);
		if($parts && $parts[0] != $current){
			$usedParts = [$current];
			foreach($parts as $part){
				$link = \Control::$model[$current]['links']['dc'][$part];
				if(!$link){
					Debug::toss('Unknown unlinked field: '.$field);
				}elseif(!$linksUsed[$fieldPrefix]){
					$previousColumn = \Db::quoteIdentity(implode('.',$usedParts),false).'.'.\Db::quoteIdentity($part);
					
					
					$usedParts[] = $part;
					$newTableAlias = \Db::quoteIdentity(implode('.',$usedParts),false);
					$linkColumn = $newTableAlias.'.'.\Db::quoteIdentity($link['fc']);
					
					//table is aliased to the column prefix (the linking key)
					$joins[] = \Db::quoteIdentity($link['ft']).' '.$newTableAlias.' on '.$previousColumn.' = '.$linkColumn;
				}
			}
		}
		return (array)$joins;
	}
	///get all possible fields, including linked ones
	function possibleFields($scope,$maxDepth=5,$prefixes=[]){
		$lowerDepth = $maxDepth - 1;
		$columns = array_keys(\Control::$model[$scope]['columns']);
		$prefix = implode('.',$prefixes);
		if($prefix){
			foreach($columns as $column){
				$fields[] = $prefix.'.'.$column;
			}
		}else{
			$fields = $columns;
		}
		
		if($lowerDepth){
			$links = \Control::$model[$scope]['links']['dc'];
			if($links){
				foreach($links as $dc=>$link){
					$fields = array_merge($fields,self::possibleFields($link['ft'],$lowerDepth,array_merge($prefixes,[$dc])));
				}
			}
		}
		return $fields;
	}
	/**
	
	*/
	function delete($scope,$options=[]){
		if($options['possibleFields']){
			//incase possibleFields is passed in as a subset of actual possible
			$possibleFields = Arrays::toArray($options['possibleFields']);
			$actualPossibleFields = self::possibleFields($scope);
			$possibleFields = array_intersect($possibleFields,$actualPossibleFields);
		}else{
			$possibleFields = self::possibleFields($scope);
		}
		$selectFields = [];
		
		if($options['where']){
			$where = $options['where'];
			$whereSet = self::handleWhere($where, $possibleFields, $selectFields, $scope);
		}else{
			\Control::error('No item selected');
			return;
		}
		
		$joins = [];
		foreach($selectFields as $field){
			$joins = array_merge($joins,self::makeJoins($field,$scope));
		}
		$from = \Db::quoteIdentity($scope);
		if($joins){
			$sql = 'delete '.$from.' from '.$from."\n\tleft join ".implode("\n\tleft join ",$joins);
		}else{
			$sql = 'delete from '.$from;
		}
		
		$sql .= ' WHERE '.implode(' AND ',$whereSet);
		return \Db::query($sql)->rowCount();
	}
	/**
	@note, options[select] will be extended as needed for where class (to make joins)
	*/
	function read($scope,$options=[]){
		if($options['possibleFields']){
			//incase possibleFields is passed in as a subset of actual possible
			$possibleFields = Arrays::toArray($options['possibleFields']);
			$actualPossibleFields = self::possibleFields($scope);
			$possibleFields = array_intersect($possibleFields,$actualPossibleFields);
		}else{
			$possibleFields = self::possibleFields($scope);
		}
		if($options['select']){
			$selectFields = array_intersect($possibleFields,Arrays::toArray($options['select']));
		}else{
			$selectFields = self::possibleFields($scope,1);
		}
		
		if(!$selectFields){
			\Control::error('No fields for select');
			return;
		}
		
		if($options['where']){
			$where = $options['where'];
			$whereSet = self::handleWhere($where, $possibleFields, $selectFields, $scope);
		}
		
		$sql = self::select($scope,$selectFields);
		
		if($whereSet){
			$sql .= ' WHERE '.implode(' AND ',$whereSet);
		}
		
		return ['sql'=>$sql,'possibleFields'=>$possibleFields,'selectFields'=>$selectFields];
	}
	function readOne($scope,$options=[]){
		$read = self::read($scope,$options);
		if($read){
			return \Db::row($read['sql']);
		}
	}
	/*
	@param	options	{select:,sort:,where:,page:}
	*/
	function readMany($scope,$options=[]){
		$read = self::read($scope,$options);
		if($read){
			$sql = $read['sql'];
			
			if($options['sort']){
				$sort = \view\SortPage::sort($read['possibleFields'],$options['sort'])['sql'];
				$sql .= $sort;
			}
			
			if(isset($options['page'])){
				return \view\SortPage::page($sql,$options);
			}else{
				return \Db::rows($sql);
			}
		}
	}
	function handleWhere($where,$possibleFields,&$selectFields,$scope){
		$whereSet = [];
		if(is_array($where)){
			foreach($where as $k=>$v){
				$whereSet[] = self::handleKeyValue($k,$v);
			}
		}else{
			$whereSet[] = ['id','=',\Db::quote($where)];
		}
		
		$usedWhereSet = [];
		foreach($whereSet as $set){
			if(!in_array($set[1],['=','>','<','<>','<=','>='])){
				continue;
			}
			if(!in_array($set[0],$possibleFields)){
				continue;
			}
			///if the where uses the column must be in selectFields so join happens
			if(!in_array($set[0],$selectFields)){
				$selectFields[] = $set[0];
			}
			if($set[2] === 'null'){
				if($set[1] === '='){
					$set[1] = ' is ';
				}else{
					$set[1] = ' is not ';
				}
			}
			
			$usedWhereSet[] = self::quoteField($scope.'.'.$set[0]).' '.$set[1].' '.$set[2];
		}
		return $usedWhereSet;
		
	}
	function handleKeyValue($field,$value){
		if(is_array($value)){
			$value = '('.implode(',',$value).')';//it's unlikely there is unintentionnal overlap causing an array, so treat this as an 'in' statement
		}
		if(strpos($field,'?')!==false){
			preg_match('@(^[^?]+)\?([^?]+)$@',$field,$match);
			$field = $match[1];
			$equater = $match[2];
		}else{
			$equater = '=';
		}
		if($field[0]==':'){
			$field = substr($field,1);
			if($value == 'null' || $value === null){
				$equater = '=';
				$value = 'null';
			}elseif(($equater == 'in' || $equater == 'not in') && preg_match('@^\(([0-9]{1,30},?){1,30}\)$@',$value)){
				$value = \Db::quote($value);
			}
		}elseif($value === null){
			$value = 'null';
		}else{
			$value = \Db::quote($value);
		}
		return [$field,$equater,$value];
	}
}