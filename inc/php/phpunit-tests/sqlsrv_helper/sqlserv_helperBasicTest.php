<?php
require_once("C:/server/data/apacheData/dev.local/inc/php/classes/sqlsrv_helper.php");
class sqlserv_helperBasicTest extends PHPUnit_Framework_TestCase{

	function testConstructor(){
		$localDbcObj = new sqlsrv_helper('m2m');
		require($_SERVER['DOCUMENT_ROOT'].'/inc/php/models/credentials/m2m.php');
		$this->assertEquals($server,$localDbcObj->server);
		$this->assertEquals($user,$localDbcObj->user);
		$this->assertEquals($password,$localDbcObj->password);

		$localDbcObj = new sqlsrv_helper('m2m');
		$this->assertEquals(array(),$localDbcObj->configs);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp #Could not connect to server .*#
	 */
	function testConnectBadPasswordParam(){
		$localDbcObj = new sqlsrv_helper('m2m');
		$localDbcObj->password = 'bad password';
		$localDbcObj->connect();
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp #Could not connect to server .*#
	 */
	function testConnectConnectionFailed(){
		$localDbcObj = new sqlsrv_helper();
		$localDbcObj->server = '12345';
		$localDbcObj->user = '';
		$localDbcObj->password = '';
		$localDbcObj->configs['LoginTimeout'] = 3;
		$localDbcObj->connect();
	}

	function testConnectClose(){
		$localDbcObj = new sqlsrv_helper('m2m');
		$this->assertEquals(false,$localDbcObj->connected);
		$localDbcObj->connect();
		$this->assertEquals(true,$localDbcObj->connected);
		$localDbcObj->close();
		$this->assertEquals(false,$localDbcObj->connected);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Missing Server IP/hostname Parameter.
	 */
	function testConnectNoParams(){
		$localDbcObj = new sqlsrv_helper();
		$localDbcObj->connect();
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Missing Server IP/hostname Parameter.
	 */
	function testConnectNullServerParam(){
		$localDbcObj = new sqlsrv_helper();
		$localDbcObj->server = null;
		$localDbcObj->connect();
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Missing Server IP/hostname Parameter.
	 */
	function testConnectNullUserParam(){
		$localDbcObj = new sqlsrv_helper();
		$localDbcObj->user = null;
		$localDbcObj->connect();
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Missing Server IP/hostname Parameter.
	 */
	function testConnectNullServerAndUserParam(){
		$localDbcObj = new sqlsrv_helper();
		$localDbcObj->server = null;
		$localDbcObj->user = null;
		$localDbcObj->connect();
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Missing User Parameter.
	 */
	function testConnectBlankServerNullUserParam(){
		$localDbcObj = new sqlsrv_helper();
		$localDbcObj->server = '';
		$localDbcObj->user = null;
		$localDbcObj->connect();
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Missing Password Parameter.
	 */
	function testConnectNullPasswordParam(){
		$localDbcObj = new sqlsrv_helper();
		$localDbcObj->server = '';
		$localDbcObj->user = '';
		$localDbcObj->password = null;
		$localDbcObj->connect();
	}

	function testSetConfigFunctions(){
		$localDbcObj = new sqlsrv_helper();
		$configArray = array('key' => 'value','key2' => 'value2');
		$localDbcObj->setConfigArray($configArray);
		$this->assertEquals($configArray,$localDbcObj->configs);

		$changedKey = 'key2';
		$changedValue = 'newValue2';
		$configArray[$changedKey] = $changedValue;
		$localDbcObj->setConfig($changedKey,$changedValue);
		$this->assertEquals($configArray,$localDbcObj->configs);

		$newKey = 'key3';
		$newValue = 'value3';
		$configArray[$newKey] = $newValue;
		$localDbcObj->setConfig($newKey,$newValue);
		$this->assertEquals($configArray,$localDbcObj->configs);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp #Could not select database DATABASE DOES NOT EXIST.*#
	 */
	function testSelectDatabase(){
		$localDbcObj = new sqlsrv_helper('m2m');
		$localDbcObj->select_db('FWE_DEV');
		$localDbcObj->select_db('DATABASE DOES NOT EXIST');
	}

	function testTransaction(){
		$tableName = '#TestTable';
		$localDbcObj = new sqlsrv_helper('m2m');
		$localDbcObj->begin_transaction();
		$localDbcObj->query("CREATE TABLE {$tableName}(
			c1 int,
			c2 varchar(50)
			);");
		$localDbcObj->insert($tableName,array('c1' => 1,'c2' => 'Test'));
		$localDbcObj->insert($tableName,array('c1' => 2,'c2' => 'Test2'));
		$localDbcObj->commit();
		$results = $localDbcObj->fetch_all_assoc("SELECT c1,c2 FROM {$tableName}");
		$this->assertCount(2,$results);

		$localDbcObj->begin_transaction();
		$localDbcObj->insert($tableName,array('c1' => 3,'c2' => 'Test3'));
		$localDbcObj->rollback();
		$results = $localDbcObj->fetch_all_assoc("SELECT c1,c2 FROM {$tableName}");
		$this->assertCount(2,$results);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp #Error Calling Begin Transaction!.*#
	 */
	function testTransactionException(){
		$localDbcObj = new sqlsrv_helper('m2m');
		$localDbcObj->begin_transaction();
		$localDbcObj->begin_transaction();
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp #Error Calling Commit Transaction!.*#
	 */
	function testTransactionCommitException(){
		$localDbcObj = new sqlsrv_helper('m2m');
		$localDbcObj->begin_transaction();
		$localDbcObj->commit();
		$localDbcObj->commit();
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp #Error Calling RollBack Transaction!.*#
	 */
	function testTransactionRollbackException(){
		$localDbcObj = new sqlsrv_helper('m2m');
		$localDbcObj->begin_transaction();
		$localDbcObj->rollback();
		$localDbcObj->rollback();
	}

}