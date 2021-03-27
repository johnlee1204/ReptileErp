<?php

class AgileSessionModel{
	/* @var AgileApp */
	public $AgileApp;
	/* @var sqlsrv_helper */
	public $database;
	private $sessionTable;

	function init(){
		$this->sessionTable = $this->AgileApp->systemConfigs['table']['sessions'];
		$this->sessionDataTable = $this->AgileApp->systemConfigs['database']['systemDatabase'].'.SessionData';
		$this->database = $this->AgileApp->systemDb;
	}
	
	function cleanupSessions(){
		$currentTime = date('Y-m-d H:i:s');
		$sql = "delete from {$this->sessionTable} where expires<=?";
		$this->database->query($sql, [$currentTime]);
		//echo "deleted ".$this->database->affected_rows()." expired sessions<BR>\r\n";
		
		$sql = "delete from {$this->sessionTable} where employeeId is null";
		$this->database->query($sql);
		//echo "deleted ".$this->database->affected_rows()." non logged in sessions<BR>\r\n";
		
	}
	
	function read($sessionId){
		$currentTime = date('Y-m-d H:i:s');
		$sql = "select employeeId, sessionId, expires as expiresTimeStamp from {$this->sessionTable} where sessionId=?";

		try{
			$sessionRecord = $this->database->fetch_assoc($sql, array($sessionId));
		}catch(Exception $ex){
			echo "<HR>\r\nFailed to read session. Your database config is bad.\r\n<HR>\r\n";
			echo $ex->getMessage(),"\r\n<HR>\r\n";
			exit;
		}
		if($sessionRecord === null){
			return $sessionRecord;
		}
		$sessionRecord['expiresTimeStamp'] = strtotime($sessionRecord['expiresTimeStamp']);
		$sessionRecord['nowTimeStamp'] = strtotime($currentTime);
		return $sessionRecord;
	}
	
	function create($sessionId ){
		//Does this session ID exist?
		if(NULL !== $session =  $this->read($sessionId)){
			return FALSE;
		}
		$currentTime = date('Y-m-d H:i:s');
		$sql = "insert into {$this->sessionTable} (created,expires,sessionId) values (?, DATE_ADD(?, INTERVAL ? SECOND), ?)";
		//var_dump($sql, $currentTime, $this->AgileApp->systemConfigs["session"]["length"], $currentTimeSeconds, $sessionId);die();
		$params = array($currentTime,$currentTime, $this->AgileApp->systemConfigs["session"]["length"], $sessionId);

		try{
			$this->database->query($sql, $params);
		}catch(Exception $ex){
			echo "<HR>\r\nFailed to create session. Your database config is bad.\r\n<HR>\r\n";
			echo $ex->getMessage(),"\r\n<HR>\r\n";
			exit;
		}

		return TRUE;
	}
	
	function setUserId($sessionId, $userId){ //update a session
		$sql = "update {$this->sessionTable} set employeeId=? where sessionId=?";
		$this->database->query($sql,array($userId,$sessionId));
	}

	function deleteAllSessionsForUserId($userId){
		$this->database->delete($this->sessionTable, array('employeeId' => $userId));
	}

	function delete($sessionId){
		$sql = "delete from {$this->sessionTable} where sessionId=?";
		$this->database->query($sql, array($sessionId));
//		if($this->database->affected_rows() < 1){ //session not deleted
//			throw new Exception("Session Not Deleted");
//		}
	}

	function createSessionDataKey($sessionId, $key, $value){

		$this->database->insert(
			$this->sessionDataTable,
			array(
				'sessionId'=> $sessionId,
				'dataKey'=>$key,
				'dataValue'=>$value
			)
		);

	}

	function readSessionDataKey($sessionId, $key){

		$this->database->select(
			$this->sessionDataTable,
			array(
				'dataValue'
			),
			array(
				'sessionId'=> $sessionId,
				'dataKey'=>$key
			)
		);

		return $this->database->fetch_assoc();
	}

	function readAllSessionData($sessionId){

		$sessionDataRows = $this->database->fetch_all_assoc("select dataKey, dataValue from {$this->sessionDataTable} where sessionId=?", array($sessionId));

		$sessionData = array();
		foreach($sessionDataRows as $row){
			$sessionData[$row['dataKey']] = $row['dataValue'];
		}

		return $sessionData;
	}

	function updateSessionDataKey($sessionId, $key, $value){

		$this->database->update(
			$this->sessionDataTable,
			array(
				'dataValue'=>$value
			),
			array(
				'sessionId'=>$sessionId,
				'dataKey'=>$key
			)
		);

	}

	function deleteSessionDataKey($sessionId, $key){

		$this->database->delete(
			$this->sessionDataTable,
			array(
				'sessionId'=>$sessionId,
				'dataKey'=>$key
			)
		);
	}
}
