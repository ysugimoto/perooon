<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * Class test base class
 * 
 * @package  Seezoo-Framework
 * @category Test
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_ClassTest extends PHPUnit_Framework_TestCase
{
	public $class;
	
	public function __construct()
	{
		$targetClass = preg_replace('/(.+)Test$/', '$1', get_class($this));
		$this->class = Seezoo::$Importer->classes($targetClass);
	}
}
