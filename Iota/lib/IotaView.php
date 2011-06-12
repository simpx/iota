<?php
/**
 * IotaView
 * @method render renderBase renderAppend
 * @throws IotaException
 * @author simpx(simpxx@gmail.com)
 */
interface IotaViewInterface {
	public static function render($view, $vars, $return=false);	
	public static function renderBase($base = '');
	public static function renderAppend($appendValue = '',$appendView = '');
}
class IotaView implements IotaViewInterface {
	private static $config = array('viewPath'=>'',
									'ext'=>'html');
	private static $base = '';
	private static $append = array();
	public static function setConfig($key,$value = ''){
		if(is_array($key)){
			self::$config = ArrayUtils::merge($key, self::$config);	
		}
		elseif(isset(self::$config[$key])){
			self::$config[$key] = $value;	
		}
	}
	public static function getConfig($key = ''){
		return empty($key)? self::$config: self::$config[$key];	
	}
	private static function getRealPath($view){
		$realPath = self::getConfig('viewPath').$view.'.html';	
		//TODO ext
		return $realPath;
	}
	public static function renderBase($base = ''){
		self::$base = $base;		
	}
	public static function renderAppend($appendValue = '',$appendView = ''){
		if($appendValue === false){
			self::$append = array();	
		}
		elseif(is_array($appendValue)){
			self::$append = ArrayUtils::merge(self::$append, $appendValue);
		}
		else{
			self::$append = ArrayUtils::merge(self::$append, array($appendValue=>$appendView));	
		}
	}
	public static function render($view, $vars='', $var='page', $return=false){
		$logger = IotaLog::getLogger('IotaView','render');
		if(DEBUG)$logger->log(Level_FINE, 'render the view',$view);
		if(empty(self::$base)){
			return self::excuteRender($view, $vars, $var);	
		}	
		else{
			$page = self::excuteRender($view, $vars, true);	
			foreach (self::$append as $appendValue => $appendView){
				$append[$appendValue] = self::excuteRender($appendView, null, true);		
			}
			$baseVars = array($var=>$page);
			$baseVars['_IOTA'] = $vars['_IOTA'];
			if(!empty($append)){
				$baseVars = ArrayUtils::merge($baseVars, $append);
			}
			return self::excuteRender(self::$base,$baseVars,$return);
		}
	}
	public static function excuteRender($view, $vars='', $return=false){
		$logger = IotaLog::getLogger('IotaView','excuteRender');
		$viewPath = self::getRealPath($view);
		$logger->log(Level_FINE, 'the real path of view to be render',$viewPath);
		$logger->log(Level_FINE, 'is return?',$return);
		if(file_exists($viewPath)){
			ob_start();
			if(!empty($vars)){
				extract($vars);	
			}
			include $viewPath;
			if($return){
				$buffer = ob_get_clean();	
				return $buffer;
			}	
			else{
				ob_end_flush();
				return true;
			}
		}	
		else{
			throw new IotaException('view file not exist:'.$viewPath,'viewNotFound',IE_APP_ERROR);
		}
	}
}