<?php
/**
 * MySQL ORM Library
 *
 * @created 2013-03-02
 * @version 2014-10-07
 * @author Maiz
 *
 * @usage 

	// include this class in the beginning of your project,
	// so you can use $mysql globally
	require_once('path/to/your/library/mysql.php');
	$mysql = new MySQL('demo_database', 'username', 'password', 'localhost');
	
	
	// execute a SQL and output the result
	$sql = 'SELECT `id`,`title` FROM `post` WHERE `category`="news" ORDER BY `id` DESC LIMIT 10;';
	$result = $mysql->query($sql);
	// each row is an array
	foreach ($result as $post) {
		echo $post['id'].': '.$post['title'];
	}
	
	// OR, you can get the same result in this way:
	$result = $mysql->select(array('id', 'title'))->from('post')->where('category', 'news')
					->order_by('id', 'DESC')->limit(10)->get();
	
	
	// insert a record to table 'post' based on array
	$new = array(
		'id' => 123,
		'title' => 'I am a demo'
	);
	$id = $mysql->add($new, 'post');
	
	// OR, you can do it in this way:
	$id = $mysql->table('post')->set('id', 123)->set('title', 'I am a demo')->insert();
	
	
	// update record(s):
	$mysql->table('post')->set('title', 'I am new title')->where('id', 123)->limit(1)->update();
	
	
	// delete record(s):
	$mysql->from('post)->where('id', 123)->delete();

 *
 */
class MySQL
{
	protected $hostname;
	protected $username;
	protected $password;
	protected $database;
	protected $pconnect;
	protected $charset;
	protected $conn;
	
	public $msg = '';
	public $query = '';
	public $num_rows = 0;
	public $affected_rows = 0;
	public $insert_id = 0;
	
	protected $_select = array();
	protected $_distinct = false;
	protected $_from = array();
	protected $_set = array(); // 'key'=>'value'
	protected $_where = array(); // array('key'=>$k, 'value'=>$v, 'sign'=>'=', 'logic'=>'AND')
	protected $_offset = 0;
	protected $_limit = 0;
	protected $_orderby = array(); // 'key'=>'DESC|ASC'
	protected $_having = array();
	protected $_groupby = array();
	
	
	/**
	 * Class construction
	 *
	 * @version 2013-03-13
	 */
	function __construct($database, $username, $password, $hostname, $pconnect = true, $charset = 'utf8')
	{
		$this->hostname = $hostname;
		$this->username = $username;
		$this->password = $password;
		$this->database = $database;
		$this->pconnect = $pconnect;
		$this->charset = $charset;
		
		$this->connect();
	}
	
	
	
	/**
	 * Class desctruction
	 *
	 * @version 2013-03-02
	 */
	function __destruct()
	{
		$this->close();
	}
	
	
	/**
	 * Connect to mysql
	 *
	 * @version 2013-12-24
	 *
	 * @return boolean
	 */
	public function connect()
	{
		if ($this->conn) {
			mysqli_close($this->conn);
		}
		
		$this->conn = mysqli_connect($this->hostname, $this->username, $this->password, $this->database);
		
		if (mysqli_connect_error()) {
			$this->msg = 'Cannot connect to server: (#' . mysqli_connect_errno() .') ' . mysqli_connect_error();
			return false;
		}
		
		//mysqli_query('set names "'.$this->charset.'";');
		if (!mysqli_set_charset($this->conn, $this->charset)) {
			$this->msg = 'Cannot set charset: '.mysqli_error($this->conn);
			return false;
		}
		return true;
	}
	
	
	/**
	 * Connect to mysql (Old Version)
	 *
	 * @version 2013-03-13
	 *
	 * @return boolean
	 */
	public function connect_old()
	{
		if ($this->conn) {
			mysql_close($this->conn);
		}
		
		if ($this->pconnect==true) {
			$this->conn = mysql_pconnect($this->hostname, $this->username, $this->password);
		} else {
			$this->conn = mysql_connect($this->hostname, $this->username, $this->password);
		}
		
		if (!$this->conn) {
			$this->msg = 'Cannot connect to server: ' . mysql_error($this->conn);
			return false;
		}
		
		if (!$this->select_db($this->database)) {
			$this->msg = 'Cannot connect to database: ' . mysql_error($this->conn);
			return false;
		}
		
		mysql_query('set names "'.$this->charset.'";');
		return true;
	}
	
	
	
	/**
	 * Close mysql
	 *
	 * @version 2013-12-24
	 *
	 * @return boolean
	 */
	public function close()
	{
		return mysqli_close($this->conn);
	}
	
	
	
