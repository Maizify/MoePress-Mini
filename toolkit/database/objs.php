<?php

class Objs implements Iterator
{
	// 数据库名，对应config里的名字，须在子类中覆盖
	protected $db_name = '';
	// 数据库表名，须在子类中覆盖
	protected $db_table = '';
	// 数据库主键，须在子类中覆盖
	protected $db_primary_key = 'id';
	// 单个对象类名，须在子类中覆盖
	protected $child_class = '';

	// 数据库操作对象（指向单例模式数据库对象）
	protected $db = null;
	
	// 对象们
	protected $rows = array();
	
	// 错误们
	public $error = array();
	
	
	
	function __construct($docs = null)
	{
		if ($docs !== null)
		{
			$this->install($docs);
		}
		else
		{
			$this->load_db();
		}
	}
	
	
	
	public function load_db()
	{
		if ($this->db === null)
		{
			$this->db = Database::get_mysql($this->db_name);
		}
	}
	
	
	
	/**
	 * 加载对象们
	 *
	 * @version 2014-04-07
	 *
	 * @return object
	 */
	public function load()
	{
		$this->load_db();
		$rows = $this->db->from($this->db_table)->get();
		if (!$rows)
		{
			$this->error[] = '找不到记录。';
			return $this;
		}
		$this->install($rows);
		return $this;
	}
	
	
	
	/**
	 * 获取列表
	 *
	 * @veresion 2014-04-07
	 *
	 * @return object
	 */
	public function get()
	{
		return $this->rows;
	}
	
	
	
	/**
	 * 获取指定属性的值的集合
	 *
	 * @veresion 2014-07-11
	 *
	 * @return object
	 */
	public function get_values($key)
	{
		$valeus = array();
		foreach ($this->rows as $k => $row)
		{
			$values[$k] = $row->{$key};
		}
		return $values;
	}
	
	
	
	/**
	 * 根据键名和键值查找首个符合条件的对象
	 *
	 * @veresion 2014-07-11
	 *
	 * @return object|null
	 */
	public function find_one($key, $value)
	{
		foreach ($this->rows as $k => $row)
		{
			if ($row->{$key} == $value)
			{
				return $row;
			}
		}
		return null;
	}
	
	
	
	/**
	 * 根据键名和键值查找所有符合条件的对象
	 *
	 * @veresion 2014-07-11
	 *
	 * @return array
	 */
	public function find_all($key, $value)
	{
		$rows = array();
		foreach ($this->rows as $k => $row)
		{
			if ($row->{$key} == $value)
			{
				$rows[] = $row;
			}
		}
		return $rows;
	}
	
	
	
	/**
	 * 装载列表
	 *
	 * @version 2014-01-10
	 *
	 * @param array $docs
	 * @return object
	 */
	public function install($docs)
	{
		$class = $this->child_class;
		$obj = new $class();
		foreach ($docs as $doc)
		{
			$this->rows[] = $obj->install($doc)->get();
		}
		unset($obj);
		return $this;
	}
	
	
	
	/**
	 * Database functions
	 */
	public function _where($logic, $key, $sign, $value, $filter = true)
	{
		// e.g.: $this->_where('AND', $key, '=', $value, $filter);
		$this->db->_where($logic, $key, $sign, $value);
		return $this;
	}
	public function where($key, $value, $filter = true)
	{
		$this->db->where($key, $value, $filter);
		return $this;
	}
	public function where_in($key, $value, $filter = true)
	{
		$this->db->where_in($key, $value, $filter);
		return $this;
	}
	public function or_where($key, $value, $filter = true)
	{
		$this->db->or_where($key, $value, $filter);
		return $this;
	}
	public function page($curpage, $perpage)
	{
		$this->db->page($curpage, $perpage);
		return $this;
	}
	public function having($key, $sign, $value)
	{
		$this->db->having($key, $sign, $value);
		return $this;
	}
	public function group_by($key)
	{
		$this->db->group_by($key);
		return $this;
	}
	public function order_by($key, $order = 'DESC')
	{
		$this->db->order_by($key, $order);
		return $this;
	}
	
	
	
	/**
	 * Iteration functions
	 */
	public function rewind()
	{
		reset($this->rows);
	}
	public function current()
	{
		$row = current($this->rows);
		return $row;
	}
	public function key()
	{
		$key = key($this->rows);
		return $key;
	}
	public function next()
	{
		$row = next($this->rows);
		return $row;
	}
	public function valid()
	{
		$key = key($this->rows);
		$var = ($key !== null and $key !== false);
		return $var;
	}
	
	
} // END class Objs

/* END */