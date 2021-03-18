<?php
class Login extends AgileBaseController {

	function index(){
		if ($this->AgileApp->SessionManager->getAuthenticated()) {

			//this FALSE only happens if a user has been deleted from the database and the corresponding session has not been deleted/invalated
			if(FALSE === $userInfo = $this->AgileApp->SessionManager->getUserInfoFromSession() ){
				$this->AgileApp->SessionManager->delete();
				$this->AgileApp->SessionManager->generateNewUserSession();
			}else{
				// The only way to ever get here is if you have a bookmark to the login screen and you are a logged in
				if (isset($_GET['redirect'])){
					$class = $_GET['redirect'];
					header($_SERVER["SERVER_PROTOCOL"]." 302"); //HTTP 302 Found
					header('Location: ' . $this->AgileApp->systemConfigs['publicRootUrl'] . $class);
					return;
				}
				header($_SERVER["SERVER_PROTOCOL"]." 302"); //HTTP 302 Found
				header('Location: ' . $this->AgileApp->systemConfigs['publicRootUrl']);
				return;
			}

		}
		readfile(__DIR__ . '/../index.html');
		return;
	}

	function checkLogin(){
		$session = $this->AgileApp->SessionManager->getUserInfoFromSession();

		if($session === false){
			$this->outputSuccess([
				'loggedIn' => false,
				'name' => $session['firstName'].' '.$session['lastName']
			]);
		} else {
			$this->outputSuccess([
				'loggedIn' => true,
				'name' => $session['firstName'].' '.$session['lastName']
			]);
		}
	}
	
	function cleanupSessions(){
		$this->AgileApp->SessionManager->cleanupSessions();
	}

	function logout(){
		//Updated to seperate endpoint "/Logout" . Makes it easier to explain to people when they ask how to logout ("/login/logout" was extreamly confusing last time I had to explain it).
		//This remains as a legacy redirect
		
		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
			$this->outputSuccess();
		}else{
			header('Location: ' . $this->AgileApp->systemConfigs['publicRootUrl'].'Logout');
		}
		return;
	}

	function authenticate(){
		$params = Validation::validatePOST(array('user','pass'));

		if($this->AgileApp->SessionManager->authenticateCredentials($params['user'], $params['pass'])){
			$userData = $this->AgileApp->SessionManager->getUserInfoFromSession();
			$this->outputJson(array(
				'success'=>true,
				'login'=>true,
				'rootUrl'=>$this->AgileApp->systemConfigs['publicRootUrl'],
				'firstName'=>$userData['firstName'],
				'lastName'=>$userData['lastName']
			));
			return true;
		}
		$this->outputJson(array(
			'success'=>true,
			'login'=>false
		));
		return false;
	}

	function resetPassword() {
		$input = Validation::validateJsonInput([
			'user' => 'notBlank'
		]);

		$userModel = $this->AgileApp->loadModel('AgileUserModel');
		$userModel->resetPassword($input['user']);

		$this->outputSuccess();
	}
}