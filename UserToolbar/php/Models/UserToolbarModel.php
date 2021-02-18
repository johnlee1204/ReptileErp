<?php

namespace UserToolbar\Models;

use AgileModel;

class UserToolbarModel extends AgileModel {

	static function readCategories() {
		self::$database->select(
			'ToolbarLinks',
			[
				'distinct linkCategory'
			],
			[],
			'ORDER BY linkCategory'
		);

		return self::$database->fetch_all_row();
	}

	static function readToolbarLinks() {
		self::$database->select(
			'ToolbarLinks',
			[
				'toolbarLinkId',
				'linkName',
				'linkCategory'
			],
			[],
			'ORDER BY linkCategory, linkName'
		);

		return self::$database->fetch_all_row();
	}

	static function readToolbarLink($toolbarLinkId) {
		self::$database->select(
			'ToolbarLinks',
			[
				'linkName',
				'linkPath',
				'iconPath',
				'linkCategory'
			],
			['toolbarLinkId' => $toolbarLinkId]
		);

		return self::$database->fetch_assoc();
	}

	static function createToolbarLink($inputs) {
		self::$database->insert(
			'ToolbarLinks',
			[
				'linkName' => $inputs['linkName'],
				'linkPath' => $inputs['linkPath'],
				'iconPath' => $inputs['iconPath'],
				'linkCategory' => $inputs['linkCategory']
			]
		);

		$newId = self::$database->fetch_assoc("SELECT LAST_INSERT_ID() id");

		return $newId['id'];
	}

	static function updateToolbarLink($inputs) {
		self::$database->update(
			'ToolbarLinks',
			[
				'linkName' => $inputs['linkName'],
				'linkPath' => $inputs['linkPath'],
				'iconPath' => $inputs['iconPath'],
				'linkCategory' => $inputs['linkCategory']
			],
			['toolbarLinkId' => $inputs['toolbarLinkId']]
		);
	}

	static function deleteToolbarLink($toolbarLinkId) {
		self::$database->delete(
			'ToolbarLinks',
			['toolbarLinkId' => $toolbarLinkId]
		);

		self::$database->delete(
			'UserToolbarLink',
			['toolbarLinkId' => $toolbarLinkId]
		);
	}

	static function readAllUnusedLinks($userId) {
		$readAllUnusedLinksSql = "
			SELECT
				toolbarLinkId,
				linkName,
				linkCategory
			FROM
			ToolbarLinks
			WHERE
				toolbarLinkId NOT IN(SELECT toolbarLinkId FROM UserToolbarLink WHERE userId = ?)
			ORDER BY linkCategory
		";
		return self::$database->fetch_all_row($readAllUnusedLinksSql, [$userId]);
	}

	static function readUserLinks($userId) {
		$readUserToolbarLinksSql = "
			SELECT
				UserToolbarLink.userToolbarLinkId,
				ToolbarLinks.linkName,
				ToolbarLinks.linkCategory
			FROM
			UserToolbarLink
			JOIN ToolbarLinks ON UserToolbarLink.toolbarLinkId = ToolbarLinks.toolbarLinkId
			WHERE
				UserToolbarLink.userId = ?
		";

		return self::$database->fetch_all_row($readUserToolbarLinksSql, [$userId]);
	}

	static function addUserLink($userId, $toolbarLinkId) {
		self::$database->insert(
			'UserToolbarLink',
			[
				'toolbarLinkId' => $toolbarLinkId,
				'userId' => $userId
			]
		);
	}

	static function removeUserLink($userToolbarLinkId) {
		self::$database->delete(
			'UserToolbarLink',
			[
				'userToolbarLinkId' => $userToolbarLinkId
			]
		);
	}

	static function readUserLinkButtons($userId) {
		$readUserToolbarLinksSql = "
			SELECT
				ToolbarLinks.linkName,
				ToolbarLinks.linkPath,
				ToolbarLinks.iconPath
			FROM
			UserToolbarLink
			JOIN ToolbarLinks ON UserToolbarLink.toolbarLinkId = ToolbarLinks.toolbarLinkId
			WHERE
				UserToolbarLink.userId = ?
			ORDER BY linkName 
		";

		return self::$database->fetch_all_assoc($readUserToolbarLinksSql, [$userId]);
	}

	static function readAllButtonsByCategory() {
		$allLinks = self::$database->fetch_all_assoc("
			SELECT
				ToolbarLinks.linkName,
				ToolbarLinks.linkPath,
				ToolbarLinks.iconPath,
				ToolbarLinks.linkCategory
			FROM ToolbarLinks
			ORDER BY linkName 
		");

		$output = [];

		foreach($allLinks as $link) {
			if(!array_key_exists($link['linkCategory'], $output)) {
				$output[$link['linkCategory']] = [];
			}
			$output[$link['linkCategory']][] = [
				'linkName' => $link['linkName'],
				'linkPath' => $link['linkPath'],
				'iconPath' => $link['iconPath']
			];
		}
		return $output;
	}

}