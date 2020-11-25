<?php

require_once("C:/server/data/apacheData/dev.local/inc/php/classes/sqlsrv_helper.php");
class sqlsrv_helperDeleteTest extends PHPUnit_Framework_TestCase{

	/* @var sqlsrv_helper */
	protected static $sqlSrv;
	protected static $dbc;
	protected static $tableName = "FWE_DEV..unitTestSqlSrvHelper";

	public static function setUpBeforeClass(){
		self::$sqlSrv = new sqlsrv_helper('m2m');

		require("C:/server/data/apacheData/dev.local/inc/php/models/credentials/m2m.php");
		self::$dbc = sqlsrv_connect($server, array(
			"UID"=>$user,
			"PWD"=>$password,
			"Database"=>'FWE_DEV'
		));

		$dropQuery = "IF OBJECT_ID('".self::$tableName."', 'U') IS NOT NULL DROP TABLE ".self::$tableName;
		$createQuery = "CREATE TABLE ".self::$tableName."(
			[test_dateTime] [datetime] NULL,
			[test_numeric] [numeric](10, 2) NULL,
			[test_int] [int] NULL,
			[test_text] [text] NULL,
			[test_varChar] [varchar](20) NULL,
			[test_bit] [bit] NULL,
			[test_float] [float] NULL
			)";

		sqlsrv_query(self::$dbc,$dropQuery);
		sqlsrv_query(self::$dbc,$createQuery);
	}

	public function setUp(){
		sqlsrv_query(self::$dbc, "INSERT INTO ".self::$tableName." 
		(test_dateTime, test_numeric, test_int, test_text, test_varChar, test_bit, test_float)
		VALUES
		(2010-06-02 12:00:00, 12.34, 5, 'abcdefghijklmnopqrstuvwxyz', '1234567890',0,6.7)");
	}

	/**
	 * @dataProvider filterProvider
	 */
	public function testDelete($column, $filter){

		self::$sqlSrv->delete(self::$tableName, array($column => $filter));
		$stmt = sqlsrv_query(self::$dbc, "SELECT * from ".self::$tableName);
		$this->assertEmpty(sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC));
	}

	/**
	 * @dataProvider badFilterProvider
	 */
	public function testBadFilters($column, $filter){

		$this->setExpectedExceptionRegExp("Exception", "/Query Error.*/");

		self::$sqlSrv->delete(self::$tableName, array($column => $filter));
		$stmt = sqlsrv_query(self::$dbc, "SELECT * from ".self::$tableName);
		$this->assertNull(sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC));
	}

	public function filterProvider(){
		return array(
			array('test_dateTime', '2010-06-02 12:00:00'),
			array('test_numeric', 12.34),
			array('test_int', 5),
			array('cast(test_text as nvarchar(max))', 'abcdefghijklmnopqrstuvwxyz'),
			array('test_varChar', '1234567890'),
			array('test_bit', 0),
			array('test_float', 6.7)
		);
	}

	public function badFilterProvider(){
		return array(
			array('test_text', 'abcdefghijklmnopqrstuvwxyz'),
			array('invalid_column', 'bogus')
		);
	}

	public function tearDown(){
		sqlsrv_query(self::$dbc, "TRUNCATE TABLE ".self::$tableName);
	}

	public static function tearDownAfterClass(){
		self::$dbc = NULL;
	}
}
