<?php

/**
 * Handles database sessions. By default Form Tools uses PHP sessions, not database sessions. To enable this
 * usage, just add a $g_session_type = "database"; value to the global/config.php file.
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;


class SessionManager
{
	public function SessionManager()
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
		$db->query("SELECT session_data FROM {PREFIX}sessions WHERE session_id = :session_id AND expires > :time");
        $db->bindAll(array(
            ":session_id" => $session_id,
            ":time" => time()
        ));
        $db->execute();

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
		global $g_api_sessions_timeout;

		if (isset($_SESSION["ft"]["account"]["sessions_timeout"])) {
            $life_time = $_SESSION["ft"]["account"]["sessions_timeout"] * 60;
        } else {
            $life_time = $g_api_sessions_timeout;
        }

		$time = time() + $life_time;

		$newid   = mysql_real_escape_string($session_id, $this->db_link);
		$newdata = mysql_real_escape_string($data, $this->db_link);

		$sql = "REPLACE {PREFIX}sessions (session_id, session_data, expires) VALUES('$newid', '$newdata', $time)";
		mysql_query($sql, $this->db_link);

		return true;
	}

    private function destroy($id)
	{
		$newid = mysql_real_escape_string($id);
		$sql = "DELETE FROM {PREFIX}sessions WHERE session_id = '$newid'";

		mysql_query($sql, $this->db_link);
		return true;
	}

    private function gc()
	{
		// delete all records who have passed the expiration time
		$sql = "DELETE FROM {PREFIX}sessions WHERE expires < UNIX_TIMESTAMP()";
		mysql_query($sql);
		return true;
	}
}
