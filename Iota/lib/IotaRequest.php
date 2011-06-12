<?php
/**
 * IotaRequest
 * @method get post data
 * @throws IotaException
 * @author simpx(simpxx@gmail.com)
 *
 */
interface IotaRequestInterface {
	public static function get($value = '');
	public static function post($value = '');
	public static function data($value = '');
}
class IotaRequest implements IotaRequestInterface{
	public static function get($value = '', $pattern = '', $default = null){
		$get = self::getCheckFromArry($_GET, $value, $pattern, $default);
		if(DEBUG){
			$logger = IotaLog::getLogger('IotaRequest', 'get');
			$log = array('value'=>$value,
						'pattern'=>$pattern,
						'default'=>$default,
						'result'=>$get);
			$logger->log(Level_FINE, $log);
		}
		return $get;
	}
	public static function post($value = '', $pattern = '', $default = null){
		$post = self::getCheckFromArry($_POST, $value, $pattern, $default);
		if(DEBUG){
			$logger = IotaLog::getLogger('IotaRequest', 'post');
			$log = array('value'=>$value,
						'pattern'=>$pattern,
						'default'=>$default,
						'result'=>$post);
			$logger->log(Level_FINE, $log);
		}
		return $post;
	}
	public static function data($value = '', $pattern = '', $default = null){
		$logger = IotaLog::getLogger('IotaRequest', 'data');
		if($value == ''){
			$data = ArrayUtils::merge(self::get(), self::post());	
		}	
		else{
			$data = is_null(self::get($value, $pattern, $default))? 
				self::post($value, $pattern, $default): self::get($value, $pattern, $default);
		}
		if(DEBUG){
			$logger = IotaLog::getLogger('IotaRequest', 'data');
			$log = array('value'=>$value,
						'pattern'=>$pattern,
						'default'=>$default,
						'result'=>$data);
			$logger->log(Level_FINE, $log);
		}
		return $data;
	}
	private static function getCheckFromArry($array, $value = '', $pattern = '', $default = null){
		if($value == ''){
			$result =  $array;
		}	
		else{
			switch (true) {
				case !isset($array[$value]):
					$result = $default;
					break;
				case empty($pattern):
				case preg_match($pattern, $array[$value]):
					$result = $array[$value];
					break;
				default:
					if($default === null){
						throw new IotaException($value.'is valid',$value,IE_USER_ERROR);
					}
					else{
						$result = $default;
					}
					break;
			}
		}
		return $result;
	}
}