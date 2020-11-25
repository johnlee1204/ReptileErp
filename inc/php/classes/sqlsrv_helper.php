<?php

class sqlsrv_helper {

	public $DefaultLoginTimeout = 5;
	public $DefaultQueryTimeout = 89;

	public $server; //server ip or hostname
	public $user;
	public $password;

	public $database; //not required
	public $databases;

	public $connected = FALSE; //BOOL for determining of there is an active connection to a database
	public $connectionResource = null;
	public $statementResource = null;
	public $queryResource;
	public $numRows;

	function __construct($model = FALSE){ //$server='', $port='', $user='', $password='', $database=''){
			
		if($model !== FALSE){
		
			$credentialsFile = $_SERVER['DOCUMENT_ROOT'] .'/inc/php/models/credentials/'.$model.'.php';
			if(!file_exists($credentialsFile)){
				//Don't change this to an Exception! Exceptions try and log to the database, which will fail here again, causing a white screen of death.
				die("SQLSRV Credentials File Does Not Exist"); // @codeCoverageIgnore
			}
			require( $credentialsFile );
			
			$this->server = $server;
			$this->user = $user;
			$this->password = $password;
			
			if(isset($database)){
				$this->database = $database;
			}
			
		}
		
		$this->configs = array();
	}
		
	function setConfigArray($configArray){
		foreach($configArray as $configName=>$configValue){
			$this->configs[$configName] = $configValue;
		}
	}
	
	function setConfig($configName, $configValue){
		$this->configs[$configName] = $configValue;
	}
	
	function connect(){
		
		if($this->server === null){
			throw new Exception('Missing Server IP/hostname Parameter.');
		}
		
		if($this->user === null){
			throw new Exception('Missing User Parameter.');
		}
		
		if($this->password === null){
			throw new Exception('Missing Password Parameter.');
		}

		$this->configs['UID'] = $this->user;
		$this->configs['PWD'] = $this->password;
		
		if($this->database !== null){
			$this->configs['Database'] = $this->database;
		}

		$this->configs['ReturnDatesAsStrings'] = true;

		$this->configs['LoginTimeout'] = $this->DefaultLoginTimeout;
	
		if( FALSE === $this->connectionResource = sqlsrv_connect($this->server, $this->configs) ){
			throw new Exception("Could not connect to server {$this->server}. ".$this->get_last_message());
		}
		$this->connected = TRUE;
	}

	function select_db($database){
	
		if(FALSE === $this->connected){
			$this->connect();
		}
		
		if(FALSE === sqlsrv_query($this->connectionResource, "USE {$database}") ){
			throw new Exception("Could not select database {$database}. ".$this->get_last_message());
		}
	}

	function get_last_message(){
		
		$errorStr = '';
		$errors = sqlsrv_errors();
		if( $errors != null){
			foreach( $errors as $error){
				$errorStr .= "SQLSTATE: {$error['SQLSTATE']}, code: {$error['code']}, message: {$error['message']}. \r\n";
			}
		}

		return $errorStr;
	}

	function query($sql, $params=array(), $options=array()){
		
		if(FALSE === $this->connected){
			$this->connect();
		}
		/*
		if(!isset($options["Scrollable"])){
			$options["Scrollable"] = SQLSRV_CURSOR_STATIC;
		}
		*/

		if(!isset($options["QueryTimeout"])){
			$options["QueryTimeout"] = $this->DefaultQueryTimeout;
		}
		
		if(FALSE === $this->statementResource = sqlsrv_query($this->connectionResource, $sql, $params, $options) ){
			throw new Exception('Query Error! '.$this->get_last_message() . "<BR>\r\nFull Query: {$sql}<BR>\r\n Params: ".print_r($params, TRUE));
		}
	}
	function affected_rows(){
		return $this->rows_affected();
	}
	function rows_affected(){
		if(FALSE === $result = sqlsrv_rows_affected($this->statementResource) ){
			throw new Exception('Error Getting Rows Affected! '.$this->get_last_message());
		}
		return $result;
	}
	function has_rows(){
		return sqlsrv_has_rows($this->statementResource);
	}
	function num_rows(){
		return sqlsrv_num_rows($this->statementResource);
	}
	function next_result($count=1){
		while($count--){
			$returnVal = sqlsrv_next_result($this->statementResource);
		}
		return $returnVal;
	}

