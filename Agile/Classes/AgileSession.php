<?php

class AgileSession {
	private $sessionConfigs;
	private $sessionModel;
	private $usersModel;
	//private $debugSessions = false;
	private $sessionId = NULL;
	private $sessionUserId = NULL;
	private $sessionUserData = NULL;
	private $sessionUserGroupIds = NULL;

	/**
	 * AgileSession constructor.
	 * @param AgileApp $AgileAppInstance
	 */
	function __construct($AgileAppInstance){ //constructor
		$this->sessionConfigs = $AgileAppInstance->systemConfigs['session'];
		$this->sessionModel = $AgileAppInstance->loadModel('AgileSessionModel');
		$this->usersModel = $AgileAppInstance->loadModel('AgileUserModel');

		$this->AgileApp = $AgileAppInstance;
		//if(isset($_GET['debugsessions'])){
		//	error_reporting(E_ALL);
		//	$this->debugSessions = true;
		//}
		//$this->debugOutput('sessionConfigs: ' . print_r($this->sessionConfigs, true) );
	}

	//function debugOutput($debugMsg){
	//	if($this->debugSessions){
	//		echo "<HR>{$debugMsg}<HR>";
	//	}
	//}
	function cleanupSessions(){
		$this->sessionModel->cleanupSessions();
	}

	private function getSessionCookie(){
		if( isset($_COOKIE[$this->sessionConfigs['cookieName']]) ){
			return $_COOKIE[$this->sessionConfigs['cookieName']];
		}
		return FALSE;
	}
	function initSession(){
		//$this->debugOutput("session cookie ({$this->sessionConfigs['cookieName']}) found!");

		$sessionCookieValue = $this->getSessionCookie();
		if(FALSE === $sessionCookieValue){
			$this->generateNewUserSession();
			return TRUE;
		}
		//$this->debugOutput("sessionId = {$this->sessionId}");

		//check for invalid session length and content
		if(strlen($sessionCookieValue) !== 64 || !ctype_alnum($sessionCookieValue)){
			$this->generateNewUserSession();
			return TRUE;
		}

		$sessionRecord = $this->sessionModel->read($sessionCookieValue);

		//session record not found?
		if( $sessionRecord === NULL ){
			//$this->debugOutput("session cookie existed, but no sessionId found in database");

			//attempt to create a new session and save it to the database

			$this->generateNewUserSession();
			return TRUE;
		}

		if($this->isSessionExpired($sessionRecord)){
			$this->generateNewUserSession();
			return TRUE;
		}

		$this->sessionId = $sessionCookieValue;
		$this->sessionUserId = $sessionRecord['employeeId'];

		//$this->debugOutput("sessionData = " . print_r($sessionRecord, true) );
		if(NULL !== $this->sessionUserId ){
			$this->usersModel->updateLastActivityTime($this->sessionUserId);
		}

		return TRUE;
	}

	private function isSessionExpired($sessionRecord){

		if($sessionRecord['nowTimeStamp'] >= $sessionRecord['expiresTimeStamp']){
			return TRUE;
		}
		return FALSE;
	}

	function getAuthenticated(){

		if(NULL === $this->sessionUserId ){
			return FALSE;
		}
		return TRUE;
	}

	private function generateNewUserSession(){
		$this->create();

		setcookie($this->sessionConfigs['cookieName'], $this->sessionId, time()+$this->sessionConfigs['length'], $this->sessionConfigs['cookiePath']);
	}

	private function setUserId($sessionId, $userId){
		$this->sessionUserId = $userId;
		return $this->sessionModel->setUserId($sessionId, $userId);
	}
	/**
	 * Use this to get logged in user info from current session. Please use only this!
	 */
	function getUserDataFromSession(){

		if(NULL === $this->sessionUserId ){
			return FALSE;
		}
		if($this->sessionUserData === null){
			$this->sessionUserData = $this->usersModel->readUserById($this->sessionUserId);
		}
		return $this->sessionUserData;
	}
	/**
	 * @deprecated use getUserDataFromSession instead
	 */
	function getUserInfoFromSession(){
		return $this->getUserDataFromSession();
	}

