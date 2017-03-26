<?php

/**
 * PDO database connectivity methods. Added in 2.3.0 to replace the older mysql_* methods.
 *
 * @copyright Benjamin Keen 2017
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-3-x
 * @subpackage Database
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDO, PDOException;


class Database
{
    private $dbh;
    private $error;
    private $statement;
    private $table_prefix;


    public function __construct($hostname, $db_name, $port, $username, $password, $table_prefix) {
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        // if required, set all queries as UTF-8 (enabled by default)
        $attrInitCommands = array();
        if (Core::isUnicode()) {
            $attrInitCommands[] = "Names utf8";
        }
        //
        if (Core::shouldSetSqlMode()) {
            $attrInitCommands[] = "SQL_MODE=''";
        }
        if (!empty($attrInitCommands)) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET " . implode(",", $attrInitCommands);
        }

        try {
            $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8", $hostname, $port, $db_name);
            $this->dbh = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
        }

        $this->table_prefix = $table_prefix;


        if ($g_check_ft_sessions && isset($_SESSION["ft"]["account"])) {
            ft_check_sessions_timeout();
        }
    }

    /**
     * This is a convenience wrapper for PDO's prepare method. It replaces {PREFIX} with the database
     * table prefix so you don't have to include it everywhere.
     * @param $query
     */
    public function query($query) {
        $query = str_replace('{PREFIX}', $this->table_prefix, $query);
        $this->statement = $this->dbh->prepare($query);
    }

    public function bind($param, $value, $type = null) {
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

    public function bindAll(array $data) {
        foreach ($data as $k => $v) {
            $this->bind($k, $v);
        }
    }

    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }

    public function processTransaction() {
        return $this->dbh->commit();
    }

    public function rollbackTransaction() {
        return $this->dbh->rollBack();
    }

    // method execution methods
    public function execute() {
        return $this->statement->execute();
    }

    public function fetch($fetch_style = PDO::FETCH_ASSOC) {
        $this->execute();
        return $this->statement->fetch($fetch_style);
    }

    public function fetchAll($fetch_style = PDO::FETCH_ASSOC) {
        $this->execute();
        return $this->statement->fetchAll($fetch_style);
    }

    public function getInsertId() {
        return $this->dbh->lastInsertId();
    }

    // ---------------------------------------------------------------------------------------------------

    /**
     * Static helper method to checks a database connection.
     *
     * @param string $hostname
     * @param string $db_name
     * @param string $port
     * @param string $username
     * @param string $password
     * @return array
     */
    public static function checkConnection($hostname, $db_name, $port, $username, $password)
    {
        global $LANG;

        try {
            $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8", $hostname, $port, $db_name);
            new PDO($dsn, $username, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (PDOException $e) {
            $placeholders = array("db_connection_error" => $e->getMessage());
            $error = Installation::evalSmartyString($LANG["notify_install_invalid_db_info"], $placeholders);
            return array(false, $error);
        }

        return array(true, "");
    }
}
