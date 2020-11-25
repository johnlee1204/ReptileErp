<?php
class AgileAutoloader {

	private $agileApp;
	private $systemConfigs;

	public function __construct($agileApp){
		$this->agileApp = $agileApp;
		$this->systemConfigs = $agileApp->systemConfigs;
	}

	public function loadClass($className){

		if(false !== strpos($className,"\\")){

			$classParts = explode("\\",$className);
			$piecesFound =count($classParts);
			switch($piecesFound){
				case 3:
					list($controller,$type,$class) = explode("\\",$className);
					return $this->loadControllerClass($className, $controller,$type,$class);

				case 2:
					list($namespace,$class) = explode("\\",$className);
					if($namespace !== 'Libraries'){
						return false;
					}
					return $this->requireClassFile($this->systemConfigs['systemPath'].'Libraries/'.$class.'.php', $className);
			}
			return false;
		}

		$validLocations = array(
			$this->systemConfigs['systemPath']."inc/php/Classes/" . $className . '.php',
			$this->systemConfigs['systemPath']."inc/php/Models/" . $className . '.php'
		);

		foreach($validLocations as $location){
			echo 2;
			if(true === $this->requireClassFile($location, $className)){
				echo 3;
				return true;
			}
		}

		return false;
	}
	private function loadControllerClass($className, $controller,$type,$class){
		switch($type){
			case 'Tables':
				$folder = 'tables';
				break;
			case 'Models':
				$folder = 'Models';
				break;
			case 'Records':
				$folder = 'records';
				break;
			default:
				return false;
		}

		if($this->requireClassFile($this->systemConfigs['systemPath'].$controller.'/php/'.$folder.'/'.$class.'.php', $className)){
			return true;
		}
		return false;
	}

	public function requireClassFile($classFile, $className){
		if(true === file_exists($classFile)){
			if(false === require_once($classFile)){
				return false;
			}

			if(is_callable(array($className, 'assignAgileApp'))){
				$className::assignAgileApp($this->agileApp);
			}

			return true;
		}
		return false;
	}
}