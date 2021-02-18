<?php


use Employee\Models\EmployeeModel;

class Employee extends AgileBaseController {

	function readAppInitData() {
		$groupModel = $this->loadModel('AgileGroupModel');
		$groupsAssoc = $groupModel->getAllGroupsArray();
		$groups = [];
		foreach($groupsAssoc as $group) {
			$groups[] = array_values($group);
		}

		$this->outputSuccess([
			'groups' => $groups
		]);
	}

	function readEmployees() {
		$this->outputSuccessData(EmployeeModel::readEmployees());
	}

	function readEmployee() {
		$input = Validation::validateJsonInput([
			'employeeId' => 'numeric'
		]);

		$this->outputSuccessData(EmployeeModel::readEmployee($input['employeeId']));
	}

	function createEmployee() {
		$inputs = Validation::validateJsonInput([
			'employeeNumber' => 'notBlank',
			'userName' => 'notBlank',
			'password' => 'notBlank',
			'firstName' => 'notBlank',
			'lastName' => 'notBlank',
			'email',
			'hireDate',
			'terminationDate',
			'payRate' => 'numericOrNull',
			'position',
			'permissions'
		]);

		$this->outputSuccessData(EmployeeModel::createEmployee($inputs));
	}

	function updateEmployee() {
		$inputs = Validation::validateJsonInput([
			'employeeId' => 'numeric',
			'employeeNumber' => 'notBlank',
			'userName' => 'notBlank',
			'firstName' => 'notBlank',
			'lastName' => 'notBlank',
			'email',
			'hireDate',
			'terminationDate',
			'payRate' => 'numericOrNull',
			'position',
			'permissions'
		]);

		EmployeeModel::updateEmployee($inputs);

		$this->outputSuccess();
	}

	function deleteEmployee() {
		$input = Validation::validateJsonInput([
			'employeeId' => 'numeric'
		]);

		EmployeeModel::deleteEmployee($input['employeeId']);

		$this->outputSuccess();
	}
}