	/**
	 * Select a database
	 *
	 * @version 2013-12-24
	 *
	 * @param string $database
	 * @return boolean
	 */
	public function select_db($database)
	{
		$this->database = $database;
		if (mysqli_select_db($this->conn, $database)) {
			return true;
		} else {
			$this->msg = 'Cannot select database: ' . mysqli_error($this->conn);;
			return false;
		}
	}
	
	
	
	/**
	 * Ping MySQL to prevent 'MySQL server has gone away'
	 *
	 * @version 2013-12-24
	 *
	 * @return object
	 */
	public function ping()
	{
		if (!mysqli_ping($this->conn)) {
			$this->close();
			$this->connect();
		}
		return $this;
	}
	
	
	
	/**
	 * Execute a query
	 *
	 * @version 2013-12-24
	 *
	 * @param string $sql
	 * @return boolean|array
	 */
	public function query($sql)
	{
		$this->sql = $sql;
		$this->result = mysqli_query($this->conn, $sql);
		$this->reset();
		if ($this->result) {
			$this->num_rows = @mysqli_num_rows($this->result);
			$this->affected_rows = @mysqli_affected_rows($this->conn);//v($this->num_rows);
			if ($this->num_rows > 0) {
				return $this->results2array();
			} elseif (preg_match('/^select/i', trim($sql))) {
				return null;
			} else {
				return true;
			}
		} else {
			$this->msg = mysqli_error($this->conn);
			return false;
		}
	}
	
	
	
	/**
	 * Insert a record based on the array key names
	 *
	 * @version 2013-12-24
	 *
	 * @param array $data the record to be inserted
	 * @param string $table table name
	 * @return int the id of new record
	 */
	public function add($data, $table)
	{
		if (!$data) {
			$this->msg = 'The data to be inserted does not exist.';
			return false;
		}
		$data = $this->escape_value($data);
		$set = array();
		foreach ($data as $key => $value)
		{
			$set[] = '`'.$key.'`="'.$value.'"';
		}
		$sql = 'INSERT INTO `'.$table.'` SET '.implode(', ', $set).';';
		if ($this->query($sql)) {
			$this->insert_id = mysqli_insert_id($this->conn);
			return $this->affected_rows;
		} else {
			return 0;
		}
	}
	
	
	
	/**
	 * Get record count
	 *
	 * @version 2014-10-07
	 *
	 * @return int
	 */
	public function count()
	{
		$sql = $this->compile_count_sql();
		$this->query($sql);
		$count = intval($this->result[0]['__count']);
		return $count;
	}
	
	
	
	/**
	 * Get records
	 *
	 * @version 2013-03-03
	 *
	 * @return array
	 */
	public function get()
	{
		$sql = $this->compile_select_sql();
		return $this->query($sql);
	}
	
	
	
	/**
	 * Get the first row from the result records
	 *
	 * @version 2013-03-03
	 *
	 * @return array
	 */
	public function get_one()
	{
		if ($this->get()) {
			return $this->result[0];
		} else {
			return false;
		}
	}
	
	
	
	/**
	 * Update records
	 *
	 * @version 2013-03-03
	 *
	 * @return array
	 */
	public function update()
	{
		$sql = $this->compile_update_sql();
		return $this->query($sql);
	}
	
	
	
	/**
	 * Insert a record
	 *
	 * @version 2014-02-16
	 *
	 * @return int the id of new record
	 */
	public function insert()
	{
		$sql = $this->compile_insert_sql();
		$result = $this->query($sql);
		if ($result) {
			$this->insert_id = mysqli_insert_id($this->conn);
			return $this->insert_id;
		} else {
			return 0;
		}
	}
	
	
	
	/**
	 * Delete records
	 *
	 * @version 2013-03-03
	 *
	 * @return boolean
	 */
	public function delete()
	{
		$sql = $this->compile_delete_sql();
		$result = $this->query($sql);
		return $result;
	}
	
	
	
	/**
	 * Set selected columns
	 *
	 * @version 2013-03-03
	 *
	 * @param string|array $select the colunm(s) to be selected
	 * @param string $as column alias
	 * @return object
	 */
	public function select($select, $as)
	{
		if (!is_array($select)) {
			$this->_select[] = '`'.$select.'`'.($as ? ' AS '.$as : '');
		} else {
			$this->_select = array_merge($this->_select, $select);
		}
		return $this;
	}
	
	
	
