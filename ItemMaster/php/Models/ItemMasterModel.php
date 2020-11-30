<?php

namespace ItemMaster\Models;

use AgileModel;
use SmartTruncate;

class ItemMasterModel extends AgileModel {

	static function searchParts($partName) {
		return self::$database->fetch_all_row("
			SELECT
				partId,
				partName,
				partDescription,
				source
			FROM Part
			WHERE
				partName LIKE CONCAT(?,'%')
			ORDER BY partName
		", [$partName]);
	}

	static function readPartsCombo($partName) {
		return self::$database->fetch_all_row("
			SELECT
				partId,
				partName
			FROM Part
			WHERE
				partName LIKE CONCAT(?,'%')
			ORDER BY partName
			LIMIT 50
		", [$partName]);
	}

	static function readPart($partId) {
		return self::$database->fetch_assoc("
			SELECT
				partId,
				partName,
				partDescription,
				source,
				partsPerMinute
			FROM Part
			WHERE
				partId = ?
		", [$partId]);
	}

	static function readPartByName($partName) {
		return self::$database->fetch_assoc("
			SELECT
				partId,
				partName,
				partDescription,
				source,
				partsPerMinute
			FROM Part
			WHERE
				partName = ? 
		", [$partName]);
	}

	static function createPart($inputs) {
		self::$database->query("
			INSERT INTO Part(partName, partDescription, partsPerMinute, source)
			VALUES(?, ?, ?, ?)
		", [$inputs['partName'], $inputs['partDescription'], $inputs['partsPerMinute'], $inputs['source']]);
	}

	static function updatePart($inputs) {
		self::$database->query("
			UPDATE Part
			SET
				partName = ?,
				partDescription = ?,
				partsPerMinute = ?,
				source = ?
			WHERE
				partId = ?
		", [$inputs['partName'], $inputs['partDescription'], $inputs['partsPerMinute'], $inputs['source'], $inputs['partId']]);
	}

	static function deletePart($partId) {
		return self::$database->query("
			DELETE FROM Part
			WHERE
				partId = ?
		", [$partId]);
	}

	static function readLastPartMade() {
		$lastPartMade = self::$database->fetch_assoc("
			SELECT MAX(partId) partId FROM Part
		");

		if($lastPartMade !== NULL) {
			$lastPartMade = $lastPartMade['partId'];
		}

		return $lastPartMade;
	}

	static function readPartBom($partId) {
		$bom = self::$database->fetch_assoc("
			SELECT
				Part.partName,
				Part.partDescription,
				1 quantity,
				1 extendedQuantity,
				'' bomId,
				Part.partId,
				0 isBomRecord
			FROM Part
			WHERE
				Part.partId = ?
		", [$partId]);

		$bom['children'] = [];

		self::readPartBomRecursive($partId, $bom['children'], 1);

		return $bom;
	}

	static function readPartBomRecursive($partId, &$bom, $extensionMultiplier) {
		$children = self::$database->fetch_all_assoc("
			SELECT
				Part.partName,
				Part.partDescription,
				BillOfMaterial.quantity,
				BillOfMaterial.bomId,
				Part.partId,
				1 isBomRecord
			FROM BillOfMaterial
			JOIN Part on Part.partId = BillOfMaterial.partId
			WHERE BillOfMaterial.parentPartId = ?
		", [$partId]);

		foreach($children as $child) {
			$child['quantity'] = SmartTruncate::truncate($child['quantity']);
			$child['extendedQuantity'] = $child['quantity'] * $extensionMultiplier;
			$child['extendedQuantity'] = SmartTruncate::truncate($child['extendedQuantity']);
			$bom[] = $child;
			$bom[count($bom) - 1]['children'] = [];
			self::readPartBomRecursive($child['partId'], $bom[count($bom) - 1]['children'], $child['extendedQuantity']);
		}
	}

	static function readPartBomRecord($bomId) {
		$bomRecord = self::$database->fetch_assoc("
			SELECT
				BillOfMaterial.bomId,
				BillOfMaterial.partId bomPart,
				bomPart.partName bomPartName,
				BillOfMaterial.parentPartId parentPart,
				parent.partName parentPartName,
				BillOfMaterial.quantity
			FROM BillOfMaterial
			JOIN Part parent ON parent.partId = BillOfMaterial.parentPartId
			JOIN Part bomPart ON bomPart.partId = BillOfMaterial.partId
			WHERE
				bomId = ?
		", [$bomId]);
		$bomRecord['quantity'] = SmartTruncate::truncate($bomRecord['quantity']);
		return $bomRecord;
	}

	static function createBomRecord($inputs) {
		self::$database->query("
			INSERT INTO BillOfMaterial(partId, parentPartId, quantity)
			VALUES(?, ?, ?)
		", [$inputs['bomPart'], $inputs['parentPart'], $inputs['quantity']]);
	}

	static function updateBomRecord($inputs) {
		self::$database->query("
			UPDATE BillOfMaterial
			SET
				partId = ?,
				parentPartId = ?,
				quantity = ?
			WHERE
				bomId = ?
		", [$inputs['bomPart'], $inputs['parentPart'], $inputs['quantity'], $inputs['bomId']]);
	}

	static function deleteBomRecord($bomId) {
		return self::$database->query("
			DELETE FROM BillOfMaterial
			WHERE
				bomId = ?
		", [$bomId]);
	}

	static function readLastBomRecordMade() {
		$lastBomRecordMade = self::$database->fetch_assoc("
			SELECT MAX(bomId) bomId FROM BillOfMaterial
		");

		if($lastBomRecordMade !== NULL) {
			$lastBomRecordMade = $lastBomRecordMade['bomId'];
		}

		return $lastBomRecordMade;
	}

	static function readRoutings($partId) {
		return self::$database->fetch_all_row("
			SELECT
				Routing.routingId,
				Workcenter.workcenterName,
				Part.partsPerMinute,
				Workcenter.energy
			FROM Routing
			JOIN Part ON Part.partId = Routing.partId
			JOIN Workcenter ON Workcenter.workcenterId = Routing.workcenterId
			WHERE
				Routing.partId = ?
		", [$partId]);
	}

	static function readRouting($routingId) {
		$routingRecord = self::$database->fetch_assoc("
			SELECT
				Routing.workcenterId workcenter,
				Part.partsPerMinute,
				Workcenter.energy
			FROM Routing
			JOIN Part ON Part.partId = Routing.partId
			JOIN Workcenter ON Workcenter.workcenterId = Routing.workcenterId
			WHERE
				routingId = ?
		", [$routingId]);

		return $routingRecord;
	}

	static function createRouting($inputs) {
		self::$database->query("
			INSERT INTO Routing(partId, workcenterId)
			VALUES(?, ?)
		", [$inputs['partId'], $inputs['workcenter']]);
	}

	static function updateRouting($inputs) {
		self::$database->query("
			UPDATE Routing
			SET
				partId = ?,
				workcenterId = ?
			WHERE
				routingId = ?
		", [$inputs['partId'], $inputs['workcenter'], $inputs['routingId']]);
	}

	static function deleteRouting($routingId) {
		self::$database->query("
			DELETE FROM Routing
			WHERE
				routingId = ?
		", [$routingId]);
	}

	static function readLastRoutingMade() {
		$lastRoutingMade = self::$database->fetch_assoc("
			SELECT MAX(routingId) routingId FROM Routing
		");

		if($lastRoutingMade !== NULL) {
			$lastRoutingMade = $lastRoutingMade['routingId'];
		}

		return $lastRoutingMade;
	}

	static function readPartChildren($partId) {
		return self::$database->fetch_all_assoc("
			SELECT
				partId part,
				quantity
			FROM BillOfMaterial
			WHERE
				parentPartId = ?
		", [$partId]);
	}


}