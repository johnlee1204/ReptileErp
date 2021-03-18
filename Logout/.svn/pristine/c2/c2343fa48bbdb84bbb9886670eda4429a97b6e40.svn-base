<?php
	class Logout extends AgileBaseController{
		function index(){
			$this->AgileApp->SessionManager->delete();

			if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
				$this->outputSuccess();
			}else{
				readfile($_SERVER['DOCUMENT_ROOT'].'/Logout/index.html');	
			}
			
		}
	}