	/**
	 * Set distinct
	 *
	 * @version 2013-03-02
	 *
	 * @param boolean $value
	 * @return object
	 */
	public function distinct($value = true)
	{
		$this->_distinct = $value;
		return $this;
	}
	
	
	
	/**
	 * Select table
	 *
	 * @version 2013-03-02
	 *
	 * @param string $from table name
	 * @param string $as table alias
	 * @return object
	 */
	public function from($from, $as = '')
	{
		$this->_from[] = '`'.$from.'`' . ($as ? ' AS '.$as : '');
		return $this;
	}
	
	
	
	/**
	 * Select table
	 * (this function is an alias of method 'from()')
	 *
	 * @version 2013-03-03
	 *
	 * @param string $from table name
	 * @param string $as table alias
	 * @return object
	 */
	public function table($from, $as)
	{
		return $this->from($from, $as);
	}
	
	
	
	/**
	 * Set update/insert column
	 *
	 * @version 2013-03-03
	 *
	 * @param string|array $key
	 * @param string $value
	 * @return object
	 */
	function set($key, $value)
	{
		if (!is_array($key)) {
			$value = $this->escape_value($value);
			$this->_set['`'.$key.'`'] = $value;
		} else {
			foreach ($key as $k => $v)
			{
				$this->_set['`'.$k.'`'] = $this->escape_value($v);
			}
		}
		return $this;
	}
	
	
	
	/**
	 * Set where query
	 *
	 * @version 2013-03-02
	 *
	 * @param string $logic such as 'AND', 'OR'
	 * @param string $key column name
	 * @param string $sign such as '=', '>', 'LIKE'
	 * @param string|array $value column value
	 * @param boolean $filter 
	 * @return object
	 */
	public function _where($logic, $key, $sign, $value, $filter = true)
	{
		$value = $this->escape_value($value);
		if ($filter == true) {
			if (is_array($value)) {
				foreach ($value as $k => $v)
				{
					$value[$k] = '"'.$v.'"';
				}
			} else {
				$value = '"'.$value.'"';
			}
		}
		$this->_where[] = array(
			'key' => $key,
			'value' => $value,
			'sign' => $sign,
			'logic' => $logic
		);
		return $this;
	}
	
	
	
	
	/**
	 * Set where 'equal' query with 'AND'
	 *
	 * @version 2013-03-02
	 *
	 * @param string $key
	 * @param string $value
	 * @return object
	 */
	public function where($key, $value, $filter = true)
	{
		return $this->_where('AND', $key, '=', $value, $filter);
	}
	
	
	
	/**
	 * Set where 'equal' query with 'OR'
	 *
	 * @version 2013-03-02
	 *
	 * @param string $key
	 * @parm string $value
	 * @return object
	 */
	public function or_where($key, $value, $filter = true)
	{
		return $this->_where('OR', $key, '=', $value, $filter);
	}
	
	
	
	
	/**
	 * Set where 'LIKE' query with 'AND'
	 *
	 * @version 2013-03-02
	 *
	 * @param string $key
	 * @param string $value
	 * @return object
	 */
	public function where_like($key, $value, $filter = true)
	{
		return $this->_where('AND', $key, 'LIKE', $value, $filter);
	}
	
	
	
	/**
	 * Set where 'IN' query with 'AND'
	 *
	 * @version 2013-03-02
	 *
	 * @param string $key
	 * @param array $values
	 * @return object
	 */
	public function where_in($key, $values, $filter = true)
	{
		return $this->_where('AND', $key, 'IN', $values, $filter);
	}
	
	
	
	/**
	 * Set where 'NOT IN' query with 'AND'
	 *
	 * @version 2013-03-02
	 *
	 * @param string $key
	 * @param array $values
	 * @return object
	 */
	public function where_not_in($key, $values, $filter = true)
	{
		return $this->_where('AND', $key, 'NOT IN', $values, $filter);
	}
	
	
	
	/**
	 * Set offset number
	 *
	 * @version 2013-03-02
	 *
	 * @param int $value
	 * @return object
	 */
	public function offset($value)
	{
		$this->_offset = $value;
		return $this;
	}
	
	
	
	/**
	 * Set limit number
	 *
	 * @version 2013-03-02
	 *
	 * @param int $value
	 * @return object
	 */
	public function limit($value)
	{
		$this->_limit = $value;
		return $this;
	}
	
	
	
	/**
	 * Set paging offset and limit number
	 *
	 * @version 2013-03-02
	 *
	 * @param int $page the number of current page
	 * @param int $perpage the number of records in a page
	 * @return object
	 */
	public function page($page, $perpage)
	{
		$this->_offset = ($page-1)*$perpage;
		$this->_limit = $perpage;
		return $this;
	}
	
	
	
