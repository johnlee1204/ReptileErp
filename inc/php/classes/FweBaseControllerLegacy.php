<?php
//class_alias('AgileBaseController','FweBaseController');
//class_alias('AgileUserMessageException','FweUserMessageException');

/**
 * If you need to display an error to the user then throw one of these exceptions.
 * If you need the output to be in JSON format, set the controller 'jsonErrorOutput'
 *  value to true.
 * @deprecated USE AgileUserMessageException instead
 */
class FweUserMessageException extends Exception {
	public $fweExceptionData;
}

/**
 * Extend your apps class with this one in order to recieve the benefits of:
 * #Automatic error logging to the filesystem
 * #Convieniently wrapped output into a json format
 * #Shutdown handlers
 * #Developer only error messages sent to the client
 *
 * @deprecated USE AgileBaseController instead
 */
class FweBaseController {
	public $jsonErrorOutput = false;
	/*
	 * Called automatically from the router
	 */
	function initController(){

		ini_set('display_errors',0);
		error_reporting(0);

		set_exception_handler(array($this, 'handleException'));
		set_error_handler(array($this,'handleError'), E_ALL);

		register_shutdown_function(array($this,'handleShutdownError'));

	}

	function outputSuccess($data=false){
		if($data === false){
			$this->outputJson(array('success'=>true));
		}else{
			$this->outputJson(array_merge(array('success'=>true),$data));
		}
	}

	/**
	 * @deprecated Use outputSuccess() instead
	 */
	function outputSuccessDataJson($data){
		$this->outputJson(array('success'=>true,'data'=>$data));
	}

	function outputErrorJson($error){
		$this->outputJson(array('success'=>false,'error'=>$error));
	}

	function utf8_encode_all($data){
		if (is_string($data)){
			if(mb_detect_encoding($data, "UTF-8", true) === "UTF-8"){
				return $data;
			}
			return utf8_encode($data);
		}
		if (!is_array($data)){
			return $data;
		}
		$returnData = array();
		foreach($data as $dataKey=>$dataValue){
			$returnData[$dataKey] = $this->utf8_encode_all($dataValue);
		}
		return $returnData;
	}

	function outputJson($output){
		header('Content-Type: application/json; charset=utf-8');
		$this->output = $output;
		$this->outputJson = json_encode($this->utf8_encode_all($output));
		echo $this->outputJson;
	}

	function handleException(Exception $exception){

		$this->writeExceptionToLog($exception);

		if(get_class($exception) === "FweUserMessageException"){
			$errorDetail = $exception->getMessage();
		}elseif(getenv('PHPENV')==="DEV"){

			$errorDetail = "Fatal Exception!<BR>\r\n" .
				$exception->getMessage() .
				 "<BR>\r\n" .
				"File: " . $exception->getFile() .
				 "<BR>\r\n" .
				 "Line: " . $exception->getLine() .
				 "<BR>\r\n" .
				 "<PRE>" . print_r($exception->getTrace(), true) . "</PRE>";
		}else{
			$errorDetail = "Please contact support.";
		}

		$this->outputErrorMessage(array(
			'message' => "Error!",
			'details' => $errorDetail
		));

		exit;

	}

	function handleError($errorNo, $errorMessage, $errorFile, $errorLine, $shutdownError=false) {

		/*
		http://www.php.net/manual/en/function.set-error-handler.php

		errno
			The first parameter, errno, contains the level of the error raised, as an integer.
		errstr
			The second parameter, errstr, contains the error message, as a string.
		errfile
			The third parameter is optional, errfile, which contains the filename that the error was raised in, as a string.
		errline
			The fourth parameter is optional, errline, which contains the line number the error was raised at, as an integer.
		*/
		$errorNumberDescription = $this->getErrorDescriptionFromNumber($errorNo);
		$formattedError = "PHP Error: #{$errorNo} ".$errorNumberDescription."<BR>\r\nMessage: {$errorMessage}<BR>\r\nLine: {$errorLine}<BR>\r\nFile: {$errorFile}";

		if(getenv('PHPENV')==="DEV"){
			$errorDetail = $formattedError;
		}else{
			$errorDetail = "Please contact support.";
		}

		//FYI - you might say to yourself. I can just throw an exception here. NOPE!
		//If this is a "shutdown" error (triggered by E_ERROR conditions) then the exception will not fire!

		if($shutdownError === true){
			$this->outputErrorMessage(array(
				'message' => "Error!",
				'details' => $errorDetail
			));

			$this->writeErrorLog(array(
				'errorNo'		=> $errorNo,
				'errorNoDesc'	=> $errorNumberDescription,
				'errorMessage'	=> $errorMessage,
				'errorFile'		=> $errorFile,
				'errorLine'		=> $errorLine
			));

		}else{
			throw new Exception($formattedError);
		}
	}

