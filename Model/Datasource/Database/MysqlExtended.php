<?php
/**
 * An extended/customized version of the MySQL Datasource/Database
 */
App::uses('DboSource', 'Model/Datasource');
App::uses('Mysql', 'Model/Datasource/Database');

class MysqlExtended extends Mysql {


	/**
	 * MySQL column definition
	 *
	 * @var array
	 */
	public $columns = array(
		'primary_key' => array('name' => 'NOT NULL AUTO_INCREMENT'),
		'string' => array('name' => 'varchar', 'limit' => '255'),
		'text' => array('name' => 'text'),
        'longtext' => array('name' => 'longtext'),
		'biginteger' => array('name' => 'bigint', 'limit' => '20'),
		'integer' => array('name' => 'int', 'limit' => '11', 'formatter' => 'intval'),
		'float' => array('name' => 'float', 'formatter' => 'floatval'),
		'datetime' => array('name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'timestamp' => array('name' => 'timestamp', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'time' => array('name' => 'time', 'format' => 'H:i:s', 'formatter' => 'date'),
		'date' => array('name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
		//'binary' => array('name' => 'blob'),
		'boolean' => array('name' => 'tinyint', 'limit' => '1'),
		// customizations
		'binary' => array('name' => 'binary'),
		'blob' => array('name' => 'blob'),
		'longblob' => array('name' => 'longblob'),
		'tinyint' => array('name' => 'tinyint', 'limit' => '3', 'formatter' => 'intval'),
		'smallint' => array('name' => 'smallint', 'limit' => '6', 'formatter' => 'intval'),
		'mediumint' => array('name' => 'mediumint', 'limit' => '8', 'formatter' => 'intval'),
	);

	/**
	 * Config for tryExecuteAgain()
	 *
	 * @var array
	 */
	public $_tryExecuteAgainConfig = [
		'limit' => 3,     // retry 3 times (change to 0 to disable retrys)
		'usleep' => 2000, // 2 ms
	];

	/**
	 * Config for debug logging
	 *
	 * @var boolean
	 */
	public $debugging = true;


	/**
	 * Set the _queriesLogMax protected value
	 *
	 * @param int $logMax
	 * @return void
	 */
	public function setLogMax($logMax=200) {
		$this->_queriesLogMax = $logMax;
	}

	/**
	 * Overwritten value for _queriesLogMax
	 * 200 Queries was too limiting,
	 *
	 * You can customize this value with setLogMax()
	 *
	 * @var int
	 */
	protected $_queriesLogMax = 2000;

	/**
	 * Customized: more options/types
	 *
	 * Converts database-layer column types to basic types
	 *
	 * @param string $real Real database-layer column type (i.e. "varchar(255)")
	 * @return string Abstract column type (i.e. "string")
	 */
	public function column($real) {
		if (is_array($real)) {
			$col = $real['name'];
			if (isset($real['limit'])) {
				$col .= '(' . $real['limit'] . ')';
			}
			return $col;
		}

		$col = str_replace(')', '', $real);
		$limit = $this->length($real);
		if (strpos($col, '(') !== false) {
			list($col, $vals) = explode('(', $col);
		}

		if (in_array($col, array('date', 'time', 'datetime', 'timestamp'))) {
			return $col;
		}
		if (($col === 'tinyint' && $limit == 1) || $col === 'boolean') {
			return 'boolean';
		}
		if (strpos($col, 'bigint') !== false || $col === 'bigint') {
			return 'biginteger';
		}
		if (($col === 'integer' && $limit >= 12)) {
			return 'biginteger';
		}
		if (($col === 'integer' && $limit <= 3) || $col === 'tinyint') {
			return 'tinyint';
		}
		if (($col === 'integer' && $limit <= 6) || $col === 'smallint') {
			return 'smallint';
		}
		if (($col === 'integer' && $limit <= 8) || $col === 'mediumint') {
			return 'mediumint';
		}
		if (strpos($col, 'int') !== false) {
			return 'integer';
		}
		if (strpos($col, 'char') !== false || $col === 'tinytext') {
			return 'string';
		}
		if (strpos($col, 'text') !== false) {
			return 'text';
		}
		if ($col === 'binary') {
			return 'binary';
		}
		if ($col === 'longblob') {
			return 'longblob';
		}
		if (strpos($col, 'blob') !== false) {
			return 'blob';
		}
		if (strpos($col, 'float') !== false || strpos($col, 'double') !== false || strpos($col, 'decimal') !== false) {
			return 'float';
		}
		if (strpos($col, 'enum') !== false) {
			return "enum($vals)";
		}
		return 'text';
	}

/**
 * Customized: we try/catch trapping the PDOException
 *
 * Given a PDOException
 * When the exception message contains "try restarting transaction"
 * And when we have not restarted 3 times already
 * Then restart the execute on the exact same query again
 *
 * This is a method to help mitigate MySQL / Percona DEADLOCK errors in InnoDB tables
 *
 * The deadlocks are actually things working right... MySQL recomments restarting...
 *
 * So the application code should be capable of simple restarting... i
 * thus this extension.
 *
 *
 * Queries the database with given SQL statement, and obtains some metadata about the result
 * (rows affected, timing, any errors, number of rows in resultset). The query is also logged.
 * If Configure::read('debug') is set, the log is shown all the time, else it is only shown on errors.
 *
 * ### Options
 *
 * - log - Whether or not the query should be logged to the memory log.
 *
 * @param string $sql SQL statement
 * @param array $options The options for executing the query.
 * @param array $params values to be bound to the query.
 * @return mixed Resource or object representing the result set, or false on failure
 */
	public function execute($sql, $options = array(), $params = array()) {
		try {
			return $this->executeOnParent($sql, $options, $params);
		} catch (PDOException $e) {
			$return = $this->tryExecuteAgain($e, $sql, $options, $params);
			$this->tryExecuteAgainUnsetRepeat();
			return $return;
		}
	}

	/**
	 * Split out to parent caller function - for easier testing/mocking
	 *
	 * @param string $sql SQL statement
	 * @param array $options The options for executing the query.
	 * @param array $params values to be bound to the query.
	 * @return mixed Resource or object representing the result set, or false on failure
	 */
	public function executeOnParent($sql, $options = array(), $params = array()) {
		return parent::execute($sql, $options, $params);
	}

	/**
	 * try to re-execute again, maybe
	 * based on the query, exception, etc
	 *
	 * @param PDOException $e
	 * @param string $sql SQL statement
	 * @param array $options The options for executing the query.
	 * @param array $params values to be bound to the query.
	 * @return mixed Resource or object representing the result set, or false on failure
	 */
	public function tryExecuteAgain($e, $sql, $options, $params) {
		if (!$this->shouldWeExecuteAgain($e)) {
			$this->tryExecuteAgainUnsetRepeat();
			if ($this->debugging) {
				$this->log('MysqlExtended: Did not retry query THROWING: ' . $sql, 'debug');
			}
			throw $e;
		}

		$this->tryExecuteAgainSleep($e);
		$this->tryExecuteAgainAddRepeat($e);

		if ($this->debugging) {
			$this->log('MysqlExtended: Attempting to retry query: ' . $sql, 'debug');
			$return = $this->execute($sql, $options, $params);
			$this->log('MysqlExtended: Successful retry of query: ' . $sql, 'debug');
			return $return;
		}

		return $this->execute($sql, $options, $params);
	}

	/**
	 * should we try to re-execute again?
	 * based on the query, exception, etc
	 *
	 * @param PDOException $e
	 * @return boolean
	 */
	public function shouldWeExecuteAgain($e) {
		if (!($e instanceof PDOException)) {
			return false;
		}
		if (!isset($e->queryString)) {
			return false;
		}
		if (!$this->shouldWeExecuteAgainAllowedMessage($e)) {
			return false;
		}
		if (!$this->shouldWeExecuteAgainAllowedRepeat($e)) {
			return false;
		}
		return true;
	}

	/**
	 * should we try to re-execute again?
	 * based on the query message
	 *
	 * @param PDOException $e
	 * @return boolean
	 */
	public function shouldWeExecuteAgainAllowedMessage($e) {
		// TODO consider limiting to specific error codes: 1213, 1205
		return (strpos($e->getMessage(), 'try restarting transaction') !== false);
	}

	/**
	 * should we try to re-execute again?
	 * based on the already repeated X times
	 *
	 * @param PDOException $e
	 * @return boolean
	 */
	public function shouldWeExecuteAgainAllowedRepeat($e) {
		if (!isset($this->_tryExecuteAgain[$e->queryString])) {
			$this->_tryExecuteAgain[$e->queryString] = 0;
		}
		return ($this->_tryExecuteAgain[$e->queryString] < $this->_tryExecuteAgainConfig['limit']);
	}

	/**
	 * add 1 to the count of retries for this query
	 *
	 * @param PDOException $e
	 * @return boolean
	 */
	public function tryExecuteAgainAddRepeat($e) {
		if (!isset($this->_tryExecuteAgain[$e->queryString])) {
			$this->_tryExecuteAgain[$e->queryString] = 1;
			return;
		}
		$this->_tryExecuteAgain[$e->queryString]++;
	}

	/**
	 * add 1 to the count of retries for this query
	 *
	 * @return boolean
	 */
	public function tryExecuteAgainUnsetRepeat() {
		unset($this->_tryExecuteAgain);
	}

	/**
	 * add 1 to the count of retries for this query
	 *
	 * @param PDOException $e
	 * @return boolean
	 */
	public function tryExecuteAgainSleep($e) {
		if (strpos($e->getMessage(), '1205') !== false) {
			// error 1205 is a long timeout, so we sleep longer: 100 ms
			usleep(10000);
			return true;
		}
		// default: sleep 1 ms
		usleep($this->_tryExecuteAgainConfig['usleep']);
		return true;
	}

}
