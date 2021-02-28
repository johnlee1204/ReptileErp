<?php


namespace AgileInventory\Models;


use AgileModel;

class BinModel extends AgileModel {
	static function readBins() {
		return self::$database->fetch_all_row("
			SELECT
				binId,
				binName,
				binDescription,
				locationName location
			FROM Bin
			LEFT JOIN Location ON Location.locationId = Bin.locationId
			ORDER BY binName
		");
	}

	static function readBinsCombo() {
		self::$database->select(
			"Bin",
			[
				'binId',
				'binName'
			],
			[],
			'ORDER BY binName'
		);

		return self::$database->fetch_all_row();
	}

	static function readBin($binId) {

		self::$database->select(
			"Bin",
			[
				'binName',
				'binDescription',
				'locationId location'
			],
			['binId' => $binId]
		);

		return self::$database->fetch_assoc();
	}

	static function createBin($inputs) {
		$binId = self::$database->insert(
			"Bin",
			[
				'binName' => $inputs['binName'],
				'binDescription' => $inputs['binDescription'],
				'locationId' => $inputs['location']
			]
		);

		return $binId['id'];
	}

	static function updateBin($inputs) {
		self::$database->update(
			"Bin",
			[
				'binName' => $inputs['binName'],
				'binDescription' => $inputs['binDescription'],
				'locationId' => $inputs['location']
			],
			['binId' => $inputs['binId']]
		);
	}

	static function deleteBin($binId) {
		self::$database->delete(
			"Bin",
			['binId' => $binId]
		);
	}

}