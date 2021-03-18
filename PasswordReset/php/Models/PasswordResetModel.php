<?php

namespace PasswordReset\Models;
use AgileModel;
use AgileUserMessageException;
use Employee\Models\EmployeeModel;

class PasswordResetModel extends AgileModel {
	static function verifyNonce($nonce) {
		self::$database->select(
			"PasswordReset",
			['employeeId'],
			['nonce' => $nonce]
		);

		$employeeId = self::$database->fetch_assoc();

		if($employeeId === NULL) {
			throw new AgileUserMessageException("Invalid Nonce!");
		}

		$employee = EmployeeModel::readEmployee($employeeId['employeeId']);

		return $employee['firstName'] . ' ' . $employee['lastName'];
	}

	static function resetPassword($newPassword, $nonce) {
		self::$database->select(
			"PasswordReset",
			['employeeId'],
			['nonce' => $nonce]
		);

		$employeeId = self::$database->fetch_assoc();

		if($employeeId === NULL) {
			throw new AgileUserMessageException("Invalid Nonce!");
		}

		$userModel = self::$agileApp->loadModel('AgileUserModel');
		$passwordSalt = $userModel->generatePasswordSalt();
		$passwordHash = $userModel->generatePasswordHash($newPassword, $passwordSalt);

		self::$database->update(
			"Employee",
			[
				'passwordSalt' => $passwordSalt,
				'passwordHash' => $passwordHash
			],
			['employeeId' => $employeeId['employeeId']]
		);

		self::$database->delete(
			"PasswordReset",
			[
				'employeeId' => $employeeId['employeeId']
			]
		);
	}
}