<?php

namespace PetMaster\Models;
use AgileModel;
use AgileUserMessageException;
use PetMaster\Tables\PetMasterLog;
use Schedule\Models\ScheduleModel;

class PetMasterModel extends AgileModel {

	static function readReptiles() {
		return self::$database->fetch_all_row("
			SELECT
				petId,
				serial,
				type,
				sex,
				receiveDate,
				sellDate
			FROM Pet
			ORDER BY receiveDate DESC
		");
	}

	static function readMorphs() {
		self::$database->select(
			"Morph",
			[
				'morphId',
				'morphName'
			],
			[],
			'ORDER BY morphName'
		);

		return self::$database->fetch_all_row();
	}

	static function readPet($petId) {

		return self::$database->fetch_assoc("
			SELECT
				Pet.petId,
				Pet.serial,
				Pet.type,
				Pet.price,
				Pet.sex,
				Pet.birthDate,
				Pet.receiveDate,
				Pet.sellDate,
				Pet.vendor,
				Pet.cost,
				Pet.habitatId,
				Habitat.rack,
				Pet.food,
				Pet.feedingQuantity,
				Pet.feedingFrequency,
				Pet.customer,
				Pet.notes,
				Pet.weight,
				Pet.sellPrice,
				Pet.status,
			    Pet.morphId morph,
			    Pet.maleParentId maleParent,
			    Pet.femaleParentId femaleParent
			FROM Pet
			LEFT JOIN Habitat ON Habitat.habitatId = Pet.habitatId
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

		if($inputs['sellDate'] != NULL) {
			$userInformation = self::$agileApp->SessionManager->getUserDataFromSession();
			$startDate = date("Y-m-d", strtotime($inputs['sellDate']));
			$endDate = date("Y-m-d", strtotime($inputs['sellDate']));
			$startTime = "09:00:00";
			$endTime = "17:00:00";

			ScheduleModel::createShift([
				'employeeId' => $userInformation['employeeId'],
				'startDate' => $startDate,
				'endDate' => $endDate,
				'startTime' => $startTime,
				'endTime' => $endTime,
				'hours' => NULL,
				'type' => 2,
				'title' => 'Sell Reptile Serial: ' . $inputs['serial']
			]);
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
				'status' => $inputs['status'],
				'morphId' => $inputs['morph'],
				'maleParentId' => $inputs['maleParent'],
				'femaleParentId' => $inputs['femaleParent']
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

		$userInformation = self::$agileApp->SessionManager->getUserDataFromSession();

		if($oldReptile['sellDate'] !== $inputs['sellDate']) {

			ScheduleModel::deleteShiftByTitle('Sell Reptile Serial: ' . $inputs['serial']);

			if($inputs['sellDate'] !== NULL) {
				$startDate = date("Y-m-d", strtotime($inputs['sellDate']));
				$endDate = date("Y-m-d", strtotime($inputs['sellDate']));
				$startTime = "09:00:00";
				$endTime = "17:00:00";
				ScheduleModel::createShift([
					'employeeId' => $userInformation['employeeId'],
					'startDate' => $startDate,
					'endDate' => $endDate,
					'startTime' => $startTime,
					'endTime' => $endTime,
					'hours' => NULL,
					'type' => 2,
					'title' => 'Sell Reptile Serial: ' . $inputs['serial']
				]);
			}
		}

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
				'status' => $inputs['status'],
				'morphId' => $inputs['morph'],
				'maleParentId' => $inputs['maleParent'],
				'femaleParentId' => $inputs['femaleParent']
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

	static function readCanBreedWith($reptileId) {
		$reptile = self::readPet($reptileId);
		return self::$database->fetch_all_row("
			SELECT
				petId,
				serial
			FROM Pet
			LEFT JOIN Breeding ON (Breeding.maleReptileId = ? AND Breeding.femaleReptileId = petId) OR (Breeding.femaleReptileId = ? AND Breeding.maleReptileId = petId)
			WHERE
				petId != ?
			AND
				sex != ?
			AND
				Breeding.breedingId IS NULL
		", [$reptileId, $reptileId, $reptileId, $reptile['sex']]);
	}

	static function readCurrentlyBreedingWith($reptileId) {
		return self::$database->fetch_all_row("
			SELECT DISTINCT
				breedingId,
				CASE WHEN femaleReptileId = ? THEN maleReptileId ELSE femaleReptileId END reptileId,
				CASE WHEN femaleReptileId = ? THEN male.serial ELSE female.serial END serial
			FROM Breeding
			JOIN Pet male ON male.petId = maleReptileId
			JOIN Pet female ON female.petId = femaleReptileId
			WHERE
				Breeding.maleReptileId = ? OR Breeding.femaleReptileId = ?
		", [$reptileId, $reptileId, $reptileId, $reptileId]);
	}

	static function createBreedingPair($reptileId1, $reptileId2) {
		self::checkForIncest($reptileId1, $reptileId2);

		$reptile1 = self::readPet($reptileId1);
		$reptile2 = self::readPet($reptileId2);

		$currentlyBreedingWith = self::readCurrentlyBreedingWith($reptileId1);

		if(count($currentlyBreedingWith) === 6) {
			throw new AgileUserMessageException("Cannot Breed with over 6 Reptiles");
		}

		if($reptile1['sex'] === $reptile2['sex']) {
			throw new AgileUserMessageException("Cannot Breed Same Sex!");
		}

		$male = NULL;
		$female = NULL;

		if($reptile1['sex'] === 'Male') {
			$male = $reptileId1;
			$female = $reptileId2;
		} else {
			$male = $reptileId2;
			$female = $reptileId1;
		}

		self::$database->insert(
		'Breeding',
			[
				'maleReptileId' => $male,
				'femaleReptileId' => $female
			]
		);
	}

	static function readBreedingPair($breedingId) {
		self::$database->select(
			"Breeding",
			['notes'],
			['breedingId' => $breedingId]
		);

		return self::$database->fetch_assoc();
	}

	static function updateBreedingPair($inputs) {
		self::$database->update(
			"Breeding",
			['notes' => $inputs['notes']],
			['breedingId' => $inputs['breedingId']]
		);
	}

	static function checkForIncest($reptile1, $reptile2) {
		$familyMembers = [];
		$familyMembers = [intval($reptile1), intval($reptile2)];
		self::checkForIncestRecursive($reptile1, $familyMembers, 1);
		self::checkForIncestRecursive($reptile2, $familyMembers, 1);
	}

	static function checkForIncestRecursive($reptileId, &$familyMembers, $level) {

		if($reptileId === NULL) {
			return;
		}

		$reptile = PetMasterModel::readPet($reptileId);

		if(in_array($reptile['maleParent'], $familyMembers)) {
			$relation = self::readFamilyLevelName($level);
			if($relation !== NULL) {
				throw new AgileUserMessageException("Incest Detected!<BR>Reptiles share a " . $relation[0] . " Father.<BR>Reptiles are " . $relation[1] . ".");
			}
		}

		if(in_array($reptile['femaleParent'], $familyMembers)) {
			$relation = self::readFamilyLevelName($level);
			if($relation !== NULL) {
				throw new AgileUserMessageException("Incest Detected!<BR>Reptiles share a " . $relation[0] . " Mother.<BR>Reptiles are " . $relation[1] . ".");
			}
		}



		if($reptile['maleParent'] !== NULL && $reptile['femaleParent'] !== NULL) {
			$familyMembers[] = $reptile['maleParent'];
			$familyMembers[] = $reptile['femaleParent'];
			self::checkForIncestRecursive($reptile['maleParent'], $familyMembers, $level + 1);
			self::checkForIncestRecursive($reptile['femaleParent'], $familyMembers, $level+ 1);
		}
	}

	static function readFamilyLevelName($level) {
		$dictionary = [
			1 => ["", "Siblings"],
			2 => ["Grand", "Cousins"],
			3 => ["Great Grand", "Second Cousins"],
			4 => ["Great Great Grand", "Third Cousins"],
			5 => ["Great Great Great Grand", "Fourth Cousins"],
			6 => ["Great Grand Great Grand Great", "Fifth Cousins"],
			7 => ["Great Grand Great Grand Great Grand", "Sixth Cousins"],
			8 => ["Great Grand Great Grand Great Grand Grand", "Seventh Cousins"],
			9 => ["Great Grand Great Grand Great Grand Grand Grand", "Eighth Cousins"]
		];

		if(isset($dictionary[$level])) {
			return $dictionary[$level];
		} else {
			return NULL;
		}
	}

	static function deleteBreedingPair($breedingId) {
		self::$database->delete(
			'Breeding',
			[
				'breedingId' => $breedingId
			]
		);
	}

	static function readParentOptions() {
		self::$database->select(
			"Pet",
			[
				'petId',
				'serial'
			],
			['sex' => "Male"]
		);

		$males = self::$database->fetch_all_row();

		self::$database->select(
			"Pet",
			[
				'petId',
				'serial'
			],
			['sex' => "Female"]
		);

		$females = self::$database->fetch_all_row();

		return [
			'males' => $males,
			'females' => $females
		];
	}

}