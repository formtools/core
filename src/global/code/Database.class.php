<?php

/**
 * PDO database connectivity methods. Added in 2.3.0 to replace the older mysql_* methods.
 *
 * @copyright Benjamin Keen 2018
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-3-x
 * @subpackage Database
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDO, Exception;


class Database
{
	private $dbh;
	private $error;
	private $statement;
	private $table_prefix;

	public function __construct($hostname, $db_name, $port, $username, $password, $table_prefix)
	{
		$options = array(
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_EMULATE_PREPARES => false
		);

		// if required, set all queries as UTF-8 (enabled by default). N.B. we're supporting 5.3.0 so passing charset
		// in the DSN isn't sufficient, as described here: https://phpdelusions.net/pdo
		$attrInitCommands = array();
		if (version_compare(PHP_VERSION, '5.3.6', '<')) {
			$attrInitCommands[] = "Names utf8";
		}

		$use_strict_mode = Core::getSqlStrictMode();
		if ($use_strict_mode == "off") {
			$attrInitCommands[] = "SQL_MODE=''";
		} else if ($use_strict_mode == "on") {
			$attrInitCommands[] = "SQL_MODE='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'";
		}

		if (!empty($attrInitCommands)) {
			$options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET " . implode(",", $attrInitCommands);
		}

		try {
			$dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8", $hostname, $port, $db_name);
			$this->dbh = new PDO($dsn, $username, $password, $options);
		} catch (Exception $e) {
			$this->error = $e->getMessage();
		}

		$this->table_prefix = $table_prefix;
	}

	/**
	 * This is a convenience wrapper for PDO's prepare method. It replaces {PREFIX} with the database
	 * table prefix so you don't have to include it everywhere.
	 * @param $query
	 */
	public function query($query)
	{
		$query = str_replace('{PREFIX}', $this->table_prefix, $query);
		$this->statement = $this->dbh->prepare($query);
	}

	public static function placeholders($text, $count = 0, $separator = ",")
	{
		$result = array();
		if ($count > 0) {
			for ($i = 0; $i < $count; $i++) {
				$result[] = $text;
			}
		}
		return implode($separator, $result);
	}

	/**
	 * Another convenience function to abstract away PDO's awful insert-multiple syntax and actually execute the
	 * query as well. Does the same thing as a query() and an execute().
	 * @param string $table table name (OMIT the prefix)
	 * @param array $cols ordered array of tables names
	 * @param array $data array of arrays. Each subarray is a hash of col name to value.
	 * @return result of executed statement
	 */
	public function insertQueryMultiple($table, $cols, $data)
	{
		$insert_values = array();
		foreach ($data as $d) {
			$question_marks[] = '(' . self::placeholders('?', sizeof($d)) . ')';
			$insert_values = array_merge($insert_values, array_values($d));
		}

		$table_name = $this->table_prefix . $table;
		$query = "INSERT INTO $table_name (" . implode(",", $cols) . ") VALUES " . implode(',', $question_marks);

		$this->statement = $this->dbh->prepare($query);

		return $this->statement->execute($insert_values);
	}

	public function bind($param, $value, $type = null)
	{
		if (is_null($type)) {
			switch (true) {
				case is_int($value):
					$type = PDO::PARAM_INT;
					break;
				case is_bool($value):
					$type = PDO::PARAM_BOOL;
					break;
				case is_null($value):
					$type = PDO::PARAM_NULL;
					break;
				default:
					$type = PDO::PARAM_STR;
			}
		}
		$this->statement->bindValue($param, $value, $type);
	}

	public function bindAll(array $data)
	{
		foreach ($data as $k => $v) {
			$this->bind($k, $v);
		}
	}

	public function beginTransaction()
	{
		return $this->dbh->beginTransaction();
	}

	public function processTransaction()
	{
		return $this->dbh->commit();
	}

	public function rollbackTransaction()
	{
		return $this->dbh->rollBack();
	}

	// method execution methods
	public function execute($params = "")
	{
		if (!empty($params)) {
			return $this->statement->execute($params);
		} else {
			return $this->statement->execute();
		}
	}

	public function fetch($fetch_style = PDO::FETCH_ASSOC)
	{
		return $this->statement->fetch($fetch_style);
	}

	public function fetchColumn()
	{
		return $this->statement->fetchColumn();
	}

	public function fetchAll($fetch_style = PDO::FETCH_ASSOC)
	{
		return $this->statement->fetchAll($fetch_style);
	}

	public function numRows()
	{
		return $this->statement->rowCount();
	}

	public function getResultsArray()
	{
		$info = array();
		foreach ($this->fetchAll() as $row) {
			$info[] = $row;
		}
		return $info;
	}

	public function getInsertId()
	{
		return $this->dbh->lastInsertId();
	}

	public function getMySQLVersion()
	{
		$this->query("SELECT VERSION()");
		$this->execute();
		return $this->fetchColumn();
	}

	/**
	 * Convenience method for constructing PDO-friendly insert statements. This is passed a hash of
	 * column names to values and returns an array with two indexes:
	 *          [0] a comma delimited list of col names, like "mycol1, mycol2, mycol3"
	 *          [1] a command delimited list of placeholders for those same columns, like ":mycol1, :mycol2, :mycol3"
	 *
	 * Since PDO doesn't permit placeholder starting with numbers but MySQL DOES permit columns to do that, this method
	 * also takes that into account.
	 *
	 * @param $hash array of columns => values
	 * @return array
	 */
	public function getInsertStatementParams($hash)
	{
		$col_names = array();
		$placeholders = array();
		$clean_hash = $hash;

		foreach ($hash as $col_name => $value) {
			$col_names[] = $col_name;

			$clean_col_name = $col_name;
			if (preg_match('/^\d/', $col_name) === 1) {
				$clean_col_name = "SAFE_{$col_name}";

				$value = $hash[$col_name];
				unset($clean_hash[$col_name]);
				$clean_hash[$clean_col_name] = $value;
			}
			$placeholders[] = ":{$clean_col_name}";
		}

		$cols_str = join(", ", $col_names);
		$placeholders_str = join(", ", $placeholders);

		return array($cols_str, $placeholders_str, $clean_hash);
	}

	/**
	 * Returns a string of col1 = :col1, col2 = :col2 ... for an UPDATE statement.
	 * @param $hash
	 * @return string
	 */
	public function getUpdateStatements($hash)
	{
		$col_names = array_keys($hash);
		$update_statements = array();
		foreach ($col_names as $col_name) {
			$update_statements[] = "$col_name = :$col_name";
		}

		return implode(", ", $update_statements);
	}
}
