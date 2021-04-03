<?php


use Employee\Models\EmployeeModel;
use Libraries\Excel;
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
		'exportEmployeeLaborHistory' => 'read',
		'readLabor' => 'read',
		'updateLabor' => 'update',
		'deleteLabor' => 'delete',
		'readSchedule' => 'read',
		'createShift' => 'read',
		'updateShift' => 'update',
		'deleteShift' => 'delete'
	];

	function readAppInitData() {
		$userInformation = $this->AgileApp->SessionManager->getUserDataFromSession();
		$this->outputSuccess([
			'employees' => EmployeeModel::readEmployeesComboData(),
			'employeeId' => $userInformation['employeeId'],
			'isScheduleAdmin' => ScheduleModel::isScheduleAdmin()
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

		$labor = ScheduleModel::readEmployeeLaborHistory($input['employeeId']);
		$output = [];

		foreach($labor as $laborRecord) {
			$output[] = array_values($laborRecord);
		}

		$this->outputSuccessData($output);
	}

	function exportEmployeeLaborHistory() {
		$input = Validation::validateGet([
			'employeeId' => 'numeric'
		]);

		$employee = EmployeeModel::readEmployee($input['employeeId']);
		$labor = ScheduleModel::readEmployeeLaborHistory($input['employeeId']);
		$spec = [
			['dataCol' => 'startTime', 'text' => 'startTime'],
			['dataCol' => 'endTime', 'text' => 'endTime'],
			['dataCol' => 'hoursWorked', 'text' => 'hoursWorked']
		];

		Excel::outputExcelViaSpec($spec, $labor, [], "Labor for " . $employee['firstName'] . ' ' . $employee['lastName'] . ' ' . date("F j, Y g:i a"));
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
			'employeeId' => 'arrayOfIds',
			'startDate' => 'notBlank',
			'startTime',
			'endDate' => 'notBlank',
			'endTime',
			'type' => 'numeric',
			'title',
			'allDay' => 'checkBox',
			'private' => 'checkBox'
		]);

		$this->database->begin_transaction();
		ScheduleModel::createShift($inputs);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function updateShift() {
		$inputs = Validation::validateJsonInput([
			'scheduleId' => 'numeric',
			'employeeId' => 'arrayOfIds',
			'startDate' => 'notBlank',
			'startTime',
			'endDate' => 'notBlank',
			'endTime',
			'type' => 'numeric',
			'title',
			'allDay' => 'checkBox',
			'private' => 'checkBox'
		]);

		$this->database->begin_transaction();
		ScheduleModel::updateShift($inputs);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function deleteShift() {
		$input = Validation::validateJsonInput([
			'scheduleId' => 'numeric'
		]);

		$this->database->begin_transaction();
		ScheduleModel::deleteShift($input['scheduleId']);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

}