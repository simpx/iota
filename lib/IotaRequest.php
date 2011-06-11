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
		return self::getCheckFromArry($_GET, $value, $pattern, $default);
	}	
	public static function post($value = '', $pattern = '', $default = null){
		return self::getCheckFromArry($_POST, $value, $pattern, $default);
	}
	public static function data($value = '', $pattern = '', $default = null){
		if($value == ''){
			return ArrayUtils::merge(self::get(), self::post());	
		}	
		else{
			return is_null(self::get($value, $pattern, $default))? 
				self::post($value, $pattern, $default): self::get($value, $pattern, $default);
		}
	}
	private static function getCheckFromArry($array, $value = '', $pattern = '', $default = null){
		if($value == ''){
			return $array;
		}	
		else{
			switch (true) {
				case !isset($array[$value]):
					return $default;
					break;
				case empty($pattern):
				case preg_match($pattern, $array[$value]):
					return $array[$value];
					break;
				default:
					if($default === null){
						throw new IotaException($value.'is valid',$value,IE_USER_ERROR);
					}
					else{
						return $default;
					}
					break;
			}
		}
	}
}