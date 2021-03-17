<?php

namespace Incubator\Models;
use AgileModel;
use PetMaster\Models\PetMasterModel;

class IncubatorModel extends AgileModel {
	static function readEggs() {
		self::$database->select(
			"Egg",
			[
				'eggId',
				'serial',
				'layDate'
			],
			[],
			"ORDER BY hatchDate DESC"
		);

		return self::$database->fetch_all_row();
	}

	static function readReptileComboBox() {
		self::$database->select(
			"Pet",
			[
				'petId',
				'serial'
			],
			[],
			"ORDER BY serial"
		);

		return self::$database->fetch_all_row();
	}

	static function readMaleReptileComboBox() {
		self::$database->select(
			"Pet",
			[
				'petId',
				'serial'
			],
			['sex' => "Male"],
			"ORDER BY serial"
		);

		return self::$database->fetch_all_row();
	}

	static function readFemaleReptileComboBox() {
		self::$database->select(
			"Pet",
			[
				'petId',
				'serial'
			],
			['sex' => "Female"],
			"ORDER BY serial"
		);

		return self::$database->fetch_all_row();
	}

	static function readEgg($eggId) {
		self::$database->select(
			"Egg",
			[
				"serial",
				"layDate",
				"maleParentId maleParent",
				"femaleParentId  femaleParent",
				"hatchDate",
				"hatched",
				"reptileId reptile",
			],
			['eggId' => $eggId]
		);

		return self::$database->fetch_assoc();
	}

	static function createEgg($inputs) {

		if($inputs['hatchDate'] === "") {
			$inputs['hatchDate'] = NULL;
		} else {
			$inputs['hatchDate'] = date('Y-m-d', strtotime($inputs['hatchDate']));
		}

		$inputs['layDate'] = date('Y-m-d', strtotime($inputs['layDate']));

		$newId = self::$database->insert(
			"Egg",
			[
				"serial" => $inputs['serial'],
				"layDate" => $inputs['layDate'],
				"maleParentId" => $inputs['maleParent'],
				"femaleParentId" => $inputs['femaleParent'],
				"hatchDate" => $inputs['hatchDate'],
				"hatched" => $inputs['hatched'],
				"reptileId" => $inputs['reptile']
			]
		);

		return $newId['id'];
	}

	static function updateEgg($inputs) {

		if($inputs['hatchDate'] === "") {
			$inputs['hatchDate'] = NULL;
		} else {
			$inputs['hatchDate'] = date('Y-m-d', strtotime($inputs['hatchDate']));
		}

		$inputs['layDate'] = date('Y-m-d', strtotime($inputs['layDate']));

		self::$database->update(
			"Egg",
			[
				"serial" => $inputs['serial'],
				"layDate" => $inputs['layDate'],
				"maleParentId" => $inputs['maleParent'],
				"femaleParentId" => $inputs['femaleParent'],
				"hatchDate" => $inputs['hatchDate'],
				"hatched" => $inputs['hatched'],
				"reptileId" => $inputs['reptile']
			],
			['eggId' => $inputs['eggId']]
		);
	}

	static function deleteEgg($eggId) {
		self::$database->delete(
			"Egg",
			['eggId' => $eggId]
		);
	}

	static function readFamilyTree($eggId) {
		$familyTree = [];
		$id = 1;

		$egg = self::readEgg($eggId);
	}

	static function readTopParents($reptileId) {
		$reptile = PetMasterModel::readPet($reptileId);
		if($reptile['maleParent'] !== NULL) {

		}
	}

	static function readFamilyTreeRecursive($id, &$tree, $eggId = NULL, $reptileId = NULL) {
		$parents = self::$database->fetch_all_assoc("
			SELECT
				male.serial father,
				female.serial mother
			FROM Egg
			JOIN Pet male ON male.petId = Egg.maleParentId
			JOIN Pet female ON female.petId = Egg.maleParentId
			WHERE
				eggId = ?
		");

		$tree[] = [
			'id' => $id,
			'pid' => self::readFamilyTreeRecursive($id, $tree),
			'name' => $parents['father']
		];

		$id++;

		$tree[] = [
			'id' => $id,
			'pid' => self::readFamilyTreeRecursive($id, $tree),
			'name' => $parents['father']
		];
		self::readFamilyTreeRecursive($id, $tree);
	}
}