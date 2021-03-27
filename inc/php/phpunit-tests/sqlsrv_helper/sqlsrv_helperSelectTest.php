<?php
require_once("C:/server/data/apacheData/dev.local/inc/php/classes/sqlsrv_helper.php");

class sqlsrv_helperSelectTest extends PHPUnit_Framework_TestCase{

	/* @var sqlsrv_helper */
	protected static $sqlSrv;
	protected static $dbc;
	protected static $tableName = "FWE_DEV..unitTestSqlSrvHelper";

	static function setUpBeforeClass(){

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

		$insertTestDataSql = "INSERT INTO ".self::$tableName." VALUES
		('2010-06-02 12:00:00', 12.34, 5, 'abcdefghijklmnopqrstuvwxyz', '1234567890',0,6.7),
		('2011-07-03 13:00:00', 10.10, 2, 'some text here', 'some chars', 1, 2.25)";

		sqlsrv_query(self::$dbc,$insertTestDataSql);
	}

	function selectionsProvider(){
		return array(
			array('test_dateTime', '2010-06-02 12:00:00.000'),
			array('test_numeric', 12.34),
			array('test_int', 5),
			array('cast(test_text as nvarchar(max))', 'abcdefghijklmnopqrstuvwxyz'),
			array('test_varChar', '1234567890'),
			array('test_bit', 0),
			array('test_float', 6.7)
		);
	}

	/**
	 * @dataProvider selectionsProvider
	 * @param $column
	 * @param $value
	 */
	function testSelectAllRow($column, $value){
		self::$sqlSrv->select(self::$tableName, array($column), array());
		$rows = self::$sqlSrv->fetch_all_row();
		$this->assertCount(2,$rows, "Failed to select all of one column");

		$rows = self::$sqlSrv->fetch_all_row("SELECT {$column} FROM ".self::$tableName."");
		$this->assertCount(2,$rows, "Failed to select all of one column");

		self::$sqlSrv->select(self::$tableName, array($column), array($column=>$value));
		$rows = self::$sqlSrv->fetch_all_row();
		$this->assertCount(1,$rows, "Failed simple filter for select");
	}

	/**
	 * @dataProvider selectionsProvider
	 * @param $column
	 * @param $value
	 */
	function testSelectRow($column, $value){
		self::$sqlSrv->select(self::$tableName, array($column), array($column=>$value));
		$row = self::$sqlSrv->fetch_row();
		$this->assertEquals($value,$row[0]);

		$row = self::$sqlSrv->fetch_row("SELECT {$column} FROM ".self::$tableName." WHERE {$column} = ?",array($value));
		$this->assertEquals($value,$row[0]);
	}

