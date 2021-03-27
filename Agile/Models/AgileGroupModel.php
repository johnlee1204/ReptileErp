<?php

class AgileGroupModel{
	/* @var AgileApp */
	public $AgileApp;
	/* @var sqlsrv_helper */
	public $database;
	private $groupTable;
	private $userTable;
	private $userGroupsTable;
	private $groupPermissionsTable;

	function init(){
		$this->groupTable = $this->AgileApp->systemConfigs['table']['groups'];
		$this->groupPermissionsTable = $this->AgileApp->systemConfigs['table']['groupPermissions'];
		$this->userGroupsTable = $this->AgileApp->systemConfigs['table']['userGroups'];
		$this->userTable = $this->AgileApp->systemConfigs['table']['users'];
	}

	function getAllGroupsArray(){
		$this->database->query("SELECT groupId,groupName FROM {$this->groupTable} ORDER BY groupName");
		return $this->database->fetch_all_assoc();
	}

	function checkGroupExists($groupName){
		$this->database->query("SELECT groupId,groupName FROM {$this->groupTable} WHERE groupName = ?",array($groupName));
		if($this->database->has_rows()){
			return true;
		}
		return false;
	}

	function autoAddNewGroup(){
		$this->database->query("SELECT MAX(groupId) + 1 as nextGroupId FROM {$this->groupTable}");
		$nextGroupId = $this->database->fetch_row();
		$nextGroupId = $nextGroupId[0];
		return $this->addNewGroup("Group".$nextGroupId);
	}

	function addNewGroup($groupName){
		$this->database->query("INSERT INTO {$this->groupTable}(groupName) VALUES (?)",array($groupName));
		$this->database->query("SELECT LAST_INSERT_ID() FROM {$this->groupTable}");
		$insertId = $this->database->fetch_row();
		$insertId = $insertId[0];
		return $insertId;
	}

	function editGroup($groupId,$groupName){
		$this->database->query("UPDATE {$this->groupTable} SET groupName = ? WHERE groupId = ?",array($groupName,$groupId));
	}

	function deleteGroup($groupId){
		$this->database->query("DELETE FROM {$this->groupTable} WHERE groupId = ?",array($groupId));
		$this->database->query("DELETE FROM {$this->groupPermissionsTable} WHERE groupId = ?",array($groupId));
	}

	function getUsersInGroup($groupId){
		return $this->database->fetch_all_assoc("SELECT userId FROM {$this->userGroupsTable} WHERE groupId = ?",array($groupId));
	}

	function checkIfUserInGroup($employeeId, $groupName) {
		$hasPermission = $this->database->fetch_assoc("
			SELECT
				userGroupId
			FROM {$this->userGroupsTable}
			JOIN {$this->groupTable} ON {$this->groupTable}.groupId = {$this->userGroupsTable}.groupId 
			WHERE
				userId = ?
			AND
				groupName = ?
		", [$employeeId, $groupName]);

		return $hasPermission !== NULL;
	}
}
