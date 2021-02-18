<?php

namespace Schedule\Models;
use AgileModel;
use AgileUserMessageException;

class ScheduleModel extends AgileModel{

	static function clockOn($employeeId) {
		$openShift = self::$database->fetch_assoc("
			SELECT
				laborId
			FROM Labor
			WHERE
				employeeId = ?
			AND
				endTime IS NULL
		", [$employeeId]);

		if($openShift !== NULL) {
			throw new AgileUserMessageException("You are currently Clocked On! Please Clock Off!");
		}

		$currentTime = date("Y-m-d H:i:s");
		self::$database->query("
			INSERT INTO Labor(employeeId, startTime) VALUES(?,?)
		", [$employeeId, $currentTime]);
	}

	static function clockOff($employeeId) {
		$openShift = self::$database->fetch_assoc("
			SELECT
				laborId,
				startTime
			FROM Labor
			WHERE
				employeeId = ?
			AND
				endTime IS NULL
		", [$employeeId]);

		if($openShift === NULL) {
			throw new AgileUserMessageException("You are not currently Clocked On!");
		}

		$currentTime = date("Y-m-d H:i:s");

		$startTime = strtotime($openShift['startTime']);

		$hoursWorked = strtotime($currentTime) - $startTime;
		$hoursWorked = $hoursWorked / ( 60 * 60 );

		self::$database->query("
			UPDATE Labor
			SET
				endTime = ?,
				hoursWorked = ?
			WHERE
				laborId = ?
		", [$currentTime, $hoursWorked, $openShift['laborId']]);
	}

	static function readClockOnDetails($employeeId) {
		$openShift = self::$database->fetch_assoc("
			SELECT
				laborId,
				startTime
			FROM Labor
			WHERE
				employeeId = ?
			AND
				endTime IS NULL
		", [$employeeId]);

		if($openShift === NULL) {
			return "You are not clocked in!";
		} else {
			return "You clocked in at " . date("h:ia", strtotime($openShift['startTime']));
		}
	}

	static function readEmployeeSchedule() {
		$employees = self::$database->fetch_all_assoc("
			SELECT
				Employee.employeeId,
				employeeNumber,
				firstName,
				lastName,
				startTime
			FROM Employee
			LEFT JOIN Labor ON Labor.employeeId = Employee.employeeId AND endTime IS NULL 
			ORDER BY firstName
		");

		$output = [];
		foreach($employees as $employee) {
			if($employee['startTime'] !== NULL) {
				$employee['startTime'] = date('h:ia', strtotime($employee['startTime']));
			}

			$output[] = array_values($employee);
		}

		return $output;
	}

	static function readEmployeeLaborHistory($employeeId) {
		$labor = self::$database->fetch_all_assoc("
			SELECT
				laborId,
				startTime,
				endTime,
				hoursWorked
			FROM Labor
			WHERE
				employeeId = ?
			ORDER BY startTime DESC
		", [$employeeId]);

		$output = [];
		foreach($labor as $laborRecord) {
			if($laborRecord['startTime'] !== NULL) {
				$laborRecord['startTime'] = date('F j, Y g:i a', strtotime($laborRecord['startTime']));
			}

			if($laborRecord['endTime'] !== NULL) {
				$laborRecord['endTime'] = date('F j, Y g:i a', strtotime($laborRecord['endTime']));
			}

			$output[] = array_values($laborRecord);
		}

		return $output;
	}

	static function deleteLabor($laborId) {
		self::$database->delete(
			'Labor',
			['laborId' => $laborId]
		);
	}
}