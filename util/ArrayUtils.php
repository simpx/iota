<?php
/**
 * ArrayUtils
 * @author simpx(simpxx@gmail.com)
 * @method merge
 */
class ArrayUtils {
	public static function merge($array1, $array2){
		$array1 = is_array($array1)? $array1 : array();
		$array2 = is_array($array2)? $array2 : array();
		return array_merge($array1, $array2);	
	}
}