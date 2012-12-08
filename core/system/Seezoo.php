<?php if ( ! defined('SZ_EXEC') ) exit('access denied.');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * ------------------------------------------------------------------
 * 
 * Factory and utility class
 * 
 * @package  Seezoo-Framework
 * @category System
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class Seezoo
{
	/**
	 * Public class importer
	 * @var Importer class instance
	 */
	public static $Importer;
	
	
	/**
	 * Public class logger
	 * @var Logger class instance
	 */
	public static $Logger;
	
	/**
	 * Public class response
	 * @var Response class instance
	 */
	public static $Response;
	
	
	/**
	 * Public class Cache
	 * @var Cache class imstance
	 */
	public static $Cache;
	
	
	/**
	 * loaded Classes strings (extension included)
	 * @var array
	 */
	public static $Classes = array();
	
	
	/**
	 * Stack of system environments
	 * @var Environment class instance
	 */
	protected static $_stackENV;
	
	
	/**
	 * Stack of Request instance
	 * @var Request class instance
	 */
	protected static $_stackRequest;
	
	
	/**
	 * System statup/prepare flagment
	 * @var bool
	 */
	private static $startuped = FALSE;
	private static $prepared  = FALSE;
	
	
	/**
	 * Propery alias stacks
	 * @var array
	 */
	private static $_propertyAliases = array();
	
	
	/**
	 * Output buffer mode flag
	 * @var bool
	 */
	public static $outpuBufferMode = TRUE;
	
	
	/**
	 * Prefix list
	 * @var array
	 */
	private static $prefixes = array(SZ_PREFIX_CORE);
	
	/**
	 * Default suffix list
	 * @var array
	 */
	public static $suffixes = array(
	                           'classes'      => array(''),
	                           'helper'       => array('', 'Helper'),
	                           'library'      => array(''),
	                           'model'        => array('', 'Model'),
	                           'activerecord' => array('Activerecord')
	                         );
	
	/**
	 * Using applications
	 * @var array
	 */
	private static $application;
	
	private static $_processes = array();
	private static $instances  = array();
	private static $_debugStack = array();
	private static $level = 0;
	private static $dbs   = array();
		/**
	 * suffix of page_link
	 * @var array
	 */
	private static $_queryStringSuffix = array();
	
	/**
	 * Stack of instantiated class
	 * @var array
	 */
	private static $_classCache = array();
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Add instantiated class cache
	 * 
	 * @access public static
	 * @param  string $className
	 * @param  object $instance
	 */
	public static function setClassCache($className, $instance)
	{
		self::$_classCache[$className] = $instance;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get class cache
	 * 
	 * @access public static
	 * @param  string $className
	 * @return mixed object/FALSE
	 */
	public static function getClassCache($className)
	{
		return ( isset(self::$_classCache[$className]) )
		         ? self::$_classCache[$className]
		         : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Add system process
	 * @access public static
	 * @param object $proc
	 */
	public static function addProcess($proc)
	{
		return array_push(self::$_processes, $proc);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get current system process
	 * @access public static
	 * @return object
	 */
	public static function getProcess()
	{
		return end(self::$_processes);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Mark the process level
	 * 
	 * @access public static
	 * @param  object $instance
	 * @return int    $level
	 */
	public static function sub(&$instance)
	{
		self::$instances[] = $instance;
		return ++self::$level;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get a current process instance
	 * 
	 * @access public static
	 * @return object instance
	 */
	public static function getInstance()
	{
		return end(self::$instances);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get current process instances
	 * 
	 * @access public static
	 * @return object instance
	 */
	public static function getInstancesForDebug()
	{
		return self::$_debugStack;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get current level
	 * 
	 * @access public static
	 * @return int    $level
	 */
	public static function getLevel()
	{
		return self::$level;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Add database stack
	 * 
	 * @access public static
	 * @param  string $group
	 * @param  object $db
	 */
	public static function pushDB($group, $db)
	{
		self::$dbs[$group] =& $db;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get database instance from stack
	 * 
	 * @access public static
	 * @param  string $group
	 * @return mixed
	 */
	public static function getDB($group)
	{
		return ( isset(self::$dbs[$group]) ) ? self::$dbs[$group] : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	public static function getQueryStringSuffix()
	{
		return implode('&amp;', self::$_queryStringSuffix);
	}
	
	
	// ---------------------------------------------------------------
	
	
	public static function addQueryStringSuffix($key, $value, $replace = FALSE)
	{
		if ( isset(self::$_queryStringSuffix[$key]) )
		{
			if ( $replace === TRUE )
			{
				self::$_queryStringSuffix[$key] = rawurlencode($key) . '=' . rawurlencode($value);
			}
		}
		else
		{
			self::$_queryStringSuffix[$key] = rawurlencode($key) . '=' . rawurlencode($value);
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Attach use application
	 * 
	 * @access public static
	 * @param  Application $app
	 */
	public static function addApplication(Application $app)
	{
		self::$application = $app;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get using applications
	 * 
	 * @access public static
	 * @return Application
	 */
	public static function getApplication()
	{
		return self::$application->getApps();
	}


	// ---------------------------------------------------------------


	/**
	 *
	 * Register regex GET request
	 * @access public static
	 * @param  string $pathRegex
	 * @param  callable $callback
	 * @param  bool $forceQuit
	*/
	public static function get($pathRegex, $callback, $forceQuit = FALSE)
	{
		self::_handleRegexRequest('GET', $pathRegex, $callback, $forceQuit);
	}


	// ---------------------------------------------------------------


	/**
	 *
	 * Register regex POST request
	 * @access public static
	 * @param  string $pathRegex
	 * @param  callable $callback
	 * @param  bool $forceQuit
	*/
	public static function post($pathRegex, $callback, $forceQuit = FALSE)
	{
		self::_handleRegexRequest('POST', $pathRegex, $callback, $forceQuit);
	}


	// ---------------------------------------------------------------


	/**
	 *
	 * Fire the regex request if path matched
	 * @access private static
	 * @param  string $method
	 * @param  string $pathRegex
	 * @param  callable $callback
	 * @param  bool $forceQuit
	*/
	private static function _handleRegexRequest($method, $pathRegex, $callback, $forceQuit)
	{
		if ( ! is_callable($callback) )
		{
			throw new InvalidArgumentException('Second argument must be callable.');
		}
		$pathInfo    = ( isset($_SERVER['PATH_INFO']) ) ? $_SERVER['PATH_INFO'] : '';
		$queryString = ( isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : '';
		$path        = ltrim($pathInfo, '/');
		$regex       = '^' . trim(str_replace('|', '\|', $pathRegex), '^$') . '$';
		if ( $queryString !== '' )
		{
			$path .= '?' . $request->server('query_string');
		}
		if ( $_SERVER['REQUEST_METHOD'] === $method
		     && preg_match('|' . $regex . '|', $path, $match) )
		{
			array_shift($match);
			call_user_func_array($callback, $match);
			if ( $forceQuit )
			{
				exit;
			}
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set property alias
	 * @access public static
	 * @param  mixed  $prop
	 * @param  string $aliasName
	 */
	public static function setAlias($prop, $aliasName = '')
	{
		if ( is_array($prop) )
		{
			foreach ( $prop as $p => $name )
			{
				self::$_propertyAliases[$p] = $name;
			}
		}
		else
		{
			self::$_propertyAliases[$prop] = $aliasName;
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get property alias
	 * @access public static
	 * @return array
	 */
	public static function getAliases()
	{
		return self::$_propertyAliases;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Add application prefix
	 * 
	 * @access pubic static
	 * @param  string $prefix
	 */
	public static function addPrefix($prefix)
	{
		self::$prefixes[] = $prefix;
	}
	
	
	// ---------------------------------------------------------------√
	
	
	/**
	 * Check class has prefix
	 * 
	 * @access public static
	 * @param  string $className
	 * @return bool
	 */
	public static function hasPrefix($className)
	{
		$regex = '/^' . implode('|', self::$prefixes) . '/';
		return ( preg_match($regex, $className) ) ? TRUE : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Remove or split prefix
	 * 
	 * @access public static
	 * @param  string $className
	 * @param  bool $returnPrefix
	 */
	public static function removePrefix($className, $returnPrefix = FALSE)
	{
		$regex = '/^(' . implode('|', self::$prefixes) . ')(.+)$/';
		if ( preg_match($regex, $className, $matches) )
		{
			return ( $returnPrefix )
			         ? array($matches[1], $matches[2])
			         : $matches[2];
		}
		return ( $returnPrefix ) ? array($className, '') : $className;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Add suffix format
	 * 
	 * @access public static
	 * @param  string $type
	 * @param  string $suffix
	 */
	public static function addSuffix($type, $suffix)
	{
		if ( ! isset(self::$suffixes[$type]) )
		{
			self::$suffixes[$type] = array();
		}
		array_unshift(self::$suffixes[$type], $suffix);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get suffix
	 * 
	 * @access public static
	 * @param  string $type
	 * @return array
	 */
	public static function getSuffix($type)
	{
		return ( isset(self::$suffixes[$type]) )
		         ? self::$suffixes[$type]
		         : array('');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Write log file
	 * 
	 * @access public static
	 * @param string $msg
	 * @param int $level
	 */
	public static function log($msg, $level = FALSE)
	{
		if ( ! self::$Logger )
		{
			self::$Logger = self::$Importer->classes('Logger');
		}
		self::$Logger->write($msg, $level);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get stacked Environment instance
	 * 
	 * @access public static
	 * @return object
	 */
	public static function getENV()
	{
		return self::$_stackENV;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get stacked Request instance
	 * 
	 * @access public static
	 * @return object
	 */
	public static function getRequest()
	{
		return self::$_stackRequest;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Release a process instance
	 * 
	 * @access public static
	 * @param  Dog $SZ
	 */
	public static function releaseInstance($SZ)
	{
		$instance = array_pop(self::$instances);
		self::$_debugStack[] = $instance;
		unset($instance);
		--self::$level;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * System startup and load Core classes
	 * 
	 * @access private static
	 * @param  Application $app
	 * @throws RuntimeException
	 */
	public static function startup(Application $app)
	{
		if ( self::$startuped )
		{
			throw new RuntimeException('System enable to start only once!');
		}
		
		
		// Request initialize ----------------------------------------
		
		SZ_Request::init();
		
		// Set application and instantiate itinitial classes ---------
		
		self::$application   = $app;
		self::$Importer      = new SZ_Importer();
		self::$_stackENV     = new SZ_Environment();
		self::$_stackRequest = new SZ_Request();
		
		// Exception setting -----------------------------------------
		
		self::$Classes['Exception'] = self::$Importer->classes('Exception', FALSE);
		$Exception = new self::$Classes['Exception']();
		set_exception_handler(array($Exception, 'handleException'));
		set_error_handler(array($Exception, 'handleError'));
		register_shutdown_function(array($Exception, 'handleShutdown'));
		
		
		// Startup event fire ----------------------------------------
		Event::fire('startup');
		
		self::$startuped = TRUE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Prepare core classes on boot application
	 * 
	 * @access public static
	 * @param  array $config
	 * @throws RuntimeException
	 */
	public static function prepare(array $config)
	{
		if ( self::$prepared )
		{
			throw new RuntimeException('Prepare application boots only once time!');
		}
		else if ( empty($config) )
		{
			throw new RuntimeException('Application configuration is empty!');
		}
		
		// Reload core classes ( extend )
		self::$Importer  = self::$Importer->classes('Importer');
		self::$_stackENV = self::$Importer->classes('Environment');
		self::$_stackENV->setAppConfig($config);
		
		self::$_stackRequest      = self::$Importer->classes('Request');
		self::$Response           = self::$Importer->classes('Response');
		self::$Cache              = self::$Importer->classes('Cache');
		self::$Classes['View']    = self::$Importer->classes('View',    FALSE);
		self::$Classes['Router']  = self::$Importer->classes('Router',  FALSE);
		self::$Classes['Breeder'] = self::$Importer->classes('Breeder', FALSE);
		
		// Preprocess event fire
		Event::fire('preprocess');
		
		self::$prepared = TRUE;
	}
}

