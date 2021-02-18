<?php
	class UserGroup extends AgileBaseController {

//		public static $AgilePermissions = array(
//			'index' => array('read')
//			,'getGroupList' => array('read')
//			,'addGroup' => array('create')
//			,'getGroupPermissions' => array('read')
//			,'editGroup' => array('update')
//			,'deleteGroup' => array('delete')
//		);

        /* @var AgileGroupModel */
        private $groupModel;
        /* @var AgilePermissionModel */
        private $permissionsModel;

        function init(){
			$this->groupModel = $this->loadModel('AgileGroupModel');
			$this->permissionsModel = $this->loadModel('AgilePermissionModel');
		}

		function getGroupList(){
			$this->outputSuccess(array(
				'groups' => $this->groupModel->getAllGroupsArray()
			));
			return true;
		}

		function addGroup(){
			$newGroupId = $this->groupModel->autoAddNewGroup();
			$this->outputJson(array(
				'success' => true
				,'groups' => $this->groupModel->getAllGroupsArray()
				,'newGroupId' => $newGroupId
			));
			return true;
		}

		function getGroupPermissions(){
			$this->outputJson(array(
				'success' => true
				,'groupPermissions' => $this->permissionsModel->getGroupPermissions($_POST['groupId'])
			));
		}

		function editGroup(){
			$groupInfo = json_decode($_POST['groupInfo'],true);
			if(!$groupInfo){
				throw new \Exception("The groupInfo is invalid JSON!");
			}
			if(!isset($groupInfo['editGroupIdField'])){
				throw new \Exception("The editGroupIdField is required!");
			}
			if(!isset($groupInfo['editGroupNameField'])){
				throw new \Exception("The editGroupNameField is required!");
			}
			if(preg_match("/^[a-z,A-Z,0-9, ]{3,50}$/",$groupInfo['editGroupNameField']) !== 1){
				throw new \Exception("The editGroupNameField must be at 3-50 alpha-numeric characters or spaces!");
			}
			$groupId = $groupInfo['editGroupIdField'];
			$newGroupName = $groupInfo['editGroupNameField'];
			$this->groupModel->editGroup($groupId, $newGroupName);

			$changedPermissions = array();
			if(isset($_POST['groupPermissions'])){
				$changedPermissions = json_decode($_POST['groupPermissions'],true);
			}

			if(count($changedPermissions)>0){
				foreach($changedPermissions as $permission){
					if($permission['groupPermissionId'] === null){
						//This permission wasn't created yet, add an insert!
						$this->permissionsModel->insertPermission($permission,$groupId);
					}else{
						//This permission already exists, let's update it!
						$this->permissionsModel->updatePermission($permission);
					}
				}
			}

			$this->outputJson(array(
				'success' => true
				,'groups' => $this->groupModel->getAllGroupsArray()
			));
		}

		function deleteGroup(){
			if(!isset($_POST['groupId'])){
				throw new \Exception("groupId is required!");
			}
			$groupId = $_POST['groupId'];

			//TODO: get list of users in group, do not let them delete if users are still in group!
			$usersInGroup = $this->groupModel->getUsersInGroup($groupId);
			if(count($usersInGroup)>0){
				$this->outputJson(array(
					'success' => false
					,'error' => 'Failed to delete - There are '.count($usersInGroup).' user(s) left in this group!'
				));
				return false;
			}

			$this->groupModel->deleteGroup($groupId);

			$this->outputJson(array(
				'success' => true
				,'groups' => $this->groupModel->getAllGroupsArray()
			));
			return true;
		}
	}
?>
