<?php

class FoxProRunReport {
	public $outputFile;

	public $foxProReturnCodes = array(
		1 => 'Unknown Error Executing Foxpro Report'
		,100=>'Missing Required Command Line Arguments'
		,200=>'Internal FoxPro Error'
		,300=>'No Printers Installed'
		,301=>'The Printer Specified Does Not Exist on This Computer'
		,400=>'DBC Database Connection File Does Not Exist'
		,401=>'Error Connecting to Database With Specified DBC File'
		,402=>'Error Executing SQL to Fetch Data'
		,500=>'FoxPro Report File Does Not Exist'
		,510=>'Error while excuting foxpro report!'
		,600=>'PDF Output File Not Generated'
	);

	function __construct($configs){
	
		$this->currentError = 0;
		$this->currentErrorDesc = '';
	
		$this->statistics = array(
			'reportTime' => FALSE
			,'cleanup' => FALSE
			,'fileWaitTime' => FALSE
		);
		
		$configDefaults = array(
			'printerName' => 'PSFILE'
			,'PDFPrinterOutputLocation' => $_SERVER['DOCUMENT_ROOT'] .'/pdfOutput/'
			,'PDFPrinterOutputExtension' => '.pdf'
			,'cleanupTempTable' => TRUE
			,'data'=> FALSE
			,'reportRunnerExeLocation' => 'c:/server/FoxProApps/RunReport/RunReport.exe'
			,'PDFPrinterTimeout' => 90 // seconds
			,'PDFFileCheckDelay' => 200 // ms
			,'DBCLocation' => 'connection.dbc'
			,'templateFolder' => ''
			,'printJobName' => ''
			,'outputFileName' => ''
		);
		
		$configsRequired = array(
			'mssqlHelperInstance' => TRUE // Database connection object,  this will be used to perform the table cleanup after the report runs
			,'DBCLocation' => FALSE // .DBC Location (FoxPro Database Connection Setup File)
			,'template' => TRUE // .FRX Location
			,'templateFolder' => FALSE // .FRX Location
			//,'tableName' => TRUE // Name of table to get data from
			//,'data' => FALSE
			,'printerName' => FALSE // Optional printer name for direct printer output
			,'PDFPrinterOutputLocation' => FALSE
			,'PDFPrinterOutputExtension' => FALSE 
			,'cleanupTempTable' => FALSE // Delete table after report runs?
			,'reportRunnerExeLocation' => FALSE // Location of FoxPro Command Line Report Runner EXE
			,'PDFPrinterTimeout' => FALSE // If printing a PDF, total time to wait for PDF Printer to generate the PDF before assuming failure
			,'PDFFileCheckDelay' => FALSE // Frequency to check for PDF File generation
			,'printJobName' => FALSE //friendly name send to the printer and used for the pdf file name
			,'outputFileName' => FALSE //friendly name sent to the client upon download
		);
		
		foreach($configsRequired as $configName=>$required){
			if(isset($configs[$configName]) ){
				$this->$configName = $configs[$configName];
			}else{
				if($required){
					throw new AgileUserMessageException("Config {$configName} is required, but not set!");
				}else{
					$this->$configName = $configDefaults[$configName];
				}
			}
		}
		
		if(!isset($configs['tableName']) && !isset($configs['data']) ){
			throw new AgileUserMessageException("Need tableName or data parameter!");
		}
		if(isset($configs['tableName'])){
			$this->tableName = $configs['tableName'];
		}
		if(isset($configs['data'])){
			$this->data = $configs['data'];
		}

		$path_parts = pathinfo($this->reportRunnerExeLocation);
		$this->reportRunnerExe = $path_parts['basename'];
		$this->reportRunnerExeDir = $path_parts['dirname'];
		$this->pdfOutputDir = $this->reportRunnerExeDir.'/output/';
	}

// ===================================

	function cleanup(){
		if($this->cleanupTempTable){
			$startTS = microtime(true);
			
			$sql = "drop table {$this->tableName}";
			$this->mssqlHelperInstance->query($sql);
			
			$this->statistics['cleanupTime'] = microtime(true) - $startTS;
		}
	}

