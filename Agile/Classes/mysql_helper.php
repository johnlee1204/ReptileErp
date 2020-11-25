<?php


class mysql_helper
{
	private $connection;
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

	public function fetch_assoc($sql, $params = []) {
		$statement = $this->query($sql, $params);
		$result = $statement->get_result();
		if($result === FALSE) {
			var_dump($this->connection->error);
			die();
		}
		return $result->fetch_assoc();
	}

	public function fetch_row($sql, $params = []) {
		$statement = $this->query($sql, $params);
		$result = $statement->get_result();
		if($result === FALSE) {
			var_dump($this->connection->error);
			die();
		}
		return $result->fetch_row();
	}

	public function fetch_all_assoc($sql, $params = []) {
		$statement = $this->query($sql, $params);
		$result = $statement->get_result();
		if($result === FALSE) {
			var_dump($this->connection->error);
			die();
		}

		$output = [];
		while($row = $result->fetch_assoc()) {
			$output[] = $row;
		}
		return $output;
	}

	public function fetch_all_row($sql, $params = []) {
		$statement = $this->query($sql, $params);
		$result = $statement->get_result();
		if($result === FALSE) {
			var_dump($this->connection->error);
			die();
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
			var_dump($this->connection->error);
			die();
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
		return $statement;
	}
}