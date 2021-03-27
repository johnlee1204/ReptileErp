<?php


namespace AgileInventory\Models;


use AgileModel;

class BinModel extends AgileModel {
	static function readBins() {
		$shop = AgileInventoryModel::readShopFromCookie();
		return self::$database->fetch_all_row("			
			SELECT
				binId,
				binName,
				binDescription,
				locationName location
			FROM Bin
			LEFT JOIN Location ON Location.locationId = Bin.locationId AND Location.shop = Bin.shop
			WHERE Bin.shop = ?
			ORDER BY binName
		", [$shop]);
	}

	static function readBinsCombo() {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->select(
			"Bin",
			[
				'binId',
				'binName'
			],
			['shop' => $shop],
			'ORDER BY binName'
		);

		return self::$database->fetch_all_row();
	}

	static function readBin($binId) {
		$shop = AgileInventoryModel::readShopFromCookie();

		self::$database->select(
			"Bin",
			[
				'binName',
				'binDescription',
				'locationId location'
			],
			[
				'binId' => $binId,
				'shop' => $shop
			]
		);

		return self::$database->fetch_assoc();
	}

	static function createBin($inputs) {
		$shop = AgileInventoryModel::readShopFromCookie();
		$binId = self::$database->insert(
			"Bin",
			[
				'binName' => $inputs['binName'],
				'binDescription' => $inputs['binDescription'],
				'locationId' => $inputs['location'],
				'shop' => $shop
			]
		);

		return $binId['id'];
	}

	static function updateBin($inputs) {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->update(
			"Bin",
			[
				'binName' => $inputs['binName'],
				'binDescription' => $inputs['binDescription'],
				'locationId' => $inputs['location']
			],
			[
				'binId' => $inputs['binId'],
				'shop' => $shop
			]
		);
	}

	static function deleteBin($binId) {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->delete(
			"Bin",
			[
				'binId' => $binId,
				'shop' => $shop
			]
		);
	}

}