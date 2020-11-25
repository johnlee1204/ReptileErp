<?php


use Workcenter\Models\WorkcenterModel;

class Workcenter extends AgileBaseController {

	function readWorkcenters() {
		$this->outputSuccessData(WorkcenterModel::readWorkcenters());
	}

	function readWorkcenter() {
		$input = Validation::validateJsonInput([
			'workcenterId' => 'numeric'
		]);

		$this->outputSuccessData(WorkcenterModel::readWorkcenter($input['workcenterId']));
	}

	function createWorkcenter() {
		$inputs = Validation::validateJsonInput([
			'workcenterName' => 'notBlank',
			'workcenterDescription',
			'energy'
		]);

		WorkcenterModel::createWorkcenter($inputs);

		$this->outputSuccessData(WorkcenterModel::readLastCreatedWorkcenter());
	}

	function updateWorkcenter() {
		$inputs = Validation::validateJsonInput([
			'workcenterId' => 'numeric',
			'workcenterName' => 'notBlank',
			'workcenterDescription',
			'energy'
		]);

		$this->outputSuccessData(WorkcenterModel::updateWorkcenter($inputs));
	}

	function deleteWorkcenter() {
		$input = Validation::validateJsonInput([
			'workcenterId' => 'numeric'
		]);

		$this->outputSuccessData(WorkcenterModel::deleteWorkcenter($input['workcenterId']));
	}
}