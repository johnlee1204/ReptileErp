<?php

class AgileUserModel{
	/* @var AgileApp */
	public $AgileApp;
	/* @var sqlsrv_helper */
	public $database;
	private $userTable;
	public $userData;
	private $userGroupsTable;
	private $userCustomDataTable;
	private $groupTable;
	private $ldapConnection;

	function init(){
		$this->userTable = $this->AgileApp->systemConfigs['table']['Employee'];
		$this->userGroupsTable = $this->AgileApp->systemConfigs['table']['userGroups'];
		$this->groupTable = $this->AgileApp->systemConfigs['table']['groups'];
		$this->userCustomDataTable = $this->AgileApp->systemConfigs["database"]["systemDatabase"].'..userCustomData';
		$this->database = $this->AgileApp->systemDb;
	}

	private function ldapBind($user, $psasword, $domain, $server){

		$this->ldapConnection = ldap_connect($server);
		if(FALSE === $this->ldapConnection){
			throw new Exception("Invalid LDAP Configuration Paramters. Connect Check failed.");
		}
		ldap_set_option($this->ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($this->ldapConnection, LDAP_OPT_REFERRALS, 0);
		ldap_set_option($this->ldapConnection, LDAP_OPT_NETWORK_TIMEOUT, 5);

		try{
			$ldapBindResult = ldap_bind($this->ldapConnection, $user."@".$domain, $psasword);
		}catch(Exception $e){
			$ldapBindResult = FALSE;
		}

		if($ldapBindResult === FALSE){
			$ldapErrorNo = ldap_errno($this->ldapConnection);

			// 49 is the Invalid Credentials return code
			if($ldapErrorNo === 49){ //replace with error code for password invalid!
				return FALSE;
			}

			$ldapErrorMessage = ldap_error($this->ldapConnection);

			ldap_get_option($this->ldapConnection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extendedError);
			$ldapErrorMessage .= $extendedError;

			throw new Exception($ldapErrorMessage);
		}

		return $ldapBindResult;
	}

	private function ldapAuthenticate($user, $password){

		if(
			!isset($this->AgileApp->systemConfigs['ldapDomain']) ||
			!isset($this->AgileApp->systemConfigs['ldapServer'])
		){
			throw new Exception("Missing LDAP Configuration");
		}

		return $this->ldapBind(
			$user,
			$password,
			$this->AgileApp->systemConfigs['ldapDomain'],
			$this->AgileApp->systemConfigs['ldapServer']
		);
	}

	private function ldapAutoCreateUser($user, $password){

		if(TRUE !== $this->ldapAuthenticate($user, $password)) {
			return FALSE;
		}

		if(!isset($this->AgileApp->systemConfigs['ldapRootDN'])){
			throw new Exception('ldapRootDN not set in system configs!');
		}
		$filter="(sAMAccountName=$user)";
		$justthese = array("sn", "givenname", "mail");
		$sr=ldap_search($this->ldapConnection,$this->AgileApp->systemConfigs['ldapRootDN'], $filter, $justthese);
		$info = ldap_get_entries($this->ldapConnection, $sr);
		if($info['count'] === 0){
			throw new Exception('Ldap user query returned nothing. Cannot create user! Is your root DN correct?');
		}

		$ldapDataErrors = array();
		if(!isset($info[0]['givenname']) || !isset($info[0]['givenname'][0])){
			$ldapDataErrors[] = 'First Name';
		}
		if(!isset($info[0]['sn']) || !isset($info[0]['sn'][0])){
			$ldapDataErrors[] = 'Last Name';
		}
		if(!isset($info[0]['mail']) || !isset($info[0]['mail'][0])){
			$ldapDataErrors[] = 'Email';
		}
		if(count($ldapDataErrors) > 0){
			throw new AgileUserMessageEmailException("Cannot Create User!<BR><BR>User {$user} is missing ".implode(', ', $ldapDataErrors)."!<BR><BR><B>Contact support!</B>");
		}

		$firstName = trim($info[0]['givenname'][0]);
		$lastName = trim($info[0]['sn'][0]);
		$email = trim($info[0]['mail'][0]);

		$newUserData = array(
			'userName'=>$user,
			'admin'=>0,
			'email' => $email,
			'firstName' => $firstName,
			'lastName' => $lastName,
			'groupIds' => array(),
			'ldapUser' => 1,
			'employeeNumber' => null
		);

		$userId = $this->createUser($newUserData);

		$userData = $this->readUserById($userId);

		return $userData;
	}

	function authenticateCredentials($user, $password){
		if(FALSE === $userData = $this->readUserByName($user, true)){
			if(isset($this->AgileApp->systemConfigs['ldapAuthentication']) && $this->AgileApp->systemConfigs['ldapAuthentication'] && FALSE !== $newLdapUserdata = $this->ldapAutoCreateUser($user, $password)){
				$userData = $newLdapUserdata;
			}else {
				return FALSE;
			}

		}
		$currentDate = date('Y-m-d H:i:s');
		$userUpdateColumns = array(
			'lastLogin' => $currentDate
		);
		if($userData['ldapUser'] === 1){
			if(TRUE !== $result = $this->ldapAuthenticate($user, $password) ){
				return FALSE;
			}
			$userUpdateColumns['ldapLastBind'] = $currentDate;
		}else{
			$testHash = $this->generatePasswordHash($password, $userData['passwordSalt']);
			if($testHash !== $userData['passwordHash']){
				return FALSE;
			}
		}
		$this->database->update($this->userTable, $userUpdateColumns, array('employeeId' => $userData['employeeId']));

		$this->userData = $userData;
		return TRUE;

	}

	function getAllUsersArray(){
		$readAllUsersSql = "SELECT employeeId, userName,firstName,lastName, convert(char(16), lastLogin, 121) lastLogin, convert(char(16), lastActivity, 121) lastActivity, admin, email, ldapUser from {$this->userTable}";
		return $this->database->fetch_all_assoc($readAllUsersSql);
	}

	function updateUser($userId, $userData){

		$overlap = $this->database->fetch_all_assoc("SELECT userName from {$this->userTable} WHERE userName = ? AND employeeId <> ?",array(strtolower($userData['userName']),$userId));
		if(count($overlap) !== 0){
			throw new AgileUserMessageException('Username Taken');
		}

		if(trim($userData['email']) != '') {
			$emailOverlap = $this->database->fetch_all_assoc("SELECT userName from {$this->userTable} WHERE email = ? AND employeeId <> ?", array(strtolower($userData['email']),$userId));
			if (count($emailOverlap) !== 0) {
				throw new AgileUserMessageException('Email already used by another user!');
			}
		}

		if(trim($userData['employeeNumber']) != '') {
			$empOverlap = $this->database->fetch_assoc("SELECT employeeId, employeeNumber from {$this->userTable} WHERE employeeNumber = ?", array(strtolower($userData['employeeNumber'])));
			if($empOverlap !== NULL && $userId != $empOverlap['employeeId']) {
				throw new AgileUserMessageException('Employee Number already used by another user!');
			}
		}

		$groupInserts = array();
		$groupInsertValues = array();
		if(!is_array($userData['groupIds'])) {
			$userData['groupIds'] = [];
		}
		foreach($userData['groupIds'] as $groupId){
			if(!is_numeric($groupId)){
				throw new Exception('Invalid GroupId\'s');
			}
			$groupInserts[] = "?,?";
			$groupInsertValues[] = $userId;
			$groupInsertValues[] = $groupId;
		}

		$admin = false;

		if(isset($userData['admin'])){
			$admin = $userData['admin'];
		}

		$updateUserData = array(
			'userName' => $userData['userName'],
			'admin' => $admin,
			'email' => $userData['email'],
			'firstName' => $userData['firstName'],
			'lastName' => $userData['lastName'],
			'hireDate' => $userData['hireDate'],
			'terminationDate' => $userData['terminationDate'],
			'payRate' => $userData['payRate'],
			'position' => $userData['position'],
			'employeeNumber' => $userData['employeeNumber']
		);

		$user = $this->readUserById($userId, true);
		if(!isset($userData['password'])|| $userData['password'] == ''){
			$userData['password'] = NULL;
		}
//		if($userData['password'] == NULL && !$userData['ldapUser'] && (trim($user['passwordSalt']) === '' || trim($user['passwordHash']) === '')){
//			throw new AgileUserMessageException('You need to set a password if you are disabling LDAP');
//		}

		if($userData['password'] !== NULL && $userData['ldapUser']){
			throw new AgileUserMessageException('Do not set passwords on LDAP users');
		}

		if($userData['password'] !== NULL && !$userData['ldapUser']){
			$updateUserData['passwordSalt'] = $this->generatePasswordSalt();
			$updateUserData['passwordHash'] = $this->generatePasswordHash($userData['password'], $updateUserData['passwordSalt']);
		}

//		if($userData['ldapUser']){
//			$updateUserData['passwordSalt'] = '';
//			$updateUserData['passwordHash'] = '';
//		}

		$this->database->update($this->userTable,$updateUserData,array('employeeId' => $userId));
		$this->database->delete($this->userGroupsTable,array('userId' => $userId));
		if(count($groupInserts) > 0) {
			$this->database->query("INSERT INTO {$this->userGroupsTable} (userId,groupId) VALUES (" . implode("),(", $groupInserts) . ")",$groupInsertValues);
		}
		return true;
	}

	function createUser($userData){
		$overlap = $this->database->fetch_all_assoc("SELECT userName from {$this->userTable} WHERE userName = ?",array(strtolower($userData['userName'])));
		if(count($overlap) !== 0){
			throw new AgileUserMessageException('Username already taken by another user!');
		}

		//make sure user emails are unique
		if(trim($userData['email']) != '') {
			$emailOverlap = $this->database->fetch_all_assoc("SELECT userName from {$this->userTable} WHERE email = ?", array(strtolower($userData['email'])));
			if (count($emailOverlap) !== 0) {
				throw new AgileUserMessageException('Email already used by another user!');
			}
		}

		//make sure empId unique
		if(trim($userData['employeeNumber']) != '') {
			$emailOverlap = $this->database->fetch_all_assoc("SELECT userName from {$this->userTable} WHERE employeeNumber = ?", array(strtolower($userData['employeeNumber'])));
			if (count($emailOverlap) !== 0) {
				throw new AgileUserMessageException('Employee ID already used by another user!');
			}
		}

		$admin = false;
		if(isset($userData['admin'])){
			$admin = $userData['admin'];
		}

		$insertUserData = array(
			'userName' => $userData['userName'],
			'admin' => $admin,
			'email' => $userData['email'],
			'firstName' => $userData['firstName'],
			'lastName' => $userData['lastName'],
			'hireDate' => $userData['hireDate'],
			'terminationDate' => $userData['terminationDate'],
			'payRate' => $userData['payRate'],
			'position' => $userData['position'],
			'employeeNumber' => $userData['employeeNumber']
		);

		if(isset($userData['ldapUser']) && $userData['ldapUser']){
			$insertUserData['ldapUser'] = 1;
			$insertUserData['ldapLastBind'] = 'GETDATE()';
			$insertUserData['passwordSalt'] = '';
			$insertUserData['passwordHash'] = '';
			if(isset($userData['password']) && $userData['password'] !== ''){
				throw new AgileUserMessageException('Do not set passwords on LDAP users');
			}
		}else{
			if($userData['password'] == ''){
				$userData['password'] = NULL;
			}
			$insertUserData['ldapUser'] = 0;
			$insertUserData['passwordSalt'] = $this->generatePasswordSalt();
			$insertUserData['passwordHash'] = $this->generatePasswordHash($userData['password'], $insertUserData['passwordSalt']);
		}

		$this->database->insert($this->userTable,$insertUserData);
		$insertResult = $this->database->fetch_assoc("SELECT LAST_INSERT_ID() as insertId");
		$groupInserts = array();
		if(isset($userData['groupIds'])) {
			foreach ($userData['groupIds'] as $groupId) {
				if (!is_numeric($groupId)) {
					throw new Exception('Invalid GroupId\'s');
				}
				$groupInserts[] = $insertResult['insertId'] . "," . $groupId;
			}
		}
		if(count($groupInserts) > 0) {
			$this->database->query("INSERT INTO {$this->userGroupsTable} (userId,groupId) VALUES (" . implode("),(", $groupInserts) . ")");
		}
		return $insertResult['insertId'];
	}

	function deleteUser($userId){
		$this->database->query("delete from {$this->userTable} where employeeId=?",array($userId));
	}

	private function readUser($filter, $filterValue, $authenticationData=false){
		
		if($authenticationData===true){
			$authenticationCols = 'passwordSalt, passwordHash, ldapLastBind ';
		}else{
			$authenticationCols = '';
		}
		$readUserSql = "SELECT {$authenticationCols} userName, userPreferences, employeeId, admin, email, firstName, lastName, ldapUser, employeeNumber from {$this->userTable} where {$filter}";
		if(NULL === $user = $this->database->fetch_assoc($readUserSql, array($filterValue))){
			return FALSE;
		}
		
		return $user;
	}

	function readUserById( $userId, $authenticationData=false){
		return $this->readUser("employeeId=?", $userId, $authenticationData);
	}

	function readUserByName( $userName, $authenticationData=false){
		return $this->readUser("userName=?", $userName, $authenticationData);
	}

	# Returns Group ID's
	function readUserGroups($userId){
		$groupIds = array();
		$userGroups = $this->database->fetch_all_assoc("SELECT groupId FROM {$this->userGroupsTable} WHERE userId = ?",array($userId));
		foreach($userGroups as $userGroup){
			$groupIds[] = $userGroup['groupId'];
		}
		return $groupIds;
	}

	function readUserGroupNames($userId){
		$groupNames = array();
		$userGroups = $this->database->fetch_all_assoc("select {$this->groupTable}.groupName from {$this->userGroupsTable} left join {$this->groupTable} on {$this->userGroupsTable}.groupId = {$this->groupTable}.groupId where userId = ?",array($userId));
		foreach($userGroups as $userGroup){
			$groupNames[] = $userGroup['groupName'];
		}
		return $groupNames;
	}

	function generatePasswordSalt(){ //create a new session

		$crypto_strong;
		//128 bytes = 1,024 bits of entropy
		$hash = hash("sha256",openssl_random_pseudo_bytes(128,$crypto_strong) );
		if(TRUE !== $crypto_strong){
			throw new Exception("Secure Random Session Data Could Not be Generated!");
		}
		return $hash;
	}

	function generatePasswordHash($password, $passwordSalt){
		return hash('sha256', $passwordSalt . $password);
	}

	function updateLastActivityTime($userId){
		$currentDate = date('Y-m-d H:i:s');
		$this->database->query("UPDATE {$this->userTable} set lastActivity=? where employeeId=?",array($currentDate, $userId));
	}

	function updateLastLoginTime($userId){
		$currentDate = date('Y-m-d H:i:s');
		$this->database->query("UPDATE {$this->userTable} set lastLogin=? where employeeId=?",array($currentDate, $userId));
	}

	function readUserCustomField($userId,$field){
		return $this->database->fetch_assoc("SELECT value FROM {$this->userCustomDataTable} WHERE employeeId = ? AND field = ?",array($userId,$field));
	}

	function readAllLdapUsers(){

		if(
			!isset($this->AgileApp->systemConfigs['ldapBindUser']) ||
			!isset($this->AgileApp->systemConfigs['ldapBindPass']) ||
			!isset($this->AgileApp->systemConfigs['ldapDomain']) ||
			!isset($this->AgileApp->systemConfigs['ldapServer'])
		){
			throw new Exception("Missing LDAP Configuration");
		}

		$bindResult = $this->ldapBind(
			$this->AgileApp->systemConfigs['ldapBindUser'],
			$this->AgileApp->systemConfigs['ldapBindPass'],
			$this->AgileApp->systemConfigs['ldapDomain'],
			$this->AgileApp->systemConfigs['ldapServer']
		);
		if(!$bindResult){
			throw new AgileUserMessageEmailException('Could not connect to LDAP!');
		}

		$filter="(&(objectCategory=person)(samaccountname=*))";
		$justthese = array("sAMAccountName","sn", "givenname", "mail");
		$sr=ldap_search($this->ldapConnection,$this->AgileApp->systemConfigs['ldapRootDN'], $filter, $justthese);
		$results = ldap_get_entries($this->ldapConnection, $sr);
		$output = array();

		$sortArray = array();
		foreach($results as $result){
			$row = array(
				'username' => '',
				'firstName' => '',
				'lastName' => '',
				'email' => ''
			);
			if(isset($result['samaccountname']) && isset($result['samaccountname'][0])){
				$row['username'] = strtolower($result['samaccountname'][0]);
			}else{
				continue;
			}
			if(isset($result['givenname']) && isset($result['givenname'][0])){
				$row['firstName'] = $result['givenname'][0];
			}
			if(isset($result['sn']) && isset($result['sn'][0])){
				$row['lastName'] = $result['sn'][0];
			}
			if(isset($result['mail']) && isset($result['mail'][0])){
				$row['email'] = $result['mail'][0];
			}
			$output[] = array_values($row);
			$sortArray[] = trim($row['firstName']);
		}

		array_multisort($sortArray, SORT_NATURAL | SORT_FLAG_CASE , $output);

		return $output;
	}
}
