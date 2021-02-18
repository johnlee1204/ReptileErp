<?php

namespace Employee\Models;
use AgileModel;
use AgileUserModel;

class EmployeeModel extends AgileModel{
	static function readEmployees() {
		return self::$database->fetch_all_row("
			SELECT
				employeeId,
				employeeNumber,
				userName,
				firstName,
				lastName,
				email,
				hireDate,
				terminationDate,
				payRate,
			   	position
			FROM Employee
			ORDER BY firstName
		");
	}

	static function readEmployee($employeeId) {
		return self::$database->fetch_assoc("
			SELECT
				employeeNumber,
				userName,
				firstName,
				lastName,
				email,
				hireDate,
				terminationDate,
				payRate,
			    position
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

		$userModel = self::$agileApp->loadModel('AgileUserModel');

		return $userModel->createUser($inputs);
	}

	static function updateEmployee($inputs) {

		if($inputs['hireDate'] === "") {
			$inputs['hireDate'] = NULL;
		}
		if($inputs['terminationDate'] === "") {
			$inputs['terminationDate'] = NULL;
		}

		self::$database->update(
			'Employee',
			[
				'employeeNumber' => $inputs['employeeNumber'],
				'userName' => $inputs['userName'],
				'firstName' => $inputs['firstName'],
				'lastName' => $inputs['lastName'],
				'email' => $inputs['email'],
				'hireDate' => $inputs['hireDate'],
				'terminationDate' => $inputs['terminationDate'],
				'payRate' => $inputs['payRate'],
				'position' => $inputs['position']
			],
			['employeeId' => $inputs['employeeId']]
		);
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