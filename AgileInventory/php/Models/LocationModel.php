<?php


namespace AgileInventory\Models;


use AgileModel;

class LocationModel extends AgileModel {
	static function readLocations() {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->select(
			"Location",
			[
				'locationId',
				'locationName',
				'locationDescription'
			],
			['shop' => $shop],
			'ORDER BY locationName'
		);

		return self::$database->fetch_all_row();
	}

	static function readLocation($locationId) {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->select(
			"Location",
			[
				'locationName',
				'locationDescription'
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
				'locationDescription' => $inputs['locationDescription']
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