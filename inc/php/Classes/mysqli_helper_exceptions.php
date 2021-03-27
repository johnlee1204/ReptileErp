<?php

class mysqli_helper {

	public $server;	//server ip or hostname
	public $port;	//server port (1433 for MS SQL, t5 )
	public $user;
	public $password;
	public $database;	//not required

	public $connected;	//$this->connected = FALSE; //BOOL for determining of there is an active connection to a database
	public $error;	//$this->error = FALSE;
	public $errorDesc;	//description of last error that occured, typically used when a function returns FALSE
	
	public $connectionResource;
	public $mysqli_result;
	public $numRows;

	function __construct($model = FALSE){
	
		$this->server = FALSE;
		
		$this->connected = FALSE;
		$this->error = FALSE;
		
		$this->mysqli_result = FALSE;
		
		$this->dieOnError = FALSE;
			
		if($model !== FALSE){
			require( $_SERVER['DOCUMENT_ROOT'] . '/inc/php/models/credentials/'.$model.'.php' );
			
			$this->server = $server;
			$this->port = $port;
			$this->user = $user;
			$this->password = $password;
			$this->database = $database;
		}
		
	}
	
	function error($errorDesc = 'unknown error'){
		$this->error = TRUE;
		$this->errorDesc = 'mysqli_helper FATAL ERROR ' . $errorDesc;
		
		throw new Exception($this->errorDesc);
		
		if($this->dieOnError){
			die("Database Error! ".$this->errorDesc);
		}
		return FALSE;
		
	}
	
	function connect(){
		$serverString = "";
		$connectionResult;
		
		if($this->server === FALSE){
			return $this->error("Server Connection Parameter is not set! Verify your configuration!");
		}
		
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
	
		if( !isset($this->database) || trim($this->database) == '' ){
			$this->database = NULL;
		}
		
		$this->mysqli = new mysqli($this->server, $this->user, $this->password, $this->database, $this->port);
		
		if ($this->mysqli->connect_error) {
			return $this->error("Could not connecto to mysql server {$this->server}. Error # ".$this->mysqli->connect_errno ." ". $this->mysqli->connect_error);
		}
		
		$this->connected = TRUE;
		
		return TRUE;
	}

	function select_db($database){
	
		if(!$this->connected){
			if($this->error){
				return $this->error("Connection Error. ".$this->errorDesc);
			}
			if(!$this->connect() ){
				return $this->error("Error connecting to database!");
			}
		}
		
		if(FALSE === $this->mysqli->select_db($database) ){
			return $this->error("Could not change to database {$database}. ".$this->getLastMessage());
		}
			
		$this->currentDatabase = $database;
		return TRUE;
	}

	function getLastMessage(){
		return $this->mysqli->errno.' '.$this->mysqli->error;
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
		
		if(FALSE === $this->mysqli_result = $this->mysqli->query($sql) ){
			return $this->error("Query Error! ".$this->getLastMessage() ." Query: ". $sql );
		}
		return TRUE;
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
		
		if(0 === $insertId = $this->mysqli->insert_id){
			return $this->error("Error getting last identity value!");
		}
		return $insertId;
	}
	
	function affected_rows(){
		if(-1 === $result = $this->mysqli->affected_rows ){
			return $this->error("Error Getting Row Count! ".$this->getLastMessage());
		}
		$this->numRows = $result;
		return $result;
	}
	
	function num_rows(){
		if(FALSE === $this->mysqli_result ){
			return $this->error("Error Getting Row Count! mysqli_result does not exist.");
		}
		//$this->mysqli->store_result(); //not sure if this is needed. Add later if cannot get rows as per the docs
		return $this->mysqli_result->num_rows;
	}
	
	function fetch_assoc($query = FALSE){
		if($query !== FALSE){
			$this->query($query);
		}
		
		if(FALSE === $this->mysqli_result ){
			return $this->error("Error calling fetch_assoc! mysqli_result does not exist.");
		}
		
		return $this->mysqli_result->fetch_assoc();
	}
	
	function fetch_all_assoc($query = FALSE){
		if($query !== FALSE){
			$this->query($query);
		}
		
		if(FALSE === $this->mysqli_result ){
			return $this->error("Error calling fetch_all_assoc! mysqli_result does not exist.");
		}
		
		return $this->mysqli_result->fetch_all(MYSQLI_ASSOC);
	}
	
	function fetch_array($query = FALSE){
		if($query !== FALSE){
			$this->query($query);
		}
		
		if(FALSE === $this->mysqli_result ){
			return $this->error("Error calling fetch_array! mysqli_result does not exist.");
		}
		
		return $this->mysqli_result->fetch_array();
	}
	
	function fetch_all_array($query = FALSE){
		if($query !== FALSE){
			$this->query($query);
		}
		
		if(FALSE === $this->mysqli_result ){
			return $this->error("Error calling fetch_all_array! mysqli_result does not exist.");
		}
		
		return $this->mysqli_result->fetch_all(MYSQLI_NUM);
	}
	
	function fetch_row($query = FALSE){
		if($query !== FALSE){
			$this->query($query);
		}
		
		if(FALSE === $this->mysqli_result ){
			return $this->error("Error calling fetch_row! mysqli_result does not exist.");
		}
		
		return $this->mysqli_result->fetch_row();
	}
	
	function fetch_all_row($query = FALSE){
		if($query !== FALSE){
			$this->query($query);
		}
		
		if(FALSE === $this->mysqli_result ){
			return $this->error("Error calling fetch_all_array! mysqli_result does not exist.");
		}
		
		return $this->mysqli_result->fetch_all(MYSQLI_NUM);
	}
	
	function data_seek($rowIndex){
		if(FALSE === $this->mysqli_result ){
			return $this->error("Error calling data_seek! mysqli_result does not exist.");
		}
		if(FALSE === $result = $this->mysqli_result->data_seek($rowIndex) ){
			return $this->error("Error! data_seek returned FALSE! ".$this->getLastMessage());
		}
		return $result;
	}
	
	function prepare($query){
	
		if(!$this->connected){
			if($this->error){
				return $this->error("Connection Error. ".$this->errorDesc);
			}
			if(!$this->connect() ){
				return $this->error("Error connecting to database!");
			}
		}
		
		if(FALSE === $statement = $this->mysqli->prepare($query) ){
			return $this->error("Query Prepare Error! ".$this->getLastMessage() ." Query: ". $query );
		}
		
		return $statement;
		
	}
	
	function escape_string($escapestr){
	
		return $this->real_escape_string($escapestr);
	}
	
	function real_escape_string($escapestr){
	
		if(!$this->connected){
			if($this->error){
				return $this->error("Connection Error. ".$this->errorDesc);
			}
			if(!$this->connect() ){
				return $this->error("Error connecting to database!");
			}
		}
		
		return $this->mysqli->real_escape_string($escapestr);
	}
	
	
} //end of: class mssql_helper

?>