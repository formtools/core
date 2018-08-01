<?php

namespace FormTools\Tests;

use PHPUnit\Framework\TestCase;
use FormTools\Database;


class DatabaseTest extends TestCase
{

	private $db;

	protected function setUp()
	{
		$hostname = "hostname";
		$db_name = "dbname";
		$port = 123;
		$username = "user";
		$password = "pass";
		$table_prefix = "ft_";

		$this->db = new Database($hostname, $db_name, $port, $username, $password, $table_prefix);
	}


    // Database->getInsertStatementParams

    public function testGetInsertStatementParams_ReturnsExpectedValues()
    {
        $data = array(
        	"column1" => "one",
			"column2" => "two",
		);

        list($col_names_str, $placeholders_str, $updated_data) = $this->db->getInsertStatementParams($data);

        $this->assertEquals($col_names_str, "column1, column2");
		$this->assertEquals($placeholders_str, ":column1, :column2");
		$this->assertEquals($updated_data, $data);
    }

	public function testGetInsertStatementParams_HandlesDbColumnsStartingWithNumber()
	{
		$data = array(
			"col1" => "one",
			"2col" => "two",
			"3col" => "three",
			"col4" => "four"
		);

		list($col_names_str, $placeholders_str, $updated_data) = $this->db->getInsertStatementParams($data);

		$this->assertEquals($col_names_str, "col1, 2col, 3col, col4");
		$this->assertEquals($placeholders_str, ":col1, :SAFE_2col, :SAFE_3col, :col4");
		$this->assertEquals($updated_data, array(
			"col1" => "one",
			"SAFE_2col" => "two",
			"SAFE_3col" => "three",
			"col4" => "four"
		));
	}

}