	//No need to test manual debug function.
	//@codeCoverageIgnoreStart
	function next_result_debug(){

		$it = 0;
		do {
			echo $it,"<BR>";
			var_dump(sqlsrv_fetch_array($this->statementResource, SQLSRV_FETCH_ASSOC));
			$it++;
		}while($this->next_result());

	}//@codeCoverageIgnoreEnd

	/*
	function first_result_all_assoc(){
		do {
			$firstRow = sqlsrv_fetch_array($this->statementResource, SQLSRV_FETCH_ASSOC);
			//First non error & non empty result for fetch assoc.
			if(FALSE !== $firstRow && NULL !== $firstRow){
				$allRows = $this->fetch_all_assoc();
				array_unshift($allRows, $firstRow);
				return $allRows;
			}
		}while($this->next_result());
		return array();
	}
	*/

	function fetch_data($type){
		if(FALSE === $result = sqlsrv_fetch_array($this->statementResource, $type)){
			throw new Exception("Error Fetching Data! ".$this->get_last_message());
		}
		return $result;
	}

	/**
	 * @param bool $query
	 * @param array $queryData
	 * @return array|null
	 * @throws Exception
	 */
	function fetch_assoc($query = false, $queryData = array(), $options=array()){
		if($query !== false){
			$this->query($query, $queryData, $options);
		}
		//SQLSRV_FETCH_ASSOC

		return $this->fetch_data(SQLSRV_FETCH_ASSOC);
	}

	/**
	 * @param bool $query
	 * @param array $queryData
	 * @return array
	 * @throws Exception
	 */
	function fetch_all_assoc($query = false, $queryData = array(), $options=array()){
		if($query !== false){
			$this->query($query, $queryData, $options);
		}
		$rows = array();
		while($row = $this->fetch_data(SQLSRV_FETCH_ASSOC)){
			$rows[] = $row;
		}
		return $rows;
	}
	
	function fetch_row($query = false, $queryData = array(), $options=array()){
		if($query !== false){
			$this->query($query, $queryData, $options);
		}
		//SQLSRV_FETCH_NUMERIC

		return $this->fetch_data(SQLSRV_FETCH_NUMERIC);
	}


	/**
	 * @param string|boolean $query
	 * @param array|null $queryData
	 * @return array
	 * @throws Exception
	 */
	function fetch_all_row($query = false, $queryData = array(), $options=array()){
		if($query !== false){
			$this->query($query, $queryData, $options);
		}
		$rows = array();
		while($row = $this->fetch_data(SQLSRV_FETCH_NUMERIC)){
			$rows[] = $row;
		}
		return $rows;
	}

	/**
	 * @param integer $rowIndex
	 * @return bool
	 * @throws Exception
	 */
	function fetch($rowIndex){
		//Note this is only usable if you pass $options=array('Scrollable'=>SQLSRV_CURSOR_STATIC) into your query
		if(FALSE === $result = sqlsrv_fetch ($this->statementResource,$rowIndex) ){
			throw new Exception('Error Calling Fetch on Query Results! '.$this->get_last_message());
		}
		return $result;
	}
	
	function begin_transaction(){
	
		if(FALSE === $this->connected){
			$this->connect();
		}
		
		if(FALSE === sqlsrv_begin_transaction($this->connectionResource)){
			throw new Exception('Error Calling Begin Transaction! '.$this->get_last_message());
		}
	}
	function rollback_transaction(){
		if(FALSE === sqlsrv_rollback($this->connectionResource)){
			throw new Exception('Error Calling RollBack Transaction! '.$this->get_last_message());
		}
	}
	/**
	 * @deprecated renamed to rollback_transaction()
	 */
	function rollback(){
		$this->rollback_transaction();
	}
	function commit_transaction(){
		if(FALSE === sqlsrv_commit($this->connectionResource)){
			throw new Exception('Error Calling Commit Transaction! '.$this->get_last_message());
		}
	}
	/**
	 * @deprecated renamed to commit_transaction()
	 */
	function commit(){
		$this->commit_transaction();
	}
	
