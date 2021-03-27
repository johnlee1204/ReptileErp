<?php

use Habitat\Models\HabitatModel;
class Habitat extends AgileBaseController {

	function updateHabitatVital() {
		$inputs = Validation::validatePost([
			'temperature' => 'numeric',
			'humidity' => 'numeric',
			'habitatId' => 'numeric'
		]);

		HabitatModel::updateHabitatVital($inputs['temperature'], $inputs['humidity'], $inputs['habitatId']);
	}

	function readHabitatVitals() {
		$input = Validation::validateJsonInput([
			'habitatId' => 'numeric'
		]);

		$this->outputSuccessData(HabitatModel::readHabitatVitals($input['habitatId']));
	}

	function readHabitats() {
		$this->outputSuccessData(HabitatModel::readHabitats());
	}

	function readHabitat() {
		$input = Validation::validateJsonInput([
			'habitatId' => 'numeric'
		]);

		$this->outputSuccessData(HabitatModel::readHabitat($input['habitatId']));
	}

	function createHabitat() {
		$inputs = Validation::validateJsonInput([
			'habitatName' => 'notBlank',
			'rack'
		]);

		$this->outputSuccessData(HabitatModel::createHabitat($inputs));
	}

	function updateHabitat() {
		$inputs = Validation::validateJsonInput([
			'habitatId' => 'numeric',
			'habitatName' => 'notBlank',
			'rack'
		]);

		HabitatModel::updateHabitat($inputs);

		$this->outputSuccess();
	}

	function deleteHabitat() {
		$input = Validation::validateJsonInput([
			'habitatId' => 'numeric'
		]);

		HabitatModel::deleteHabitat($input['habitatId']);

		$this->outputSuccess();
	}
}