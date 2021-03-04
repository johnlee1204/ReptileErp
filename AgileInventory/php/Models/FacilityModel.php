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

	static function readFacility($facilityId) {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->select(
			"Facility",
			[
				'facilityId',
				'facilityName',
				'facilityDescription',
				'address1',
				'address2',
				'city',
				'province',
				'zip',
				'country',
				'phone',
				'shopifyId'
			],
			[
				'facilityId' => $facilityId,
				'shop' => $shop
			]
		);

		return self::$database->fetch_assoc();
	}

	static function createFacility($inputs) {
		$shop = AgileInventoryModel::readShopFromCookie();
		$facilityId = self::$database->insert(
			"Facility",
			[
				'facilityName' => $inputs['facilityName'],
				'facilityDescription' => $inputs['facilityDescription'],
				'address1' => $inputs['address1'],
				'address2' => $inputs['address2'],
				'city' => $inputs['city'],
				'province' => $inputs['province'],
				'zip' => $inputs['zip'],
				'country' => $inputs['country'],
				'phone' => $inputs['phone'],
				'shop' => $shop
			]
		);

		return $facilityId['id'];
	}

	static function updateFacility($inputs) {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->update(
			"Facility",
			[
				'facilityName' => $inputs['facilityName'],
				'facilityDescription' => $inputs['facilityDescription'],
				'address1' => $inputs['address1'],
				'address2' => $inputs['address2'],
				'city' => $inputs['city'],
				'province' => $inputs['province'],
				'zip' => $inputs['zip'],
				'country' => $inputs['country'],
				'phone' => $inputs['phone']
			],
			[
				'facilityId' => $inputs['facilityId'],
				'shop' => $shop
			]
		);
	}

	static function deleteFacility($facilityId) {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->delete(
			"Facility",
			[
				'facilityId' => $facilityId,
				'shop' => $shop
			]
		);
	}

}