	/**
	 * Set group by query
	 *
	 * @version 2013-03-02
	 *
	 * @param string|array $key the columns to be grouped by
	 * @return object
	 */
	public function group_by($key)
	{
		if (is_array($key)) {
			$this->_groupby = array_merge($this->_groupby, $key);
		} else {
			$this->_groupby[] = $key;
		}
		return $this;
	}
	
	
	
	/**
	 * Set having query
	 *
	 * @version 2013-03-02
	 *
	 * @param string $key column name
	 * @param string $sign such as '>', '<='
	 * @param string $value
	 * @return object
	 */
	public function having($key, $sign, $value)
	{
		$this->_having[] = array(
			'key' => $key,
			'value' => $value,
			'sign' => $sign
		);
		return $this;
	}
	
	
	
	/**
	 * Set order by query
	 *
	 * @version 2013-03-02
	 *
	 * @param string $key the column to be ordered by
	 * @param string $order sortorder, 'DESC' or 'ASC' 
	 * @return object
	 */
	public function order_by($key, $order = 'DESC')
	{
		$this->_orderby[$key] = $order;
		return $this;
	}
	
	
	
	/**
	 * Compile SQL based on query factors
	 *
	 * @version 2014-10-07
	 *
	 * @return string
	 */
	protected function compile_count_sql()
	{
		// select
		$sql = 'SELECT COUNT(1) AS __count ';
		
		// from
		$sql .= 'FROM '.implode(', ', $this->_from).' ';
		
		// where
		$sql .= $this->compile_where_sql();
		
		// group by
		if ($this->_groupby) {
			$sql .= 'GROUP BY '.implode(', ', $this->_groupby);
		}
		
		// having
		if ($this->_having) {
			$having = array();
			foreach ($this->_having as $value)
			{
				$having[] = $value['key'].' '.strtoupper($value['sign']).' '.$value['value'];
			}
			$sql .= 'HAVING '.implode(' AND ', $having);
		}
		
		$sql .= ';';
		return $sql;
	}
	
	
	
	/**
	 * Compile SQL based on query factors
	 *
	 * @version 2013-03-02
	 *
	 * @return string
	 */
	protected function compile_select_sql()
	{
		// distinct
		$sql = 'SELECT '.($this->_distinct?'DISTINCT ':'');
		
		// select
		if (!$this->_select) {
			$sql .= '* ';
		} else {
			$sql .= implode(', ', $this->_select).' ';
		}
		
		// from
		$sql .= 'FROM '.implode(', ', $this->_from).' ';
		
		// where
		$sql .= $this->compile_where_sql();
		
		// group by
		if ($this->_groupby) {
			$sql .= 'GROUP BY '.implode(', ', $this->_groupby);
		}
		
		// having
		if ($this->_having) {
			$having = array();
			foreach ($this->_having as $value)
			{
				$having[] = $value['key'].' '.strtoupper($value['sign']).' '.$value['value'];
			}
			$sql .= 'HAVING '.implode(' AND ', $having);
		}
		
		// order by
		if ($this->_orderby) {
			$orderby = array();
			foreach ($this->_orderby as $key => $order)
			{
				$orderby[] = '`'.$key.'` '.strtoupper($order);
			}
			$sql .= 'ORDER BY '.implode(', ', $orderby).' ';
		}
		
		// offset & limit
		if ($this->_offset and $this->_limit) {
			$sql .= 'LIMIT '.$this->_offset.','.$this->_limit.' ';
		} elseif ($this->_limit) {
			$sql .= 'LIMIT '.$this->_limit.' ';
		}
		
		$sql .= ';';
		return $sql;
	}
	
	
	
	/**
	 * Compile update SQL
	 *
	 * @version 2013-03-03
	 *
	 * @return string
	 */
	protected function compile_update_sql()
	{
		// table
		$sql = 'UPDATE '.implode(', ', $this->_from).' ';
		
		// set
		$set = array();
		foreach ($this->_set as $key => $value)
		{
			$set[] = $key.'="'.$value.'"';
		}
		$sql .= 'SET '.implode(', ', $set).' ';
		
		// where
		$sql .= $this->compile_where_sql();
		
		// offset & limit
		if ($this->_offset and $this->_limit) {
			$sql .= 'LIMIT '.$this->_offset.','.$this->_limit.' ';
		} elseif ($this->_limit) {
			$sql .= 'LIMIT '.$this->_limit.' ';
		}
		
		// order by
		if ($this->_orderby) {
			$orderby = array();
			foreach ($this->_orderby as $key => $order)
			{
				$orderby[] = '`'.$key.'` '.strtoupper($order);
			}
			$sql .= 'ORDER BY '.implode(', ', $orderby).' ';
		}
		
		$sql .= ';';
		return $sql;
	}
	
	
	
