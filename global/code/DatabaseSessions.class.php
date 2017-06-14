<?php

/**
 * Handles database sessions. By default Form Tools uses PHP sessions, not database sessions. To enable this
 * usage, just add a $g_session_type = "database"; value to the global/config.php file.
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDO, PDOException;


class DatabaseSessions
{
	public function DatabaseSessions()
	{
		// register the various session handler functions
		session_set_save_handler(
			array(&$this, "open"),
			array(&$this, "close"),
			array(&$this, "read"),
			array(&$this, "write"),
			array(&$this, "destroy"),
			array(&$this, "gc")
		);
	}

	private function open($save_path)
	{
	    // TODO... this looks backwards... Core::getSessionSavePath()
		global $sess_save_path;
		$sess_save_path = $save_path;
		return true;
	}

    private function close()
	{
		return true;
	}

    private function read($session_id)
	{
	    $db = Core::$db;

		// fetch session data from the selected database
		$db->query("
            SELECT session_data
            FROM {PREFIX}sessions
            WHERE session_id = :session_id AND 
                  expires > :expiry_time
        ");
		try {
            $db->bindAll(array(
                "session_id" => $session_id,
                "expiry_time" => time()
            ));
            $db->execute();
        } catch (PDOException $e) {
            Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
            exit;
        }

        $result = $db->fetchAll();

//		$a = mysql_num_rows($rs);
//      $data = "";
//
//		if ($a > 0) {
//			$row = mysql_fetch_assoc($rs);
//			$data = $row["session_data"];
//		}
//
//		return $data;
	}

	// this is only executed until after the output stream has been closed
    private function write($session_id, $data)
	{
	    $db = Core::$db;

		if (Sessions::exists("account.sessions_timeout")) {
            $life_time = Sessions::get("account.sessions_timeout") * 60;
        } else {
            $life_time = Core::getApiSessionsTimeout();
        }

		$db->query("
		    REPLACE {PREFIX}sessions (session_id, session_data, expires)
		    VALUES (:session_id, :session_data, :expiry_time)
        ");
		try {
            $db->bindAll(array(
                "session_id" => $session_id,
                "session_data" => $data,
                "expiry_time" => time() + $life_time
            ));
        } catch (PDOException $e) {
		    Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
		    return false;
        }

		return true;
	}

    private function destroy($id)
	{
	    $db = Core::$db;

		$newid = mysql_real_escape_string($id);
		$sql = "DELETE FROM {PREFIX}sessions WHERE session_id = '$newid'";

		$db->query($sql, $this->db_link);
		return true;
	}

    private function gc()
	{
		// delete all records who have passed the expiration time
		$sql = "DELETE FROM {PREFIX}sessions WHERE expires < UNIX_TIMESTAMP()";
		$db->query($sql);
		return true;
	}
}
