<?php
/**
 * Iota  - PHP Framework
 * @version 0.1
 * @author simpx(simpxx@gmail.com)
 * @license BSD-new 
 * @link http://simpx.me/iota
 * @link https://github.com/simpx/iota
 * 
 */
define('IOTA_BASE_PATH',dirname(__FILE__));
define('IOTA_ERROR_PATH',IOTA_BASE_PATH.'/error/');
define('IOTA_UTIL_PATH',IOTA_BASE_PATH.'/util/');
define('IOTA_LIB_PATH',IOTA_BASE_PATH.'/lib/');

require IOTA_ERROR_PATH.'IotaException.php';

require IOTA_UTIL_PATH.'ArrayUtils.php';
require IOTA_UTIL_PATH.'IotaUtils.php';

require IOTA_LIB_PATH.'IotaDb.php';
require IOTA_LIB_PATH.'Orm.php';
require IOTA_LIB_PATH.'IotaRequest.php';
require IOTA_LIB_PATH.'IotaView.php';

function __autoload($classname){
	if(!empty(Iota::$config['autoload'])){
		foreach (Iota::$config['autoload'] as $path){
			$path = '/'.$path;
			$file = APPPATH.$path.$classname.'.php';	
			if(file_exists($file)){
				require $file;
			}
		}
	}
			
}

set_exception_handler('iotaExceptionHandler');
function iotaExceptionHandler($e){
	Iota::iotaExceptionHandler($e);
}
class Iota {

	private $debug = true;
	
	public static $config = array('view' => 'view/',
									'dbConfig' => array(),
									'autoload'=> array('model/','controller/','component/','util/'),
							);
	private $urls = array();
	
	/**
	 * 
	 * build the application 
	 * @param array $urls
	 * @param array $config
	 * @return object
	 */
	public static function application($urls, $config = array()){
		$iota = self::getInstance();
		if(!empty($config)){
			self::$config = ArrayUtils::merge(self::$config, $config);
		}
		$iota->urls = ArrayUtils::merge($iota->urls, $urls);
		return $iota;
	}
	public static function getEnv($key){
		return '/workspace/Jinlin/static';	
	}
	public static function redirect($uri){
		$appBaseUrl = IotaUtils::getAppBaseUrl();
		$redirectUrl = $appBaseUrl.$uri;
		header("Location: ".$redirectUrl);
		exit;
	}
	/**	
	 * 
	 * IotaView
	 * @method render
	 * @param $view like 1.blog 2.blog.php 3.blog/index 4.d:\df\adf\blog\index.php
	 */
	public static function render($view, $vars=array(), $return = false){
		$viewPath = APPPATH.'/'.self::$config['view'];	
		if(IotaView::getConfig('viewPath') != $viewPath){
			IotaView::setConfig('viewPath',$viewPath);	
		}
		$vars['_IOTA'] = self::getIotaVars();
		return IotaView::render($view, $vars, $return);
	}
	private static function getIotaVars(){
		$_IOTA['base'] = IotaUtils::getAppBaseUrl();
		return $_IOTA;
	}
	public static function renderBase($base=''){
		IotaView::renderBase($base);	
	}
	public static function renderAppend($append = array()){
		IotaView::renderAppend($append);
	}
	/**
	 * IotaRequest
	 * 
	 */
	public static function get($value = '',$pattern = '',$default = null){
		return IotaRequest::get($value, $pattern, $default = null);	
	}
	public static function post($value = '',$pattern = '',$default = null){
		return IotaRequest::post($value, $pattern, $default = null);
	}	
	public static function data($value = '',$pattern = '',$default = null){
		return IotaRequest::data($value, $pattern, $default = null);
	}
	/**
	 * 
	 * run the application
	 * @return void
	 */
	public function run(){
		$request = IotaUtils::getRequest();
		return $this->excuteMethodByUri($request['uri'],$request['method']);
	}
	private function excuteMethodByUri($uri,$method='GET'){
		if(!empty($this->urls)){
			$matched = false;
			foreach ($this->urls as $pattern => $class_name){
				$pattern = '#^'.$pattern.'$#';	
				if(preg_match($pattern, $uri,$matches)){
					$matched = true;
					array_shift($matches);
					$params = empty($matches)? array(): $matches;
					$function = $method;
					$this->excute($class_name, $function, $params);
					break;
				}
			}
			if(!$matched){
				throw new IotaException('404 page not found','404',IE_USER_ERROR);
			}
		}
		else{
			throw new IotaException('need url config','needUrlConfig',IE_APP_ERROR);
		}
	}
	private static function getInstance(){
		static $instance = null;
		if($instance == null){
			$instance = new Iota();
		}
		return $instance;
	}
	public static function iotaExceptionHandler($e){
		echo 'Error Msg:';
		echo $e->getMessage();
		echo '<br>';
		echo 'Error Extra';
		echo $e->getExtra();	
		die();
		//TODO 
	}
	/**
	 * 
	 * create a class and excute the method 
	 * @param string $class_name
	 * @param string $function
	 * @param array $params
	 * @throws IotaException
	 * @retuen void
	 */
	private function excute($class_name,$function='GET',$params=array()){
		if(class_exists($class_name)){
			$class = new $class_name;
			if(method_exists($class, $function)){
				if(method_exists($class, 'exceptionHandler')){
					try{
						call_user_func_array(array($class,$function), $params);	
					}
					catch(IotaException $e){
						call_user_func(array($class,'exceptionHandler'), $e);	
					}
				}
				else{
					call_user_func_array(array($class,$function), $params);	
				}
			}	
			else{
				throw new IotaException('Error : method "'.$function.'" is not found in class "'.$class_name.'"','MethodNotFound',IE_APP_ERROR);
			}
		}
		else{
			throw new IotaException('Error : class "'.$class_name.'" is not found','ClassNotFound',IE_APP_ERROR);
		}
	}
}