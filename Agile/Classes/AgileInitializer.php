<?php

new AgileInitializer();

class AgileInitializer{

	function __construct(){
		//get all the system configs
		/** @var $systemConfigs array */
		if(FALSE === file_exists('../SystemConfigs.php')){
			echo "Could Not Find System Configs! Contact Support!";
		} else {
			include('../SystemConfigs.php');
		}

		//based on the system class path, pull in the main App class
		require_once( $systemConfigs['systemCorePath']."Classes/" . 'AgileApp.php');

		//use config for time zone since php.ini is sometimes inaccessible
		date_default_timezone_set($systemConfigs['systemTimeZone']);

		//initialize the application
		new AgileApp($systemConfigs);
	}
}
