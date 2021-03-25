<?php

use UserToolbar\Models\UserToolbarModel;

class UserToolbar extends AgileBaseController {

	public function readLoggedInInformation() {
		$userInfo = $this->AgileApp->SessionManager->getUserDataFromSession();
		$output = NULL;
		$userButtons = [];
		$allApps = UserToolbarModel::readAllButtonsByCategory();
		if($userInfo !== FALSE) {
			$output = [
				'firstName' => $userInfo['firstName'],
				'lastName' => $userInfo['lastName'],
				'userId' => $userInfo['employeeId']
			];
			$userButtons = UserToolbarModel::readUserLinkButtons($userInfo['employeeId']);
		}


		$this->outputSuccess([
			'userData' => $output,
			'userButtons' => $userButtons,
			'allApps' => $allApps
		]);
	}

	public function readCategories() {
		$this->outputSuccessData(UserToolbarModel::readCategories());
	}

	public function readToolbarLinks() {
		$this->outputSuccessData(UserToolbarModel::readToolbarLinks());
	}

	public function readToolbarLink() {
		$input = Validation::validateJsonInput([
			'toolbarLinkId' => 'numeric'
		]);

		$this->outputSuccessData(UserToolbarModel::readToolbarLink($input['toolbarLinkId']));
	}

	public function createToolbarLink() {
		$inputs = Validation::validateJsonInput([
			'linkName' => 'notBlank',
			'linkPath' => 'notBlank',
			'iconPath',
			'linkCategory' => 'notBlank'
		]);

		$newId = UserToolbarModel::createToolbarLink($inputs);

		$this->outputSuccessData($newId);
	}

	public function updateToolbarLink() {
		$inputs = Validation::validateJsonInput([
			'toolbarLinkId' => 'numeric',
			'linkName' => 'notBlank',
			'linkPath' => 'notBlank',
			'iconPath',
			'linkCategory' => 'notBlank'
		]);

		UserToolbarModel::updateToolbarLink($inputs);

		$this->outputSuccess();
	}

	public function deleteToolbarLink() {
		$input = Validation::validateJsonInput([
			'toolbarLinkId' => 'numeric'
		]);

		$this->database->begin_transaction();
		UserToolbarModel::deleteToolbarLink($input['toolbarLinkId']);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	public function readAllUnusedLinks() {
		$input = Validation::validateJsonInput([
			'userId' => 'numeric'
		]);

		$this->outputSuccessData(UserToolbarModel::readAllUnusedLinks($input['userId']));
	}
	public function readUserLinks() {
		$input = Validation::validateJsonInput([
			'userId' => 'numeric'
		]);

		$this->outputSuccessData(UserToolbarModel::readUserLinks($input['userId']));
	}
	public function addUserLink() {
		$inputs = Validation::validateJsonInput([
			'userId' => 'numeric',
			'toolbarLinkId' => 'numeric'
		]);

		UserToolbarModel::addUserLink($inputs['userId'], $inputs['toolbarLinkId']);

		$this->outputSuccess();
	}
	public function removeUserLink() {
		$input = Validation::validateJsonInput([
			'userToolbarLinkId' => 'numeric'
		]);

		UserToolbarModel::removeUserLink($input['userToolbarLinkId']);

		$this->outputSuccess();
	}

	public function readUserLinkButtons() {
		$input = Validation::validateJsonInput([
			'userId' => 'numeric'
		]);

		$this->outputSuccessData();
	}
}