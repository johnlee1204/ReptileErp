<?php


use Employee\Models\EmployeeModel;
use Schedule\Models\ScheduleModel;

class Schedule extends AgileBaseController {

	static $AgilePermissions = [
		'index' => 'read',
		'readAppInitData' => 'read',
		'clockOn' => 'read',
		'clockOff' => 'read',
		'readClockOnDetails' => 'read',
		'readEmployeeSchedule' => 'read',
		'readEmployeeLaborHistory' => 'read',
		'readLabor' => 'read',
		'updateLabor' => 'update',
		'deleteLabor' => 'delete'
	];

	function readAppInitData() {
		$this->outputSuccess([
			'employees' => EmployeeModel::readEmployeesComboData()
		]);
	}

	function clockOn() {

		$userInformation = $this->AgileApp->SessionManager->getUserDataFromSession();

		ScheduleModel::clockOn($userInformation['employeeId']);

		$this->outputSuccess();
	}

	function clockOff() {

		$userInformation = $this->AgileApp->SessionManager->getUserDataFromSession();

		ScheduleModel::clockOff($userInformation['employeeId']);

		$this->outputSuccess();
	}

	public function readClockOnDetails() {

		$userInformation = $this->AgileApp->SessionManager->getUserDataFromSession();

		$this->outputSuccessData(ScheduleModel::readClockOnDetails($userInformation['employeeId']));
	}

	function readEmployeeSchedule() {
		$this->outputSuccessData(ScheduleModel::readEmployeeSchedule());
	}

	function readEmployeeLaborHistory() {
		$input = Validation::validateJsonInput([
			'employeeId' => 'numeric'
		]);

		$this->outputSuccessData(ScheduleModel::readEmployeeLaborHistory($input['employeeId']));
	}

	function readLabor() {
		$input = Validation::validateJsonInput([
			'laborId' => 'numeric'
		]);

		$this->outputSuccessData(ScheduleModel::readLabor($input['laborId']));
	}

	function updateLabor() {
		$inputs = Validation::validateJsonInput([
			'laborId' => 'numeric',
			'startTime' => 'notBlank',
			'endTime'
		]);

		ScheduleModel::updateLabor($inputs);

		$this->outputSuccess();
	}

	function deleteLabor() {
		$input = Validation::validateJsonInput([
			'laborId' => 'numeric'
		]);

		ScheduleModel::deleteLabor($input['laborId']);

		$this->outputSuccess();
	}
}