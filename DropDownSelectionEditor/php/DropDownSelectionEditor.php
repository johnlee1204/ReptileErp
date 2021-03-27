<?php

use DropDownSelectionEditor\Models\DropDownSelectionEditorModel;

class DropDownSelectionEditor extends AgileBaseController {

	function readSelections() {
		$input = Validation::validateJsonInput([
			'selectionKey' => 'notBlank'
		]);

		$selections = DropDownSelectionEditorModel::readSelections($input['selectionKey']);

		$output = [];

		foreach($selections as $selection) {
			$output[] = array_values($selection);
		}

		$this->outputSuccessData($output);
	}

	function readSelectionsForCombo() {
		$input = Validation::validateJsonInput([
			'selectionKey' => 'notBlank'
		]);

		$selections = DropDownSelectionEditorModel::readSelectionsForCombo($input['selectionKey']);

		$output = [];

		foreach($selections as $selection) {
			$output[] = array_values($selection);
		}

		$this->outputSuccessData($output);
	}

	function readSelection() {
		$input = Validation::validateJsonInput([
			'dropDownSelectionId' => 'numeric'
		]);

		$this->outputSuccessData(DropDownSelectionEditorModel::readSelection($input['dropDownSelectionId']));
	}

	function createSelection() {
		$inputs = Validation::validateJsonInput([
			'selectionKey' => 'notBlank',
			'selection' => 'notBlank',
			'displayOrder'
		]);

		if($inputs['displayOrder'] === "") {
			$inputs['displayOrder'] = NULL;
		}

		$selectionId = DropDownSelectionEditorModel::createSelection($inputs);

		$this->outputSuccessData($selectionId);
	}

	function updateSelection() {
		$inputs = Validation::validateJsonInput([
			'dropDownSelectionId' => 'numeric',
			'selectionKey' => 'notBlank',
			'selection' => 'notBlank',
			'displayOrder'
		]);

		if($inputs['displayOrder'] === "") {
			$inputs['displayOrder'] = NULL;
		}

		DropDownSelectionEditorModel::updateSelection($inputs);

		$this->outputSuccess();
	}

	function deleteSelection() {
		$input = Validation::validateJsonInput([
			'dropDownSelectionId' => 'numeric'
		]);

		DropDownSelectionEditorModel::deleteSelection($input['dropDownSelectionId']);

		$this->outputSuccess();
	}
}