<?php
/**
 * IotaDb 
 * @author simpx(simpxx@gmail.com)
 */
class IotaDb {
	private $config;
	private $db;
	private $query;
	private $result = array();
	
	private $select = '*';
	private $table = '';
	private $join = '';
	private $data = array();
	
//以下自动包含limit等关键字	
	private $where = '';
	private $limit = '';
	private $orderby = '';
	
	public function __construct($config = array()){
		if(!empty($config)){
			$this->config = $config;	
		}
	}
	private function getDb(){
		if(!$this->db){
			$this->db = mysql_connect($this->config['HOST'], $this->config['USER'], $this->config['PSW']);	
			mysql_select_db($this->config['DATABASE'], $this->db);
			mysql_set_charset('utf8',$this->db);
		}
		return $this->db;
	}
	private function excute($sql){
		$this->data = array();
		$this->limit = '';
		$this->orderby = '';
		$this->where = '';
		$logger = IotaLog::getLogger('IotaDb','excute');
		if (DEBUG) $logger->log(Level_FINE, $sql);
		return $this->query($sql);	
	}
	public function query($sql){
		$logger = IotaLog::getLogger('IotaDb','query');
		if($this->query = mysql_query($sql, $this->getDb())){
			return $this->query;
		}	
		else{
			if (DEBUG) $logger->log(Level_ERROR, $sql);
			throw new IotaException(mysql_error($this->getDb()),$sql,IE_DB_ERROR);	
		}
	}
	public function result(){
		$this->result = mysql_fetch_array($this->query, MYSQL_ASSOC);
		return $this->result;
	}
	public function results(){
		$this->result = array();
		while($result = mysql_fetch_array($this->query, MYSQL_ASSOC)){
			$this->result[] = $result;
		}
		return $this->result;
	}
	
	public function select($column=''){
		if($column != '')
			$this->select = $column;	
		else{
			$this->select = '*';
		}
		return $this;
	}
	public function whereIn($column, $array){
		//TODO	
	}
	public function groupBy($column){
		//TODO	
	}
	public function from($table){
		$this->setTable($table);
		return $this;
	}
	private function setTable($table){
		if($table){
			$this->table = '`'.$table.'`';
		}
	}
	public function join($table, $condition, $type = ''){
		//TODO
	}
	public function limit($offset = 0, $limit = ''){
		if($limit){
			$this->limit = ' LIMIT '.$offset . ', ' . $limit; 
		}
		else if($offset){
			$this->limit = ' LIMIT '.$offset;
		}
		return $this;
	}
	public function orderBy($column, $order){
		if($this->orderby){
			$this->orderby .= ', '.$column.' '.$order;
		}	
		else{
			$this->orderby = ' ORDER BY '.$column.' '.$order;
		}
		return $this;
	}
	public function get($table = '', $offset = 0, $limit = ''){
		$this->setTable($table);
		$this->limit($offset,$limit);
		return $this->doSelect($this->select, $this->table, $this->where, $this->limit, $this->orderby);
	}
	private function doSelect($select,$table,$where,$limit,$orderby){
		$this->sql = 'SELECT '.$select.' FROM '.$table.$where.$orderby.$limit;
		return $this->excute($this->sql);
	}
	public function where($column,$value = ''){
		if(is_array($column)){
			$this->whereArray($column, 'AND');	
		}
		else{
			$this->whereSingle($column, $value, 'AND');	
		}
		return $this;
	}
	public function orWhere($column,$value = ''){
		if(is_array($column)){
			$this->whereArray($column, 'OR');	
		}
		else if($value != ''){
			$this->whereSingle($column, $value, 'OR');	
		}
		return $this;	
	}
	private function whereSingle($column,$value,$link){
		if(empty($this->where)){
			$link = '';
			$this->where = ' WHERE ';
		}
		else
			$link = ' ' . $link . ' ';
		if(strpos($column, '=') === false &&
		   strpos($column, '<') === false &&
		   strpos($column, '>') === false){
		   	$condition = $column . " = '" . $value . "'";
		}
		else{
			$condition = $column . "'" . $value . "'";	
		}
		$this->where = $this->where . $link . $condition; 
	}
	private function whereArray($array,$link){
		foreach($array as $column => $value){
			$this->whereSingle($column, $value, $link);	
		}	
	}
	public function set($column, $value){
		if($column != ''){
			$this->data[$column] = $value;	
		}
		return $this;
	}
	public function insert($table, $data = array()){
		$this->data = array_merge($data,$this->data);
		$this->setTable($table);
		$this->doInsert($this->table,$this->data);
		return $this;
	}
	public function insertId(){
		return mysql_insert_id();
	}
	private function doInsert($table, $data){
		$key = implode(',', array_keys($data));
		$value = implode("','", array_values($data));
		$this->sql = 'INSERT INTO '.$table.' ( '.$key.' ) VALUES ( \''.$value.'\' ) ';		
		return $this->excute($this->sql);	
	}
	public function update($table, $data = array(), $condition = array()){
		$this->data = array_merge($data,$this->data);
		$this->setTable($table);
		$this->where($condition);
		$this->doUpdate($this->table, $this->data, $this->where);
		return $this;
	}
	private function doUpdate($table, $data, $where){
		if(!empty($data)){
			$sql = '';
			foreach ($data as $column => $value){
				$sql .= $column. " = '".$value."',"; 	
			}	
			$sql = substr($sql, 0, -1);
			$this->sql = 'UPDATE '.$table.' SET '.$sql.$where;
			return $this->excute($this->sql);	
		}	
	}
	public function delete($table, $condition = array()){
		$this->setTable($table);	
		$this->where($condition);
		$this->doDelete($this->table, $this->where);
		return $this;
	}
	private function doDelete($table, $where){
		$this->sql = 'DELETE FROM '.$table.$where;		
		return $this->excute($this->sql);
	}
	public function getSql(){
		return $this->sql;
	}
	
}