<?php

/******************************************
 *	Data Logging Class
 *	 Created by Andrew Rymarczyk 9:58 AM 10/14/2009
 ****************************************
*/


/*
Reference Implementation:
---------------
	include($_SERVER['DOCUMENT_ROOT'] .'/inc/php/classes/LegacyAerpLog.php');

	$logger = new LegacyAerpLog();
	$logger->setLogDir($_SERVER['DOCUMENT_ROOT'] .'/logs/testlog/');
	
	$logger->logMessage('db','ERRORTYPE','This is an Error log test message ONE');
	$logger->logMessage('db','VALIDATION','This is an Error test message TWO');
	$logger->logMessage('db','LOGIC','This is an Error test message THREE');
	
	$logger->logMessage('error','STATE','This is a DB log test message 1');
	$logger->logMessage('error','RECORD','This is a DB log test message 2');
	$logger->logMessage('error','VALIDATION','This is a DB log test message 3');
	
	//NOTE: if explicit call to $logger->writeLogs(); is not made. destructor on the class will call it automatically (requires PHP5+);
---------------
*/

class LegacyAerpLog {

	var $logFolder;
	var $loggedMessages;
	
	function __construct($logFolder = ''){ //__construct requires PHP5+
		$this->loggedMessages = array();
		
		// if( $logFolder !== ''){
		// 	$this->logFolder = $logFolder;
			
		// 	if( !is_dir($this->logFolder) ){
		// 		if( !mkdir($this->logFolder) ){
		// 			die("\r\nFATAL ERROR during logging.<BR>\r\nLog dir:".$this->logFolder." is not a folder!\r\n");
		// 		}
		// 	}
		// }

	}
	
	// function __destruct(){ //__destruct requires PHP5+
	// 	$this->writeLogs();
	// }
	
	function setLogDir($logFolder){
		
		$this->logFolder = $logFolder;
		
		// if( !is_dir($this->logFolder) ){
		// 	if( !mkdir($this->logFolder) ){
		// 		die("\r\nFATAL ERROR during logging.<BR>\r\nLog dir:".$this->logFolder." is not a folder!\r\n");
		// 	}
		// }
		
	}
	
	function logMessage($type, $operation, $message){
		//$operation is arbitraty designator for operation name.
			//This is useful for looking at the logs and grouping messages by the operation.
			//e.g. "INV TRAN" operation would mean that an Inventory Transaction operation is being logged
		
		return true;
		
		// switch($type){
		// 	case 'db':
		// 		$thisLogFile = 'db.csv';
		// 		break;
		// 	case 'error':
		// 		$thisLogFile = 'errors.csv';
		// 		break;
		// 	default:
		// 		$thisLogFile = 'errors.csv';
				
		// }
		
		// $timestamp = date('Y-m-d H:i:s');
		
		// $message = str_replace('"','""',$message); //because this string will be encapsulated in double quotes, the double quotes contained must be doubled as per the CSV format to ensure that each log entry only takes up 1 line.
		
		// $logText = "\"{$timestamp}\",\"{$_SERVER['REMOTE_ADDR']}\",\"{$operation}\",\"{$message}\"\r\n";
		
		// if( !isset($this->loggedMessages[$thisLogFile]) ){
		// 	$this->loggedMessages[$thisLogFile] = "";
		// }
		
		// $this->loggedMessages[$thisLogFile] .= $logText;
		
		
	}
	
	function writeLogs(){
	
		// foreach( $this->loggedMessages as $logFile=>$logText){
		
		// 	if( file_exists($this->logFolder.$logFile) && !is_writable( $this->logFolder.$logFile ) ){
		// 		die("\r\nFATAL ERROR during logging.<BR>\r\nLog file:".$this->logFolder.$logFile."<BR>\r\nThis file is not writable!\r\n");
		// 	}
		
		// 	$fp = fopen($this->logFolder.$logFile,'a');
		// 	if( !$fp){
		// 		die("\r\nFATAL ERROR during logging.<BR>\r\nLog file:".$this->logFolder.$logFile."<BR>\r\nThis file could not be opened!\r\n");
		// 	}
			
		// 	fwrite($fp,$logText) or die("\r\nFATAL ERROR during logging.<BR>\r\nApp Name:".$appName."<BR>\r\nLog file:".$this->logFolder.$logFile."<BR>\r\n Could not write to file!\r\n");
		// 	fclose($fp);
			
		// }
	}
}

?>