	function build_insert_query($table, $data, $outputIdColumn=null){

		if(count($data) < 1){
			throw new Exception('Error building query, empty data array passed');
		}

		$columns = implode(',', array_keys($data));

		$valueStr = array();
		$valuesArr = array();

		foreach ($data as $item)  {
			if(is_string($item) && strtolower(trim($item)) == 'getdate()') {
				$valueStr[] = "getdate()";
			}else{
				$valueStr[] = "?";
				$valuesArr[] = $item;
			}
		}
		$valueStr = implode(',',$valueStr);

		if($outputIdColumn !== null){
			$outputSql = "OUTPUT INSERTED.{$outputIdColumn}";
		}else{
			$outputSql = "";
		}

		$query = "INSERT INTO {$table} ({$columns}) {$outputSql} VALUES ({$valueStr})";

		return array(
			"query"=>$query,
			"values"=>$valuesArr
		);
	}

	/**
	 * @param string $table table name
	 * @param array $data key are column names, values are data to insert
	 * @param null $outputIdColumn
	 * @return array|null
	 * @throws Exception
	 */
	function insert($table, $data, $outputIdColumn=null){
		$queryData = $this->build_insert_query($table, $data, $outputIdColumn);

		$this->query($queryData["query"], $queryData['values']);

		if($outputIdColumn !== null){
			return $this->fetch_assoc();
		}
	}

	/**
	 * @param string $table
	 * @param array $data An associative 2d array of insert data.
	 * @param int $paramLimit (optional) Max number of parameters for each insert statement. Defaults to 1000.
	 * @throws Exception
	 */
	function insertBatch($table, $data, $paramLimit = 1000){
		//sqlsrv max escaped params is 2100. driver will throw an error if you exceed 2100.
		//sql has a hard cap at 1000 rows inserted at once.
		if(!isset($data[0])){
			throw new Exception("data needs to be an associative 2d array");
		}
		$firstRow = $data[0];
		$columns = array_keys($firstRow);
		$columnCount = count($columns);
		if($columnCount > $paramLimit){
			throw new Exception("Cannot insert more columns than the parameter limit! Column Count = {$columnCount}. Parameter Limit = {$paramLimit}.");
		}

		$rowCount = count($data);
		$paramTotal = $columnCount*$rowCount;
		$groupsNeeded = ceil($paramTotal/$paramLimit);
		$chunkSize = floor($rowCount/$groupsNeeded);
		$chunks  = array_chunk($data,$chunkSize);

		foreach($chunks as $chunk) {

			$insertSqlArray = array();
			$queryValues = array();
			foreach ($chunk as $row) {
				$values = array_values($row);
				$rowArray = array();
				foreach ($values as $value) {
					$rowArray[] = "?";
					$queryValues[] = $value;
				}
				$insertSqlArray[] = "(" . implode(",", $rowArray) . ")";
			}
			$insertQuery = "INSERT INTO {$table} (" . implode(",", $columns) . ") VALUES " . implode(",", $insertSqlArray) . "";
			$this->query($insertQuery, $queryValues);
		}
	}

	/**
	 * @param string $table
	 * @param string[] $filters keys are columns, values are filters
	 * @throws Exception
	 */
	function delete($table, $filters){
		$query = "DELETE FROM {$table} WHERE ";

		if( count($filters) > 0 ){
			$filterColumnsArr = array();
			$filterValuesArr = array();
			foreach($filters as $filterColumn=>$filterValue){
				$filterValuesArr[] = $filterValue;
				$filterColumnsArr[] = "{$filterColumn} = ?";
			}
			$query .= implode(' AND ',$filterColumnsArr);
		}

		$this->query($query, array_values($filterValuesArr));
	}

