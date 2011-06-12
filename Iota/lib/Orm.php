<?php
/**
 * 
 * Iota-ORM v0.1
 * @author simpx
 * @todo 1.单一函数操作数据库 2.更抽象化
 * @todo 1.单次save 2.where('meta.gender','male')
 *
 */
abstract class Orm {
	//InnoDB
	protected $dbConfig = array();
	protected $primaryKey = 'id';
	protected $table = '';
	
	protected $select = '*';
	protected $hasOne = array();
	protected $hasMany = array();
	protected $belongsTo = array();
	protected $manyToMany = array();

	private $db = null;
	private $id = '';
	private $calledFunction = '';
	private $columns = array();
	private $modifiedColumns = array();
	private $modifiedEntities = array('hasOne'=>array(),
									  'belongsTo'=>array(),
									  'manyToMany'=>array(),
									  'hasMany' =>array());
	private $isSelected = false;
	private $selectEntity = array();
	private $found = false;
	
	
	public function __construct($id=''){
		if($id != ''){
			$this->find($id);
		}
		if($this->table == ''){
			$this->table = strtolower(get_class($this));	
		}
	}
	public function __get($property){
		if($this->isRelation($property)){
			if(!$this->isHasEntity($property)){
				$this->modifiedEntities[$property] = $this->$property;
				$this->columns[$property] = $this->getEntityByRelationship($property);
			}
		}
		else{
			if(!$this->isSelected){
				$this->selectOne();
			}
		}
		return $this->columns[$property];	
	}	
	private function getEntityByRelationship($property){
		$relation = $this->$property;
		switch ($relation[0]){
			case 'hasOne':
				return $this->hasOne($this->$property);
				break;
			case 'hasMany':
				return $this->hasMany($this->$property);
				break;
			case 'belongsTo':
				return $this->belongsTo($this->$property);
				break;
			case 'manyToMany':
				return $this->manyToMany($this->$property);
				break;
		}
	}
	private function isHasEntity($property){
		//maybe !isset($this->selectEntity[$property]) OR $this->selectEntity[$property] != true
		if(isset($this->columns[$property]) && 
		(is_object($this->columns[$property]) OR is_array($this->columns[$property]))    ){
			return true;	
		}
		else{
			return false;
		}
	}
	private function isRelation($property){
		if(isset($this->$property) && is_array($this->$property)){
			return true;
		}
		else
			return false;
	}

