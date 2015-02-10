<?php

class Obj
{
	// 数据库名，对应config里的名字，须在子类中覆盖
	protected $db_name = '';
	// 数据库表名，须在子类中覆盖
	protected $db_table = '';
	// 数据库主键，须在子类中覆盖
	protected $db_primary_key = 'id';
	
	// 数据库操作对象（指向单例模式数据库对象）
	protected $db = null;
	// 字段们
	protected $attr = null;
	// 字段类型，标明非字符串类的即可。如 'date'=>'int'
	// 可选为 int, json（字符串转对象）, list（半角逗号分隔，转成数组）
	protected $attr_types = array();
	// insert、update操作时涉及到的字段
	protected $set = array();
	
	// 错误们
	public $error = array();
	
	
	
	function __construct($id, $primary_key)
	{
		if ($id !== null)
		{
			$this->load_db();
			$primary_key = !$primary_key ? $this->db_primary_key : $primary_key;
			if (method_exists($this, 'load'))
			{
				$this->load($id, $primary_key);
			}
		}
	}
	
	
	
	public function __set($key, $value)
	{
		$this->attr->{$key} = $value;
	}
	
	
	
	public function __get($key)
	{
		return $this->attr->{$key};
	}
	
	
	
	/**
	 * 验证字段
	 *
	 * @version 2013-12-26
	 *
	 * @param array $check_functions 格式 'check_method'=>array($value1, $value2, ...)
	 * @param boolean $skip_empty 是否检查未赋值（null）字段
	 * @return boolean 是否通过验证（不通过时，错误信息存在$this->error里
	 */
	protected function check_set($check_functions = array(), $skip_empty = true)
	{
		$pass = true;
		foreach ($check_functions as $method => $values)
		{
			if ($skip_empty == true and $check_functions[$method][0] === null)
			{
				continue;
			}
			$result = call_user_func_array(array($this, $method), $values);
			if ($result['status'] == false)
			{
				$pass = false;
				$this->error[] = $result['msg'];
			}
		}
		return $pass;
	}
	
	
	/**
	 * 写入数据库
	 *
	 * @version 2014-02-18
	 *
	 * @return boolean|int 新id
	 */
	protected function db_insert()
	{
		$this->load_db();
		foreach ($this->set as $key => $value)
		{
			$value = $this->fix_setted_attr($key, $value);
			$this->db->set($key, $value);
		}
		$id = $this->db->table($this->db_table)->insert();
		
		if ($id)
		{
			// 更新字段
			$this->set('id', $id);
			$this->update_attr();
			$this->reset_error();
			return $id;
		}
		else
		{
			$this->error[] = $this->db->msg;
			return false;
		}
	}
	
	
	/**
	 * 更新数据库
	 *
	 * @version 2014-02-18
	 *
	 * @return boolean
	 */
	protected function db_update()
	{
		$this->load_db();
		foreach ($this->set as $key => $value)
		{
			$value = $this->fix_setted_attr($key, $value);
			$this->db->set($key, $value);
		}
		$result = $this->db->table($this->db_table)->where($this->db_primary_key, $this->id)->limit(1)->update();
		
		if ($result)
		{
			// 更新字段
			$this->update_attr();
			$this->reset_error();
			return true;
		}
		else
		{
			return false;
		}
	}
	
	
	/**
	 * 修正字段类型
	 * 已转换过的字段不会被重复转换
	 *
	 * @version 2013-12-26
	 *
	 * @return object
	 */
	public function fix_attr()
	{
		foreach ($this->attr as $key => $value)
		{
			$type = $this->attr_types[$key];
			switch ($type)
			{
				case 'int':
					$value = intval($value);
					break;
				case 'list':
					if (is_string($value))
					{
						$value = explode(',', $value);
					}
					break;
				case 'json':
					if (is_string($value))
					{
						$value = json_decode($value);
					}
					break;
			}
			$this->attr->{$key} = $value;
		}
		return $this;
	}
	
	
	
	/**
	 * 若$value为非字符串，且对应的$key设有$attr_types，那么$value会被自动转成对应的类型，以便写入数据库
	 *
	 * @version 2013-12-26
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return string
	 */
	public function fix_setted_attr($key, $value)
	{
		$type = $this->attr_types[$key];
		if (!$type)
		{
			return $value;
		}
		switch ($type)
		{
			case 'int':
				$value = $value.'';
				break;
			case 'list':
				if (is_array($value))
				{
					$value = implode(',', $value);
				}
				break;
			case 'json':
				if (is_object($value) or is_array($value))
				{
					$value = json_encode($value);
				}
				break;
		}
		return $value;
	}
	
	
	
	/**
	 * 获取所有属性值
	 *
	 * @veresion 2013-12-25
	 *
	 * @return object
	 */
	public function get()
	{
		return clone $this->attr;
	}
	
	
	
	/**
	 * 判断是否有错误
	 *
	 * @version 2013-12-26
	 *
	 * @return boolean
	 */
	public function has_error()
	{
		if ($this->error)
		{
			return true;
		}
		return false;
	}
	
	
	
	/**
	 * 装载字段
	 *
	 * @version 2013-12-26
	 *
	 * @param object $attr
	 * @return object
	 */
	public function install($attr)
	{
		$this->attr = Obj::arr2obj($attr);
		$this->fix_attr();
		return $this;
	}
	
	
	
	/**
	 * 加载对象
	 *
	 * @version 2013-12-26
	 *
	 * @param string $id
	 * @param string $primary_key
	 * @return object
	 */
	public function load($id, $primary_key = 'id')
	{
		$this->load_db();
		$attr = $this->db->from($this->db_table)->where($primary_key, $id)->limit(1)->get_one();
		if (!$attr)
		{
			$this->error[] = '找不到记录。';
			return $this;
		}
		$this->install($attr);
		return $this;
	}
	
	
	
	public function load_db()
	{
		if ($this->db === null)
		{
			$this->db = Database::get_mysql($this->db_name);
		}
	}
	
	
	/**
	 * 重置错误信息
	 *
	 * @version 2013-12-26
	 *
	 * @return object
	 */
	public function reset_error()
	{
		$this->error = array();
		return $this;
	}
	
	
	
	/**
	 * 设置字段，用于add、update
	 *
	 * @version 2013-12-26
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return object
	 */
	public function set($key, $value)
	{
		$this->set[$key] = $value;
		return $this;
	}
	
	
	
	/**
	 * 从$this->set中更新字段
	 *
	 * @version 2013-12-26
	 *
	 * @return object
	 */
	public function update_attr()
	{
		foreach ($this->set as $key => $value)
		{
			$this->attr->{$key} = $value;
		}
		$this->fix_attr();
		$this->set = array();
		return $this;
	}
	
	
	
	/**
	 * 数组转对象
	 *
	 * @version 2014-01-10
	 *
	 * @param array $arr
	 * @return object
	 */
	static public function arr2obj($arr)
	{
		if (!is_array($arr))
		{
			return $arr;
		}
		$obj = new stdClass();
		foreach ($arr as $key => $value)
		{
			if (is_array($value))
			{
				$value = Obj::arr2obj($value);
			}
			$obj->{$key} = $value;
		}
		return $obj;
	}
	

} // END class Obj

/* END */