<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * (like)ActiveRecord base class
 * 
 * @package  Seezoo-Framework
 * @category Classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

// Export non-prefixed syntax class if not exists
if ( ! class_exists('ActiveRecord') )
{
	class ActiveRecord extends SZ_ActiveRecord {}
}

class SZ_ActiveRecord
{
	/**
	 * Stack AR instances
	 * @var array
	 */
	private static $_instances = array();
	
	/**
	 * ActiveRecord class works mode
	 * @var boolean
	 */
	protected $_isFinderMode = FALSE;
	
	/**
	 * Table schema info
	 * @var array
	 */
	protected $_schemas = array();
	
	protected $_joins   = array();
	protected $_extraKeys = array();
	
	/**
	 * Select query statements
	 */
	protected $_limit    = 0;
	protected $_offset   = 0;
	protected $_orderBy  = array();
	protected $_distinct = '';
	
	
	/**
	 * Return instance on finder mode
	 * 
	 * @access public static
	 * @param  string $arName
	 * @return object ActiveRecord Instance(cloned)
	 */
	public static function finder($arName)
	{
		// inline to camel case
		$arName = preg_replace_callback(
		           '/_([a-zA-Z])/',
		           create_function('$m', 'return strtoupper($m[1]);'),
		           $arName
		          );
		$arName = ucfirst($arName);
		
		if ( ! isset(self::$_instances[$arName]) )
		{
			self::$_instances[$arName] = Seezoo::$Importer->activeRecord($arName);
		}
		return clone self::$_instances[$arName];
	}
	
	
	/**
	 * Create new record instance
	 * 
	 * @access public static
	 * @param  string $arName
	 * @return object ActiveRecord Instance
	 */
	public static function create($arName)
	{
		// inline to camel case
		$arName = preg_replace_callback(
		           '/_([a-zA-Z])/',
		           create_function('$m', 'return strtoupper($m[1]);'),
		           $arName
		          );
		$arName = ucfirst($arName);
		
		if ( ! isset(self::$_instances[$arName]) )
		{
			self::$_instances[$arName] = Seezoo::$Importer->activeRecord($arName);
		}
		return self::$_instances[$arName];
	}
	
	
	// =============== overload methods ==========================
	
	public function __set($field, $value)
	{
		if ( $this->_isFinderMode === TRUE )
		{
			throw new InvalidArgumentException(get_class($this) . ' works Finder mode! Property cannot set.');
		}
		$this->{$field} = $value;
	}
	
	public function __clone()
	{
		// lock instance insert/update/delete...
		$this->_isFinderMode = TRUE;
		$this->reset();
	}
	
	public function __call($method, $args)
	{
		if ( preg_match('/^find(.*)$/', $method) )
		{
			if ( $this->_isFinderMode === FALSE )
			{
				throw new BadMethodCallException('Instance is not finder mode.');
			}
			list($columns, $conditions, $limit) = $this->_parseMethod(substr($method, 4), $args);
			
			$query  = $this->_execFindQuery($columns, $conditions, $limit)->get();
			$this->reset();
			if ( $limit === 1 )
			{
				$query->setFetchMode(PDO::FETCH_CLASS, get_class($this));
				return $query->fetch(PDO::FETCH_CLASS, PDO::FETCH_ORI_ABS, 0);
			}
			return $query->fetchAll(PDO::FETCH_CLASS, get_class($this));
		}
	}
	
	// =============== /overload methods =========================
	
	public function leftJoin($table, $key)
	{
		$this->_join('LEFT', $table, $key);
		return $this;
	}

	public function leftOuterJoin($table, $key)
	{
		$this->_join('LEFT OUTER', $table, $key);
		return $this;
	}

	public function rightJoin($table, $key)
	{
		$this->_join('RIGHT', $table, $key);
		return $this;
	}

	public function rightOuterJoin($table, $key)
	{
		$this->_join('RIGHT OUTER', $table, $key);
		return $this;
	}

	public function innerJoin($table)
	{
		$this->_join('INNER', $table);
		return $this;
	}
	
	protected function _join($type, $table, $key = FALSE)
	{
		$this->_joins[] = array($type, $table, $key);
		if ( $key )
		{
			foreach ( (array)$key as $k )
			{
				if ( ! in_array($k, $this->_extraKeys) )
				{
					$this->_extraKeys[] = $k;
				}
			}
		}
	}
	
