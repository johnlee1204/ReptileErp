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
		$userInformation = self::$agileApp->SessionManager->getUserDataFromSession();
		$viewAll = self::isScheduleAdmin();

		$where = "";
		$params = [];
		if(!$viewAll) {
			$where = "WHERE Employee.employeeId = ?";
			$params[] = $userInformation['employeeId'];
		}

		$employees = self::$database->fetch_all_assoc("
			SELECT
				Employee.employeeId,
				employeeNumber,
				firstName,
				lastName,
				startTime,
			    position
			FROM Employee
			LEFT JOIN Labor ON Labor.employeeId = Employee.employeeId AND endTime IS NULL	
				{$where}
			ORDER BY firstName
		", $params);

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

	static function readLabor($laborId) {
		self::$database->select(
			'Labor',
			[
				'startTime',
				'endTime',
				'hoursWorked'
			],
			['laborId' => $laborId]
		);

		return self::$database->fetch_assoc();
	}

	static function updateLabor($inputs) {

		if($inputs['endTime'] === "") {
			$inputs['endTime'] = NULL;
		}

		$labor = self::readLabor($inputs['laborId']);

		$hoursWorked = NULL;
		if($inputs['endTime'] !== NULL) {
			$hoursWorked = strtotime($inputs['endTime']) - strtotime($inputs['startTime']);
			$hoursWorked = $hoursWorked / ( 60 * 60 );

			if($hoursWorked < 0) {
				throw new AgileUserMessageException("End time must be after Start time!");
			}
		}

		self::$database->update(
			'Labor',
			[
				'startTime' => $inputs['startTime'],
				'endTime' => $inputs['endTime'],
				'hoursWorked' => $hoursWorked
			],
			['laborId' => $inputs['laborId']]
		);
	}

	static function deleteLabor($laborId) {
		self::$database->delete(
			'Labor',
			['laborId' => $laborId]
		);
	}

	static function readSchedule($calendarId) {
		return self::$database->fetch_all_assoc("
			SELECT
				scheduleId id,
				scheduleId,
				Schedule.employeeId,
				startTime startDate,
				endTime endDate,
				#hours,
				FLOOR( RAND() * (3-1) + 1) as calendarId,
				CASE
					WHEN calendarId = 1 THEN CONCAT(CASE WHEN DATEDIFF(endTime,startTime) > 1 THEN DATE_FORMAT(startTime, '%h:%i %p') ELSE DATE_FORMAT(startTime, '%h:%i %p') END,' - ',DATE_FORMAT(endTime, '%h:%i %p'), ' ' ,Employee.firstName, ' ', Employee.lastName)	
					WHEN calendarId = 4 THEN CONCAT(Employee.firstName, ' ', Employee.lastName, ' Time Off')
					ELSE title
				END title,
				CASE WHEN calendarId = 4 THEN 1 ELSE 0 END allDay
			FROM Schedule
			JOIN Employee ON Employee.employeeId = Schedule.employeeId
			WHERE
				calendarId = ?
		", [$calendarId]);
	}

	static function createShift($inputs) {
		$startDate = date("Y-m-d H:i:s", strtotime($inputs['startDate'] . ' ' . $inputs['startTime']));
		$endDate = date("Y-m-d H:i:s", strtotime($inputs['endDate'] . ' ' . $inputs['endTime']));

		$hoursWorked = NULL;
		$hoursWorked = strtotime($endDate) - strtotime($startDate);
		$hoursWorked = $hoursWorked / ( 60 * 60 );

		if($hoursWorked < 0) {
			throw new AgileUserMessageException("End time must be after Start time!");
		}

		self::$database->insert(
			'Schedule',
			[
				'employeeId' => $inputs['employeeId'],
				'startTime' => $startDate,
				'endTime' => $endDate,
				'hours' => $hoursWorked,
				'calendarId' => $inputs['type'],
				'title' => $inputs['title']
			]
		);
	}

	static function updateShift($inputs) {
		$startDate = date("Y-m-d H:i:s", strtotime($inputs['startDate'] . ' ' . $inputs['startTime']));
		$endDate = date("Y-m-d H:i:s", strtotime($inputs['endDate'] . ' ' . $inputs['endTime']));

		$hoursWorked = NULL;
		$hoursWorked = strtotime($endDate) - strtotime($startDate);
		$hoursWorked = $hoursWorked / ( 60 * 60 );

		if($hoursWorked < 0) {
			throw new AgileUserMessageException("End time must be after Start time!");
		}


		self::$database->update(
			'Schedule',
			[
				'employeeId' => $inputs['employeeId'],
				'startTime' => $startDate,
				'endTime' => $endDate,
				'hours' => $hoursWorked,
				'calendarId' => $inputs['type'],
				'title' => $inputs['title']
			],
			['scheduleId' => $inputs['scheduleId']]
		);
	}

	static function deleteShift($scheduleId) {
		self::$database->delete(
			"Schedule",
			['scheduleId' => $scheduleId]
		);
	}

	static function deleteShiftByTitle($title) {
		self::$database->delete(
			"Schedule",
			['title' => $title]
		);
	}

	static function isScheduleAdmin() {
		$userInformation = self::$agileApp->SessionManager->getUserDataFromSession();
		if($userInformation === FALSE) {
			return FALSE;
		}
		$groupModel = self::$agileApp->loadModel('AgileGroupModel');
		return $groupModel->checkIfUserInGroup($userInformation['employeeId'], "Schedule Admin");
	}

}