	function build_select_query($table, $columns, $filters, $order=''){

		$query = "SELECT ".implode(',', $columns)." FROM ".$table;

		$filterValuesArr = array();

		if( count($filters) < 1 ) {
			$query .= ' ' . $order;
			return array(
				'query' => $query,
				'values' => $filterValuesArr
			);
		}

		$filterColumnsArr = array();

		foreach($filters as $filterColumn=>$filterValue){
			if(is_array($filterValue)
					&& isset($filterValue['type'])
					&& isset($filterValue['value'])
			){
				switch(strtolower($filterValue['type'])){
					default:
						continue;
					case 'like':
						$filterValuesArr[] = $filterValue['value'];
						$filterColumnsArr[] = "{$filterColumn} LIKE ?+'%'";
						break;
					case 'likebefore':
						$filterValuesArr[] = $filterValue['value'];
						$filterColumnsArr[] = "{$filterColumn} LIKE '%'+?";
						break;
					case 'likebeforeafter':
						$filterValuesArr[] = $filterValue['value'];
						$filterColumnsArr[] = "{$filterColumn} LIKE '%'+?+'%'";
						break;
				}
			}else{
				if($filterValue === NULL){
					$filterColumnsArr[] = "{$filterColumn} IS NULL";
				}else{
					$filterValuesArr[] = $filterValue;
					$filterColumnsArr[] = "{$filterColumn} = ?";
				}
			}
		}
		$query .= " WHERE " . implode(' AND ',$filterColumnsArr);

		$query .= ' ' . $order;
		return array(
			'query' => $query,
			'values' => $filterValuesArr
		);
	}

	/**
	 * @param string $table
	 * @param array $columns
	 * @param array $filters array of arrays where the key is a column name, the value is either:
	 *                    NULL to perform SQL IS NULL check,
	 *                    is a value and checked for equality,
	 *                    or is an array with keys 'type' and 'value' where type is either 'like', 'likebefore', or
	 *                    'likebeforeafter' which correspond to the SQL tests LIKE ?+'%', LIKE '%'+? and LIKE '%'+?+'%'
	 *                    respectively and value is a string
	 * @param string $order
	 * @throws Exception
	 */
	public function select($table, $columns, $filters = array(), $order = ''){

		$queryArray = $this->build_select_query($table, $columns, $filters, $order);

		$this->query($queryArray['query'], array_values($queryArray['values']));
	}

	function build_update_query($table, $updateData, $filters){

		$valuesArr = array();

		$setColSqlArr = array();
		foreach($updateData as $setCol=>$setValue) {

			if ($setValue === NULL) {
				$setColSqlArr[] = "{$setCol}=NULL";
			}else if(strtolower($setValue) == "getdate()") {
				$setColSqlArr[] = "{$setCol}=GETDATE()";
			}else{
				$setColSqlArr[] = "{$setCol}=?";
				$valuesArr[] = $setValue;
			}

		}

		$whereColSqlArr = array();
		foreach($filters as $whereCol=>$whereValue){
			if ($whereValue === NULL) {
				$whereColSqlArr[] = "{$whereCol} IS NULL";
			}else{
				$whereColSqlArr[] = "{$whereCol}=?";
				$valuesArr[] = $whereValue;
			}
		}

		return array(
			'query' => "UPDATE {$table} SET ".implode(', ', $setColSqlArr) ." WHERE ".implode(' AND ', $whereColSqlArr),
			'values' => $valuesArr
		);
		
	}

	function update($table, $updateData, $filters){

		$queryArray = $this->build_update_query($table, $updateData, $filters);
		
		$this->query($queryArray['query'], array_values($queryArray['values']));
	
	}
	
	function close(){
		if(FALSE === sqlsrv_close($this->connectionResource) ){
			throw new Exception('Error closing connection! '.$this->get_last_message()); // @codeCoverageIgnore
		}
		$this->connected = FALSE;

	}
}