	function getUserGroupIdsFromSession(){
		if(NULL === $this->sessionUserId ){
			return FALSE;
		}
		if($this->sessionUserGroupIds === null){
			$this->sessionUserGroupIds = $this->usersModel->readUserGroups($this->sessionUserId);
		}
		return $this->sessionUserGroupIds;
	}

	function readUserCustomField($field){
		if(NULL === $this->sessionUserId ){
			return NULL;
		}
		$result = $this->usersModel->readUserCustomField($this->sessionUserId,$field);
		if($result === NULL){
			return NULL;
		}
		return $result['value'];
	}

	private function create(){

		$maxSessionTries = 10;
		$counter = $maxSessionTries;
		do {
			$this->sessionId = $this->generateSessionId();
			if(TRUE === $this->sessionModel->create($this->sessionId)){
				return;
			}
		}while($counter--);

		$this->sessionId = NULL;
		throw new Exception('Failed to allocate session ID. Max tries exceeded. Max Tries = '.$maxSessionTries);
	}

	function delete(){
		$this->checkIfSessionIdIsNull();
		$this->sessionModel->delete($this->sessionId);
		$this->sessionId = NULL;
		$this->sessionUserId = NULL;
		setcookie($this->sessionConfigs['cookieName'], '', time()-31536000, $this->sessionConfigs['cookiePath']); //set cookie
	}

	private function generateSessionId( ){

		$crypto_strong = null;
		//128 bytes = 1,024 bits of entropy
		$hash = hash("sha256",openssl_random_pseudo_bytes(128,$crypto_strong) );
		if(TRUE !== $crypto_strong){
			throw new Exception("Secure Random Session Data Could Not be Generated!");
		}
		return $hash;
	}

	function authenticateCredentials($user, $password){

		//$this->debugOutput('authenticateCredentials');
		if(TRUE === $this->usersModel->authenticateCredentials($user, $password)){
			//$this->debugOutput('PASSED');
			$this->setUserId($this->sessionId, $this->usersModel->userData['employeeId']);
			return TRUE;
		}else{
			//$this->debugOutput('FAILED');
			return FALSE;
		}
	}

	private function checkIfSessionIdIsNull(){
		if($this->sessionId === NULL){
			throw new Exception("cannot create sessionData when there is no sessionId");
		}
	}

	function readAllSessionData(){
		$this->checkIfSessionIdIsNull();

		return $this->sessionModel->readAllSessionData($this->sessionId);
	}

	function updateOrCreateSessionDataKey($key, $value){
		$this->checkIfSessionIdIsNull();

		$existingKey = $this->sessionModel->readSessionDataKey($this->sessionId, $key);
		if(NULL === $existingKey){
			$this->sessionModel->createSessionDataKey($this->sessionId, $key, $value);
		}else{
			$this->sessionModel->updateSessionDataKey($this->sessionId, $key, $value);
		}

	}

	function deleteSessionDataKey($key){
		$this->checkIfSessionIdIsNull();

		$this->sessionModel->deleteSessionDataKey($this->sessionId, $key);
	}

	/**
	 * Determines if a current user logged in has permission to a given crud action in a given app
	 *
	 * @param $appClass
	 * @param $action
	 * @return bool
	 */
	function checkSessionPermissionsForAppAction($appClass, $action){

		$action = ucfirst(strtolower($action));

		if(FALSE === $userData = $this->getUserDataFromSession() ){
			return FALSE;
		}

		if($userData['admin'] == 1){
			return TRUE;
		}

		if(FALSE === $groups = $this->AgileApp->SessionManager->getUserGroupIdsFromSession()){
			return FALSE;
		}
		$permissionModel = $this->AgileApp->loadModel('AgilePermissionModel');
		if(NULL === $result = $permissionModel->readPermissionsForUserGroupsAndClass($groups,$appClass)){
			return FALSE;
		}

		return isset($result['permission'.$action]) && $result['permission'.$action] === 1;

	}

}