<?php

/**
 * Class sqlsrv_helperTest
 * We assume that the basic query function here works, rather this test suite is
 * for testing the library's ability to build complex queries through its build
 * functions and we use hand written queries to test that it built and executed
 * the commands correctly.
 */

require_once("C:/server/data/apacheData/dev.local/inc/php/classes/sqlsrv_helper.php");

class sqlsrv_helperInsertTest extends PHPUnit_Framework_TestCase{
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

	public function insertProvider(){
		return array(
			array('test_dateTime', new DateTime()),
			array('test_numeric', 2.04),
			array('test_int', 32),
			array('test_text', "THIS IS A LONG STRING OF TEXT USED TO TEST THAT WE ARE TRULY USING A TEXT FIELD :)"),
			array('test_varChar',"20 CHARACTER MAXIMUM"),
			array('test_bit', 0),
			array('test_float', 12.4324)
		);
	}

	/**
	 * @dataProvider insertProvider
	 * @param $column
	 * @param $value
	 */
	public function testSingleInsert($column, $value){
		$values = array($column=>$value);
		self::$sqlSrv->insert(self::$tableName,$values);
		$stmt = sqlsrv_query(self::$dbc, "SELECT ".$column." from ".self::$tableName);
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		$this->assertEquals($value, $row[$column]);
	}

	public function testSingleInsertOutput(){
		$randInt = rand(1,1000);
		$values = array('test_int'=> $randInt);

		$output = self::$sqlSrv->insert(self::$tableName,$values,'test_int');
		$stmt = sqlsrv_query(self::$dbc, "SELECT test_dateTime from ".self::$tableName);
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);

		$this->assertEquals($output['test_int'],$randInt);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Error building query, empty data array passed
	 */
	public function testBadEmptyInsert(){
		self::$sqlSrv->insert(self::$tableName,array());
	}

	public function testFunctionInsert(){
		$values = array('test_dateTime'=> 'GETDATE()');

		self::$sqlSrv->insert(self::$tableName,$values);
		$phpTime = new DateTime();
		$stmt = sqlsrv_query(self::$dbc, "SELECT test_dateTime from ".self::$tableName);
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);

		$this->assertLessThanOrEqual(3, $row[0]->getTimestamp() - $phpTime->getTimestamp());
	}

	public function testMultiInsert(){
		$data = array('test_dateTime'=> new DateTime(),
			'test_numeric'=> 2.04,
			'test_int'=> 32,
			'test_text'=> "THIS IS A LONG STRING OF TEXT USED TO TEST THAT WE ARE TRULY USING A TEXT FIELD :)",
			'test_varChar'=>"20 CHARACTER MAXIMUM",
			'test_bit'=> 0,
			'test_float'=> 12.432);


		self::$sqlSrv->insert(self::$tableName,$data);
		$stmt = sqlsrv_query(self::$dbc, "SELECT ".implode(',',array_keys($data))." from ".self::$tableName);
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

		$this->assertEquals($data, $row);
	}

	/**
	 * @expectedException Exception
	 */
	public function testBadInsertBatch(){
		self::$sqlSrv->insertBatch(self::$tableName,array());
	}

	/**
	 * @expectedException Exception
	 */
	public function testBadInsertBatch2(){
		$data = array();
		$data[] = array('column1' => 1, 'column2' => 2, 'column3' => 3, 'column4' => 4);
		self::$sqlSrv->insertBatch(self::$tableName,$data,3);
	}

	/**
	 * @dataProvider insertBatchDataProvider
	 */
	public function testInsertBatch($count){

		$data = array();
		for($i=1;$i<=$count;$i++){
			$data[] = array(
				'test_int' => $i
			);
		}
		
		self::$sqlSrv->insertBatch(self::$tableName,$data);
		$stmt = sqlsrv_query(self::$dbc, "SELECT test_int from ".self::$tableName);
		$countCheck = 0;
		while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
			$countCheck++;
			$this->assertEquals($countCheck,$row['test_int']);
		}
		$this->assertEquals($countCheck,$count);
	}

	public function insertBatchDataProvider(){
		return [
			[1],
			[2],
			[5],
			[10],
			[998],
			[999],
			[1000],
			[1001],
			[1002]
		];
	}

	public function mismatchTypeProvider(){
		return array(
			array('test_varChar', "this text is too long and I should get a truncate error"),
			array('invalid_fieldname', "bogus"),
			array('test_int', "text"),
			array('test_numeric', "text invalid"),
			array('test_dateTime', "clearly not a date"),
			array('test_bit',"Can't cast this down :P")
		);
	}

	/**
	 * Testing that giving bad data raises query errors
	 * @dataProvider mismatchTypeProvider
	 */
	public function testBadInsert($column, $value){
		$this->setExpectedExceptionRegExp('Exception', '/Query Error.*/');
		$values = array($column=>$value);

		self::$sqlSrv->insert(self::$tableName,$values);
		$stmt = sqlsrv_query(self::$dbc, "SELECT ".$column." from ".self::$tableName);
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);

		$this->assertNotEquals($value, $row[0]);
	}

	public function tearDown(){
		sqlsrv_query(self::$dbc, "TRUNCATE TABLE ".self::$tableName);
	}

	public static function tearDownAfterClass(){
		self::$dbc = NULL;
		self::$sqlSrv = NULL;
	}
}
