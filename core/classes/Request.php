<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * User Request parameters as $_POST, $_GET, $_SERVER, $_COOKIE management
 * 
 * @package  Seezoo-Framework
 * @category Classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Request implements Growable
{
	/**
	 * request method
	 * @var string ( always uppercase )
	 */
	public static $requestMethod;
	
	
	/**
	 * request pathinfo
	 * @var string
	 */
	protected $_pathinfo;
	
	
	/**
	 * $_COOKIE stack
	 * @var array
	 */
	protected static $_cookie;
	
	
	/**
	 * $_SERVER stack
	 * @var array
	 */
	protected static $_server;
	
	
	/**
	 * $_POST stack
	 * @var array
	 */
	protected static $_post;
	
	
	/**
	 * $_GET stack
	 * @var array
	 */
	protected static $_get;


	/**
	 * INPUT stack
	 * @var array
	 */
	protected static $_input;
	
	
	/**
	 * URI info
	 * @var string
	 */
	protected static $_uri;
	
	
	/**
	 * segment info
	 * @var array
	 */
	protected static $_uriArray = array();
	
	
	/**
	 * Accessed passinfo ( not overrided )
	 * @var string
	 */
	protected static $_accessPathInfo;
	
	
	/**
	 * Stack accessed IP address
	 * @var string
	 */
	protected $_ip;
	
	
	public function __construct()
	{
		$this->env =  Seezoo::getENV();
		
	}
	
	public static function init()
	{
		self::$requestMethod   = self::_detectRequestMethod();
		self::$_accessPathInfo =  ( isset($_SERVER['PATH_INFO']) ) ? $_SERVER['PATH_INFO'] : '';
		
		$internal      = Application::getEncoding('internal');
		self::$_input  = self::_parseInput();
		self::$_post   = self::_cleanFilter($_POST,   Application::getEncoding('post'),   $internal);
		self::$_cookie = self::_cleanFilter($_COOKIE, Application::getEncoding('cookie'), $internal);
		self::$_get    = self::_cleanFilter($_GET,    Application::getEncoding('get'),    $internal);
		self::$_server = $_SERVER;
		self::$_uri    = ( isset($_SERVER['REQUEST_URI']) ) ? trim($_SERVER['REQUEST_URI'], '/') : '';
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return SZ_Request ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->classes('Request');
	}
	
	
	// ---------------------------------------------------------------


	/**
	 * Request method detection
	 @access protected
	 @return string
	 */
	protected static function _detectRequestMethod()
	{
		$method = ( isset($_SERVER['REQUEST_METHOD']) )
		            ? $_SERVER['REQUEST_METHOD'] :
		            'GET';
		
		switch ( $method )
		{
			case 'GET':
			case 'HEAD':
				$method ='GET';
				break;
			default:
				break;
		}
		return $method;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get the server parameter
	 * 
	 * @access public
	 * @param  string $key
	 * @return mixed
	 */
	public function server($key)
	{
		$key = strtoupper($key);
		return ( isset(self::$_server[$key]) ) ? self::$_server[$key] : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get the POST parameter
	 * 
	 * @access public
	 * @param  string $key
	 * @return mixed
	 */
	public function post($key)
	{
		return ( isset(self::$_post[$key]) ) ? self::$_post[$key] : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get the GET parameter
	 * 
	 * @access public
	 * @param  string $key
	 * @return mixed
	 */
	public function get($key)
	{
		return ( isset(self::$_get[$key]) ) ? self::$_get[$key] : FALSE;
	}
	
	
	// ---------------------------------------------------------------


	/**
	 * Get the PHP input
	 *
	 * @access public
	 * @param  string $key
	 * @return mixed
	 */
	public function input($key)
	{
		return ( isset(self::$_input[$key]) ) ? self::$_input[$key] : FALSE;
	}


	// ---------------------------------------------------------------
	
	
	/**
	 * Get the COOKIE parameter
	 * 
	 * @access public
	 * @param  string $key
	 * @return mixed
	 */
	public function cookie($key)
	{
		return ( isset(self::$_cookie[$key]) ) ? self::$_cookie[$key] : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set a process request
	 * 
	 * @access public
	 * @param  string $pathinfo
	 * @param  string $mode
	 * @param  int    $level
	 */
	public function setRequest($pathinfo, $mode, $level)
	{
		// If pathinfo is empty, use server-pathinfo.
		if ( empty($pathinfo) )
		{
			$pathinfo = (string)$this->server('PATH_INFO');
		}
		
		$pathinfo = kill_traversal(trim($pathinfo, '/'));
		$segments = ( $pathinfo !== '' )
		              ? explode('/', $pathinfo)
		              : array();
		
		self::$_uriArray[$level] = $segments;
		
		// method mapping
		$mapping = $this->env->getMapping();
		if ( $mapping && isset($mapping[$mode]) && is_array($mapping[$mode]) )
		{
			foreach ( $mapping[$mode] as $regex => $map )
			{
				if ( $regex === $pathinfo )
				{
					$pathinfo = $map;
					break;
				}
				else if ( preg_match('|^' . $regex . '$|u', $pathinfo, $matches) )
				{
					$pathinfo = ( isset($matches[1]) )
					              ? preg_replace('|^' . $regex . '$|u', $map, $pathinfo)
					              : $val;
					break;
					
				}
			}
		}
		
		return $pathinfo;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get access URI-segment
	 * 
	 * @access public
	 * @param  int $index
	 * @param  mixed $default
	 * @return mixed
	 */
	public function segment($index, $default = FALSE)
	{
		$level = Seezoo::getLevel();
		return ( isset($this->_uriArray[$level]) && isset($this->_uriArray[$level][$index - 1]) )
		         ? $this->_uriArray[$level][$index - 1]
		         : $default;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get all access URI-segment array
	 * 
	 * @access public
	 * @return array
	 */
	public function uriSegments($level = FALSE)
	{
		if ( ! $level )
		{
			$level = Seezoo::getLevel();
		}
		return ( isset(self::$_uriArray[$level]) ) ? self::$_uriArray[$level] : array();
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get STDIN data
	 * 
	 * @access public
	 * @return string
	 */
	public function stdin()
	{
		if ( PHP_SAPI !== 'cli' )
		{
			throw new RuntimeException('STD_INPUT can get CLI request only!');
		}
		
		$stdin = '';
		while ( FALSE !== ($line = fgets(STDIN, 8192)) )
		{
			$stdin .= $line;
		}
		
		return $stdin;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get HTTP requested PATH_INFO
	 * 
	 * @access public
	 * @return string
	 */
	public function getAccessPathInfo()
	{
		return self::$_accessPathInfo;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get requested method
	 * 
	 * @access public
	 * @return string
	 */
	public function getRequestMethod()
	{
		return self::$requestMethod;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get client IP address
	 * 
	 * @access public
	 * @return string
	 */
	public function ipAddress()
	{
		if ( ! $this->_ip )
		{
			$remote        = $this->server('REMOTE_ADDR');
			$trusted       = (array)$this->env->getConfig('trusted_proxys');
			$ip = $default = '0.0.0.0';
			
			if ( FALSE !== ( $XFF = $this->server('X_FORWARDED_FOR'))
			     && $remote
			     && in_array($remote, $trusted) )
			{
				$exp = explode(',', $XFF);
				$ip  = reset($exp);
			}
			else if ( FALSE !== ( $HCI = $this->server('HTTP_CLIENT_IP'))
			          && $remote
				      && in_array($remote, $trusted) )
			{
				$exp = explode(',', $HCI);
				$ip  = reset($exp);
			}
			else if ( $remote )
			{
				$ip = $remote;
			}
			
			// validate
			if ( function_exists('filter_var') )
			{
				if ( ! filter_var(
				             $ip,
				             FILTER_VALIDATE_IP,
				             FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
				)
				{
					$ip = $default;
				}
			}
			else if ( function_exists('inet_pton') )
			{
				if ( FALSE === inet_pton($ip) )
				{
					$ip = $default;
				}
			}
			$this->_ip = $ip;
		}
		return $this->_ip;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * clean up parameters
	 * 
	 * @access protected
	 * @param  array $data
	 * @param  bool  $convert
	 * @return mixed
	 */
	protected static function _cleanFilter($data, $encoding, $internal)
	{
		$filtered = array();
		foreach ( $data as $key => $value )
		{
			if ( is_array($value) )
			{
				$filtered[$key] = self::_cleanFilter($value);
			}
			else
			{
				// remove magic_quote
				if ( get_magic_quotes_gpc() )
				{
					$value = stripslashes($value);
				}
				
				if ( $encoding !== 'UTF-8' )
				{
					$value = self::_convertUTF8($value, $encoding);
				}
				else if ( $internal !== 'UTF-8' )
				{
					$value = self::_convertUTF8($value, $internal);
				}
				
				// kill invisible character
				do
				{
					preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $value, -1, $count);
				}
				while( $count );
				
				// to strict linefeed
				if ( strpos($value, "\r") !== FALSE )
				{
					$value = str_replace(array("\r\n", "\r"), "\n", $value);
				}
				
				// kill nullbyte
				$filtered[$key] = kill_nullbyte($value);
			}
		}
		
		// TODO: some security process
		
		return $filtered;
	}


	// ---------------------------------------------------------------


	/**
	 * Parse PHP Input ( when requested with PUT/DELETE method )
	 * @access protected
	 * @return array
	 */
	protected static function _parseInput()
	{
		if ( self::$requestMethod === 'GET' || self::$requestMethod === 'POST' )
		{
			return array();
		}

		$input = explode('&', file_get_contents('php://input'));
		$data  = array();
		foreach ( $input as $keyValue )
		{
			list($key, $value) = explode('=', $keyValue);
			// Raw input should have been encoded
			$data[$key] = rawurldecode($value);
		}
		
		return self::_cleanFilter($data, Application::$convertPhpInput);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * String convert to UTF-8 encoding
	 * @param string $str
	 * @param string $encoding
	 */
	protected static function _convertUTF8($str, $encoding = 'UTF-8')
	{
		if ( function_exists('iconv') && ! preg_match('/[^\x00-\x7F]/S', $str) )
		{
			return @iconv($encoding, 'UTF-8//IGNORE', $str);
		}
		else if ( mb_check_encoding($str, $encoding) )
		{
			return mb_convert_encoding($str, 'UTF-8', $encoding);
		}
		return $str;
	}
}
