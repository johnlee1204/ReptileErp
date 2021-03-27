<?php


use PasswordReset\Models\PasswordResetModel;

class PasswordReset extends AgileBaseController {
	function verifyNonce() {
		$input = Validation::validateJsonInput([
			'nonce' => 'notBlank'
		]);

		$this->outputSuccessData(PasswordResetModel::verifyNonce($input['nonce']));
	}

	function resetPassword() {
		$inputs = Validation::validateJsonInput([
			'newPassword' => 'notBlank',
			'nonce' => 'notBlank'
		]);

		PasswordResetModel::resetPassword($inputs['newPassword'], $inputs['nonce']);

		$this->outputSuccess();
	}
}