	private function getRelationTable($table1,$table2){
		$table1 = strtolower($table1);
		$table2 = strtolower($table2);	
		if(strcmp($table1,$table2)<0){
			return $table1.'_'.$table2;	
		}
		else{
			return $table2.'_'.$table1;
		}
	}
	//pri前缀为当前的 for前缀为foreign
	private function parseRelation($relationMeta){
		//protected $author = array('belongsTo', 'User',	'user_id');
		
		//protected $metaaa = array('hasOne',	 'Usermeta','user_id');
		//protected $commen = array('hasMany',	 'Comment' ,'blog_id');	
		
		//protected $friend = array('manyToMany','User',	'friends','user_master_id','user_slave_id');
		$relation = $relationMeta[0];	
		$model = $relationMeta[1];
		switch ($relation){
			case 'belongsTo':
				$forKey = isset($relationMeta[2])? $relationMeta[2] : strtolower($relationMeta[1]).'_id';
				$table = $this->table;
				$priKey = $this->primaryKey;
				break;
			case 'hasOne':
			case 'hasMany':
				$forKey = isset($relationMeta[2])? $relationMeta[2] : $this->table.'_id';
				$entity = new $model();
				$table = $entity->_getConfig('table');
				$priKey = $entity->_getConfig('primaryKey');
				break;
			case 'manyToMany':
				$entity = new $model();
				$table = isset($relationMeta[2])? $relationMeta[2] : $this->getRelationTable($this->table, $entity->_getConfig('table'));
				$priKey = isset($relationMeta[3])? $relationMeta[3] : $this->table.'_id';
				$forKey = isset($relationMeta[4])? $relationMeta[4] : $entity->_getConfig('table').'_id';
				if($priKey == $forKey) $forKey = 'another_'.$forKey;
				break;	
		}
		$result['forKey'] = $forKey;
		$result['priKey'] = $priKey;
		$result['table'] = $table;
		$result['relation'] = $relation;
		$result['model'] = $model;
		return $result;
	}
	private function manyToMany($array){
		//protected $friends = array('manyToMany','User','friends','user_master_id','user_slave_id');
		$result = $this->parseRelation($array);
		$model = $result['model'];
		$table = $result['table'];
		$priKey = $result['priKey'];
		$forKey = $result['forKey'];
		$this->_db()->select($forKey)->from($table)->where($priKey,$this->id)->get();
		$results = $this->_db()->results();
		$entities = array();
		foreach ($results as $k => $v){
			$entities[] = new $model($v[$forKey]);
		}
		return $entities;
	}
	private function hasMany($array){
		//protected $comments = array('hasMany','Comment');	
		$result = $this->parseRelation($array);
		$model = $result['model'];
		$forKey = $result['forKey'];
		$table = $result['table'];
		$priKey = $result['priKey'];
		$this->_db()->select($priKey)->from($table)->where($forKey,$this->id)->get();
		$results = $this->_db()->results();
		$entities = array();
		foreach($results as $k => $v){
			$entities[] = new $model($v[$priKey]);	
		}
		return $entities;
	}
	private function hasOne($array){
		//protected $meta = array('hasOne','Usermeta','user_id');
		$result = $this->parseRelation($array);
		$model = $result['model'];
		$forKey = $result['forKey'];
		$table = $result['table'];
		$priKey = $result['priKey'];
		$this->_db()->select($priKey)->from($table)->where($forKey,$this->id)->limit(1)->get();
		$result = $this->_db()->result();
		$priId = $result[$priKey];
		$entity = new $model($priId);
		return $entity;
	}	
	private function belongsTo($array){
		//protected $author = array('belongsTo','User','user_id');
		$result = $this->parseRelation($array);
		$model = $result['model'];
		$forKey = $result['forKey'];
		if(!$this->isSelected)$this->selectOne();
		$forId = $this->columns[$forKey];
		$entity = new $model($forId);
		return $entity;
	}
	public function _getConfig($item){
		if(isset($this->$item)){
			return $this->$item;
		}
		else{
			throw new IotaException('oh my god,it could not be!','orm',IE_IOTA_ERROR);
		}
	}	
	private function selectOne(){
		$this->_db()->select($this->select)->limit(1)->where($this->primaryKey,$this->id)->get($this->table);
		$this->columns = $this->_db()->result();		
		$this->isSelected = true;
		$this->found = !empty($this->columns);
	}
	public function __set($property, $value){
		if(is_object($value)){
			$this->saveModified($property, $value);
		}
		else{
			$this->modifiedColumns[$property] = $value;
		}
		$this->columns[$property] = $value;	
	}
	private function saveModified($property, $value){
		$meta = $this->$property;
		if(empty($meta))
			throw new IotaException("$property not found ",'propertyNotFound',IE_APP_ERROR);
		$relation = $meta[0];
		$this->modifiedEntities[$relation][$property] = $value;  
	}
	public function found(){
		if(!$this->isSelected){	
			$this->selectOne();
		}
		return $this->found;
	}
	public function findEntityByCondition($condition){
		$this->_db()->select($this->select)->limit(1)->where($condition)->get($this->table);
		$this->columns = $this->_db()->result();
		$this->id = $this->columns[$this->primaryKey];
		$this->isSelected = true;
		$this->found = !empty($this->columns);
		return $this;	
	}
	public static function findAll($condition=array(),$offset='',$limit='',$orderBy=''){
		$modelName = get_called_class();	
		$model = new $modelName();
		$primaryKey = $model->_getConfig('primaryKey');
		$model->_db()->select($primaryKey)->limit($offset,$limit)->where($condition)->get($model->_getConfig('table'));
		$results = $model->_db()->results();
		foreach ($results as $result){
			$entities[] = new $modelName($result[$primaryKey]); 	
		}
		return $entities;
	}
	public static function __callstatic($function,$params){
		if(substr($function,0,6) == 'findBy'){
			$function = substr($function, 6);
			$columns = explode('AND',$function);
			$modelName = get_called_class();		
			$model = new $modelName();
			$where = array();	
			$i = 0;
			foreach ($columns as $column){
				$column = strtolower($column);
				$where[$column] = $params[$i];
				$i++;
			}
			return $model->findEntityByCondition($where);
		}
	}
	public function getOne($key='',$value=''){
		if($value == ''){
			$this->_db()->where($key);
		}
		else{
			$this->_db()->where($key,$value);
		}
		$this->_db()->limit(1);
		$entities =  $this->getEntityByRelationship($this->calledFunction);
		return $entities[0];
	}
	public function __call($function,$params){
		switch(true){
			case preg_match('/^findBy/', $function):
				$condition = substr($function,6); 
				$columns = explode('AND',$function);
				$where = array();$i = 0;
				foreach ($columns as $column){
					$column = strtolower($column);
					$where[$column] = $params[$i];
					$i++;
				}	
				return $this->findEntityByCondition($where);
				break;
			case $function == 'orderBy' :
				$this->_db()->orderBy($params[0]);
				return $this;
				break;		
			case $function == 'where' :
				if(count($params) == 2){
					$this->_db()->where($params[0],$params[1]);
				}
				else{
					$this->_db()->where($params[0]);
				}
				return $this;
				break;
			case $function == 'get':
				if(count($params)>0){
					if(isset($params[1]) && is_numeric($params[1])){
						$this->_db()->limit($params[0],$params[1]);	
					}
					else{
						$this->_db()->limit($params[0]);
					}
				}
				return $this->getEntityByRelationship($this->calledFunction);
				break;
			default:
				if(!isset($this->$function))
					throw new IotaException($function.'is not exists','fuctionNotFound',IE_APP_ERROR);
				if(count($params)>0){
					//违背原则，不应该知道那个函数内部机制来调用
					if(isset($params[1]) && is_numeric($params[1])){
						$this->_db()->limit($params[0],$params[1]);	
					}
					else{
						$this->_db()->limit($params[0]);
					}
					return $this->getEntityByRelationship($function);
				}		
				else{
					$this->calledFunction = $function;
					return $this;
				}
		}
	}
	public function toArray(){
		return $this->columns;	
	}
	public function _db(){
		if(empty($this->db)){
			$dbConfig = ArrayUtils::merge($this->dbConfig, Iota::$config['dbConfig']);
			$this->db = new IotaDb($dbConfig);
		}
		return $this->db;
	}
	public function find($id){
		$this->isSelected = false;
		$this->id = $id;
		return $this;
	}
	private function isModified(){
		if(empty($this->modifiedColumns) && !$this->isModifiedEntities()){
			return false;
		}	
		else{
			return true;
		}
	}
	private function setModified(){
		$this->modifiedColumns = array();	
		$this->modifiedEntities = array();
	}
	public function save(){
		if($this->isModified()){
			if($this->id == ''){
				$this->_db()->insert($this->table,$this->modifiedColumns);
				$this->id = $this->_db()->insertId();
				$this->modifiedColumns = array();	
			}
			if($this->isModifiedEntities()){
				if($this->isModifiedEntities('belongsTo')){
					foreach ($this->modifiedEntities['belongsTo'] as $key => $entity){
						$relation = $this->parseRelation($this->$key);
						$entity->save();
						$id = $entity->id;		
						$this->modifiedColumns[$relation['forKey']] = $id;
					}
				}
				if($this->isModifiedEntities('hasOne')){
					foreach ($this->modifiedEntities['hasOne'] as $key => $entity){
						$relation = $this->parseRelation($this->$key);
						$entity->save();
						$id = $entity->id;
						$this->_db()->where($relation['priKey'],$id)->update($relation['table'],array($relation['forKey']=>$this->id));
					}	
				}
				if($this->isModifiedEntities('hasMany')){
					foreach ($this->modifiedEntities['hasMany'] as $key => $entity){
						$relation = $this->parseRelation($this->$key);
						$entity->save();
						$id = $entity->id;
						$this->_db()->where($relation['priKey'],$id)->update($relation['table'],array($relation['forKey']=>$this->id));
					}
				}
				if($this->isModifiedEntities('manyToMany')){
					foreach ($this->modifiedEntities['manyToMany'] as $key => $entity){
						$relation = $this->parseRelation($this->$key);
						$entity->save();	
						$id = $entity->id;
						$columns = array($relation['priKey']=>$this->id,$relation['forKey']=>$id);
						$this->_db()->where($columns)->limit(1)->get($relation['table']);
						if(empty($this->_db()->result)){
							$this->_db()->insert($relation['table'],$columns);
						}
					}
				}	
			}
			if(!empty($this->modifiedColumns)){
				//当前对象需要修改
				$this->_db()->where($this->primaryKey,$this->id)->update($this->table,$this->modifiedColumns);
				$this->setModified();
				return true;
			}				
			
		}
	}
	private function isModifiedEntities($relation = ''){
		if($relation == ''){
			if(empty($this->modifiedEntities['belongsTo']) &&
				empty($this->modifiedEntities['hasOne']) &&
				empty($this->modifiedEntities['hasMany']) &&
				empty($this->modifiedEntities['manyToMany']) ){
				return false;
			}
			else{
				return true;
			}
		}
		else{
			return !empty($this->modifiedEntities[$relation]);	
		}
	}
	public function delete(){
		$this->_db()->where($this->primaryKey,$this->id)->delete($this->table);
		return true;
	}
}
