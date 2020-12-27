<?php

namespace PetMaster\Models;
use AgileModel;

class PetMasterModel extends AgileModel {
	static function readPet($petId) {
		return self::$database->fetch_assoc("
			SELECT
				petId,
				name,
				type,
				price,
				birthDate,
				receiveDate,
				sellDate,
				vendor,
				cost,
				habitatId,
				food,
				feedingQuantity,
				feedingFrequency,
				customer
			FROM Pet
			WHERE
				petId = ?
		", [$petId]);
	}

	static function createPet($inputs) {
		self::$database->query("
			INSERT INTO Pet(
				name,
				type,
				price,
				birthDate,
				receiveDate,
				sellDate,
				vendor,
				cost,
				habitatId,
				food,
				feedingQuantity,
				feedingFrequency,
				customer
			)
			VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)
		", [
			$inputs['name'],
			$inputs['type'],
			$inputs['price'],
			$inputs['birthDate'],
			$inputs['receiveDate'],
			$inputs['sellDate'],
			$inputs['vendor'],
			$inputs['cost'],
			$inputs['habitatId'],
			$inputs['food'],
			$inputs['feedingQuantity'],
			$inputs['feedingFrequency'],
			$inputs['customer']
		]);

		return self::readLatestPetId();
	}

	static function updatePet($inputs) {
		self::$database->query("
			UPDATE Pet
			SET
				name = ?,
				type = ?,
				price = ?,
				birthDate = ?,
				receiveDate = ?,
				sellDate = ?,
				vendor = ?,
				cost = ?,
				habitatId = ?,
				food = ?,
				feedingQuantity = ?,
				feedingFrequency = ?,
				customer = ?
			WHERE
				petId = ?
		", [
			$inputs['name'],
			$inputs['type'],
			$inputs['price'],
			$inputs['birthDate'],
			$inputs['receiveDate'],
			$inputs['sellDate'],
			$inputs['vendor'],
			$inputs['cost'],
			$inputs['habitatId'],
			$inputs['food'],
			$inputs['feedingQuantity'],
			$inputs['feedingFrequency'],
			$inputs['customer'],
			$inputs['petId']
		]);
	}

	static function deletePet($petId) {
		self::$database->query("
			DELETE FROM Pet
			WHERE
				petId = ?
		", [$petId]);
	}

	static function readLatestPetId() {
		$petId = self::$database->fetch_assoc("
			SELECT MAX(petId) petId FROM Pet
		");

		return $petId['petId'];
	}
}