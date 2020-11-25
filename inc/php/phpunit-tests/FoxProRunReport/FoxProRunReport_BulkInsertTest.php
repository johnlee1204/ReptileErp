<?php
require_once("C:/server/data/apacheData/dev.local/inc/php/classes/sqlsrv_helper.php");
require_once("C:/server/data/apacheData/dev.local/inc/php/classes/FoxProRunReport.php");
class FoxProRunReport_BulkInsertTest extends PHPUnit_Framework_TestCase{

	public function insertProvider(){
		return array(
			array(100, 20),
			array(20, 100),
			array(150, 30),
			array(33, 151),
			array(260, 10),
			array(1, 1),
			array(1, 3),
			array(3, 1),
		);
	}

	/**
	 * @dataProvider insertProvider
	 * @param $rows
	 * @param $columns
	 */
	function testBulkInsert($rows, $columns)
	{
		$localDbcObj = new sqlsrv_helper('m2m');

		$testData = $this->generateFakeData($rows, $columns);

		//print_r($testData);

		$foxProInstance = new FoxProRunReport(array(
			'mssqlHelperInstance' => $localDbcObj,
			'template' => 'dummy',
			'data' => $testData
		));

		$uniqueId = str_replace('.','',uniqid('',true));
		$foxProInstance->buildTempTableFromData($uniqueId);

		//get all the data from the new table
		$insertedData = $localDbcObj->fetch_all_assoc('select * from '.$foxProInstance->tableName);

		//print_r($insertedData);

		//cleanup
		$localDbcObj->query('drop table '.$foxProInstance->tableName);

		$this->assertEquals($insertedData, $testData);
	}

	function generateFakeData($rows, $cols){
		$data = array();

		while($rows--){
			$colsI = $cols;
			$row = [];
			while($colsI--){
				$row ['col'.$colsI] = 'row'.$rows.'col'.$colsI;
			}
			$data[] = $row;
		}

		return $data;
	}
}