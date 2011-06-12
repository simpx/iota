<?php
class debugToolbar {
	public static function display($loggers){
		foreach ($loggers as $logger){
			echo "\n--------------------------\n";
			echo 'className:'.$logger->className."\n";
			echo 'methodName:'.$logger->methodName."()\n";
			print_r($logger->logs);
		}	
	}			
}