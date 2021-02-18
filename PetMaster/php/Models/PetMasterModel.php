<?php

namespace PetMaster\Models;
use AgileModel;

class PetMasterModel extends AgileModel {
	static function readPet($petId) {

		self::$database->select(
			'Pet',
			[
				'petId',
				'name',
				'type',
				'price',
				'sex',
				'birthDate',
				'receiveDate',
				'sellDate',
				'vendor',
				'cost',
				'habitatId',
				'food',
				'feedingQuantity',
				'feedingFrequency',
				'customer',
				'notes',
				'weight',
				'sellPrice',
				'status'
			],
			['petId' => $petId]
		);

		return self::$database->fetch_assoc();
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

		self::$database->insert(
			'Pet',
			[
				'name' => $inputs['name'],
				'type' => $inputs['type'],
				'price' => $inputs['price'],
				'sex' => $inputs['sex'],
				'birthDate' => $inputs['birthDate'],
				'receiveDate' => $inputs['receiveDate'],
				'sellDate' => $inputs['sellDate'],
				'vendor' => $inputs['vendor'],
				'cost' => $inputs['cost'],
				'habitatId' => $inputs['habitatId'],
				'food' => $inputs['food'],
				'feedingQuantity' => $inputs['feedingQuantity'],
				'feedingFrequency' => $inputs['feedingFrequency'],
				'customer' => $inputs['customer'],
				'notes' => $inputs['notes'],
				'weight' => $inputs['weight'],
				'sellPrice' => $inputs['sellPrice'],
				'status' => $inputs['status']
			]
		);

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

		self::$database->update(
			'Pet',
			[
				'name' => $inputs['name'],
				'type' => $inputs['type'],
				'price' => $inputs['price'],
				'sex' => $inputs['sex'],
				'birthDate' => $inputs['birthDate'],
				'receiveDate' => $inputs['receiveDate'],
				'sellDate' => $inputs['sellDate'],
				'vendor' => $inputs['vendor'],
				'cost' => $inputs['cost'],
				'habitatId' => $inputs['habitatId'],
				'food' => $inputs['food'],
				'feedingQuantity' => $inputs['feedingQuantity'],
				'feedingFrequency' => $inputs['feedingFrequency'],
				'customer' => $inputs['customer'],
				'notes' => $inputs['notes'],
				'weight' => $inputs['weight'],
				'sellPrice' => $inputs['sellPrice'],
				'status' => $inputs['status']
			],
			[
				'petId' => $inputs['petId']
			]
		);
	}

	static function deletePet($petId) {
		self::$database->delete(
			'Pet',
			['petId' => $petId]
		);
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