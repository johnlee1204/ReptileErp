<?php

class AgileErrorHandler
{

	/* @var AgileApp */
	private $AgileApp;

	private $genericErrorStr = 'Please Contact Support.';
	use AgileOutput;

	function __construct($AgileApp){

		//allows out of memory errors to be caught! 40KB
		$this->memoryToFree = str_repeat('*', 1024 * 40);

		$this->AgileApp = $AgileApp;
		$this->AgileApp->jsonErrorOutput = false;
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		set_exception_handler(function($exception){
			$this->handleException($exception);
		});
		set_error_handler(function($errorNo,$errorMessage,$errorFile,$errorLine){
			$this->handleError($errorNo,$errorMessage,$errorFile,$errorLine);
		}, E_ALL);
		register_shutdown_function(function(){
			$this->shutdownHandler();
		});
	}

	/**
	 * @param Exception $exception
	 */
	function handleException($exception){

		if(isset($exception->AgileLine)){
			$exceptionLine = $exception->AgileLine;
		}else{
			$exceptionLine = $exception->getLine();
		}

		if(isset($exception->AgileFile)){
			$exceptionFile = $exception->AgileFile;
		}else{
			$exceptionFile = $exception->getFile();
		}

		$exceptionMessage = $exception->getMessage();
		$exceptionLineNumberMsg = 'Line: '.$exceptionLine;
		$exceptionFileMsg = 'File: '.$exceptionFile;
		$exceptionStackTrace = 'Stack Trace:<br /><pre>'.$exception->getTraceAsString().'</pre>';
		$exceptionExtraData = '';
		if (isset($exception->exceptionData)) {
			$exceptionExtraData = print_r($exception->exceptionData, true);
		}
		$lineBreak = "<br />\r\n";

		$outputMessage = $this->genericErrorStr;
		$outputDetails = "";
		$userMessageException = (get_class($exception) == 'AgileUserMessageException');
		if($userMessageException) {
			$outputMessage = $exceptionMessage;
		} else {
			$outputMessage = $exceptionMessage;
			$outputDetails = $exceptionLineNumberMsg .
				$lineBreak .
				$exceptionFileMsg .
				$lineBreak .
				$exceptionStackTrace .
				$lineBreak .
				$exceptionExtraData;
		}

		if (isset($exception->AgileType)) {
			$errorType = substr($exception->AgileType,0,10);
		} elseif($userMessageException){
			$errorType = 'usermsg';
		}else {
			$errorType = 'exception';
		}

		$this->logError($errorType, $exception->getMessage(), $exceptionLine, $exceptionFile, $exception->getTraceAsString(), $exceptionExtraData);
		$this->outputErrorMessage($outputMessage, $outputDetails);
	}

	function handleError($errorNo, $errorMessage, $errorFile, $errorLine, $isShutdown = false){

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

		$strMessage = "PHP " . ($isShutdown === TRUE ? 'Shutdown Error!' : '') . " " . $this->getErrorDescriptionFromNumber($errorNo) . " Error ({$errorNo})! {$errorMessage}";
		$strLineNumber = "Line: {$errorLine}";
		$strFile = "File: {$errorFile}";
		$lineBreak = "<br />\r\n";

		$outputMessage = $this->genericErrorStr;
		$outputDetails = '';

		//if (getenv('PHPENV') === "DEV" || isset($_GET['AgileErrorDebug'])) {
			$outputMessage = $strMessage;
			$outputDetails = $strLineNumber . $lineBreak . $strFile;
		//}

		if ($isShutdown) {
			//$this->logError('shutdown', $strMessage, $errorLine, $errorFile);
			$this->outputErrorMessage($outputMessage, $outputDetails);
		} else {
			$ex = new Exception($strMessage);
			$ex->AgileType = 'error';
			$ex->AgileLine = $errorLine;
			$ex->AgileFile = $errorFile;
			throw $ex;
		}
	}

	function shutdownHandler(){
		//allows out of memory errors to be caught!
		$this->memoryToFree = null;

		if (NULL === $error = error_get_last()) {
			return;
		}
		$this->handleError($error['type'], $error['message'], $error['file'], $error['line'],true);
	}

