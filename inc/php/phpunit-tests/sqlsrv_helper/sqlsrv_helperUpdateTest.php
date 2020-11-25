<?php
require_once("C:/server/data/apacheData/dev.local/inc/php/classes/sqlsrv_helper.php");

class sqlsrv_helperUpdateTest extends PHPUnit_Framework_TestCase{
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

		$insertTestDataSql = "INSERT INTO ".self::$tableName." VALUES
		('2010-06-02 12:00:00', 12.34, 5, 'abcdefghijklmnopqrstuvwxyz', '1234567890',0,6.7),
		('2011-07-03 13:00:00', 10.10, 2, 'some text here', 'some chars', 1, 2.25)";

		sqlsrv_query(self::$dbc,$insertTestDataSql);
	}

	public function updateProvider(){
		return array(
			array("test_dateTime",'2010-06-02 12:00:00', '2011-07-03 13:00:00'),
			array("test_numeric",12.34, 10.10),
			array("test_int",5, 2),
			array("test_varChar",'1234567890', "some chars"),
			array("test_bit",0, 1),
			array("test_float",6.7, 2.25)
		);
	}

	/**
	 * @dataProvider updateProvider
	 * @param $column
	 * @param $old
	 * @param $new
	 */
	public function testUpdate($column, $old, $new){
		self::$sqlSrv->update(self::$tableName,array($column=>$new),array($column=>$old));
		$stmt = sqlsrv_query(self::$dbc, "SELECT count(*) FROM ".self::$tableName." WHERE {$column} = ?", array($new));
		$rows = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
		$this->assertEquals(2, $rows[0]);
	}

	public function testFunctionUpdate(){
		$values = array('test_dateTime'=> 'GETDATE()');
		self::$sqlSrv->update(self::$tableName,$values,array('test_dateTime'=>'2010-06-02 12:00:00'));
		$phpTime = new DateTime();
		$stmt = sqlsrv_query(self::$dbc, "SELECT test_dateTime from ".self::$tableName.' ORDER BY test_dateTime DESC');
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
		$this->assertLessThanOrEqual(3, $row[0]->getTimestamp() - $phpTime->getTimestamp());
	}

	public function testBulkUpdate(){
		$oldVals = array("test_dateTime"=> '2010-06-02 12:00:00',"test_numeric"=> 12.34, "test_int"=> 5,
		"test_varChar"=> '1234567890', "test_bit"=> 0, "test_float"=> 6.7);

		$newVals = array("test_dateTime"=> '2011-07-03 13:00:00',"test_numeric"=> 10.10, "test_int"=> 2,
			"test_varChar"=> 'some chars', "test_bit"=> 1, "test_float"=> 2.25);

		self::$sqlSrv->update(self::$tableName, $oldVals, $newVals);

		$stmt = sqlsrv_query(self::$dbc, "SELECT 
		test_dateTime,test_numeric,test_int,test_varChar,test_bit,test_float 
		FROM ".self::$tableName);

		$row1 = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
		$row2 = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);

		$this->assertEquals($row1,$row2, 'The 2 rows are not the same after bulk update');
	}

	public function testUpdateSetAndWhereNull(){
		//There's no "update all" feature with the update function, but I want to cover "setting a null value".
		//I manually set all the values to 0, so I can update them all with nulls to test the feature.
		sqlsrv_query(self::$dbc, "UPDATE ".self::$tableName." SET test_int = 0");

		self::$sqlSrv->update(self::$tableName,array('test_int' => null),array('test_int' => '0'));
		$stmt = sqlsrv_query(self::$dbc, "SELECT test_int FROM ".self::$tableName);
		$nullRowCount = 0;
		while($nullRow = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)){
			$this->assertEquals(NULL,$nullRow['test_int']);
			$nullRowCount++;
		}
		$this->assertGreaterThan(0,$nullRowCount);

		self::$sqlSrv->update(self::$tableName,array('test_int' => 100),array('test_int' => null));
		$stmt = sqlsrv_query(self::$dbc, "SELECT test_int FROM ".self::$tableName);
		$nonNullRowCount = 0;
		while($nonNullRow = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)){
			$this->assertEquals(100,$nonNullRow['test_int']);
			$nonNullRowCount++;
		}
		$this->assertGreaterThan(0,$nonNullRowCount);
	}

	function testAffectedRows(){

		self::$sqlSrv->insert(self::$tableName,array('test_numeric' => 2));
		$this->assertEquals(1,self::$sqlSrv->affected_rows());

		self::$sqlSrv->query("DELETE FROM ".self::$tableName);
		$this->assertEquals(3,self::$sqlSrv->affected_rows());
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp #Error Getting Rows Affected!.*#
	 */
	function testAffectedRowsException(){
		self::$sqlSrv->query("SELECT test_numeric FROM ".self::$tableName,array(),array("Scrollable" => SQLSRV_CURSOR_STATIC ));
		//Should throw exception!
		self::$sqlSrv->affected_rows();
	}

	public function tearDown(){
		sqlsrv_query(self::$dbc, "TRUNCATE TABLE ".self::$tableName);
	}

	public static function tearDownAfterClass(){
		self::$dbc = NULL;
		self::$sqlSrv = NULL;
	}
}