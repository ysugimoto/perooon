<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Dependency injection Management Class
 * 
 * @package  Seezoo-Framework
 * @category System
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
 
class DI
{
	/**
	 * Stack instance
	 * @var object
	 */
	private $instance;
	
	/**
	 * Stack class name
	 * @var string
	 */
	private $className;
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * create DI wrapper
	 * 
	 * @access pubic static
	 * @param  object $instance
	 * @return DI
	 */
	public static function make($instance)
	{
		return new DI($instance);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * constructor
	 * 
	 * @access private
	 * @param  object $instance
	 */
	private function __construct($instance)
	{
		$this->instance  = $instance;
		$this->className = get_class($instance);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Inject method detect and execute
	 * 
	 * @access public
	 * @return DI $this
	 */
	public function inject()
	{
		$ref = new ReflectionClass($this->instance);
		foreach ( $ref->getMethods() as $method )
		{
			// Process "inject" prefixed method only
			if ( strpos($method->name, 'inject') !== 0 )
			{
				continue;
			}
			
			$injections = array();
			foreach ( $method->getParameters() as $param )
			{
				// Get type-hinting classname
				$className = $param->getClass()->getName();
				if ( empty($className) )
				{
					continue;
				}
				
				// Does cache exists?
				if ( FALSE === ($inject = Seezoo::getClassCache($className)) )
				{
					if ( ! class_exists($className) )
					{
						throw new UndefinedClassException('Class ' . $className . ' is undefined.');
					}
					
					$inject = new $className();
					// If inject object implments Growable interface, extend it
					if ( $inject instanceof Growable )
					{
						$inject = call_user_func(array($inject, 'grow'));
					}
					Seezoo::setClassCache($className, $inject);
				}
				$injections[] = $inject;
			}
			// Execute injection
			$method->invokeArgs($this->instance, $injections);
		}
		
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Inject class
	 * 
	 * @access public
	 * @param  mixed $methodName
	 * @param  mixed $diInstance
	 * @return $this
	 */
	public function injectByAnnotation($methodName = '', $diInstance = NULL)
	{
		// If no argument supplied, inject from class annotation.
		if ( $methodName === '' )
		{
			$ref = new ReflectionClass($this->instance);
			$docc = $ref->getDocComment();
		}
		// Else, inject from class-method annotation.
		else
		{
			if ( ! method_exists($this->instance, $methodName) )
			{
				throw new LogicException($this->className . ' class doesn\'t have method:' . $methodName);
			}
			$ref = new ReflectionMethod($this->instance, $methodName);
			$docc = $ref->getDocComment();
		}
		
		$docs = $this->_parseAnnotation($docc);
		
		foreach ( array('model', 'library', 'helper', 'class') as $di )
		{
			if ( ! isset($docs['di:' . $di]) )
			{
				continue;
			}
			foreach ( explode(',', $docs['di:' . $di]) as $module )
			{
				$module = trim($module);
				$mod = lcfirst($module);
				if ( ! property_exists($this->instance, $mod) )
				{
					$this->instance->{$mod} = Seezoo::$Importer->{$di}($module);
				}
			}
		}
		
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse annotation comment
	 * 
	 * @access private
	 * @param  string $docc
	 * @return array
	 */
	private function _parseAnnotation($docc)
	{
		$ret  = array();
		if ( preg_match_all('/@(.+)/u', $docc, $matches, PREG_PATTERN_ORDER) )
		{
			foreach ( $matches[1] as $line )
			{
				list($key, $value) = explode(' ', trim($line), 2);
				$ret[$key] = $value;
			}
		}
		return array_change_key_case($ret, CASE_LOWER);
		
	}
}
