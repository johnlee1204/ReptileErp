<?php

namespace Employee\Models;
use AgileModel;

class EmployeeModel extends AgileModel{
	static function readEmployees() {
		return self::$database->fetch_all_row("
			SELECT
				employeeId,
				employeeNumber,
				username,
				firstName,
				lastName,
				email,
				hireDate,
				terminationDate,
				payRate
			FROM Employee
			ORDER BY firstName
		");
	}

	static function readEmployee($employeeId) {
		return self::$database->fetch_assoc("
			SELECT
				employeeNumber,
				username,
				firstName,
				lastName,
				email,
				hireDate,
				terminationDate,
				payRate
			FROM Employee
			WHERE
				employeeId = ?
		", [$employeeId]);
	}

	static function createEmployee($inputs) {

		if($inputs['hireDate'] === "") {
			$inputs['hireDate'] = NULL;
		}
		if($inputs['terminationDate'] === "") {
			$inputs['terminationDate'] = NULL;
		}

		self::$database->query("
			INSERT INTO Employee(
				employeeNumber,
				username,
				firstName,
				lastName,
				email,
				hireDate,
				terminationDate,
				payRate
			) VALUES (?,?,?,?,?,?,?,?)
		", [
			$inputs['employeeNumber'],
			$inputs['username'],
			$inputs['firstName'],
			$inputs['lastName'],
			$inputs['email'],
			$inputs['hireDate'],
			$inputs['terminationDate'],
			$inputs['payRate']
		]);

		return self::readMaxEmployee();
	}

	static function updateEmployee($inputs) {

		if($inputs['hireDate'] === "") {
			$inputs['hireDate'] = NULL;
		}
		if($inputs['terminationDate'] === "") {
			$inputs['terminationDate'] = NULL;
		}

		self::$database->query("
			UPDATE Employee
			SET
				employeeNumber = ?,
				username = ?,
				firstName = ?,
				lastName = ?,
				email = ?,
				hireDate = ?,
				terminationDate = ?,
				payRate = ?
			WHERE
				employeeId = ?
		", [
			$inputs['employeeNumber'],
			$inputs['username'],
			$inputs['firstName'],
			$inputs['lastName'],
			$inputs['email'],
			$inputs['hireDate'],
			$inputs['terminationDate'],
			$inputs['payRate'],
			$inputs['employeeId']
		]);
	}

	static function deleteEmployee($employeeId) {
		self::$database->query("
			DELETE FROM Employee
			WHERE
				employeeId = ?
		", [$employeeId]);
	}

	static function readMaxEmployee() {
		$employee = self::$database->fetch_assoc("
			SELECT
				MAX(employeeId) employeeId
			FROM Employee
		");

		return $employee['employeeId'];
	}

	static function readEmployeesComboData() {
		return self::$database->fetch_all_row("
			SELECT
				employeeId,
				CONCAT(firstName, ' ', lastName) name
			FROM Employee
			ORDER BY firstName
		");
	}

}