	//Takes a 2D ARRAY and turns it into table for you :)
	 function buildTempTableFromData($uniqueId){
		//require_once($_SERVER['DOCUMENT_ROOT'] ."/inc/php/classes/sql_safe.php" );
		//Grab first row
		 if(!isset($this->data[0])){
		 	throw new Exception("Bad Data Format. Foxpro report needs an array of arrays");
		 }
		$firstRow = $this->data[0];
		$columns = array_keys($firstRow);

		//See the excel sheet in project files for an explanation of this logic.
		$paramLimit = 1500; //sqlsrv max escaped params is 2100. driver will throw an error if you exceed 2100.
		$rowCount = count($this->data);
		$columnCount = count($this->data[0]);
		if($columnCount > $paramLimit){
			throw new Exception("Cannot insert more columns than the parameter limit! Column Count = {$columnCount}. Parameter Limit = {$paramLimit}.");
		}
		$paramTotal = $columnCount*$rowCount;
		$groupsNeeded = ceil($paramTotal/$paramLimit);
		$chunkSize = floor($rowCount/$groupsNeeded);
		$chunks  = array_chunk($this->data,$chunkSize);

		$tableName = "tmp_foxpro_autogen_".$uniqueId;

		$columnSqlArray = array();
		foreach ($columns as $column) {
			$columnSqlArray[] = "[{$column}] [varchar](MAX) NULL";
		}
		$tableNameQuery = "FWE_DEV..[{$tableName}]";
		$createQuery = "CREATE TABLE {$tableNameQuery} (" . implode(",", $columnSqlArray) . ")";
		$this->mssqlHelperInstance->query($createQuery);

		$dbClass = get_class($this->mssqlHelperInstance);
		if ($dbClass == 'mssql_helper') {
			require_once($_SERVER['DOCUMENT_ROOT'] . '/inc/php/classes/sqlsrv_helper.php');
			$insertInstance = new sqlsrv_helper('m2m');
		} else {
			$insertInstance = $this->mssqlHelperInstance;
		}

		foreach($chunks as $chunk) {

			$insertSqlArray = array();
			$queryValues = array();
			foreach ($chunk as $row) {
				$values = array_values($row);
				$rowArray = array();
				foreach ($values as $value) {
					$rowArray[] = "?";
					$queryValues[] = (string)$value;
				}
				$insertSqlArray[] = "(" . implode(",", $rowArray) . ")";
			}
			$insertQuery = "INSERT INTO {$tableNameQuery} (" . implode(",", $columns) . ") VALUES " . implode(",", $insertSqlArray) . "";

			$insertInstance->query($insertQuery, $queryValues);
		}
		$this->tableName = $tableNameQuery;
	}

	function runReport(){
	
		$startTS = microtime(true);

		$uniqueId = str_replace('.','',uniqid('',true));

		if(isset($this->data)){
			if(count($this->data) < 1){
				throw new Exception('Attempting to run a report with no data!');
			}
			$this->buildTempTableFromData($uniqueId);
		}

		if($this->printJobName !== ''){
			$this->printJobNameUnique = $this->printJobName."_".$uniqueId;
		}else{
			$this->printJobNameUnique = str_replace("..","_",$this->tableName);
		}

		//Check php version. Apparently older ones need an extra pair of quotes to run this command.
		$phpversion = explode('.', phpversion());
		$phpversion = $phpversion[0] * 10000 + $phpversion[1] * 100 + $phpversion[2];
		//Less than 5.3
		if($phpversion < 50300){
			$extraQuotes ='"';
		}else{
			$extraQuotes ='';
		}
		$foxProCommand = $extraQuotes.escapeshellarg($this->reportRunnerExe).' '.escapeshellarg($this->DBCLocation).' '.escapeshellarg($this->tableName).' '.escapeshellarg($this->templateFolder . $this->template).' '.escapeshellarg($this->printerName).' '.escapeshellarg($this->printJobNameUnique).$extraQuotes;

		$this->output = '';
		$this->return_var = 1;
		
		//echo "chdir: {$path_parts['dirname']}";
		//echo "Executing command:<BR>{$foxProCommand}<BR>";
		//$preCmdCwd = getcwd(); //capture current working directory

		$ErrorFilePath = $this->pdfOutputDir.$this->printJobNameUnique.'.error';
		chdir($this->reportRunnerExeDir);

		//die($foxProCommand);
		exec($foxProCommand, $this->output, $this->return_var);
		//chdir($preCmdCwd); //set back to working directory

		if($this->return_var !== 0){

			if(file_exists($ErrorFilePath)){
				if(FALSE !== $errorFileText = @file_get_contents($ErrorFilePath) ){
					unlink($ErrorFilePath);
					$errorText = ' '.$errorFileText;
				}
			}else{
				if( isset($this->foxProReturnCodes[$this->return_var]) ){ 
					$errorText = ' '.$this->foxProReturnCodes[$this->return_var];
					//if($this->return_var > 100){
					//}
				}
			}
			$this->statistics['reportTime'] = microtime(true) - $startTS;
			$this->cleanUp();
			
			$this->currentError = $this->return_var;
			$this->currentErrorDesc = $errorText;

			throw new Exception('Error # '.$this->currentError .' - '.$this->currentErrorDesc);
		}
		
		$this->statistics['reportTime'] = microtime(true) - $startTS;
		$this->cleanUp();
		
		return true;
	}

// ===================================

