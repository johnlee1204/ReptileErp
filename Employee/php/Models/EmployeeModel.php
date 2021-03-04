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
			   	position,
			   	lastLogin,
			   	lastActivity
			FROM Employee
			ORDER BY employeeNumber
		");
	}

	static function readEmployee($employeeId) {
		$employee = self::$database->fetch_assoc("
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

		$employee['permissions'] = self::$database->fetch_all_row("SELECT groupId FROM userGroups WHERE userId = ?", [$employeeId]);
		return $employee;
	}

	static function createEmployee($inputs) {

		if($inputs['hireDate'] === "") {
			$inputs['hireDate'] = NULL;
		}
		if($inputs['terminationDate'] === "") {
			$inputs['terminationDate'] = NULL;
		}

		$inputs['groupIds'] = json_decode($inputs['permissions']);

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

		$inputs['groupIds'] = json_decode($inputs['permissions']);

		$userModel = self::$agileApp->loadModel('AgileUserModel');

		return $userModel->updateUser($inputs['employeeId'], $inputs);
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