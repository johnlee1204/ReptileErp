<?php

namespace Incubator\Models;
use AgileModel;
use AgileUserMessageException;
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
				"sex",
				"type"
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
				"reptileId" => $inputs['reptile'],
				'sex' => $inputs['sex'],
				'type' => $inputs['type']
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

		$oldEgg = self::readEgg($inputs['eggId']);

		self::$database->update(
			"Egg",
			[
				"serial" => $inputs['serial'],
				"layDate" => $inputs['layDate'],
				"maleParentId" => $inputs['maleParent'],
				"femaleParentId" => $inputs['femaleParent'],
				"hatchDate" => $inputs['hatchDate'],
				"hatched" => $inputs['hatched'],
				"reptileId" => $inputs['reptile'],
				'sex' => $inputs['sex'],
				'type' => $inputs['type']
			],
			['eggId' => $inputs['eggId']]
		);

		if($oldEgg['hatched'] === 0 && $inputs['hatched'] === 1) {
			if($inputs['hatchDate'] === NULL) {
				throw new AgileUserMessageException("Must enter Hatch Date!");
			}

			if($inputs['sex'] === "") {
				throw new AgileUserMessageException("Must enter Sex!");
			}

			$reptileId = PetMasterModel::createPet([
				'serial' => $inputs['serial'],
				'type' => $inputs['type'],
				'price' => NULL,
				'sex' => $inputs['sex'],
				'birthDate' => $inputs['hatchDate'],
				'receiveDate' => $inputs['hatchDate'],
				'sellDate' => NULL,
				'vendor' => "",
				'cost' => 0,
				'habitatId' => NULL,
				'food' => '',
				'feedingQuantity' => 0,
				'feedingFrequency' => 0,
				'customer' => '',
				'notes' => '',
				'weight' => NULL,
				'sellPrice' => NULL,
				'status' => NULL,
				'morph' => NULL,
				'maleParent' => $inputs['maleParent'],
				'femaleParent' => $inputs['femaleParent']
			]);

			self::$database->update(
				"Egg",
				[
					"reptileId" => $reptileId
				],
				['eggId' => $inputs['eggId']]
			);
		}
	}

	static function deleteEgg($eggId) {
		self::$database->delete(
			"Egg",
			['eggId' => $eggId]
		);
	}

	static function readFamilyTree($eggId) {
		$familyTree = [];

		$egg = self::readEgg($eggId);
		$familyTree[] = [
			'id' => 1,
			'name' => $egg['serial'],
			'pid' => NULL,
			'ppid' => NULL
		];
		self::readFamilyTreeRecursive($familyTree, $egg['maleParent']);

		return $familyTree;
	}

	static function readFamilyTreeRecursive(&$tree, $reptileId, $partner = NULL) {
		$reptile = PetMasterModel::readPet($reptileId);
		$maleParent = PetMasterModel::readPet($reptile['maleParent']);
		$femaleParent = PetMasterModel::readPet($reptile['femaleParent']);

		$tree[] = [
			'id' => $reptileId,
			'name' => $reptile['serial'],
			'pid' => $reptile['maleParent'],
			'ppid' => $reptile['femaleParent']
		];

		if($partner !== NULL) {
			$tree[count($tree) - 1]['tags'] = ['partner'];
			$tree[count($tree) - 1]['pid'] = $partner;
		}

		if($reptile['maleParent']) {
			self::readFamilyTreeRecursive($tree, $reptile['maleParent']);
		}

		if($reptile['femaleParent']) {
			self::readFamilyTreeRecursive($tree, $reptile['femaleParent'], $reptile['maleParent']);
		}
	}
}