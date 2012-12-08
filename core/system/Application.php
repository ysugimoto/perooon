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
 * Application info
 * 
 * @package  Seezoo-Framework
 * @category System
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */


class Application
{
	/**
	 * Application absolute path
	 * @var string
	 */
	public $path;
	
	/**
	 * Application name
	 * @var string
	 */
	public $name;
	
	/**
	 * Application prefix namepsace
	 * @var string
	 */
	public $prefix;
	
	
	public $mode;
	public $level;
	public $pathinfo;
	public $router;
	public $env;
	public $request;
	public $config = array();
	
	public static $timeZone       = 'Asia/Tokyo';
	public static $errorReporting = E_ALL;
	
	/**
	 * Using applications stack
	 * @var array
	 */
	protected static $apps = array();
	
	
	/**
	 * Application current instance
	 * @var Application
	 */
	private static $instance;
	
	private static $encodings = array(
	                              'internal' => 'UTF-8',
	                              'post'     => 'UTF-8',
	                              'get'      => 'UTF-8',
	                              'cookie'   => 'UTF-8',
	                              'input'    => 'UTF-8'
	                            );
	
	
	public static function setEncoding($type, $encoding = 'UTF-8')
	{
		self::$encodings[$type] = $encoding;
	}
	
	public static function getEncoding($type)
	{
		return self::$encodings[$type];
	}
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Run the subprocess with same environment
	 * 
	 * @access public static
	 * @param  string $mode
	 * @param  string $overridePathInfo
	 * @param  array  $extraArgs
	 * @return mixed
	 */
	public static function run($mode = FALSE, $overridePathInfo = '', $extraArgs = FALSE)
	{
		if ( ! self::$instance )
		{
			throw new RuntimeException('Application has not main process!');
		}
		$sub = clone self::$instance;
		return $sub->run($mode, $overridePathInfo, $extraArgs);
		
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get stackes applications
	 * 
	 * @access public
	 * @return array
	 */
	public function getApps()
	{
		return self::$apps;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get currnt applications
	 * 
	 * @access public static
	 * @return array
	 */
	public static function get()
	{
		return self::$instance;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Application constructor
	 * 
	 * @access private
	 * @param  string $appName
	 * @param  string $prefix
	 */
	private function __construct($appName, $prefix)
	{
		if ( ! is_dir(APPPATH . $appName) )
		{
			throw new RuntimeException('Application "' . $appName . '" is undefined.');
		}
		$this->name   = $appName;
		$this->path   = trail_slash(APPPATH . $appName);
		$this->prefix = ( $prefix === '' )
		                     ? trim(ucfirst($appName), '_') . '_'
		                     : trim($prefix, '_') . '_';
		
		foreach ( Autoloader::$loadTargets as $path => $loadType )
		{
			if ( is_dir($this->path . $path) )
			{
				Autoloader::register($this->path . $path, $loadType, $this->prefix);
			}
		}
		
		// Config setup  ---------------------------------------------
		if ( FALSE === ($config = graceful_require($this->path . 'config/config.php', 'config')) )
		{
			$config = array();
		}
		$this->config = $config;
		
		
		// Event startup ---------------------------------------------
		
		if ( file_exists($this->path . 'config/event.php') )
		{
			Event::addListenerFromFile($this->path . 'config/event.php');
		}
		
		Seezoo::addPrefix($this->prefix);
		$this->bootStrap();
		self::$apps[] = $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Create application
	 * 
	 * @access public static
	 * @param  string $appName
	 * @param  string $prefix
	 * @return Application $app
	 */
	public static function init(
	                            $appName    = SZ_BASE_APPLICATION_NAME,
	                            $prefix     = SZ_PREFIX_BASE,
	                            $autoExtend = TRUE)
	{
		if ( self::$instance )
		{
			throw new RuntimeException('Application has already created!');
		}
		// initialize applications
		self::$apps     = array();
		self::$instance = new Application($appName, $prefix);
		
		Seezoo::startup(self::$instance);
		
		self::$instance->env     = Seezoo::getENV();
		self::$instance->request = Seezoo::getRequest();
		
		// Are you use default application?
		if ( $autoExtend && $appName !== SZ_BASE_APPLICATION_NAME )
		{
			self::$instance->extend(SZ_BASE_APPLICATION_NAME . ':' . SZ_PREFIX_BASE);
		}
		
		return self::$instance;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Extend application
	 * 
	 * @access public
	 * @param  mixed $apps
	 * @return Application $this
	 */
	public function extend($apps = '')
	{
		// If the last be temporarily saved based applications
		if ( end(self::$apps)->name === SZ_BASE_APPLICATION_NAME )
		{
			$baseApp = array_pop(self::$apps);
		}
		
		foreach ( (array)$apps as $app )
		{
			list($appName, $prefix) = ( strpos($app, ':') !== FALSE )
			                            ? explode(':', $app)
			                            : array($app, '');
			
			if ( ! $this->_exists($appName) )
			{
				$instance     = new Application($appName, $prefix);
				$this->config = array_merge($instance->config, $this->config);
			}
		}
		
		// Restore what was saved base application if exists
		if ( isset($baseApp) )
		{
			self::$apps[] = $baseApp;
			$this->config = array_merge($baseApp->config, $this->config);
		}
		
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Extend all applications
	 * 
	 * @access public
	 * @return Application $this
	 */
	public function extendAll()
	{
		$items        = array();
		$applications = new DirectoryIterator(APPPATH);
		
		foreach ( $applications as $application )
		{
			$name = $application->getBasename();
			
			if ( ! $application->isDir()
			     || $application->isDot()
			     || $this->_exists($name) )
			{
				continue;
			}
			
			$items[] = $name;
		}
		
		return $this->extend($items);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Check Application already exists
	 * 
	 * @access private
	 * @param  string $appName
	 * @return bool
	 */
	private function _exists($appName)
	{
		foreach ( self::$apps as $app )
		{
			if ( $app->name === $appName )
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Run status initialize
	 * 
	 * @access private
	 * @param  string $mode
	 * @param  string $pathInfo
	 */
	private function initialize($mode, $pathInfo)
	{
		$this->mode     = $mode;
		$this->level    = Seezoo::addProcess($this);
		$this->pathinfo = $this->request->setRequest($pathInfo, $mode, $this->level);
		$this->router   = new Seezoo::$Classes['Router']();
		
		$this->router->setPathInfo($this->pathinfo);
		$this->router->setMode($mode);
		$this->router->setLevel($this->level);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Run the application
	 * 
	 * @access public static
	 * @param  string $mode
	 * @param  string $overridePathInfo
	 */
	public function boot($mode = FALSE, $overridePathInfo = '', $extraArgs = FALSE)
	{
		Seezoo::prepare($this->config);
		
		// Set Application Environment
		date_default_timezone_set(self::$timeZone);
		error_reporting(self::$errorReporting);
		
		// Benchmark start
		$Mark = Seezoo::$Importer->classes('Benchmark');
		
		if ( $mode === FALSE
		     && ($mode = $this->env->getConfig('default_process')) === FALSE )
		{
			// Default process is MVC.
			$mode = SZ_MODE_MVC;
		}
		
		$this->initialize($mode, $overridePathInfo);
		
		// create process instance
		$Mark->start('baseProcess:'. $this->level);
		
		Event::fire('process_start', $this);
		
		// Priority process MVC/CLI.
		if ( $mode === SZ_MODE_MVC || $mode === SZ_MODE_CLI )
		{
			// Is really CLI access?
			if ( $mode === SZ_MODE_CLI && PHP_SAPI !== 'cli' )
			{
				show_404();
			}
			
			if ( $this->router->routing($this->level) === FALSE )
			{
				show_404();
			}
			$Mark->end('process:' . $this->level . ':MVC:Routed', 'baseProcess:'. $this->level);
			// Load Controller and execute method
			$exec = $this->router->bootController($extraArgs);
			if ( ! is_array($exec) )
			{
				show_404();
			}
			
			// extract instance/returnvalue
			list($SZ, $returnValue) = $exec;
			
			$Mark->end('process:' . $this->level . ':MVC:ControllerExecuted', 'baseProcess:'. $this->level);
			Event::fire('controller_execute');
			
			$Mark->end('process:' . $this->level . ':MVC:MethodExecuted', 'baseProcess:'. $this->level);
		}
		else
		{
			$SZ = new Seezoo::$Classes['Breeder']();
			
			switch ( $mode )
			{
				// Case : default mode execute
				// simple returns process instance.
				case SZ_MODE_DEFAULT:
					
					return $SZ;
					
				// Case : action mode execute
				// process execute from simple file, and get a output buffer
				case SZ_MODE_ACTION:
					
					$SZ->view->bufferStart();
					$SZ->router->bootAction();
					$returnValue = $SZ->view->getBufferEnd(TRUE);
					$Mark->end('process:' . $this->level . ':API:executed', 'baseProcess:'. $this->level);
					
				break;
				
				// Case : process mode execute
				// process execute from simple file, and get a return-value
				case SZ_MODE_PROC:
					
					$result = $SZ->router->bootProcess();
					self::releaseInstance($SZ);
					$Mark->end('process:' . $this->level . ':end', 'baseProcess:'. $this->level);
					return $result;
					
				break;

				// Case : not found...
				default:
					
					show_404();
					
				break;
			}
		}
		
		// process executed. release process instance.
		$Mark->end('process:' . $this->level . ':end', 'baseProcess:'. $this->level);
		Event::fire('process_end');
		
		if ( Seezoo::$outpuBufferMode === FALSE )
		{
			Seezoo::releaseInstance($SZ);
			Seezoo::$outpuBufferMode = TRUE;
			return $returnValue;
		}
		
		// Is this process in a sub process?
		if ( $this->level > 1 )
		{
			$SZ->view->finalRender($returnValue);
			
			// Does output hook method exists?
			if ( method_exists($SZ, '_output') )
			{
				$SZ->view->replaceBuffer($SZ->_output($SZ->view->getDisplayBuffer()));
			}
			Seezoo::releaseInstance($SZ);
			$this->level--;
			return $SZ->view->getDisplayBuffer();
		}
		else
		{
			$Mark->end('final', 'baseProcess:'. $this->level);
			Event::fire('session_update');
			
			// final output!
			$SZ->view->finalRender($returnValue);
			
			// Does output hook method exists?
			if ( method_exists($SZ, '_output') )
			{
				$SZ->view->replaceBuffer($SZ->_output($SZ->view->getDisplayBuffer()));
			}
			Seezoo::releaseInstance($SZ);
			$this->level--;
			
			Seezoo::$Response->display($SZ->view->getDisplayBuffer());
		}
		// -- complete!
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Execute bootstrap if application has bootstrap file
	 * 
	 * @access protected
	 * @return void
	 */
	protected function bootStrap()
	{
		if ( file_exists($this->path . 'bootstrap.php') )
		{
			require($this->path . 'bootstrap.php');
		}
	}
}
