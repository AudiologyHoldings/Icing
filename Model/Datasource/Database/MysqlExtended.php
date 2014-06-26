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

}
