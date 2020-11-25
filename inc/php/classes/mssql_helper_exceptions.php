<?php

class mssql_helper {

	public $server;	//server ip or hostname
	public $port;	//server port (1433 for MS SQL, t5 )
	public $user;
	public $password;
	public $database;	//not required

	public $connected;	//$this->connected = false; //BOOL for determining of there is an active connection to a database
	public $error;	//$this->error = false;
	public $errorDesc;	//description of last error that occured, typically used when a function returns false
	
	public $connectionResource;
	public $queryResource;
	public $numRows;

	function __construct($model){ //$server='', $port='', $user='', $password='', $database=''){
	
		require( $_SERVER['DOCUMENT_ROOT'] . '/inc/php/models/credentials/'.$model.'.php' );
		//$this->rawModel = new mssql_helper($server, $port, $user, $password, $database);
		
		$this->connected = false;
		$this->error = false;
		
		$this->server = $server;
		$this->port = $port;
		$this->user = $user;
		$this->password = $password;
		$this->database = $database;
		//$this->currentDatabase = '';
		
		$this->queryResource = false;
		$this->exitOnError = true;				
	}
	
	function error($errorDesc = 'unknown error'){
		$this->error = true;
		$this->errorDesc = 'mssql_helper FATAL ERROR ' . $errorDesc;
		
		throw new Exception($this->errorDesc);
		
		if($this->exitOnError){
			exit;
		}		
		return false;
		
	}
	
	function connect(){
		$serverString = "";
		$connectionResult;
		
		if(!isset($this->server) || trim($this->server) == ''){				
			return $this->error('Missing Server IP/hostname Parameter.');
		}
		
		if(!isset($this->user) || trim($this->user) == ''){
			return $this->error('Missing User Parameter.');
		}
		
		if(!isset($this->password) || trim($this->password) == ''){
			return $this->error('Missing Password Parameter.');
		}
		
		if(!isset($this->port) || trim($this->port) == ''){
			$this->port = '1433';
		}
		
		//$serverString=$this->server.":".$this->port;
		$serverString=$this->server;
	
		if( FALSE === $this->connectionResource = mssql_connect($serverString,$this->user,$this->password) ){
			return $this->error("Could not connecto to mssql server {$serverString}. ".$this->getLastMessage());
		}
		$this->connected = true;
		
		if( isset($this->database) && trim($this->database) != '' ){
			$this->select_db($this->database);
		}
		return TRUE;
	}

	function select_db($db){
	
		if(!$this->connected){
			$this->connect();
		}
		
		if(FALSE === mssql_select_db($db, $this->connectionResource) ){
			return $this->error("Could not select database {$db}. ".$this->getLastMessage());
		}
			
		//$this->currentDatabase = $db;
		return true;
	}

	function getLastMessage(){
		return mssql_get_last_message();
	}

	function query($sql){
		$queryResult;
		
		if(!$this->connected){
			$this->connect();
		}
		
		if(FALSE === $queryResult = mssql_query($sql,$this->connectionResource) ){
			return $this->error ('Query Error! '.$this->getLastMessage() . "\r\n\r\nFull Query: {$sql}" );
		}
		$this->queryResource = $queryResult;
		return true;
	}
	
	function affected_rows(){
		if(FALSE === $result = mssql_rows_affected($this->connectionResource) ){
			return $this->error ('Error Getting Row Count! '.$this->getLastMessage());
		}
		return $result;
	}
	
	function num_rows(){
		return mssql_num_rows($this->queryResource);
	}
	
	function fetch_assoc($query = false){
		if($query !== FALSE){
			$this->query($query);
		}
		return mssql_fetch_assoc($this->queryResource);
	}
	
	function fetch_all_assoc($query = false){
		if($query !== FALSE){
			$this->query($query);
		}
		
		$output = array();
		if($this->queryResource === FALSE){
			return $output;
		}
		while($row = mssql_fetch_assoc($this->queryResource)){
			$output[] = $row;
		}
		return $output;
	}

	function fetch_row($query = false){
		if($query !== FALSE){
			$this->query($query);
		}
		return mssql_fetch_row($this->queryResource);
	}

	function fetch_all_row($query = false){
		if($query !== FALSE){
			$this->query($query);
		}
		$output = array();
		if($this->queryResource === FALSE){
			return $output;
		}
		while($row = mssql_fetch_row($this->queryResource)){
			$output[] = $row;
		}
		return $output;
	}

	function fetch_array($query = false){
		if($query !== FALSE){
			$this->query($query);
		}
		return mssql_fetch_array($this->queryResource);
	}
	
	function data_seek($rowIndex){
			
		if(FALSE === mssql_data_seek($this->queryResource,$rowIndex) ){
			return $this->error ('Error Seeking Query! '.$this->getLastMessage());
		}
		return true;
	}
	
	function close(){
		if(FALSE === mssql_close($this->connectionResource) ){
			return $this->error ('Error closing connection! '.$this->getLastMessage());
		}
		return true;
		
	}
} //end of: class mssql_helper

?>