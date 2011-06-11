<?php
/**
 * IotaException
 * @define IE_IOTA_ERROR 1 
 * @define IE_APP_ERROR 10
 * @define IE_USER_ERROR 100
 * @define IE_DB_ERROR 1000
 */
define(IE_IOTA_ERROR,1);
define(IE_APP_ERROR,10);
define(IE_USER_ERROR,100);
define(IE_DB_ERROR,1000);
class IotaException extends Exception {
	protected $extra;
	protected $level;
	
	public function __construct($message = null, $extra = null, $level = null){
		parent::__construct($message);
		$this->extra = $extra;
		$this->level = $level;
	}
	public function	getExtra(){
		return $this->extra;	
	}
	public function getLevel(){
		return $this->level;
	}
}
