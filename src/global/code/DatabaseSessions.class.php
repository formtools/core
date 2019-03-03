<?php

/**
 * Handles database sessions. By default Form Tools uses PHP sessions, not database sessions. To enable this
 * usage, just add a $g_session_type = "database"; value to the global/config.php file.
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;

use Exception;


/**
 * Overrides the default PHP (file-based) sessions with database sessions, allowing using Form Tools being hosted
 * on multiple servers.
 */
class DatabaseSessions
{
    private $sessionSavePath;
    private $db;

    function __construct($db, $session_save_path)
	{
        $this->db = $db;
	    $this->sessionSavePath = $session_save_path;

		// register the various session handler functions
		session_set_save_handler(
			array(&$this, "open"),
			array(&$this, "close"),
			array(&$this, "read"),
			array(&$this, "write"),
			array(&$this, "destroy"),
			array(&$this, "gc")
		);

        register_shutdown_function('session_write_close');
	}

	public function open()
	{
		return true;
	}

    public function close()
	{
		return true;
	}

    public function read($session_id)
	{
		// fetch session data from the selected database
		$this->db->query("
            SELECT session_data
            FROM {PREFIX}sessions
            WHERE session_id = :session_id AND 
                  expires > :expiry_time
        ");
		try {
            $this->db->bindAll(array(
                "session_id" => $session_id,
                "expiry_time" => time()
            ));
            $this->db->execute();
        } catch (Exception $e) {
            Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
            exit;
        }

        $results = $this->db->fetchAll();
		$a = count($results);

        $data = "";

		if ($a > 0) {
			$row = $results[0];
			$data = $row["session_data"];
		}

		return $data;
	}

	// this is only executed until after the output stream has been closed
    public function write($session_id, $data)
	{
	    $db = $this->db;

		if (Sessions::exists("account.sessions_timeout")) {
            $lifeTime = Sessions::get("account.sessions_timeout") * 60;
        } else {
            $lifeTime = Core::getApiSessionsTimeout();
        }

		$db->query("
		    REPLACE {PREFIX}sessions (session_id, session_data, expires)
		    VALUES (:session_id, :session_data, :expiry_time)
        ");
		try {
            $db->bindAll(array(
                "session_id" => $session_id,
                "session_data" => $data,
                "expiry_time" => time() + $lifeTime
            ));
            $db->execute();
        } catch (Exception $e) {
		    Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
		    return false;
        }

		return true;
	}

    public function destroy($id)
	{
	    $db = $this->db;
		$db->query("DELETE FROM {PREFIX}sessions WHERE session_id = :id");
		$db->bind("id", $id);
		$db->execute();

		return true;
	}

    // delete all records who have passed the expiration time
    public function gc()
	{
		$this->db->query("DELETE FROM {PREFIX}sessions WHERE expires < UNIX_TIMESTAMP()");
		return true;
	}
}