	public function getTable()
	{
		return $this->_table;
	}
	
	/**
	 * Set limit statement
	 * 
	 * @access public
	 * @param  int $limit
	 */
	public function setLimit($limit)
	{
		$this->_limit = (int)$limit;
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set offset Statement
	 * 
	 * @access public
	 * @param  int $offset
	 */
	public function setOffset($offset)
	{
		$this->_offset = (int)$offset;
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set order by statement
	 * 
	 * @access public
	 * @param  string $column
	 * @param  string $order
	 */
	public function setOrderBy($column, $order)
	{
		$this->_orderBy[] = $column . ' ' . $order;
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set distinct
	 * 
	 * @access public
	 */
	public function distinct()
	{
		$this->_distinct = 'DISTINCT ';
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Reset stattements
	 * 
	 * @access public
	 */
	public function reset()
	{
		$this->_limit     = 0;
		$this->_offset    = 0;
		$this->_orderBy   = array();
		$this->_distinct  = '';
		$this->_joins     = array();
		$this->_extraKeys = array();
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Insert record
	 * 
	 * @access public
	 */
	public function insert()
	{
		if ( $this->_isFinderMode === TRUE )
		{
			throw new BadMethodCallException(get_class($this) . ' works Finder mode! Cannot be execute ' . get_class($this) . '::insert() method.');
		}
		$dataSet = $this->validate();
		$db      = Seezoo::$Importer->database();
		
		if ( $db->insert($this->_table, $dataSet) )
		{
			return $db->insertID();
		}
		return FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Update record
	 * 
	 * @access public
	 */
	public function update()
	{
		if ( $this->_isFinderMode === TRUE )
		{
			throw new BadMethodCallException(
			  get_class($this)
			  . ' works Finder mode! Cannot be execute '
			  . get_class($this)
			  . '::update() method.'
			);
		}
		$dataSet = $this->validate();
		$db      = Seezoo::$Importer->database();
		
		if ( $db->update($this->_table, $dataSet, array($this->_primary => $dataSet[$this->_primary])) )
		{
			return TRUE;
		}
		return FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * insert or update record
	 * 
	 * @access public
	 */
	public function save()
	{
		if ( ! empty($this->_primary) && isset($this->{$this->_primary}) )
		{
			return $this->update();
		}
		return $this->insert();
	}
	
	
	// ---------------------------------------------------------------
	
	
	public function delete()
	{
		if ( $this->_isFinderMode === TRUE )
		{
			throw new BadMethodCallException(
			  get_class($this)
			  . ' works Finder mode! Cannot be execute '
			  . get_class($this)
			  . '::delete() method.'
			);
		}
		if ( ! isset($this->{$this->_primary}) )
		{
			throw new LogicException('Delete method requires primary key record');
		}
		$dataSet = $this->validate();
		$db      = Seezoo::$Importer->database();
		
		if ( $db->update($this->_table, $this->_primary . ' = ' . $this->{$this->_primary}) )
		{
			return TRUE;
		}
		return FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Validate record properties before insert/update
	 * 
	 * @access public
	 * @return array
	 */
	public function validate()
	{
		// Implement subclass method if you need
		if ( ! $this->_schemas )
		{
			throw new LogicException('ActiveRecord schema is not defined.');
		}
		
		$dataSet = array();
		foreach ( $this->_schemas as $field => $schema )
		{
			if ( ! isset($this->{$field}) )
			{
				continue;
			}
			$Field = $this->_toCamelCase($field);
			$value = (string)$this->{$field};
			if ( method_exists($this, 'isValid' . $Field) )
			{
				if ( FALSE === $this->{'isValid' . $Field}($value) )
				{
					throw new UnexpectedValueException($field . ' value is invalid!');
				}
			}
			$dataSet[$field] = $this->{$field};
		}
		return $dataSet;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Under-scored string to camel-case
	 * 
	 * @access protected
	 * @param  string $str
	 * @return string
	 */
	protected function _toCamelCase($str)
	{
		$str = preg_replace_callback(
								'/_([a-zA-Z])/',
								create_function('$m', 'return strtoupper($m[1]);'),
								$str
							);
		return ucfirst($str);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse find condition from called method string
	 * 
	 * @access protected
	 * @param  string $method
	 * @param  array $args
	 * @return array
	 */
	protected function _parseMethod($method, $args)
	{
		if ( substr($method, 0, 3) === 'All' )
		{
			$method = substr($method, 3);
			$limit  = $this->_limit;
		}
		else
		{
			$limit = 1;
		}
		
		if ( empty($method) )
		{
			return array(array(), array(), $limit);
		}
		
		$method = preg_replace_callback(
						'/([A-Z])/',
						create_function('$match', 'return "_" . strtolower($match[1]);'),
						$method
					);
		$conditions  = array();
		$i           = -1;
		$argsPointer = 0;
		$exp         = explode('_', trim($method, '_'));
		$cond        = '';
		while ( isset($exp[++$i]) )
		{
			switch ( $exp[$i] )
			{
				case 'by':
					if ( $i === 0 )
					{
						$cond = '';
					}
					else
					{
						$cond .= '_' . $exp[$i];
					}
					break;
				case 'and':
					$conditions[substr($cond, 1)] = $args[$argsPointer++];
					$cond = '';
					break;
				default:
					$cond .= '_' . $exp[$i];
			}
		}
		$conditions[substr($cond, 1)] = $args[$argsPointer++];
		$columns = ( isset($args[$argsPointer]) ) ? (array)$args[$argsPointer] : array();
		
		return array($columns, $conditions, $limit);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Execute select query
	 * 
	 * @access protected
	 * @param  array $columns
	 * @param  array $conditions
	 * @param  int $limit
	 * @return mixed
	 */
	protected function _execFindQuery($columns, $conditions, $limit  = 1)
	{
		$db           = Seezoo::$Importer->database();
		$primaryTable = $db->prefix() . $this->_table;
		$selectColumn = ( count($columns) > 0 ) ? implode(', ', $columns) : '*';
		$bindData     = array();
		$columns      = ( count($columns) === 0 ) ? array('*') : explode(',', $columns);
		
		foreach ( $columns as $key => $col )
		{
			$col = $db->prepColumn($col);
			$columns[$key] = ( in_array($col, $this->_schemas) )
			                   ? $primaryTable . '.' . $col
			                   : $col;
		}
		
		foreach ( $this->_extraKeys as $exKey )
		{
			$selectColumn .= ', ' . $primaryTable . '.' . $exKey;
		}
		
		$sql =
				'SELECT ' . $this->_distinct
				. $selectColumn . ' '
				.'FROM '
				. $db->prefix() . $this->_table . ' ';
		
		foreach ( $this->_joins as $join )
		{
			list($joinMode, $joinTable, $joinKey) = $join;
			$sql .= $joinMode . ' JOIN ' . $db->prefix().$joinTable . ' ';
			if ( $joinKey )
			{
				$sql .= ' ON ( ';
				$joinStack = array();
				foreach ( (array)$joinKey as $k )
				{
					$k = $db->prepColumn($k);
					$joinStack[] = "{$primaryTable}.{$k} = {$joinTable}.{$k}"; 
				}
				$sql .= implode(' AND ', $joinStack) . ' ) ';
			}
		}
		
		if ( count($conditions) > 0 )
		{
			$where = array();
			foreach ( $conditions as $col => $val )
			{
				if ( in_array($col, $this->_extraKeys) )
				{
					$col = $primaryTable . '.' . $col;
				}
				$stb = $db->buildOperatorStatement($col, $val);
				if ( is_array($stb) )
				{
					$where[] = $stb[0];
					if ( is_array($stb[1]) )
					{
						foreach ( $stb[1] as $bind )
						{
							$bindData[] = $bind;
						}
					}
					else
					{
						$bindData[] = $stb[1];
					}
				}
				else
				{
					$where[] = $stb;
				}
			}
			$sql .= 'WHERE ' . implode(' AND ', $where) . ' ';
		}
		if ( count($this->_orderBy) > 0 )
		{
			$sql .= 'ORDER BY ' . implode(', ', $this->_orderBy) . ' ';
		}
		if ( $limit > 0 )
		{
			$sql .= 'LIMIT ' . $limit . ' ';
		}
		if ( $this->_offset > 0 )
		{
			$sql .= 'OFFSET ' . $this->_offset;
		}
		return $db->query($sql, ( count($bindData) > 0 ) ? $bindData : FALSE);
	}
}
