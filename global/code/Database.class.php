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
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        );

        // if required, set all queries as UTF-8 (enabled by default). N.B. we're supporting 5.3.0 so passing charset
        // in the DSN isn't sufficient, as described here: https://phpdelusions.net/pdo
        $attrInitCommands = array();
        if (version_compare(PHP_VERSION, '5.3.6', '<')) {
            $attrInitCommands[] = "Names utf8";
        }
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
    }


    /**
     * Open a database connection. This is called once for all page requests, and closed at the footer.
     * Depending on the $g_check_ft_sessions global (true by default), it also logs the time of each
     * request, to perform the sessions timeout check. This parameter is enabled for the main script
     * so that all users are subject to being booted out if there's been no activity. But for external
     * scripts (such as the API) this setting can be disabled, giving them unfettered use of the database
     * connection without worrying about being - incorrectly - logged out.
     *
     * @return resource returns a reference to the open connection.
    function ft_db_connect()
    {
    global $g_db_hostname, $g_db_username, $g_db_password, $g_db_name, $g_unicode, $g_db_ssl,
    $g_check_ft_sessions, $g_set_sql_mode;

    extract(Hooks::processHookCalls("start", array(), array()), EXTR_OVERWRITE);

    if ($g_db_ssl)
    $link = @mysql_connect($g_db_hostname, $g_db_username, $g_db_password, true, MYSQL_CLIENT_SSL);
    else
    $link = @mysql_connect($g_db_hostname, $g_db_username, $g_db_password, true);

    if (!$link)
    {
    Errors::majorError("<p>Form Tools was unable to make a connection to the database hostname. This usually means the host is temporarily down, it's no longer accessible with the hostname you're passing, or the username and password you're using isn't valid.</p><p>Please check your /global/config.php file to confirm the <b>\$g_db_hostname</b>, <b>\$g_db_username</b> and <b>\$g_db_password</b> settings.</p>");
    exit;
    }

    $db_connection = mysql_select_db($g_db_name);
    if (!$db_connection)
    {
    Errors::majorError("Form Tools was unable to make a connection to the database. This usually means the database is temporarily down, or that the database is no longer accessible. Please check your /global/config.php file to confirm the <b>\$g_db_name</b> setting.");
    exit;
    }

    // if required, set all queries as UTF-8 (enabled by default)
    if ($g_unicode)
    @$db->query("SET NAMES 'utf8'", $link);

    if ($g_set_sql_mode)
    @$db->query("SET SQL_MODE=''", $link);

    if ($g_check_ft_sessions && isset($_SESSION["ft"]["account"]))
    ft_check_sessions_timeout();

    return $link;
    }
     */


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
        return $this->statement->fetch($fetch_style);
    }

    public function fetchColumn($fetch_style = PDO::FETCH_ASSOC) {
        return $this->statement->fetchColumn($fetch_style);
    }

    public function fetchAll($fetch_style = PDO::FETCH_ASSOC) {
        return $this->statement->fetchAll($fetch_style);
    }

    public function numRows() {
        return $this->statement->rowCount();
    }

    public function getResultsArray() {
        $info = array();
        foreach ($this->fetchAll() as $row) {
            $info[] = $row;
        }
        return $info;
    }

    public function getInsertId() {
        return $this->dbh->lastInsertId();
    }
}
