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
		
		$this->dieOnError = false;
		$this->queryResource = false;
	}
	
	function error($text = ''){
	
		$this->error = true;
		$this->errorDesc = $text;
		
		if($this->dieOnError){
			die("Database Error! ".$this->errorDesc);
		}
		return false;
	}
	
	function connect(){
		$serverString = "";
		$connectionResult;
		
		if(!isset($this->server) || trim($this->server) == ''){
			return $this->error("Missing Server IP/hostname Parameter.");
		}
		
		if(!isset($this->user) || trim($this->user) == ''){
			return $this->error("Missing User Parameter.");
		}
		
		if(!isset($this->password) || trim($this->password) == ''){
			return $this->error("Missing Password Parameter.");
		}
		
		if(!isset($this->port) || trim($this->port) == ''){
			$this->port = '1433';
		}
		
		//$serverString=$this->server.",".$this->port;
		$serverString=$this->server;
	
		if( FALSE === $this->connectionResource = mssql_connect($serverString,$this->user,$this->password) ){
			return $this->error("Could not connecto to mssql server {$serverString}. ".$this->getLastMessage());
		}
		$this->connected = true;
		
		if( isset($this->database) && trim($this->database) != '' ){
			if( FALSE === $this->select_db($this->database) ){
				return $this->error("Error changing database");
			}
		}
		return TRUE;
	}

	function select_db($db){
	
		if(!$this->connected){
			if($this->error){
				return $this->error("Connection Error. ".$this->errorDesc);
			}
			if(FALSE === $this->connect() ){
				return $this->error("Error connecting to database!");
			}
		}
		
		if(FALSE === $changeDbResult = mssql_select_db($db,$this->connectionResource) ){
			return $this->error("Could not change to database {$db}. ".$this->getLastMessage());
		}
			
		$this->currentDatabase = $db;
		return true;
	}

	function getLastMessage(){
		return mssql_get_last_message();
	}

	function query($sql){
		$queryResult;
		
		if(!$this->connected){
			if($this->error){
				return $this->error("Query failed due to connection Error! ".$this->errorDesc);
			}
			if(FALSE === $this->connect() ){
				return $this->error("Error connecting to database!");
			}
		}
		
		if(FALSE === $queryResult = mssql_query($sql,$this->connectionResource) ){
			return $this->error("Query Error! ".$this->getLastMessage());
		}
		$this->queryResource = $queryResult;
		return true;
	}
	
	function affected_rows(){
		if(FALSE === $result = mssql_rows_affected($this->connectionResource) ){
			return $this->error("Error Getting Row Count! ".$this->getLastMessage());
		}
		$this->numRows = $result;
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
		if(FALSE === $result = mssql_data_seek($this->queryResource,$rowIndex) ){
			return $this->error("Error Seeking Query! ".$this->getLastMessage());
		}
		return $result;
	}
} //end of: class mssql_helper

?>