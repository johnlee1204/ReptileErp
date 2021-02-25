<?php

namespace PetMaster\Models;
use AgileModel;
use PetMaster\Tables\PetMasterLog;

class PetMasterModel extends AgileModel {
	static function readPet($petId) {

		self::$database->select(
			'Pet',
			[
				'petId',
				'serial',
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
				'serial' => $inputs['serial'],
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

		$petId = self::readLatestPetId();

		PetMasterLog::log(
			self::$database,
			[
				'action' => "Create Reptile",
				'petId' => $petId,
				'serial' => $inputs['serial'],
				'changes' => PetMasterLog::calculateDelta([], $inputs)
			]
		);

		return $petId;
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

		$oldReptile = self::readPet($inputs['petId']);

		self::$database->update(
			'Pet',
			[
				'serial' => $inputs['serial'],
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

		$newReptile = self::readPet($inputs['petId']);

		PetMasterLog::log(
			self::$database,
			[
				'action' => "Update Reptile",
				'petId' => $inputs['petId'],
				'serial' => $inputs['serial'],
				'changes' => PetMasterLog::calculateDelta($oldReptile, $newReptile)
			]
		);
	}

	static function deletePet($petId) {
		$oldReptile = self::readPet($petId);

		self::$database->delete(
			'Pet',
			['petId' => $petId]
		);

		PetMasterLog::log(
			self::$database,
			[
				'action' => "Delete Reptile",
				'petId' => $petId,
				'serial' => $oldReptile['serial'],
				'changes' => PetMasterLog::calculateDelta($oldReptile, [])
			]
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

		if($inputs['serial']) {
			$where .= " AND serial LIKE CONCAT('%', ?, '%')";
			$params[] = $inputs['serial'];
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
				serial,
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