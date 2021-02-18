<?php

class AgileRequestRouter{

	private $AgileApp;
	private $configs;
	private $debugRequest;

	public $routeClass;
	public $routeMethod;
	public $parsedUrl;
	public $routedUri;
	public $classFile;
	public $indexFile;
	public $ajaxRequested;

	/**
	 * AgileRequestRouter constructor.
	 * @param AgileApp $AgileApp
	 */
	public function __construct($AgileApp){
		$this->AgileApp = $AgileApp;
		$this->configs = $AgileApp->systemConfigs['router'];

		$this->debugRequest = false;

		if(isset($_GET['debugrouter'])){
			error_reporting(E_ALL);
			$this->debugRequest = true;
		}
	}

	function debugOutput($debugMsg){
		if($this->debugRequest){
			echo "<HR>{$debugMsg}<HR>\r\n";
		}
	}

	function routeRequest(){
		//Was requested over ajax

		$this->ajaxRequested = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

		$this->debugOutput('called AgileRequestRouter::routeRequest()');
		$this->debugOutput("_SERVER['REQUEST_URI']<BR>{$_SERVER['REQUEST_URI']}");
		$this->parsedUrl = parse_url($_SERVER['REQUEST_URI']);
		$this->debugOutput("parsedUrl: <PRE>" . print_r($this->parsedUrl, TRUE) ."</PRE>" );
		$this->routedUri = $this->AgileApp->systemConfigs['publicRoot'];

		if(!isset($this->parsedUrl['path'])){
			$this->serve404error('invalid path in route');
			return;
		}
		$systemPathPos = strpos($this->parsedUrl['path'], $this->AgileApp->systemConfigs['publicRoot']);
		$this->debugOutput("searching for systemPath: (".$this->parsedUrl['path'].") result: (" . var_export($systemPathPos, TRUE) .")" );

		//Search for and strip out the system Root:
		if(FALSE !== $systemPathPos){
			$this->routedUri = substr($this->parsedUrl['path'], strlen($this->AgileApp->systemConfigs['publicRoot']));
		}

		$this->debugOutput("routedUri: (". $this->routedUri .")" );
		$routeUriParse = $this->routedUri;

		if(substr($routeUriParse,0,1) === '/'){
			$routeUriParse = substr($routeUriParse,1);
			$this->debugOutput("found leading slash - stripping.<BR> routedUri: (". $routeUriParse .")" );
		}

		$nextSlash = strpos($routeUriParse,'/');

		if($nextSlash === FALSE){
			$this->debugOutput("There are no more slashes!" );
			$this->debugOutput(" - routeUriParse: {$routeUriParse}" );
			if($routeUriParse == ""){
				$this->routeClass = false;
			}else{
				//no trailing slash? add one!
				$this->redirect($routeUriParse.'/');
				return;
			}
			$routeUriParse = '';
		}else{
			$this->debugOutput("nextSlash: " . var_export($nextSlash, TRUE) );
			$this->routeClass = substr($routeUriParse, 0, $nextSlash);
			$routeUriParse = substr($routeUriParse, $nextSlash+1);
		}
		$this->debugOutput("routeClass: ".$this->routeClass );
		$this->debugOutput("routeUriParse: ".$routeUriParse );

		$indexFileName = 'index.html';

		//If we are literally routing to "ControllerName/index" or "ControllerName/index.html", redirect instead
		if($this->routeClass == $indexFileName){
			//This is for apps with no controller, where they use another apps controller
			$this->redirect("");
			return;
		}

		if(strlen($routeUriParse) === 0){
			$this->routeMethod = 'index';
		}else{
			//Do we have another slash?
			$nextSlash = strpos($routeUriParse, '/');
			if($nextSlash === FALSE){
				$this->routeMethod = $routeUriParse;
			}else{
				$this->routeMethod = substr($routeUriParse, 0,$nextSlash);
			}
			$this->debugOutput("routeMethod: ".$this->routeMethod );

			//If we are literally routing to "ControllerName/index" or "ControllerName/index.html", redirect instead
			if($this->routeMethod === $indexFileName || $this->routeMethod === 'index'){
				//This is for apps with no controller, where they use another apps controller
				$this->redirect($this->routeClass.'/');
				return;
			}
		}

		//If you are going to the server root with no app, use the configured default root.
		//Default Class=Root
		//Default Method=index
		if($this->routeClass === false){
			$this->routeClass = $this->configs['rootClass'];
			$this->routeMethod = $this->configs['rootMethod'];
		}
		$classExists = false;
		$phpLocations = array(
			'php'
		);
		foreach($phpLocations as $phpDir){
			$this->classFile = $this->AgileApp->systemConfigs['systemPath'].$this->routeClass.'/'.$phpDir.'/'.$this->routeClass.'.php';
			if(file_exists($this->classFile)){
				$classExists = true;
				break;
			}
		}
		$this->debugOutput("classFile: ".$this->classFile);
		$this->indexFile = $this->AgileApp->systemConfigs['systemPath'].$this->routeClass.'/'.$indexFileName;
		$this->debugOutput("indexFile: ".$this->indexFile );
		$this->debugOutput("method: ".$this->routeMethod );

		if(!$classExists){
			$this->debugOutput("class file doesn't exist! ".$this->classFile );
			//This is for apps with no controller, where they use another apps controller

			//do we have a trailing slash? Are we looking at a directory?
			if(substr($this->parsedUrl['path'],-1) == '/'){
				$this->debugOutput("URI has a trailing slash!" );
				$dirIndex = $this->AgileApp->systemConfigs['systemPath'] . substr($this->parsedUrl['path'],1) . $indexFileName;
				$this->debugOutput("Do we have a local index? ". $dirIndex );
				if(file_exists($dirIndex)){
					$this->logRequest(false,true);
					readfile($dirIndex);
				}else{
					$this->serve404error('directory route has no index file');
				}
			}else{
				$this->serve404error('File not Found - no trailing slash');
			}
			return;
		}

		chdir( dirname($this->classFile) );

		require_once($this->classFile);
		if(!class_exists($this->routeClass)){
			$this->serve404error('The '.$this->routeClass.' class is not in '.$this->classFile);
			return;
		}
		$permCheck = $this->AgileApp->PermissionsManager->checkPermissions($this->routeClass,$this->routeMethod);
		switch($permCheck){
			case AgilePermissions::NOT_AUTHENTICATED:
				$this->serveLoginRedirect();
				return;
			case AgilePermissions::PERMISSION_DENIED:
				$this->serve401error($this->AgileApp->PermissionsManager->errorMsg,$this->routeClass,$this->routeMethod);
				return;
			case AgilePermissions::METHOD_NO_PERMISSIONS:
				$this->serve404error($this->AgileApp->PermissionsManager->errorMsg);
				return;
			case AgilePermissions::PERMISSION_GRANTED:
				//carry on
				break;
		}

		$ClassInstance = new $this->routeClass();
		//Init Class Variables
		$ClassInstance->AgileApp = $this->AgileApp;
		$ClassInstance->database = $this->AgileApp->database;

		//Call AgileInit
		if(is_callable(array($ClassInstance,'init'))){
			$ClassInstance->init();
		}

		if(FALSE === is_callable(array($ClassInstance, $this->routeMethod) ) ){
			if($this->routeMethod === 'index'){
				if(file_exists($this->indexFile)){
					$this->logRequest(false,true);
					readfile($this->indexFile);
				}else{
					$this->serve404error('This class has no index method and no index.html.');
				}
				return;
			}
			$this->serve404error("Method ({$this->routeMethod}) does not exist or cannot be called in class ({$this->routeClass})!");
			return;
		}
		$this->logRequest(false,true);

		$routeMethod = $this->routeMethod;
		$ClassInstance->$routeMethod();
		return;
	}
	private function redirect($destination, $redirectCode="301",$includeQueryVars = true){
		$this->logRequest(false,false);
		header($_SERVER["SERVER_PROTOCOL"]." ".$redirectCode); //HTTP 301 Moved Permanently
		$queryString = '';
		if($_SERVER['QUERY_STRING'] !== '' && $includeQueryVars){
			$queryString = '?'.$_SERVER['QUERY_STRING'];
		}
		if(isset($this->AgileApp->systemConfigs['forceHttps']) && $this->AgileApp->systemConfigs['forceHttps'] === true){
			$protocol="https";
		}else{
			if(empty($_SERVER['HTTPS'])){
				$protocol="http";
			}else{
				$protocol="https";
			}
		}
		if(isset($this->AgileApp->systemConfigs['forceHost']) ){
			$host = $this->AgileApp->systemConfigs['forceHost'];
		}else{
			$host = $_SERVER["HTTP_HOST"];
		}
		$server = $protocol ."://" . $host . $this->AgileApp->systemConfigs["publicRoot"];
		header('Location: '.$server.$destination.$queryString);
	}
	private function serveLoginRedirect(){
		$this->logRequest(false,false);
		if($this->ajaxRequested){
			echo json_encode(array('success' => 'false','error' => 'Not Logged In','loggedIn' => 'false'));
		}else{
			$uri = $_SERVER['REQUEST_URI'];
			//cut off first slash.
			if(substr($uri,0,1) == '/'){
				$uri = substr($uri,1);
			}
			$this->redirect("Login/?redirect=".urlencode($uri), "302", false);
		}
		return;
	}
 
