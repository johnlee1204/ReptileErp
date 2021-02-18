<?php


use Employee\Models\EmployeeModel;
use Schedule\Models\ScheduleModel;

class Schedule extends AgileBaseController {
	function readAppInitData() {
		$this->outputSuccess([
			'employees' => EmployeeModel::readEmployeesComboData()
		]);
	}

	function clockOn() {
		$input = Validation::validateJsonInput([
			'employeeId' => 'numeric'
		]);

		ScheduleModel::clockOn($input['employeeId']);

		$this->outputSuccess();
	}

	function clockOff() {
		$input = Validation::validateJsonInput([
			'employeeId' => 'numeric'
		]);

		ScheduleModel::clockOff($input['employeeId']);

		$this->outputSuccess();
	}

	public function readClockOnDetails() {
		$input = Validation::validateJsonInput([
			'employeeId' => 'numeric'
		]);

		$this->outputSuccessData(ScheduleModel::readClockOnDetails($input['employeeId']));
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

	function deleteLabor() {
		$input = Validation::validateJsonInput([
			'laborId' => 'numeric'
		]);

		ScheduleModel::deleteLabor($input['laborId']);

		$this->outputSuccess();
	}
}