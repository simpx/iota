<?php
/**
 * IotaLog - poor programmer version
 * @author simpx (simpxx@gmail.com)
 */

define('Level_FINE',1);
define('Level_WARNING',10);
define('Level_ERROR',100);

interface IotaLogInterface {
	public static function getLogger($className,$methodName);
	public static function displayLogs();
	public static function getloggers();
	public function log($level, $msg, $extra='');
}
class IotaLog implements IotaLogInterface {

	private static $loggers = array();
	
	public $className = '';
	public $methodName = '';
	public $logs = array();
	private $levelStrings = array(
								1   => 'Level_FINE',
								10  => 'Level_WARNING',
								100 => 'Level_ERROR',
							);
	
	public function __construct($className,$methodName){
		$this->className = $className;
		$this->methodName = $methodName;
	}
	public static function getLogger($className,$methodName){
		self::$loggers[] = new IotaLog($className,$methodName);
		return end(self::$loggers);
	}
	public static function getloggers(){
		return self::$loggers;	
	}
	public static function displayLogs(){
		foreach (self::$loggers as $logger){
			echo $logger->className;
			echo $logger->methodName;
			var_dump($logger->logs);
		}		
	}
	public function log($level, $log, $extra =''){
		$this->logs[$extra] = array('level'=>$level, 
							  'log'=> $log,
							  );	
	}
	private function getLevelString($level){
		return $this->levelStrings[$level];	
	}
}