<?php

class AgilePermissionModel{
	/* @var AgileApp */
	public $AgileApp;
	/* @var sqlsrv_helper */
	public $database;
	private $groupPermissionsTable;
	private $usersTable;

	function init(){
		$this->groupPermissionsTable = $this->AgileApp->systemConfigs['table']['groupPermissions'];
		$this->usersTable = $this->AgileApp->systemConfigs['table']['users'];
		$this->database = $this->AgileApp->systemDb;
	}

	private function listAllValidApps(){
		$path = $this->AgileApp->systemConfigs['systemPath'];
		$validApps = array();
		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) {
				if ('.' === $file){continue;}
				if ('..' === $file){continue;}
				$filePath = null;
				$phpControllerPath = $path.$file.'/php-controller/'.$file.'.php';
				if(file_exists($phpControllerPath)){
					$filePath = $phpControllerPath;
				}
				$phpPath = $path.$file.'/php/'.$file.'.php';
				if(file_exists($phpPath)){
					$filePath = $phpPath;
				}
				if($filePath === NULL){
					continue;
				}
				$fileContents = file_get_contents($filePath);
				if(!strpos($fileContents,'$AgilePermissions')){
					continue;
				}
				$validApps[] = $file;
			}
			closedir($handle);
		}
		return $validApps;
	}
	
	function getGroupPermissions($groupId){

		$apps = $this->listAllValidApps();
		$appString = array();
		foreach($apps as $app){
			$appString[]= "(?)";
		}
		$this->database->query("CREATE TABLE #apps (appClass varchar(255))");
		$this->database->query("INSERT INTO #apps (appClass) VALUES ".implode(",",$appString),$apps);
		$this->database->query(" SELECT
								#apps.appClass
								,groupPermissionId
								,permissionCreate
								,permissionRead
								,permissionUpdate
								,permissionDelete
							FROM #apps
							LEFT JOIN
								(
									SELECT
										groupPermissionId
										,groupId
										,appClass
										,permissionCreate
										,permissionRead
										,permissionUpdate
										,permissionDelete
									FROM
										{$this->groupPermissionsTable}
									WHERE
										groupId = ?
								)
							AS currentGroupPermissions
							ON #apps.appClass = currentGroupPermissions.appClass
							",array($groupId));
		return $this->database->fetch_all_row();
	}

	function readPermissionsForUserGroupsAndClass($groups,$className){

		if(count($groups) == 0){
			return null;
		}

		$inSqlStr = array();
		foreach($groups as $val){
			$inSqlStr[] = '?';
		}

		$permissions = $this->database->fetch_all_assoc("SELECT
			appClass,
			permissionCreate,
			permissionRead,
			permissionUpdate,
			permissionDelete
			FROM {$this->groupPermissionsTable}
			WHERE groupId IN (".implode(",",$inSqlStr).")
			AND appClass = ?
			",array_merge($groups,array($className)));

		if(count($permissions) === 0){
			return null;
		}

		//merge all permissions from multiple groups to see if you have permission
		$mergedPermissions = $permissions[0];
		foreach($permissions as $permission){
			if($permission['permissionCreate'] === 1){
				$mergedPermissions['permissionCreate'] = 1;
			}
			if($permission['permissionRead'] === 1){
				$mergedPermissions['permissionRead'] = 1;
			}
			if($permission['permissionUpdate'] === 1){
				$mergedPermissions['permissionUpdate'] = 1;
			}
			if($permission['permissionDelete'] === 1){
				$mergedPermissions['permissionDelete'] = 1;
			}
		}

		return $mergedPermissions;
	}

	function insertPermission($permission,$groupId){
		$insertData = array(
			 $groupId
			,$permission['appClass']
			,$permission['permissionCreate']
			,$permission['permissionRead']
			,$permission['permissionUpdate']
			,$permission['permissionDelete']
		);
		$this->database->query("INSERT INTO {$this->groupPermissionsTable}
			(
				groupId
				,appClass
				,permissionCreate
				,permissionRead
				,permissionUpdate
				,permissionDelete
			)
			VALUES (?,?,?,?,?,?)",$insertData);
	}

	function updatePermission($permission){
		$updateData = array(
			$permission['permissionCreate']
			,$permission['permissionRead']
			,$permission['permissionUpdate']
			,$permission['permissionDelete']
			,$permission['groupPermissionId']
		);
		$this->database->query("UPDATE {$this->groupPermissionsTable} SET permissionCreate=?,permissionRead=?,permissionUpdate=?,permissionDelete=? WHERE groupPermissionId=?",$updateData);
	}

}
