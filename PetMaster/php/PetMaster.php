<?php

use DropDownSelectionEditor\Models\DropDownSelectionEditorModel;
use PetMaster\Models\PetMasterModel;
class PetMaster extends AgileBaseController {
	function readPet() {
		$input = Validation::validateJsonInput([
			'petId' => 'numeric'
		]);

		$this->outputSuccessData(PetMasterModel::readPet($input['petId']));
	}

	function createPet() {
		$inputs = Validation::validateJsonInput([
			'name' => 'notBlank',
			'type' => 'notBlank',
			'price' => 'numeric',
			'birthDate',
			'receiveDate' => 'notBlank',
			'sellDate',
			'vendor',
			'cost',
			'habitatId' => 'numericOrNull',
			'food',
			'feedingQuantity',
			'feedingFrequency',
			'customer'
		]);

		$this->outputSuccessData(PetMasterModel::createPet($inputs));
	}

	function updatePet() {
		$inputs = Validation::validateJsonInput([
			'petId' => 'numeric',
			'name' => 'notBlank',
			'type' => 'notBlank',
			'price' => 'numeric',
			'birthDate',
			'receiveDate' => 'notBlank',
			'sellDate',
			'vendor',
			'cost',
			'habitatId' => 'numericOrNull',
			'food',
			'feedingQuantity',
			'feedingFrequency',
			'customer'
		]);

		PetMasterModel::updatePet($inputs);

		$this->outputSuccess();
	}

	function deletePet() {
		$input = Validation::validateJsonInput([
			'petId' => 'numeric'
		]);

		PetMasterModel::deletePet($input['petId']);

		$this->outputSuccess();
	}

	function searchPets() {
		$inputs = Validation::validateJsonInput([
			'name',
			'type',
			'receiveDate',
			'sellDate',
		]);

		$this->outputSuccessData(PetMasterModel::searchPets($inputs));
	}
}