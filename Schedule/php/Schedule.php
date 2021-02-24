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
		'deleteLabor' => 'delete',
		'readSchedule' => 'read',
		'createShift' => 'create',
		'updateShift' => 'update',
		'deleteShift' => 'delete'
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

	function readSchedule() {
		$input = Validation::validateGet([
			'calendar' => 'numeric'
		]);

		$this->outputSuccessData(ScheduleModel::readSchedule($input['calendar']));
	}

	function createShift() {
		$inputs = Validation::validateJsonInput([
			'employeeId' => 'notBlank',
			'startDate' => 'notBlank',
			'startTime' => 'notBlank',
			'endDate' => 'notBlank',
			'endTime' => 'notBlank',
			'type' => 'numeric',
			'title'
		]);

		ScheduleModel::createShift($inputs);

		$this->outputSuccess();
	}

	function updateShift() {
		$inputs = Validation::validateJsonInput([
			'scheduleId' => 'numeric',
			'employeeId' => 'notBlank',
			'startDate' => 'notBlank',
			'startTime' => 'notBlank',
			'endDate' => 'notBlank',
			'endTime' => 'notBlank',
			'type' => 'numeric',
			'title'
		]);

		ScheduleModel::updateShift($inputs);

		$this->outputSuccess();
	}

	function deleteShift() {
		$input = Validation::validateJsonInput([
			'scheduleId' => 'numeric'
		]);

		ScheduleModel::deleteShift($input['scheduleId']);

		$this->outputSuccess();
	}

}