	function findOutputFile(){
		
		$startTS = microtime(true);
		
		$startFileSearchTime = time();
		$fileFound = false;
		$errorFileFound = false;
		
		ini_set('max_execution_time',$this->PDFPrinterTimeout + 5);
		
		//echo "Checking if file exists!";
		//echo "Current timestamp: ".(time() - $currentTime)."<BR>";
		
		$this->outputFile = $this->pdfOutputDir . $this->printJobNameUnique . $this->PDFPrinterOutputExtension;
		
		$this->errorFile = $this->pdfOutputDir . $this->printJobNameUnique.'.error';


		do{
			//echo "Current timestamp: ".(time() - $currentTime)."<BR>";

			//echo "<!-- Looking for '{$this->outputFile}' -->";
			//echo "Looking for '{$this->outputFile}'...<BR>";
			//usleep(100000);

			clearstatcache(true, $this->outputFile);
			if(file_exists($this->outputFile) ){
				$fileFound = true;
				break;
			}
			clearstatcache(true, $this->errorFile);
			if(file_exists($this->errorFile) ){
				$errorFileFound = true;
				break;
			}
			usleep($this->PDFFileCheckDelay * 1000); //sleep for some ms
			
			//echo "<!-- waiting... -->\r\n"; //prevent fast cgi from dying
		}while((time() - $startFileSearchTime) < $this->PDFPrinterTimeout);

		//usleep(250000); //wait 250 ms for ghostscript to finish

		//wait once more to ensure file contents exist
		//usleep($this->PDFFileCheckDelay * 1000); //sleep for some ms
		
		//$this->statistics['fileWaitTime'] = microtime(true) - $startTS;
		
		if($errorFileFound){
			$this->currentError = 510;
			$this->currentErrorDesc = $this->foxProReturnCodes[$this->currentError];
			if(FALSE !== $errorFileContents = @file_get_contents($this->errorFile)){
				
				$this->currentErrorDesc .= "<BR>\r\n<PRE>". $errorFileContents .'</PRE>';
				unlink($this->errorFile);
				
			}
			return false;
		}
		if( $fileFound ){
			return true;
		}else{
			$this->currentError = 600;
			$this->currentErrorDesc = $this->foxProReturnCodes[$this->currentError];
			return false;
		}
	}

// ===================================

	function getStatistics(){
		return $this->statistics;
		/*
			array(
				'reportTime' => 
				,'cleanupTime' => 
				,'fileWaitTime' => 
			)
		*/
	}

// ===================================

	function getCurrentError(){
		return $this->currentError;
	}

// ===================================

	function getCurrentErrorDesc(){
		return $this->currentErrorDesc;
	}

// ===================================

	function printReport(){
		return $this->runReport();
	}

// ===================================

	function savePDF(){
		if(FALSE === $this->runReport() ){
			return FALSE;
		}
		if(FALSE === $this->findOutputFile() ){
			return FALSE;
		}
		return $this->outputFile;
	}

// ===================================

	function outputPDF(){
		if(FALSE === $this->runReport() ){
			return false;
		}
		if(FALSE === $this->findOutputFile() ){
			return false;
		}

		$this->outputDeletePdf();

	}
	function outputDeletePdf()
	{
		if ($this->outputFileName == '') {
			$fileName = "Report PDF";
		} else {
			$fileName = $this->outputFileName;
		}
		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="' . $fileName . '.pdf"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($this->outputFile));

		readfile($this->outputFile);

		unlink($this->outputFile);
		exit;
	}

	function outputEmbeddedPDF(){

		$resultFile = $this->savePDF();

		if(FALSE == $resultFile ){
		  return FALSE;
		}

		$resultFilePathInfo = pathinfo($resultFile);

		rename($resultFile, $_SERVER['DOCUMENT_ROOT'].'/PdfOutput/'.$resultFilePathInfo['basename']);

		if(isset($this->printJobName)){
			$fileName = $this->printJobName;
		}else{
			$fileName = "Report PDF";
		}
		$fileName = urlencode($fileName);

		echo <<<SCRIPT
<html>
<head>
</head>
<body>
<style type="text/css">
body {
	margin:0;
	padding:0;
}
.embedpdf-container {
	margin: 0px 10px 0px 0px;
	width: 100%;
	height: 100%;
}
.embedpdf {
	width: 100%;
	height: 100%;
}
</style>
<div class="embedpdf-container">
<object class="embedpdf" data="/PdfOutput/getThenDeleteFile?file={$resultFilePathInfo['basename']}&fileName={$fileName}#toolbar=1&statusbar=1" type="application/pdf">
  <p>It appears you do not have a PDF reader for your web browser.<BR>
  <a href="/PdfOutput/getThenDeleteFile?file={$resultFilePathInfo['basename']}.pdf&fileName={$fileName}">Click here to download the PDF file.</a></p>
</object>
</div>
</body>
</html>
SCRIPT;

		$stats = $this->getStatistics();
		echo "\r\n<!-- \r\n Stats:\r\n ";
		print_r($stats);
		echo "\r\n--> \r\n";

		return TRUE;
	}

}

?>