<?php
	class AgilePermissions{
		const PERMISSION_GRANTED = 1;
		const PERMISSION_DENIED = 2;
		const METHOD_NO_PERMISSIONS = 3;
		const NOT_AUTHENTICATED = 4;

		private $AgileApp;
		public $errorMsg = "";
		/**
		 * @var AgilePermissionModel
		 */
		private $permissionModel;

		/**
		 * AgilePermissions constructor.
		 * @param AgileApp $AgileAppInstance
		 */
		function __construct($AgileAppInstance){
			$this->AgileApp = $AgileAppInstance;
			$this->permissionModel = $AgileAppInstance->loadModel('AgilePermissionModel');
		}

		function checkPermissions($className,$classMethod){
			$this->errorMsg = "";

			//no permissions? open sesame
			if(!isset($className::$AgilePermissions)){
				return self::PERMISSION_GRANTED;
			}

			$appPermissions = $className::$AgilePermissions;
			
			if(!isset($appPermissions[$classMethod])){
				$this->errorMsg = "The function '$classMethod' in class '$className' doesn't define permissions!";
				return self::METHOD_NO_PERMISSIONS;
			}

			if($appPermissions[$classMethod] === 'anonymous'){
				return self::PERMISSION_GRANTED;
			}

			if(!$this->AgileApp->SessionManager->getAuthenticated()){
				$this->errorMsg = "You are not logged in!";
				return self::NOT_AUTHENTICATED;
			}

			//Only require the user to be logged in.
			if($appPermissions[$classMethod] === 'loggedIn'){
				return self::PERMISSION_GRANTED;
			}

			//this FALSE only happens if a user has been deleted from the database and the corresponding session has not been deleted/invalated
			if(FALSE === $userInfo = $this->AgileApp->SessionManager->getUserDataFromSession() ){
				$this->errorMsg = "You are not logged in!";
				return self::NOT_AUTHENTICATED;
			}

			if($userInfo['admin']){
				//He's good, let him pass!
				return self::PERMISSION_GRANTED;
			}

			//Get current user permissions for current app.
			$userPermissionResult = $this->permissionModel->readPermissionsForUserGroupsAndClass($this->AgileApp->SessionManager->getUserGroupIdsFromSession($userInfo['employeeId']), $className);

			if(!is_array($appPermissions[$classMethod])){
				$appPermissions[$classMethod] = array($appPermissions[$classMethod]);
			}
			//User doesn't have any permissions at all. Show all required permissions.
			if($userPermissionResult === null){
				$this->errorMsg = "Access Denied! {$userInfo['userName']} needs <u>".implode("</u> and <u>",$appPermissions[$classMethod])."</u> access for {$className} / {$classMethod}  <br/> <div style='text-align:center'><a href='/user/requestPermission?class={$className}&method={$classMethod}'>Request Permission</a></div>";
				return self::PERMISSION_DENIED;
			}
			$missingPermissions = array();
			foreach($appPermissions[$classMethod] as $requirement) {
				$requirement = strtolower($requirement);
				if($userPermissionResult["permission".ucfirst($requirement)] == 0){
					$missingPermissions[] = $requirement;
				}
			}
			//Show missing permissions if any.
			if(count($missingPermissions) > 0){
				$this->errorMsg = "Access Denied! {$userInfo['userName']} needs <u>".implode("</u> and <u>",$missingPermissions)."</u> access for {$className} / {$classMethod} <br/> <div style='text-align:center'> <a href='/user/requestPermission?class={$className}&method={$classMethod}'>Request Permission</a></div>";
				return self::PERMISSION_DENIED;
			}
			return self::PERMISSION_GRANTED;
		}
	}
