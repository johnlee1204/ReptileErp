<?php


use AgileInventory\Models\AgileInventoryModel;
use AgileInventory\Models\BinModel;
use AgileInventory\Models\LocationModel;
use AgileInventory\Models\ProductModel;

class AgileInventory extends AgileBaseController {
	function init() {
		$this->database->selectDatabase("AgileInventory");
	}

	function readLocations() {
		$this->outputSuccessData(LocationModel::readLocations());
	}

	function readBinsForLocation() {
		$input = Validation::validateJsonInput([
			'locationId' => 'numeric'
		]);

		$this->outputSuccessData(AgileInventoryModel::readBinsForLocation($input['locationId']));
	}

	function readOnHandForBin() {
		$input = Validation::validateJsonInput([
			'binId' => 'numeric'
		]);

		$this->outputSuccessData(AgileInventoryModel::readOnHandForBin($input['binId']));
	}

	function readOnHandForLocation() {
		$input = Validation::validateJsonInput([
			'locationId' => 'numeric'
		]);

		$this->outputSuccessData(AgileInventoryModel::readOnHandForLocation($input['locationId']));
	}

	function readLocation() {
		$input = Validation::validateJsonInput([
			'locationId' => 'numeric'
		]);

		$this->outputSuccessData(LocationModel::readLocation($input['locationId']));
	}

	function createLocation() {
		$inputs = Validation::validateJsonInput([
			'locationName' => 'notBlank',
			'locationDescription' => 'notBlank'
		]);

		$this->database->begin_transaction();
		$locationId = LocationModel::createLocation($inputs);
		$this->database->commit_transaction();

		$this->outputSuccessData($locationId);
	}

	function updateLocation() {
		$inputs = Validation::validateJsonInput([
			'locationId' => 'numeric',
			'locationName' => 'notBlank',
			'locationDescription' => 'notBlank'
		]);

		$this->database->begin_transaction();
		$locationId = LocationModel::updateLocation($inputs);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function deleteLocation() {
		$input = Validation::validateJsonInput([
			'locationId' => 'numeric'
		]);

		$this->database->begin_transaction();
		LocationModel::deleteLocation($input['locationId']);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function readBins() {
		$this->outputSuccessData(BinModel::readBins());
	}

	function readBin() {
		$input = Validation::validateJsonInput([
			'binId' => 'numeric'
		]);

		$this->outputSuccessData(BinModel::readBin($input['binId']));
	}

	function createBin() {
		$inputs = Validation::validateJsonInput([
			'binName' => 'notBlank',
			'binDescription' => 'notBlank',
			'location' => 'numeric'
		]);

		$this->database->begin_transaction();
		$binId = BinModel::createBin($inputs);
		$this->database->commit_transaction();

		$this->outputSuccessData($binId);
	}

	function updateBin() {
		$inputs = Validation::validateJsonInput([
			'binId' => 'numeric',
			'binName' => 'notBlank',
			'binDescription' => 'notBlank',
			'location' => 'numeric'
		]);

		$this->database->begin_transaction();
		BinModel::updateBin($inputs);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function deleteBin() {
		$input = Validation::validateJsonInput([
			'binId' => 'numeric'
		]);

		$this->database->begin_transaction();
		BinModel::deleteBin($input['binId']);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function readProducts() {
		$this->outputSuccessData(ProductModel::readProducts());
	}

	function readProduct() {
		$input = Validation::validateJsonInput([
			'productId' => 'numeric'
		]);

		$this->outputSuccessData(ProductModel::readProduct($input['productId']));
	}

	function createProduct() {
		$inputs = Validation::validateJsonInput([
			'productName' => 'notBlank',
			'productDescription',
			'primaryLocation' => 'numericOrNull',
			'primaryBin' => 'numericOrNull',
			'secondaryLocation' => 'numericOrNull',
			'secondaryBin' => 'numericOrNull'
		]);

		$this->database->begin_transaction();
		$productId = ProductModel::createProduct($inputs);
		$this->database->commit_transaction();

		$this->outputSuccessData($productId);
	}

	function updateProduct() {
		$inputs = Validation::validateJsonInput([
			'productId' => 'numeric',
			'productName' => 'notBlank',
			'productDescription',
			'primaryLocation' => 'numericOrNull',
			'primaryBin' => 'numericOrNull',
			'secondaryLocation' => 'numericOrNull',
			'secondaryBin' => 'numericOrNull'
		]);

		$this->database->begin_transaction();
		ProductModel::updateProduct($inputs);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function deleteProduct() {
		$input = Validation::validateJsonInput([
			'productId' => 'numeric'
		]);

		$this->database->begin_transaction();
		ProductModel::deleteProduct($input['productId']);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function readOnHand() {
		$input = Validation::validateJsonInput([
			'productId' => 'numeric'
		]);

		$this->outputSuccessData(ProductModel::readOnHand($input['productId']));
	}

	function readTransactionHistory() {
		$input = Validation::validateJsonInput([
			'productId' => 'numeric'
		]);

		$this->outputSuccessData(ProductModel::readTransactionHistory($input['productId']));
	}

	function adjustQuantity() {
		$inputs = Validation::validateJsonInput([
			'productId' => 'numeric',
			'adjustmentType' => 'notBlank',
			'adjustmentQuantity' => ['tests' => ['numeric'], 'greaterThan' => 0],
			'adjustmentLocation' => 'numeric',
			'adjustmentBin' => 'numeric'
		]);

		$this->database->begin_transaction();
		AgileInventoryModel::adjustQuantity($inputs);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function transferQuantity() {
		$inputs = Validation::validateJsonInput([
			'productId' => 'numeric',
			'transferQuantity' => ['tests' => ['numeric'], 'greaterThan' => 0],
			'transferFromLocation' => 'numeric',
			'transferFromBin' => 'numeric',
			'transferToLocation' => 'numeric',
			'transferToBin' => 'numeric'
		]);

		$this->database->begin_transaction();
		AgileInventoryModel::transferQuantity($inputs);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}
}