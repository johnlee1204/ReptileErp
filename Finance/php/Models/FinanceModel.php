<?php

namespace Finance\Models;
use AgileModel;

class FinanceModel extends AgileModel {
	static function readLedger() {
		$transactions = self::$database->fetch_all_assoc("
			SELECT
				ledgerId,
				amount,
				transactionDate,
				CONCAT(Employee.firstName, ' ', Employee.lastName) name,
				notes
			FROM Ledger
			LEFT JOIN Employee ON Employee.employeeId = Ledger.employeeId
			ORDER BY transactionDate DESC
		");

		$output = [];
		foreach($transactions as $transaction) {
			$transaction['transactionDate'] = date("F j, Y", strtotime($transaction['transactionDate']));
			$output[] = array_values($transaction);
		}

		return $output;
	}

	static function readCurrentBalance() {
		$currentBalance = self::$database->fetch_assoc("
			SELECT
				SUM(amount) currentBalance
			FROM Ledger
		");

		return $currentBalance['currentBalance'];
	}

	static function readTransaction($ledgerId) {
		$transaction = self::$database->fetch_assoc("
			SELECT
				amount,
				transactionDate,
				employeeId,
				notes
			FROM Ledger
			WHERE
				ledgerId = ?
		", [$ledgerId]);

		$transaction['transactionDate'] = date("Y-m-d", strtotime($transaction['transactionDate']));

		return $transaction;
	}

	static function createTransaction($inputs) {
		$userInformation = self::$agileApp->SessionManager->getUserDataFromSession();

		self::$database->insert(
			'Ledger',
			[
				'amount' => $inputs['amount'],
				'transactionDate' => $inputs['transactionDate'],
				'employeeId' => $userInformation['employeeId'],
				'notes' => $inputs['notes']
			]
		);

		$insertedId = self::$database->fetch_assoc("SELECT LAST_INSERT_ID() id");

		return $insertedId['id'];
	}

	static function updateTransaction($inputs) {
		self::$database->update(
			'Ledger',
			[
				'amount' => $inputs['amount'],
				'transactionDate' => $inputs['transactionDate'],
				'notes' => $inputs['notes']
			],
			['ledgerId' => $inputs['ledgerId']]
		);
	}

	static function deleteTransaction($ledgerId) {
		self::$database->delete(
			'Ledger',
			['ledgerId' => $ledgerId]
		);
	}

}