	function handleShutdownError(){
		if(NULL === $error = error_get_last()){
			return true; // no error. don't do anything!
		}

		$error['message'] = "Shutdown Error! " . $error['message'];

		$this->handleError($error['type'], $error['message'], $error['file'], $error['line'], true);

	}

	function outputErrorMessage($errorDetails){
		header($_SERVER["SERVER_PROTOCOL"]." 500");
		if($this->jsonErrorOutput || ( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ){
			echo json_encode(array(
				'success' => false
				,'error' => $errorDetails['message'] ."<BR>\r\n". $errorDetails['details']
			));
		}else{
			echo "<h1><BR>{$errorDetails['message']}</h1>";
			echo "{$errorDetails['details']}";
		}

		return true;
	}

	function getErrorDescriptionFromNumber($errorNo){

		$errors = array();
		if($errorNo & E_ERROR) // 1 //
			$errors[] = 'E_ERROR';
		if($errorNo & E_WARNING) // 2 //
			$errors[] = 'E_WARNING';
		if($errorNo & E_PARSE) // 4 //
			$errors[] = 'E_PARSE';
		if($errorNo & E_NOTICE) // 8 //
			$errors[] = 'E_NOTICE';
		if($errorNo & E_CORE_ERROR) // 16 //
			$errors[] = 'E_CORE_ERROR';
		if($errorNo & E_CORE_WARNING) // 32 //
			$errors[] = 'E_CORE_WARNING';
		if($errorNo & E_COMPILE_ERROR) // 64 //
			$errors[] = 'E_COMPILE_ERROR';
		if($errorNo & E_COMPILE_WARNING) // 128 //
			$errors[] = 'E_COMPILE_WARNING';
		if($errorNo & E_USER_ERROR) // 256 //
			$errors[] = 'E_USER_ERROR';
		if($errorNo & E_USER_WARNING) // 512 //
			$errors[] = 'E_USER_WARNING';
		if($errorNo & E_USER_NOTICE) // 1024 //
			$errors[] = 'E_USER_NOTICE';
		if($errorNo & E_STRICT) // 2048 //
			$errors[] = 'E_STRICT';
		if($errorNo & E_RECOVERABLE_ERROR) // 4096 //
			$errors[] = 'E_RECOVERABLE_ERROR';
// PHP 5.26 fixes
//		if($errorNo & E_DEPRECATED) // 8192 //
//			$errors[] = 'E_DEPRECATED';
//		if($errorNo & E_USER_DEPRECATED) // 16384 //
//			$errors[] = 'E_USER_DEPRECATED';
		return join($errors,' & ');
	}

	function checkLogsDir(){

		if(!isset($this->logDir)){
			$classReflect  = new ReflectionClass($this);
			$this->logDir = $_SERVER['DOCUMENT_ROOT'].'/System/Logs/'.$classReflect->getName();
		}

		if(!is_dir($this->logDir)){
			if(false === mkdir($this->logDir, 0777, true)){ //0777 is default perms (ignored on windows), true to make any directories along the way
				return false;
			}
		}
		return true;
	}

	function writeErrorLog($logData){

		if(!$this->checkLogsDir()){
			return false;
		}

		if(FALSE === $file = @fopen($this->logDir.'/errorLog.csv','a')) { //open file for appending
			return false;
		}

		if(isset($_SERVER['HTTP_REFERER'])){
			$referer = $_SERVER['HTTP_REFERER'];
		}else{
			$referer = '';
		}
		if(isset($_SERVER['REMOTE_ADDR'])){
			$addr = $_SERVER['REMOTE_ADDR'];
		}else{
			$addr = 'cmd line';
		}

		@fputcsv($file, array(
			date('Y-m-d H:i:s')
			,$addr
			,print_r(array('POST'=>$_POST),true)
			,print_r(array('GET'=>$_GET),true)
			,print_r(array(
				'uri'=>$_SERVER['REQUEST_URI']
				,'referer'=>$referer
				,'script'=>$_SERVER['PHP_SELF']
				,'method'=>$_SERVER['REQUEST_METHOD']
				,'query'=>$_SERVER['QUERY_STRING']
			),true)
			,print_r($logData ,true)
		));

		return true;
	}

	function writeExceptionToLog($exception){

		if(isset($exception->fweExceptionData)){
			$fweExceptionData = $exception->fweExceptionData;
		}else{
			$fweExceptionData = '';
		}

		$errorData = array(
			'exceptionMessage'=>$exception->getMessage()
			,'fweExceptionData' => $fweExceptionData
			,'exceptionFile'=>$exception->getFile()
			,'exceptionLine'=>$exception->getLine()
			,'exceptionTrace'=>$exception->getTrace()
		);

		$this->writeErrorLog($errorData);

		return true;
	}

}