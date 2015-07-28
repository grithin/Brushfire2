<?
/**
Using framework standard db related naming, this model can determine relations.  And, with these links, can use relation based queries.

In using structured data, it is often the case that redundacy is desirable to serve the particulars of consumption.  For instance, functions that handle
	the table as a whole will operate on the links, which is a key on the table as $model->['table']['links'].  It would be suboptimal for these functions to have to look into each of the $model[table][columns] keys to find the links.
	But, for something like a form input line maker, the function cares only about the field it is currently dealing with.  And, this function cares if the field links to another table, but that fact lies outside the field structure,
	in the 'links' structure.  Does the remedy involve putting the key into th field structure or having the function fiddle with the link structure along with the field structure?

	The general pricinple is you want to remove as many non-apparent dependcies from a function as possible.  So, the input line function should not rely on handling the model, but instead rely on what is passed in.  This is simiiar to
	the problem of adding in searched linked fields into a table for searches despire the data being redundant.  The general rule here, when redundancy is convenient and does not interefere with data integrity, go for it.


Accronyms
	dt: domestic table
	dc: domestic column
	ft: foreign table
	fc: foreign column


$this->model = [
	'columns' => (see Db::tableInfo()['columns']),
	'keys' => (see Db::tableInfo()['keys']),
	'links' => [
		column => [ft=>foreignTable, fc=> foriegnColumn],...	]	]
		ft.fc => dc
*/
trait ModelTrait{
	public $model = [];
	/**
	@param	options	[
		forceRemake: true|false (whether to remake model)
		db: Db object,
		modelName: name]
	*/
	function  _construct($options=[]){
		$option['modelName'] = $option['modelName'] ? $option['modelName'] : $_ENV['projectName'];
		$this->savePath = $_ENV['storageFolder'].'models/'.$option['modelName'].'.model.php';
		$this->db = $options['db'] ? $options['db'] : Db::primary();

		if(!$options['forceRemake'] && is_file($this->savePath)){
			require($this->savePath);
			$this->model = $model;
		}else{
			$this->makeModel();
			//loading a model from cache would require decoding it and then evaling it.  Probably better just to load file.
			$file = "<?\n".'$model = '.var_export($this->model,1).';';
			Files::write($this->savePath,$file);
		}
		parent::__construct($this->model);//parent being the ArrayObject
	}
	function __get($key){
		return $this->model[$key];
	}
	function __toString(){
		return 'Model Save Path: '.$this->savePath;
	}

	/**
	return the model for public consuption.  This returns the full model, but you may just want to return only the linked tables;
	@note	override this method with access restrictions on model info, otherwise whole model is available
	*/
	///
	function getPublic($tables){
		return $this->model;
	}
	function makeModel(){
		if(is_file($this->savePath)){
			unlink($this->savePath);
		}
		$tables = $this->db->tables();
		foreach($tables as $table){
			$this->modelTable($table);
		}

		//get special relations (table_id => table with name column)
		foreach($this->model as &$table){
			foreach($table['links']['dc'] as $column => $link){
				if($link['fc'] == 'id'){
					//check to see of linked table has name column and it is unique
					if($this->model[$link['ft']]['columns']['name'] && $this->model[$link['ft']]['keys']['name']){
						$table['specialLinks'][$column] = $link;
						$table['columns'][$column]['nameLink'] = $link;
					}
				}
			}
		}

		$this->enhance();
	}
	///it columns, keys (unique), and links (to other tables)
	function modelTable($table){
		$mTable = &$this->model[$table];

		$tableInfo = $this->db->tableInfo($table);
		$mTable['columns'] = $tableInfo['columns'];
		$mTable['keys'] = $tableInfo['keys'];
		$mTable['links'] = ['dc'=>[],'fc'=>[]];

		foreach($mTable['columns'] as $column=>$info){
			if($link = self::parseColumn($table,$column)){
				$mTable['links']['dc'][$column] = ['ft'=>$link[0],'fc'=>$link[1]];
				$mTable['links']['fc'][implode('.',$link)] = $column;
				//duplicate data while moving over to new structure
			}
		}
	}
	///extracts a linkage from a column name
	/*
	@return	[foreign table, foriegn column]
	**/
	function parseColumn($table,$column){
		//first, check for fc_ indicater
		if(preg_match('@^fc_@',$column)){
			list($fTable,$fColumn,$commend) = explode('__',substr($column,3));
			return [$fTable,$fColumn];
		}elseif($column == 'type_id'){
			return [$table.'_type',$column];
		}else{
			//++ Id Column referencing {
			if($column[0] != '_' && preg_match('@(.+)_id($|__)@',$column,$match)){//named column
				return [$match[1],'id'];
			}elseif(preg_match('@^(_+)id($|__)@',$column,$match)){//backwards relative id
				return [self::getAbsolute($table,$match[1]),'id'];
			}
			//++ }
		}
	}
	///switch out the "_" for "/" in the table name, prefixing with one "/"
	static function tablePath($table){
		if($table[0] == '/'){
			return $table;
		}
		return '/'.str_replace('_','/',$table).'/';
	}

	static function getAbsolute($table,$dotdot){
		$path = self::tablePath($table);
		$path .= str_replace('_','../',$dotdot);
		return str_replace('/','_',substr(Tool::absolutePath($path),1,-1));
	}


	//++ generally useful functions {
	function __toArray(){
		return $this->model;
	}
	///get field array from name
	function field($name,$table=null){
		$parts = explode('.',$name);
		if(count($parts) > 1){
			return $this->model[$parts[0]]['columns'][$parts[1]];
		}elseif($table){
			return $this->model[$table]['columns'][$name];
		}
	}

	///get fully qualified field names
	function fieldFullnames($table){
		$columns = [];
		foreach($this->model[$table]['columns'] as $k=>$v){
			$columns[] = $table.'.'.$k;
		}
		return $columns;
	}
	///get a list of unique keys that the "fields" would match
	/**
	@param	fields	{<field>:<value|t:scalar>,...}
	*/
	function matchedKeys($table,$fields){
		foreach(\Control::$model[$table]['keys'] as $key=>$keyFields){
			$notKey = false;
			foreach($keyFields as $field){
				$keyFieldList[] = $field;
				if(!isset($fields[$field])){
					$notKey = true;
					break;
				}
			}
			if(!$notKey){
				$matchedKeys[] = $keyFields;
			}
		}
		return $matchedKeys;
	}
	///get a list of the key fields from matchedKeys
	/**
	@return	[<field>,...]
	*/
	function flatMatchedKeys($table,$fields){
		$matchedKeys = $this->matchedKeys($table,$fields);
		$keys = [];
		if($matchedKeys){
			foreach($matchedKeys as $match){
				foreach($match as $key){
					$keys[$key] = 1;
				}
			}
		}
		return array_keys($keys);
	}
	///do inserts and updates, and delete any that no longer exist
	function syncItems($table,$items,$where=[]){
		Db::startTransaction();
		//collect ids to exclude from delete
		$ids = [];

		foreach((array)$items as $item){
			$keys = Control::$model->flatMatchedKeys($table,$item);
			$itemWhere = array_merge($where,Arrays::extract($keys,$in));
			$id = \Db::row($table,$itemWhere,'id');
			if($id){
				$ids[] = $id;
				\Db::update($table,$item,$itemWhere);
			}else{
				$id = \Db::insert($table,array_merge($where,$item));
				$ids[] = $id;
			}
		}
		$where['"'] = 'id not in ('.implode(',',$ids).')';
		\Db::query('delete',$where);

		Db::commitTransaction();
		\Hook::run('userAction','interest update');
	}
	//++ }
}