	private function serve401error($errorMessage,$class,$method){
		$this->logRequest(false,false);
		header($_SERVER["SERVER_PROTOCOL"]." 401 Not Authorized");
		if($this->ajaxRequested){
			echo json_encode(array('success' => 'false' ,'error' => $errorMessage ));
		}else{
			$html401 = file_get_contents($this->AgileApp->systemConfigs['systemCorePath'] . 'ErrorPages/http-errors-401.html');
			if(NULL !== $user = $this->AgileApp->SessionManager->getUserDataFromSession()){
				$html401 = str_replace('{user}',$user['userName'],$html401);
			}
			$html401 = str_replace('{class}',$class,$html401);
			$html401 = str_replace('{method}',$method,$html401);
			echo $html401;
			echo '<center><span style="margin-top:10%;font-family:arial;font-size:10px;color:#bbb">php router: '. $errorMessage. "</span></center>";
		}
		return;
	}

	private function serve404error($errorMessage){
		$this->logRequest(true,false);
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
		if($this->ajaxRequested){
			echo json_encode(array('success' => 'false' ,'error' => $errorMessage ));
		}else {
			readfile($this->AgileApp->systemConfigs['systemCorePath'] . 'ErrorPages/http-errors-404.html');
			echo '<center><span style="margin-top:10%;font-family:arial;font-size:10px;color:#bbb">php router: ' . $errorMessage . "</span></center>";
		}
		return;
	}

	function logRequest($notFound,$authorized){
		$standardLogData = $this->AgileApp->readStandardLogColumnValues();

		if($standardLogData['class'] == 'shop_floor' && $standardLogData['method'] == 'checkforlatestversion'){
			return;
		}

		if($standardLogData['class'] == 'qccamerabarcodetracker' && $standardLogData['method'] == 'getlivephoto'){
			return;
		}

		if($standardLogData['class'] == 'tv'){
			return;
		}

		if($standardLogData['class'] == 'prtg'){
			return;
		}

		$insertData = array_merge($standardLogData, array(
			'notFound' => $notFound,
			'authorized' => $authorized
		));

//		try{
//			$this->AgileApp->systemDb->insert($this->AgileApp->systemConfigs['table']['logAccess'], $insertData);
//		}catch(Exception $ex){
//			echo "<HR>\r\nFailed to write to access log. Your database config is bad.\r\n<HR>\r\n";
//			echo $ex->getMessage(),"\r\n<HR>\r\n";
//			exit;
//		}
	}
}
