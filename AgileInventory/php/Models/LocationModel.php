<?php


namespace AgileInventory\Models;


use AgileModel;

class LocationModel extends AgileModel {
	static function readLocations() {
		$shop = AgileInventoryModel::readShopFromCookie();
		return self::$database->fetch_all_row("
			SELECT
				locationId,
				locationName,
				locationDescription,
				facilityName
			FROM Location
			LEFT JOIN Facility ON Facility.facilityId = Location.facilityId AND Facility.shop = Location.shop
			WHERE
				Location.shop = ?
			ORDER BY locationName
		", [$shop]);
	}

	static function readLocation($locationId) {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->select(
			"Location",
			[
				'locationName',
				'locationDescription',
				'facilityId facility'
			],
			[
				'locationId' => $locationId,
				'shop' => $shop
			]
		);

		return self::$database->fetch_assoc();
	}

	static function createLocation($inputs) {
		$shop = AgileInventoryModel::readShopFromCookie();
		$locationId = self::$database->insert(
			"Location",
			[
				'locationName' => $inputs['locationName'],
				'locationDescription' => $inputs['locationDescription'],
				'facilityId' => $inputs['facility'],
				'shop' => $shop
			]
		);

		return $locationId['id'];
	}

	static function updateLocation($inputs) {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->update(
			"Location",
			[
				'locationName' => $inputs['locationName'],
				'locationDescription' => $inputs['locationDescription'],
				'facilityId' => $inputs['facility'],
			],
			[
				'locationId' => $inputs['locationId'],
				'shop' => $shop
			]
		);
	}

	static function deleteLocation($locationId) {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->delete(
			"Location",
			[
				'locationId' => $locationId,
				'shop' => $shop
			]
		);
	}

}