	function testFetch(){
		self::$sqlSrv->query("SELECT test_varChar FROM ".self::$tableName,array(),array("Scrollable" => SQLSRV_CURSOR_STATIC));
		//Skip first result, which is 1234567890
		self::$sqlSrv->fetch(SQLSRV_SCROLL_FIRST);
		$result = self::$sqlSrv->fetch_assoc();
		$this->assertEquals("some chars",$result['test_varChar']);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp #Error Calling Fetch on Query Results!.*#
	 */
	function testFetchException(){
		self::$sqlSrv->query("SELECT 'test_varChar' FROM ".self::$tableName,array(),array("Scrollable" => SQLSRV_CURSOR_STATIC));
		//Bad parameter
		self::$sqlSrv->fetch(0);
	}

	function testSelectOrder(){
		self::$sqlSrv->select(self::$tableName,array('test_int'), array(), 'ORDER BY test_int DESC');
		$row = self::$sqlSrv->fetch_row();
		$this->assertEquals(5,$row[0]);
		self::$sqlSrv->select(self::$tableName,array('test_int'), array(), 'ORDER BY test_int ASC');
		$row = self::$sqlSrv->fetch_row();
		$this->assertEquals(2,$row[0]);
	}

	function testSelectLike(){
		self::$sqlSrv->select(self::$tableName,array('test_varChar'),
			array('test_varChar'=>array('type'=>'like','value'=>'123')));
		$row = self::$sqlSrv->fetch_row();
		$this->assertEquals('1234567890',$row[0]);
	}

	function testSelectLikeBefore(){
		self::$sqlSrv->select(self::$tableName,array('test_varChar'),
			array('test_varChar'=>array('type'=>'likebefore','value'=>'890')));
		$row = self::$sqlSrv->fetch_row();
		$this->assertEquals('1234567890',$row[0]);
	}

	function testSelectLikeBeforeAfter(){
		self::$sqlSrv->select(self::$tableName,array('test_varChar'),
			array('test_varChar'=>array('type'=>'likebeforeafter','value'=>'me ch')));
		$row = self::$sqlSrv->fetch_row();
		$this->assertEquals('some chars',$row[0]);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp #Query Error!.*#
	 */
	function testSelectBadLikeType(){
		self::$sqlSrv->select(self::$tableName,array('test_varChar'),
			array('test_varChar'=>array('type'=>'UNKNOWN TYPE','value'=>'me ch')));
		$row = self::$sqlSrv->fetch_row();
		$this->assertEquals(NULL,$row[0]);
	}

	function testSelectNull(){
		self::$sqlSrv->select(self::$tableName,array('test_varChar'),
			array('test_varChar'=>NULL));
		$row = self::$sqlSrv->fetch_row();
		$this->assertEquals(NULL,$row[0]);
	}

	function testSelectAssoc(){
		self::$sqlSrv->select(self::$tableName,array('test_varChar'));
		$result = self::$sqlSrv->fetch_assoc();
		$this->assertEquals('1234567890',$result['test_varChar']);

		$result = self::$sqlSrv->fetch_assoc("SELECT test_varChar FROM ".self::$tableName);
		$this->assertEquals('1234567890',$result['test_varChar']);
	}

	function testSelectAllAssoc(){
		self::$sqlSrv->select(self::$tableName,array('test_varChar'));
		$result = self::$sqlSrv->fetch_all_assoc();
		$this->assertEquals('1234567890',$result[0]['test_varChar']);
		$this->assertEquals('some chars',$result[1]['test_varChar']);

		$result = self::$sqlSrv->fetch_all_assoc("SELECT test_varChar FROM ".self::$tableName);
		$this->assertEquals('1234567890',$result[0]['test_varChar']);
		$this->assertEquals('some chars',$result[1]['test_varChar']);
	}

	/**
	 * Num_rows requires a special "Scrollable" flag on the query to be accurate.
	 */
	function testHasRowsNumRows(){
		self::$sqlSrv->query("SELECT test_varChar FROM ".self::$tableName,array(),array("Scrollable" => SQLSRV_CURSOR_STATIC ));
		$this->assertEquals(true,self::$sqlSrv->has_rows());
		$this->assertEquals(2,self::$sqlSrv->num_rows());

		self::$sqlSrv->query("SELECT test_varChar FROM ".self::$tableName." WHERE test_varChar = '1234567890'",array(),array("Scrollable" => SQLSRV_CURSOR_STATIC ));
		$this->assertEquals(true,self::$sqlSrv->has_rows());
		$this->assertEquals(1,self::$sqlSrv->num_rows());

		self::$sqlSrv->query("SELECT test_varChar FROM ".self::$tableName." WHERE test_varChar = 'NOT IN DATABASE'",array(),array("Scrollable" => SQLSRV_CURSOR_STATIC ));
		$this->assertEquals(false,self::$sqlSrv->has_rows());
		$this->assertEquals(0,self::$sqlSrv->num_rows());
	}

	function testNextResult(){
		self::$sqlSrv->query("
			SELECT TOP 1 
			'fakeData' as fakeColumn 
			INTO #tempNextResult
			FROM ".self::$tableName."
			
			SELECT * from #tempNextResult
		");

		self::$sqlSrv->next_result();

		$this->assertEquals(['fakeColumn' => 'fakeData'],self::$sqlSrv->fetch_assoc());
	}

	/**
	 * We have a special function to get the first query that fetches the results when a multiple queries are executed.
	 * It returns an empty array if none of the queries run had any results.
	 */
	/*
	function testFirstResultAllAssoc(){
		self::$sqlSrv->query("SELECT TOP 1 'fakeData' as fakeColumn INTO #tempFirstAssoc1 FROM ".self::$tableName);
		$this->assertEquals(array(),self::$sqlSrv->first_result_all_assoc());


		self::$sqlSrv->query("
			SELECT TOP 1 'fakeData' as fakeColumn INTO #tempFirstAssoc2 FROM ".self::$tableName.";					
			SELECT test_varChar, fakeColumn FROM ".self::$tableName." 
			LEFT JOIN #tempFirstAssoc2 ON 1=1
			WHERE test_varChar = 'some chars'");

		$this->assertEquals(array(array(
			'test_varChar' => 'some chars',
			'fakeColumn' => 'fakeData'
		)),self::$sqlSrv->first_result_all_assoc());

		self::$sqlSrv->query("
			SELECT TOP 1 
			'fakeData' as fakeColumn 
			INTO #tempFirstAssoc3
			FROM ".self::$tableName."
			
			SELECT * from #tempFirstAssoc3 where 1=2
		");
		$this->assertEquals(array(),self::$sqlSrv->first_result_all_assoc());
	}
	*/

	/**
	 * Running a query without results should give an error when trying to fetch data.
	 *
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp #Error Fetching Data!.*#
	 */
	function testFetchDataException(){
		self::$sqlSrv->query("SELECT TOP 1 'fakeData' as fakeColumn INTO #tempFetchDataException FROM ".self::$tableName);
		$this->assertEquals(array(),self::$sqlSrv->fetch_assoc());
	}

	static function tearDownAfterClass(){
		self::$dbc = NULL;
		self::$sqlSrv = NULL;
	}
}
