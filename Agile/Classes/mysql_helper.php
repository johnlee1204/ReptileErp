<?php


class mysql_helper
{
	private $connection;
	private $statement;
	public function __construct() {
		$servername = "localhost";
		$username = "root";
		$password = "Echo120499!";
		$database = "LeeSheet";

		$this->connection = new mysqli($servername, $username, $password, $database);
		if ($this->connection->connect_error) {
			die("Connection failed: " . $this->connection->connect_error);
		}
	}

	public function selectDatabase($database) {
		$this->connection->select_db($database);
	}

	public function fetch_assoc($sql = FALSE, $params = []) {
		if($sql !== FALSE) {
			$statement = $this->query($sql, $params);
		} else {
			$statement = $this->statement;
		}

		$result = $statement->get_result();
		if($result === FALSE) {
			throw new Exception($this->connection->error);
		}
		return $result->fetch_assoc();
	}

	public function fetch_row($sql = FALSE, $params = []) {
		if($sql !== FALSE) {
			$statement = $this->query($sql, $params);
		} else {
			$statement = $this->statement;
		}

		$result = $statement->get_result();
		if($result === FALSE) {
			throw new Exception($this->connection->error);
		}
		return $result->fetch_row();
	}

	public function fetch_all_assoc($sql = FALSE, $params = []) {
		if($sql !== FALSE) {
			$statement = $this->query($sql, $params);
		} else {
			$statement = $this->statement;
		}

		$result = $statement->get_result();
		if($result === FALSE) {
			throw new Exception($this->connection->error);
		}

		$output = [];
		while($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
		return $output;
	}

	public function fetch_all_row($sql = FALSE, $params = []) {
		if($sql !== FALSE) {
			$statement = $this->query($sql, $params);
		} else {
			$statement = $this->statement;
		}

		$result = $statement->get_result();
		if($result === FALSE) {
			throw new Exception($this->connection->error);
		}

		$output = [];
		while($row = $result->fetch_row()) {
			$output[] = $row;
		}
		return $output;
	}

	public function query($sql, $params = []) {
		$statement = $this->connection->prepare($sql);
		if($statement === FALSE) {
			throw new Exception($this->connection->error);
		}

		$types = "";
		foreach($params as $param) {
			switch (gettype($param)) {
				case "integer":
					$types .= 'i';
					break;
				case "double":
					$types .= 'd';
					break;
				default:
					$types .= 's';
			}
		}

		if($types !== "") {
			$statement->bind_param($types, ...$params);
		}

		$statement->execute();

		if($statement->error) {
			throw new Exception($statement->error);
		}
		$this->statement = $statement;
		return $statement;
	}

	public function begin_transaction() {
		$this->connection->begin_transaction();
	}

	public function commit_transaction() {
		$this->connection->commit();
	}

	public function rollback_transaction() {
		$this->connection->rollback();
	}

	public function getInserted() {
		return $this->connection->insert_id;
	}

	function build_insert_query($table, $data){

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


		$query = "INSERT INTO {$table} ({$columns}) VALUES ({$valueStr})";

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
	function insert($table, $data){
		$queryData = $this->build_insert_query($table, $data);

		$this->query($queryData["query"], $queryData['values']);

		return $this->fetch_assoc("SELECT LAST_INSERT_ID() id");
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
		$filterValuesArr = [];
		if( count($filters) > 0 ){
			$filterColumnsArr = array();
			$filterValuesArr = array();
			foreach($filters as $filterColumn=>$filterValue){
				$filterValuesArr[] = $filterValue;
				$filterColumnsArr[] = "{$filterColumn} = ?";
			}
			$query .= implode(' AND ',$filterColumnsArr);
		} else {
			$query .= "1 = 1";
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
						continue 2;
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
}