	/**
	 * Compile insert SQL
	 *
	 * @version 2013-12-24
	 *
	 * @return string
	 */
	protected function compile_insert_sql()
	{
		// table
		$sql = 'INSERT INTO '.implode(', ', $this->_from).' ';
		
		// column names
		$sql .= '('.implode(', ', array_keys($this->_set)).') ';
		
		// column values
		$sql .= 'VALUES ("'.implode('", "', array_values($this->_set)).'");';
		
		return $sql;
	}
	
	
	
	/**
	 * Compile delete SQL
	 *
	 * @version 2013-03-03
	 *
	 * @return string
	 */
	protected function compile_delete_sql()
	{
		// table
		$sql = 'DELETE FROM '.implode(', ', $this->_from).' ';
		
		// where
		$sql .= $this->compile_where_sql();
		
		// offset & limit
		if ($this->_offset and $this->_limit) {
			$sql .= 'LIMIT '.$this->_offset.','.$this->_limit.' ';
		} elseif ($this->_limit) {
			$sql .= 'LIMIT '.$this->_limit.' ';
		}
		
		// order by
		if ($this->_orderby) {
			$orderby = array();
			foreach ($this->_orderby as $key => $order)
			{
				$orderby[] = '`'.$key.'` '.strtoupper($order);
			}
			$sql .= 'ORDER BY '.implode(', ', $orderby).' ';
		}
		
		$sql .= ';';
		return $sql;
	}
	
	
	
	/**
	 * Compile 'WHERE' section SQL
	 *
	 * @version 2014-07-10
	 *
	 * @return string
	 */
	protected function compile_where_sql()
	{
		if (!$this->_where) {
			return '';
		}
		$sql_where = '';
		foreach ($this->_where as $value)
		{
			$sql_where .= $sql_where ? ' '.$value['logic'].' ' : '';
			if (!in_array($value['sign'], array('IN', 'NOT IN'))) {
				$sql_where .= '`'.$value['key'].'` '.strtoupper($value['sign']).' '.$value['value'];
			} else {
				$sql_where .= '`'.$value['key'].'` '.strtoupper($value['sign']).' ('.implode(', ', $value['value']).')';
			}
		}
		$sql_where = 'WHERE '.$sql_where.' ';
		return $sql_where;
	}
	
	
	
	/**
	 * Reset query factors
	 *
	 * @version 2013-03-03
	 *
	 */
	protected function reset()
	{
		$this->_select = array();
		$this->_distinct = false;
		$this->_from = array();
		$this->_where = array();
		$this->_orderby = array();
		$this->_groupby = array();
		$this->_having = array();
		$this->_offset = 0;
		$this->_limit = 0;
		$this->_set = array();
	}
	
	
	
	/**
	 * Filter special characters
	 *
	 * @version 2013-12-24
	 *
	 * @param string|array
	 * @return string|array
	 */
	protected function escape_value($value)
	{
		if (is_array($value)) {
			foreach ($value as $k => $v)
			{
				$value[$k] = mysqli_real_escape_string($this->conn, $v);
			}
		} else {
			$value = mysqli_real_escape_string($this->conn, $value);
		}
		return $value;
	}
	
	
	
	/**
	 * Convert a query result to an array
	 *
	 * @version 2013-12-24
	 *
	 * @return array
	 */
	public function result2array()
	{
		if ($this->result instanceof mysqli_result) {
			$this->result = mysqli_fetch_assoc($this->result) or die (mysqli_error($this->conn));
		} elseif (is_object($this->result)) {
			$this->result = (array)$this->result;
		}
		return $this->result;
	}
	
	
	
	/**
	 * Convert query results to an array set
	 *
	 * @version 2013-12-24
	 *
	 * @return array
	 */
	public function results2array()
	{
		$results = array();
		if ($this->result instanceof mysqli_result) {
			while ($data = mysqli_fetch_assoc($this->result)) {
				$results[] = $data;
			}
		} elseif (is_array($this->result)) {
			foreach ($this->result as $key => $result)
			{
				if (is_object($result)) {
					$result = (array)$result;
				}
				$results[] = $result;
			}
		} else {
			$results = $this->result;
		}
		$this->result = $results;
		return $this->result;
	}
	

} // END class

/* END file */