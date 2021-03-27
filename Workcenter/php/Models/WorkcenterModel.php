<?php

namespace Workcenter\Models;

use AgileModel;

class WorkcenterModel extends AgileModel {

	static function readWorkcenters() {
		return self::$database->fetch_all_row("
			SELECT
				workcenterId,
				workcenterName,
				workcenterDescription
			FROM Workcenter
			ORDER BY workcenterName
		");
	}

	static function readWorkcenter($workcenterId) {
		return self::$database->fetch_assoc("
			SELECT
				workcenterId,
				workcenterName,
				workcenterDescription,
				energy
			FROM Workcenter
			WHERE
				workcenterId = '{$workcenterId}'
		");
	}

	static function createWorkcenter($inputs) {
		return self::$database->query("
			INSERT INTO Workcenter(workcenterName, workcenterDescription, energy)
			VALUES('{$inputs['workcenterName']}', '{$inputs['workcenterDescription']}', '{$inputs['energy']}')
		");
	}

	static function updateWorkcenter($inputs) {
		return self::$database->query("
			UPDATE Workcenter
			SET 
				workcenterName = '{$inputs['workcenterName']}',
				workcenterDescription = '{$inputs['workcenterDescription']}',
				energy = '{$inputs['energy']}'
			WHERE
				workcenterId = '{$inputs['workcenterId']}'
		");
	}

	static function deleteWorkcenter($workcenterId) {
		return self::$database->query("
			DELETE FROM Workcenter
			WHERE
				workcenterId = '{$workcenterId}'
		");
	}

	static function readLastCreatedWorkcenter() {
		$lastWorkcenter = self::$database->fetch_assoc("
			SELECT MAX(workcenterId) workcenterId FROM Workcenter
		");
		return $lastWorkcenter['workcenterId'];
	}
}