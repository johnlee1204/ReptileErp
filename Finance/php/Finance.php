<?php


use Finance\Models\FinanceModel;

class Finance extends AgileBaseController {
	static $AgilePermissions = [
		'index' => 'read',
		'readLedger' => 'read',
		'readTransaction' => 'read',
		'createTransaction' => 'create',
		'updateTransaction' => 'update',
		'deleteTransaction' => 'delete'
	];

	function readLedger() {
		$this->outputSuccess([
			'data' => FinanceModel::readLedger(),
			'currentBalance' => FinanceModel::readCurrentBalance()
		]);
	}

	function readTransaction() {
		$input = Validation::validateJsonInput([
			'ledgerId' => 'numeric'
		]);

		$this->outputSuccessData(FinanceModel::readTransaction($input['ledgerId']));
	}

	function createTransaction() {
		$inputs = Validation::validateJsonInput([
			'amount' => 'numeric',
			'transactionDate' => 'notBlank',
			'notes'
		]);

		$this->outputSuccessData(FinanceModel::createTransaction($inputs));
	}

	function updateTransaction() {
		$inputs = Validation::validateJsonInput([
			'ledgerId' => 'numeric',
			'amount' => 'numeric',
			'transactionDate' => 'notBlank',
			'notes'
		]);

		FinanceModel::updateTransaction($inputs);

		$this->outputSuccess();
	}

	function deleteTransaction() {
		$input = Validation::validateJsonInput([
			'ledgerId' => 'numeric'
		]);

		FinanceModel::deleteTransaction($input['ledgerId']);

		$this->outputSuccess();
	}
}