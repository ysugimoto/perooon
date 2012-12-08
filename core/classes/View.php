<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * View management class ( use driver )
 * render with:
 *   default PHP file
 *   Smarty
 *   PHPTAL
 * 
 * @package  Seezoo-Framework
 * @category Classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_View extends SZ_Driver implements Growable
{
	/**
	 * pre assinged vars
	 * @var array
	 */
	protected $_assignedVars = array();
	
	// choose template engine
	// =======================================================
	// | engine name | description                    |
	//  -----------------------------------------------------
	// | default     | Rendering with simply PHP file |
	// | smarty      | Rendering with Smarty          |
	// | phptal      | Rendering with PHPTAL          |
	// | twig        | Rendering with Twig            |
	// =======================================================
	protected $_templateEngine;
	
	
	// engine extensions:
	// ========================================================
	// | engine name | description                     |
	//   ----------------------------------------------------- 
	// | default     | .php                            |
	// | smarty      | you can choose, default is .tpl |
	// | phptal      | you can choose, default is .php |
	// | twig        | you can choose, default is .html|
	//=========================================================
	protected $_templateExtension = '.php';
	
	
	/**
	 * layout view name
	 * @var string
	 */
	protected $_layout = FALSE;
	
	
	/**
	 * Final rendering after controller executed view
	 * @var string
	 */
	protected $_finalView = '';
	
	/**
	 * layout parts
	 * @var array
	 */
	protected $_layoutParts = array();
	
	
	public function __construct()
	{
		// set initial redering engine
		if ( FALSE === ($templateEngine = get_config('rendering_engine')) )
		{
			$templateEngine = 'default';
		}
		$this->engine($templateEngine);
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->classes('View');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * assign variable to view
	 * 
	 * @access public
	 * @param  mixed $vars
	 */
	public function assign($name, $value = null)
	{
		$vars = ( ! is_string($name) )
		          ? $this->_objectToArray($name)
		          : array($name => $value);
		
		$this->_assignedVars = array_merge($this->_assignedVars, $vars);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * add string buffer
	 * 
	 * @access public
	 * @param  string $buffer
	 * @throws Exception
	 */
	public function add($buffer)
	{
		$this->driver->addBuffer($buffer);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * get formatted display bufer
	 * 
	 * @access public
	 * @return string
	 */
	public function getDisplayBuffer()
	{
		return $this->driver->getBuffer();
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * start output buffer
	 * 
	 * @access public
	 */
	public function bufferStart()
	{
		$this->driver->bufferStart();
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * end buffer and get contents
	 * 
	 * @access public
	 * @param  bool $addStack
	 */
	public function getBufferEnd($addStack = FALSE)
	{
		return $this->driver->getBufferEnd($addStack);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * replace buffer
	 * 
	 * @access public
	 * @param  string $buf
	 */
	public function replaceBuffer($buf)
	{
		$this->driver->replaceBuffer($buf);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * rendering view
	 * 
	 * @access public
	 * @param  string $path  : view file path
	 * @param  mixed  $vars  : assing vars array or stdClass
	 * @param  bool   $return: returns buffer
	 */
	public function render($path, $vars = array(), $return = FALSE)
	{
		return $this->_renderView($path, $vars, $return);
	}
	
	// ---------------------------------------------------------------
	
	
	/**
	 * rendering view with escape variables
	 * 
	 * @access public
	 * @param  string $path  : view file path
	 * @param  mixed  $vars  : assing vars array or stdClass
	 * @param  bool   $return: returns buffer
	 */
	public function escapeRender($path, $vars = array(), $return = FALSE)
	{
		return $this->_renderView($path, $vars, $return, TRUE);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Handle rendering view
	 * 
	 * @access public
	 * @param  string $path  : view file path
	 * @param  mixed  $vars  : assign vars array or stdClass
	 * @param  bool   $return: returns buffer
	 * @param  bool   $escape
	 */
	protected function _renderView($path, $vars, $return, $escape = FALSE)
	{
		// First, set pre-adssign variables
		$assigns = $this->_assignedVars;
		// extra assign loaded helpers
		$assigns['Helper'] = Seezoo::$Importer->classes('Helpers');
		
		$SZ = Seezoo::getInstance();
		// Second, Lead process assigned variables if exists
		if ( isset($SZ->lead) )
		{
			$assigns['Lead'] = $SZ->lead;
			$assigns = array_merge($assigns, $SZ->lead->getAssignData());
		}
		
		// Third, method parameter variables
		$vars = array_merge($assigns, $this->_objectToArray($vars));
		
		// escape assign variable
		if ( $escape )
		{
			$vars = array_map('prep_str', $vars);
		}
		
		$viewFile   = FALSE;
		$viewDir    = FALSE;
		$detectFile = $path . $this->_templateExtension;
		
		foreach ( Seezoo::getApplication() as $app )
		{
			if ( file_exists($app->path . 'views/' . $detectFile) )
			{
				$viewFile = $app->path . 'views/' . $detectFile;
				$viewDir  = $app->path . 'views/' . trim(dirname($detectFile), './') . '/';
				break;
			}
		}
		
		if ( ! $viewFile )
		{
			throw new Exception('Unable to load requested file:' . $detectFile);
		}
		
		// set current directory
		if ( ! $this->driver->getDirectoryBase() )
		{
			$this->driver->setDirectoryBase($viewDir);
		}
		
		// do render with driver
		$buffer = $this->driver->renderView($viewFile, $vars, $return);
		
		// And settings clean up
		// (Buffer level should be 1)
		$this->driver->cleanUp();
		
		return $buffer;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Swap final view
	 * 
	 * @access public
	 * @param  string $viewPath
	 */
	public function swap($viewPath)
	{
		$this->_finalView = $viewPath;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Render final view
	 * 
	 * @access public
	 * @param  mixed $vars
	 */
	public function finalRender($var)
	{
		switch ( gettype($var) )
		{
			case 'boolean':
				return;
			case 'string':
				if ( $var !== 'success' )
				{
					$this->driver->replaceBuffer($var);
					return;
				}
				$var = array();
				break;
			case 'object':
				$var = get_object_vars($var);
				break;
			case 'array':
				break;
			default:
				$var = array();
		}
		$this->_renderView($this->_finalView, $var, FALSE);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * set layout
	 * 
	 * @access public
	 * @param  string $layout
	 */
	public function layout($layout = 'default')
	{
		$this->_layout = $layout;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * add layout parts
	 * 
	 * @access public
	 * @param  string $templateVar : var name in layout file
	 * @param  string $path
	 * @param  mixed  $vars
	 */
	public function addParts($templateVar, $path, $vars = array())
	{
		$this->_layoutParts[$templateVar] = $this->render($path, $vars, TRUE);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * execute layout
	 * 
	 * @access public
	 */
	public function displayLayout()
	{
		if ( ! $this->_layout )
		{
			throw new Exception('Layout file is not exists.');
			return;
		}
		
		$this->render($this->_layout, $this->_layoutParts);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * 
	 * Choose rendering engine
	 * 
	 * @access public
	 * @param  string $engine
	 * @param  string$extension
	 */
	public function engine($engine = 'default', $extension = FALSE)
	{
		// Current use engine
		if ( $engine === $this->_templateEngine )
		{
			return;
		}
		
		$this->_templateEngine = strtolower($engine);
		switch ( $this->_templateEngine )
		{
			case SZ_TMPL_SMARTY:
				$extension = ( $extension ) ? '.' . ltrim($extension, '.') : '.tpl';
				break;
				
			case SZ_TMPL_PHPTAL:
				$extension = ( $extension ) ? '.' . ltrim($extension, '.') : '.html';
				break;
				
			case SZ_TMPL_TWIG:
				$extension = ( $extension ) ? '.' . ltrim($extension, '.') : '.html';
				break;
				
			default:
				$this->_templateEngine = 'default';
				$extension             = '.php';
				break;
		}
		$this->_loadDriver('view', ucfirst($this->_templateEngine) . '_view');
		$this->setExtension($extension);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Get current engine
	 * 
	 * @access public
	 * @return string
	 */
	public function getEngine()
	{
		return $this->_templateEngine;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * set viewfile extension
	 * 
	 * @access public
	 * @param  string $ext
	 */
	public function setExtension($ext)
	{
		$this->_templateExtension = $ext;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get viewfile extension
	 * 
	 * @access public
	 * @return string
	 */
	public function getExtension()
	{
		return $this->_templateExtension;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * convert object to array
	 * 
	 * @access protected
	 * @param  mixed $object
	 * @return array
	 */
	protected function _objectToArray($object)
	{
		if ( ! is_object($object) )
		{
			return (array)$object;
		}
		return get_object_vars($object);
	}
}