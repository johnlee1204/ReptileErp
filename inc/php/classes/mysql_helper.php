<?php

class mysql_helper {

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
		
		$this->connected = false;
		$this->error = false;
		
		$this->server = $server;
		$this->port = $port;
		$this->user = $user;
		$this->password = $password;
		$this->database = $database;
		
		$this->queryResource = false;
		
		$this->dieOnError = false;
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
			$this->port = '3306';
		}
		
		$serverString=$this->server.":".$this->port;
	
		if( FALSE === $this->connectionResource = mysql_connect($serverString, $this->user, $this->password) ){
			return $this->error("Could not connecto to mysql server {$serverString}. ".$this->getLastMessage());
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
			if(!$this->connect() ){
				return $this->error("Error connecting to database!");
			}
		}
		
		if(FALSE === $changeDbResult = mysql_select_db($db, $this->connectionResource) ){
			return $this->error("Could not change to database {$db}. ".$this->getLastMessage());
		}
			
		$this->currentDatabase = $db;
		return true;
	}

	function getLastMessage(){
		return mysql_errno($this->connectionResource).' '.mysql_error($this->connectionResource);
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
		
		if(FALSE === $this->queryResource = mysql_query($sql, $this->connectionResource) ){
			return $this->error("Query Error! ".$this->getLastMessage());
		}
		return true;
	}
	
	function insert_id(){
		
		if(!$this->connected){
			if($this->error){
				return $this->error("Query failed due to connection Error! ".$this->errorDesc);
			}
			if(FALSE === $this->connect() ){
				return $this->error("Error connecting to database!");
			}
		}
		
		if(FALSE === $insertId = mysql_insert_id($this->connectionResource)){
			return $this->error("Error getting last identity value!");
		}
		return $insertId;
	}
	
	function affected_rows(){
		if(FALSE === $result = mysql_affected_rows($this->connectionResource) ){
			return $this->error("Error Getting Row Count! ".$this->getLastMessage());
		}
		$this->numRows = $result;
		return $result;
	}
	
	function num_rows(){
		return mysql_num_rows($this->queryResource);
	}
	
	function fetch_assoc($query = false){
		if($query !== false){
			$this->query($query);
		}
		
		return mysql_fetch_assoc($this->queryResource);
	}
	
	function fetch_all_assoc($query = false){
		if($query !== false){
			$this->query($query);
		}
		$rows = array();
		while($row = $this->fetch_assoc()){
			$rows[] = $row;
		}
		return $rows;
	}
	
	function fetch_array($query = false){
		if($query !== false){
			$this->query($query);
		}
		
		return mysql_fetch_array($this->queryResource, MYSQL_NUM);
	}
	
	function fetch_all_array($query = false){
		if($query !== false){
			$this->query($query);
		}
		$rows = array();
		while($row = $this->fetch_array()){
			$rows[] = $row;
		}
		return $rows;
	}
	
	function fetch_row(){
		return mysql_fetch_row($this->queryResource);
	}
	
	function fetch_all_row($query = false){
		if($query !== false){
			$this->query($query);
		}
		$rows = array();
		while($row = $this->fetch_row()){
			$rows[] = $row;
		}
		return $rows;
	}
	
	function data_seek($rowIndex){
		if(FALSE === $result = mysql_data_seek($this->queryResource,$rowIndex) ){
			return $this->error("Error Seeking Query! ".$this->getLastMessage());
		}
		return $result;
	}
} //end of: class mssql_helper

?>