	function getErrorDescriptionFromNumber($errorNo){
		$errors = array();
		if ($errorNo & E_ERROR) // 1 //
			$errors[] = 'E_ERROR';
		if ($errorNo & E_WARNING) // 2 //
			$errors[] = 'E_WARNING';
		if ($errorNo & E_PARSE) // 4 //
			$errors[] = 'E_PARSE';
		if ($errorNo & E_NOTICE) // 8 //
			$errors[] = 'E_NOTICE';
		if ($errorNo & E_CORE_ERROR) // 16 //
			$errors[] = 'E_CORE_ERROR';
		if ($errorNo & E_CORE_WARNING) // 32 //
			$errors[] = 'E_CORE_WARNING';
		if ($errorNo & E_COMPILE_ERROR) // 64 //
			$errors[] = 'E_COMPILE_ERROR';
		if ($errorNo & E_COMPILE_WARNING) // 128 //
			$errors[] = 'E_COMPILE_WARNING';
		if ($errorNo & E_USER_ERROR) // 256 //
			$errors[] = 'E_USER_ERROR';
		if ($errorNo & E_USER_WARNING) // 512 //
			$errors[] = 'E_USER_WARNING';
		if ($errorNo & E_USER_NOTICE) // 1024 //
			$errors[] = 'E_USER_NOTICE';
		if ($errorNo & E_STRICT) // 2048 //
			$errors[] = 'E_STRICT';
		if ($errorNo & E_RECOVERABLE_ERROR) // 4096 //
			$errors[] = 'E_RECOVERABLE_ERROR';
// PHP 5.26 fixes
//		if($errorNo & E_DEPRECATED) // 8192 //
//			$errors[] = 'E_DEPRECATED';
//		if($errorNo & E_USER_DEPRECATED) // 16384 //
//			$errors[] = 'E_USER_DEPRECATED';
		return join(' & ', $errors);
	}

	function outputErrorMessage($message, $details){
		if(FALSE === headers_sent() ){
			header($_SERVER["SERVER_PROTOCOL"] . " 500");
		}
		if ($this->AgileApp->jsonErrorOutput || isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			if(strlen($details) > 0){
				$details = "<br />\r\n".$details;
			}
			$this->outputJson(array(
				'success' => false,
				'error' => $message.$details
			));
			return;
		}
		$msg = "<h1>Fatal Error!<br />\r\n{$message}</h1>\r\n";
		$msg .= "{$details}";
		echo $msg;
	}

	function logError($errorType,$message, $lineNumber, $file, $stackTrace = '', $extraData = ''){

		$standardLogData = $this->AgileApp->readStandardLogColumnValues();
		$inputData = array_merge($standardLogData, array(
			'errorMessage' => $message,
			'errorLineNumber' => $lineNumber,
			'errorFile' => $file,
			'errorStackTrace' => $stackTrace,
			'errorExtraData' => $extraData,
			'errorType' => $errorType
		));

		if(isset($this->AgileApp->systemConfigs['exceptionEmailErrorTypes']) && in_array($errorType,$this->AgileApp->systemConfigs['exceptionEmailErrorTypes'])) {
			$to = $this->AgileApp->systemConfigs['adminEmail'];
			$subject = 'Error from '.$_SERVER["SERVER_NAME"];

			$headers = array();
			$headers[] = "From: Error Handler<noreply@noreply.local>";
			$headers[] = "MIME-Version: 1.0";
			$headers[] = "Content-Type: text/html; charset=ASCII";

			$message = array();
			$message[] = "<b>Error:</b> ".$inputData['errorMessage']."<br />";
			$message[] = "<b>Line:</b> ".$inputData['errorLineNumber']."<br />";
			$message[] = "<b>File:</b> ".$inputData['errorFile']."<br />";
			if($inputData['errorStackTrace'] !== '') {
				$message[] = "<b>Stack Trace:</b><br /><pre>" . $inputData['errorStackTrace'] . "</pre><br />";
			}
			$message[] = "<b>Details:</b><br />";
			$message[] = "<pre>";
			$message[] = print_r($inputData, true);
			$message[] = "</pre>";

			//@mail($to, $subject, implode("\r\n", $message), implode("\r\n",$headers));
		}

		try{
			$this->AgileApp->systemDb->insert($this->AgileApp->systemConfigs['table']['logException'] , $inputData);
		}catch(Exception $ex){
			echo "<HR>\r\nFailed to log exception. Your database config is bad.\r\n<HR>\r\n";
			echo $ex->getMessage(),"\r\n<HR>\r\n";
		}
	}
}
