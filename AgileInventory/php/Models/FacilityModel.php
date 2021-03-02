<?php


namespace AgileInventory\Models;


use AgileModel;

class FacilityModel extends AgileModel {
	static function readFacilities() {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->select(
			"Facility",
			[
				'facilityId',
				'facilityName',
				'facilityDescription',
				'CONCAT(address1, ", ", city, ", ", province, " ", zip)'
			],
			['shop' => $shop],
			'ORDER BY facilityName'
		);

		return self::$database->fetch_all_row();
	}

	static function readFacilitiesCombo() {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->select(
			"Facility",
			[
				'facilityId',
				'facilityName'
			],
			['shop' => $shop],
			'ORDER BY facilityName'
		);

		return self::$database->fetch_all_row();
	}
}