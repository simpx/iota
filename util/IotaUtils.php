<?php
/**
 * IotaUtils
 * @author simpx(simpxx@gmail.com)
 */
class IotaUtils {
	
	public static function getRequest(){
		return array('method' => self::getMethod(),
					 'uri'	  => self::getUri()); 
	}
	/**
	 * 
	 * get base uri:/xx/xx/blog
	 */
	public static function getAppBaseUri(){
		$script_name = $_SERVER['SCRIPT_NAME'];	
		return self::cutToLastChar($script_name, '/');
	}
	/**
	 * 
	 * get base url: http://xx/xx/xx/blog
	 */	
	public static function getAppBaseUrl(){
		$host = $_SERVER['HTTP_HOST'];
		$protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === false? 'http': 'https';
		return $protocol.'://'.$host.self::getAppBaseUri();
	}
	/**
	 * 
	 * get request uri
	 * @return string
	 * @todo throw & $_SERVER['xx_uri']
	 */
	private static function getUri(){
		$uri = self::cutToFirstChar($_SERVER['REQUEST_URI'], '?');
		$script_name = $_SERVER['SCRIPT_NAME'];
		if(self::isStartWithString($uri, $script_name)){
			$r = str_replace($script_name, '', $uri);	
		}	
		else if(self::isStartWithString($uri, self::getAppBaseUri())){
			$r = str_replace(self::getAppBaseUri(), '', $uri);
		}
		return empty($r)? '/': $r;
	}
	/**
	 * 
	 * @throws IotaException
	 */
	private static function getMethod(){
		if($method = $_SERVER['REQUEST_METHOD']){
			return $method;	
		}
		else{
			throw new IotaException('Error: get request information error','IotaUtils::getMethod',IE_IOTA_ERROR);	
		}
	}
	private static function isStartWithString($string,$start_string){
		$start_string = str_replace('/', '\/', quotemeta($start_string));
		$pattern = '#^' . $start_string . '#';
		if(preg_match($pattern,$string)){
			return true;
		}
		else{
			return false;
		}
	}
	private static function cutToFirstChar($string,$char){
		$found = strpos($string, $char);
		if($found){
			$string = substr($string, 0, $found);
		}
		return $string;
	}
	private static function cutToLastChar($string,$char){
		$found = strrpos($string, $char);
		if($found){
			$string = substr($string, 0, $found);
		}
		return $string;	
	} 
}