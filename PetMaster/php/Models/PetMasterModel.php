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
		if($inputs['sellDate'] === "") {
			$inputs['sellDate'] = NULL;
		}
		if($inputs['receiveDate'] === "") {
			$inputs['receiveDate'] = NULL;
		}
		if($inputs['birthDate'] === "") {
			$inputs['birthDate'] = NULL;
		}
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
		if($inputs['sellDate'] === "") {
			$inputs['sellDate'] = NULL;
		}
		if($inputs['receiveDate'] === "") {
			$inputs['receiveDate'] = NULL;
		}
		if($inputs['birthDate'] === "") {
			$inputs['birthDate'] = NULL;
		}
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

	static function searchPets($inputs) {
		$where = "";
		$params = [];

		if($inputs['name']) {
			$where .= " AND name LIKE CONCAT('%', ?, '%')";
			$params[] = $inputs['name'];
		}

		if($inputs['type']) {
			$where .= " AND type = ?";
			$params[] = $inputs['type'];
		}

		if($inputs['receiveDate']) {
			$where .= " AND receiveDate = ?";
			$params[] = $inputs['receiveDate'];
		}

		if($inputs['sellDate']) {
			$where .= " AND sellDate = ?";
			$params[] = $inputs['sellDate'];
		}

		return self::$database->fetch_all_row("
			SELECT
				petId,
				name,
				type,
				receiveDate,
				sellDate
			FROM Pet
			WHERE
				1 = 1
			{$where}
			ORDER BY receiveDate DESC
		", $params);
	}
}