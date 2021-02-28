<?php


namespace AgileInventory\Models;


use AgileModel;

class LocationModel extends AgileModel {
	static function readLocations() {
		self::$database->select(
			"Location",
			[
				'locationId',
				'locationName',
				'locationDescription'
			],
			[],
			'ORDER BY locationName'
		);

		return self::$database->fetch_all_row();
	}

	static function readLocation($locationId) {
		self::$database->select(
			"Location",
			[
				'locationName',
				'locationDescription'
			],
			['locationId' => $locationId]
		);

		return self::$database->fetch_assoc();
	}

	static function createLocation($inputs) {
		$locationId = self::$database->insert(
			"Location",
			[
				'locationName' => $inputs['locationName'],
				'locationDescription' => $inputs['locationDescription']
			]
		);

		return $locationId['id'];
	}

	static function updateLocation($inputs) {
		self::$database->update(
			"Location",
			[
				'locationName' => $inputs['locationName'],
				'locationDescription' => $inputs['locationDescription']
			],
			['locationId' => $inputs['locationId']]
		);
	}

	static function deleteLocation($locationId) {
		self::$database->delete(
			"Location",
			['locationId' => $locationId]
		);
	}

}