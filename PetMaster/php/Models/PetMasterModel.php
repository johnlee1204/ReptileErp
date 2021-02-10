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
				sex,
				birthDate,
				receiveDate,
				sellDate,
				vendor,
				cost,
				habitatId,
				food,
				feedingQuantity,
				feedingFrequency,
				customer,
				notes,
				weight,
				sellPrice
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
		if($inputs['sellPrice'] === "") {
			$inputs['sellPrice'] = 0;
		}
		if($inputs['cost'] === "") {
			$inputs['cost'] = 0;
		}
		if($inputs['feedingQuantity'] === "") {
			$inputs['feedingQuantity'] = 0;
		}
		if($inputs['feedingFrequency'] === "") {
			$inputs['feedingFrequency'] = 0;
		}
		if($inputs['weight'] === "") {
			$inputs['weight'] = 0;
		}
		self::$database->query("
			INSERT INTO Pet(
				name,
				type,
				price,
				sex,
				birthDate,
				receiveDate,
				sellDate,
				vendor,
				cost,
				habitatId,
				food,
				feedingQuantity,
				feedingFrequency,
				customer,
				notes,
				weight,
				sellPrice
			)
			VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
		", [
			$inputs['name'],
			$inputs['type'],
			$inputs['price'],
			$inputs['sex'],
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
			$inputs['notes'],
			$inputs['weight'],
			$inputs['sellPrice']
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
		if($inputs['sellPrice'] === "" || $inputs['sellPrice'] === "0.00") {
			$inputs['sellPrice'] = 0;
		}
		if($inputs['cost'] === "") {
			$inputs['cost'] = 0;
		}
		if($inputs['feedingQuantity'] === "") {
			$inputs['feedingQuantity'] = 0;
		}
		if($inputs['feedingFrequency'] === "") {
			$inputs['feedingFrequency'] = 0;
		}
		if($inputs['weight'] === "") {
			$inputs['weight'] = 0;
		}


		self::$database->query("
			UPDATE Pet
			SET
				name = ?,
				type = ?,
				price = ?,
				sex = ?,
				birthDate = ?,
				receiveDate = ?,
				sellDate = ?,
				vendor = ?,
				cost = ?,
				habitatId = ?,
				food = ?,
				feedingQuantity = ?,
				feedingFrequency = ?,
				customer = ?,
				notes = ?,
				weight = ?,
				sellPrice = ?
			WHERE
				petId = ?
		", [
			$inputs['name'],
			$inputs['type'],
			$inputs['price'],
			$inputs['sex'],
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
			$inputs['notes'],
			$inputs['weight'],
			$inputs['sellPrice'],
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
				sex,
				receiveDate,
				sellDate
			FROM Pet
			WHERE
				1 = 1
			{$where}
			ORDER BY receiveDate DESC
		", $params);
	}

	static function readPetAttachments($petId) {
		return self::$database->fetch_all_row("
			SELECT
				petAttachmentId,
				fileName,
				fileLocation,
				photoDate
			FROM PetAttachment
			WHERE
				petId = ?
		", [$petId]);
	}

	static function readAttachment($petAttachmentId) {
		return self::$database->fetch_assoc("
			SELECT
				fileName,
				fileLocation
			FROM PetAttachment
			WHERE
				petAttachmentId = ?
		", [$petAttachmentId]);
	}
}