<?php


use Incubator\Models\IncubatorModel;

class Incubator extends AgileBaseController{

	function readAppInitData() {
		$this->outputSuccess([
			'reptiles' => IncubatorModel::readReptileComboBox(),
			'maleReptiles' => IncubatorModel::readMaleReptileComboBox(),
			'femaleReptiles' => IncubatorModel::readFemaleReptileComboBox()
		]);
	}

	function readEggs() {
		$this->outputSuccessData(IncubatorModel::readEggs());
	}

	function readReptileComboBox() {
		$this->outputSuccessData(IncubatorModel::readReptileComboBox());
	}

	function readEgg() {
		$input = Validation::validateJsonInput([
			'eggId' => 'numeric'
		]);

		$this->outputSuccessData(IncubatorModel::readEgg($input['eggId']));
	}

	function createEgg() {
		$inputs = Validation::validateJsonInput([
			'serial' => 'notBlank',
			'layDate' => 'notBlank',
			'maleParent' => 'numeric',
			'femaleParent' => 'numeric',
			'hatchDate',
			'hatched' => 'checkBox',
			'sex',
			'type' => 'notBlank'
		]);

		$this->database->begin_transaction();
		$eggId = IncubatorModel::createEgg($inputs);
		$this->database->commit_transaction();

		$this->outputSuccessData($eggId);
	}

	function updateEgg() {
		$inputs = Validation::validateJsonInput([
			'eggId' => 'numeric',
			'serial' => 'notBlank',
			'layDate' => 'notBlank',
			'maleParent' => 'numeric',
			'femaleParent' => 'numeric',
			'hatchDate',
			'hatched' => 'checkBox',
			'sex',
			'type' => 'notBlank'
		]);

		$this->database->begin_transaction();
		IncubatorModel::updateEgg($inputs);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function deleteEgg() {
		$input = Validation::validateJsonInput([
			'eggId' => 'numeric'
		]);

		$this->database->begin_transaction();
		IncubatorModel::deleteEgg($input['eggId']);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function readFamilyTree() {
		$input = Validation::validateJsonInput([
			'eggId' => 'numeric'
		]);

		$this->outputSuccessData(IncubatorModel::readFamilyTree($input['eggId']));
	}
}