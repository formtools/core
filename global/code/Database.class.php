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


    public function __construct($hostname, $db_name, $port, $username, $password) {
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        try {
            $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8", $hostname, $port, $db_name);
            $this->dbh = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
        }

        // ??
        //@mysqli_query($this->link, "SET NAMES 'utf8'");
    }

    public function query($query) {
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

    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }

    public function endTransaction() {
        return $this->dbh->commit();
    }

    public function cancelTransaction() {
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
