<?
/**
* DBMySQL.php
* 
* The DBMySQL class is meant to simplify the task of accessing
* information from the website's database.
*
* @authors Mike Eldridge, Benjamin Largent
*/
class DBMySQL {
	private $_connection = null;	// The MySQL database connection
	private $_error = 0;			// error condition	
	private $_errorText = '';		// text of error condition
	private $_result = null;		// sql execute result	

	private $logBucket = array();

	
	public function __construct() {
		/* Make connection to database */
		$this->connect_db();
	}
	
	private function set_connection($connection) {
		$this->_connection = $connection;
	}
	
	public function get_connection() {
		return $this->_connection;
	}
	
	private function set_error() {
		$this->_error = mysql_errno( $this->get_connection() );
		$this->_errorText = mysql_error( $this->get_connection() );
	}
	
	public function get_error() {
		return $this->_error;
	}

	public function get_error_text() {
		return $this->_errorText;
	}

	public function success() {
		return ($this->_error == 0);
	}
	
	private function set_result($mysqlResult) {
		$this->_result = $mysqlResult;
	}
	
	public function get_result() {
		return $this->_result;
	}
	
	private function connect_db() {
		$this->set_connection(mysql_connect(DB_SERVER, DB_USER, DB_PASS));
		$this->set_error();

		if ( $this->success() )	{
			mysql_select_db(DB_NAME, $this->get_connection() );
			$this->set_error();
			// echo 'DB connection ok';
		}
	}
	
	public function select_database($name) {
		mysql_select_db($name, $this->get_connection() );
		$this->set_error();
	}
	
	/**
	 * Execute an SQL command against the database
	 *
	 * @param q		the sql to be executed
	 */
	public function execute($q) {
		if ( ! $this->get_connection() ) {
			$this->connect_db();	
		}
		
		if (TRACK_DB_ACTIONS) {
			global $session;
			
			$queryStart = microtime(true);
			//$elucidation = mysql_query("EXPLAIN " .$q);
			$queryEnd = microtime(true);
			$queryTime = $queryEnd - $queryStart;
			
			$_SESSION['timingJunk'] = $queryEnd .' - ' .$queryStart .' = ' .$queryTime;
			
			$mysqlResult = mysql_query($q, $this->get_connection());
			
		}
		else {
			$mysqlResult = mysql_query($q, $this->get_connection() );
		}
		
		
		$this->set_result($mysqlResult);
		$this->set_error();
	
		return $mysqlResult;
	}

	public function mysql_clean($array, $index, $maxLength, $db) {
		if (isset( $array["{$index}"]) ) {
			$input = substr($array["{$index}"], 0, $maxLength);
			$input =mysql_real_escape_string($input, $db);
			return ($input);
		}
		return NULL;		
	}
	
	
	
	/**
	 * Extract the number of rows in the sql result set
	 *
	 * @return	number of rows as integer
	 */
	public function num_rows() {
		$iNumRows = 0;
		
		if ( $this->success() ) {
			$iNumRows = mysql_num_rows($this->get_result());
		}
		
		return $iNumRows;
	}

	/**
	 * Fetch all the rows from the sql result. Each row of the result is fetched
	 * into an associative array and all the rows are added to an array.
	 *
	 * @return	an array of associative arrays.
	 */
	public function fetch_assoc() {
		$rows = array();
				
		if ( $this->success() ) {
			while ($row = mysql_fetch_assoc($this->get_result())) {
				$rows[] = $row;
			}
		}
		else {
			return $this->get_error_text();
		}
		
		return $rows;
	}
	
	public function fetch_assoc2() {
		return mysql_fetch_assoc($this->get_result());
	}

	
	/* busted, stupid mysqli
	public function fetch_all2() {
		return fetch_all($this->get_result());
	}
	*/


	/**
	 * Extract the number of affected rows from the sql result
	 *
	 * @return	number of rows as integer
	 */
	public function affected_rows() {
		return mysql_affected_rows($this->get_connection());
	}
	
	/****
	 * Is similar to fetch_assoc2() but instead of accessing results like:
	 *
	 *    echo $row['field1'] .$row['field2'];
	 *
	 * you would use:
	 *
	 *    echo $row->field1 .$row->field2;    
	 *
	 * @return an object of associated properties (by field).
	 **/
	 public function fetch_object2() {
		 return mysql_fetch_object($this->get_result());
	 }
	
	/**
	 *
	 */
	public function insert_id() {
		return mysql_insert_id($this->get_connection());
	}

	/**
	 *
	 */
	public function result($row, $field = null) {
		$result = '';
		
		$result = mysql_result($this->get_result(), $row, $field);
		
		return $result;
	}

	/**
	 * Prepare query takes a string or an array of variant and passes each
	 * element to MySQL to escape a set of chars to prevent some issues such
	 * as quoting and slashs.
	 *
	 * @param element	single, string element
	 * @param element	array, array of elements to be escaped
	 */
	public function prepare_query($elements) {
		if ( ! $this->get_connection() ) {
			$this->connect_db();	
		}
		
		$magicQuotes = get_magic_quotes_gpc();
		
		if ( $elements == null ) {
			;	// do nothing
		}
		else if ( is_string($elements) ) {
			if ( $magicQuotes ) {
				$elements = stripslashes($elements);
			}
			
			$elements = mysql_real_escape_string($elements, $this->get_connection() );
			$this->set_error();
		}
		else if ( is_array($elements) ) {
			$elementsCount = count($elements);
			
			for ( $i = 0; $i < $elementsCount; $i++ ) {
				if ( $magicQuotes ) {
					$elements[$i] = stripslashes($elements[$i]);
				}
				$elements[$i] = mysql_real_escape_string($elements[$i], $this->get_connection() );
				$this->set_error();
				
				if ( ! $this->success() ) {
					$i = $elementsCount;
				}
			}
		}
		
		return $elements;
	}
	
	public function close_conn() {
		if ($this->get_connection()) {
			mysql_close($this->get_connection());
		}
	}
};
?>
