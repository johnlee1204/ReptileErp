<?php

require_once("C:/server/data/apacheData/dev.local/inc/php/classes/sqlsrv_helper.php");
require_once("C:/server/data/apacheData/dev.local/inc/php/models/JobCreateModel.php");

class JobCreateModel_insertTest extends PHPUnit_Framework_TestCase {
	/* @var sqlsrv_helper */
	protected static $testingDatabase ;
	protected static $sqlSrv;
	protected static $dbc;
	protected static $tableName = "FWE_DEV..unitTestSqlSrvHelper";
	protected static $dataFilesLocation = "C:\\server\\Data\\ApacheData\\dev.local\\inc\\php\\phpunit-tests\\JobCreateModel\\";
	protected static $testData = array();


	public static function setUpBeforeClass(){
		echo "setUpBeforeClass\r\n";

    	self::restoreDB();

    	$dataFileNames = array_diff(scandir(self::$dataFilesLocation), array('..', '.'));

    	$dataFile = $rawData = $fileInfo = null;
    	foreach($dataFileNames as $dataFileName){
    		$fileInfo = pathinfo($dataFileName);
    		if($fileInfo['extension'] === 'json'){
    			$dataFile = fopen(self::$dataFilesLocation.$dataFileName, 'r');
    			$rawData = fread($dataFile, filesize(self::$dataFilesLocation.$dataFileName));
    			self::$testData[$fileInfo['filename']] = json_decode($rawData, true);
				$newJob = JobCreateModel::getNextJobNumberData(self::$sqlSrv, true);
    			self::formatTestData(self::$testData[$fileInfo['filename']], $newJob);
    		}
    	}
	}

	public static function restoreDB(){
		echo "\r\nrestoreDB\r\n";

		self::$sqlSrv = new sqlsrv_helper('m2m');
    	sqlsrv_configure("WarningsReturnAsErrors", 0);
    	self::$testingDatabase = 'm2mdata01_testing';

		require("C:/server/data/apacheData/dev.local/inc/php/models/credentials/m2m.php");

		$localDatabaseBackupPath = 'C:\\server\\Data\\DatabaseBackups\\';

		exec('sqlcmd -b -S "localhost" -U "'.$user.'" -P "'.$password.'" -Q "alter database '.self::$testingDatabase.' set offline with rollback immediate "', $output, $exitCode);
   		print_r($output, true);

		unset($output);
		unset($exitCode);

		exec('sqlcmd -b -S "localhost" -U "'.$user.'" -P "'.$password.'" -Q "alter database '.self::$testingDatabase.' set online with rollback immediate "', $output, $exitCode);
   		print_r($output, true);

    	$restoreQuery = "RESTORE DATABASE ".self::$testingDatabase." FROM DISK='{$localDatabaseBackupPath}".self::$testingDatabase.".bak' WITH RECOVERY, REPLACE,
		MOVE 'm2mdata01' TO 'C:\\server\\data\\sqlServerData\\MSSQL10_50.MSSQLSERVER\\MSSQL\\DATA\\".self::$testingDatabase.".mdf',
		MOVE 'm2mdata01_log' TO 'C:\\server\\data\\sqlServerData\\MSSQL10_50.MSSQLSERVER\\MSSQL\\DATA\\".self::$testingDatabase."_Log.ldf'";

		self::$sqlSrv->query($restoreQuery);

		//sqlsrv_query(self::$dbc, "RESTORE DATABASE ".self::$testingDatabase." WITH RECOVERY");
		self::$sqlSrv->query("USE ".self::$testingDatabase);
	}

	protected static function formatTestData(&$testData, $newJob){
		foreach($testData as $key => &$value){
			if(is_array($value)){
				self::formatTestData($value, $newJob);
			} else {
				switch($key){
					case 'newJob':
					case 'parentJob':
					case 'fjobno':
					case 'fsub_job':
					case 'fsub_from':
						if($value !== ''){
							$parentJob = substr($newJob,0,5);
							$subJobNumber = substr($value,6,10);
							$value = $parentJob.'-'.$subJobNumber;
						}
					break;
					case 'fopen_dt':
					case 'fhold_dt':
					case 'factschdst':
					case 'fstrtdate':
					case 'frel_dt':
					case 'fddue_date':
					case 'fact_rel':
						$value = date('Y-m-d')." 00:00:00.000";
					break;
					case 'FDPLANSTDT':
					case 'reWorkDate':
						$value = "1900-01-01 00:00:00.000";
					break;
					case 'fstatus':
						$value = "RELEASED";
					break;
				}
			}
		}
	}

	protected static function trimArray($value){
		if(!is_array($value)){
			return trim($value);
		}

		return array_map(array('JobCreateModel_insertTest','trimArray'), $value);
	}

	function testGetNextJobNumberData(){
		echo "\r\ntestGetNextJobNumberData\r\n";

		foreach(self::$testData as $partFile){
			foreach($partFile as $test => $data){
				if($test === 'jomast'){
					foreach($data as $job => $subData){
						if($subData['internal'] == 'I'){
							$identityTable = 'JOMAST.FJOBNOI';
							$prefix = 'I';
							$jobNumber = JobCreateModel::getNextJobNumberData(self::$sqlSrv, true);
						}else{
							$identityTable = 'JOMAST.FJOBNO';
							$prefix = '';
							$jobNumber = JobCreateModel::getNextJobNumberData(self::$sqlSrv, false);
						}

						$nextJob = self::$sqlSrv->fetch_assoc(
							"SELECT rtrim(fcnumber) num FROM SySequ where fcClass = ?",array($identityTable))['num'];

						$this->assertEquals($prefix.$nextJob.'-0000',$jobNumber);
					}
				}
			}
		}
	}

	function testGetPartDetails(){
		echo "\r\ntestGetPartDetails\r\n";

		foreach(self::$testData as $partFile){
			foreach($partFile as $test => $data){
				if($test === 'partData'){
					foreach($data as $job => $subData){
						$output = JobCreateModel::getPartDetails(
							self::$sqlSrv,
							$subData['part'],
							$subData['partRev']
						);

						$expectedData = array(
						  'part'				=>	$subData['part'],
						  'partRev'				=>	$subData['partRev'],
						  'partDescription' 	=>	$subData['partDescription'],
						  'unitOfMeasure'		=>	$subData['unitOfMeasure'],
						  'groupCode'			=>	$subData['groupCode'],
						  'productClass'		=>	$subData['productClass'],
						  'lotControlRequired'	=>	$subData['lotControlRequired'],
						  'lotControlExtent'	=>	$subData['lotControlExtent'],
						  'source'				=>	$subData['source'],
						  'standardMemo'		=>	$subData['standardMemo'],
						  'cadFile1'			=>	$subData['cadFile1'],
						  'cadFile2'			=>	$subData['cadFile2'],
						  'cadFile3'			=>	$subData['cadFile3'],
						 // 'FSTDCOST'			=>	$subData['FSTDCOST'],
						  'identity_column'		=>	$subData['identity_column']
						);

						unset($output['FSTDCOST']);

						$output = self::trimArray($output);

						$this->assertEquals($expectedData, $output);
					}
				}
			}
		}
	}

	function testGetJobPartDetailsById(){
		echo "\r\ntestGetJobPartDetailsById\r\n";

		foreach(self::$testData as $partFile){
			foreach($partFile as $test => $data){
				if($test === 'partData'){
					foreach($data as $job => $subData){
						$output = JobCreateModel::getJobPartDetailsById(
							self::$sqlSrv,
							$subData['identity_column']
						);

						$expected = array(
							'part' 				=> $subData['part'],
							'partRev' 			=> $subData['partRev'],
							'partDescription' 	=> $subData['partDescription'],
							'unitOfMeasure' 	=> $subData['unitOfMeasure'],
							'groupCode' 		=> $subData['groupCode'],
							'productClass' 		=> $subData['productClass'],
							'lotControlRequired'=> $subData['lotControlRequired'],
							'lotControlExtent'  => $subData['lotControlExtent'],
							'source' 			=> $subData['source'],
							'standardMemo' 		=> $subData['standardMemo'],
							'cadFile1' 			=> $subData['cadFile1'],
							'cadFile2' 			=> $subData['cadFile2'],
							'cadFile3' 			=> $subData['cadFile3'],
							//'FSTDCOST' 			=> $subData['FSTDCOST'],
							'identity_column' 	=> $subData['identity_column']
						);

						unset($output['FSTDCOST']);

						$output = self::trimArray($output);

						$this->assertEquals($expected, $output);
					}
				}
			}
		}
	}

	function testGetRoutingProductionHours(){
		echo "\r\ntestGetRoutingProductionHours\r\n";

		foreach(self::$testData as $partFile){
			foreach($partFile as $test => $data){
				if($test === 'partData'){
					foreach($data as $job => $subData){
						$output = JobCreateModel::getRoutingProductionHours(
							self::$sqlSrv,
							$subData['part'],
							$subData['partRev'],
							1
						);

						$expected = $subData['prodHrs'];

						$this->assertEquals(round($expected,2), round($output,2));
					}
				}
			}
		}
	}

	function testInsertJomastRecord(){
		echo "\r\ntestInsertJomastRecord\r\n";

		foreach(self::$testData as $partFile){
			foreach($partFile as $test => $data){
				if($test === 'jomast'){
					foreach($data as $job => $subData){

						if($subData['internal'] === 'I'){
							$internal = true;
						} else{
							$internal = false;
						}

						$insertJobData = array(
						 	'bomINum' 				 => $subData['bomINum'],
						 	'deliverToWorkCenter'	 => $subData['deliverToWorkCenter'],
						 	'dueDate'				 => $subData['fddue_date'],
						 	'internal' 				 => $internal,
						 	'jobCreatorEmployeeId' 	 => $subData['jobCreatorEmployeeId'],
						 	'jobType'				 => $subData['jobType'],
						 	'lock'				 	 => $subData['lock'],
						 	'newJob' 				 => $subData['newJob'],
						 	'parentJob' 			 => $subData['parentJob'],
						 	'reWorkDate'			 => $subData['reWorkDate'],
						 	'reWorkHot'				 => $subData['reWorkHot'],
						 	'reWorkInspCode'		 => $subData['reWorkInspCode'],
						 	'reWorkReason'			 => $subData['reWorkReason'],
						 	'reWorkReasonEmployeeId' => $subData['reWorkReasonEmployeeId'],
						 	'routingProductionHours' => $subData['routingProductionHours'],
						 	'salesOrder' 			 => $subData['salesOrder'],
						 	'salesOrderCompany' 	 => $subData['salesOrderCompany'],
						 	'salesOrderCustomerNo' 	 => $subData['salesOrderCustomerNo'],
						 	'salesOrderJobName' 	 => $subData['salesOrderJobName'],
						 	'salesOrderKey' 		 => $subData['salesOrderKey'],
						 	'userChar1' 			 => $subData['userChar1'],
						 	'userChar2'				 => $subData['userChar2'],
						 	'userMemo'				 => $subData['userMemo'],
						);

						$insertPartData = array(
						 	'cadFile1' 	 		 => $partFile['partData'][$job]['cadFile1'],
						 	'cadFile2' 	 		 => $partFile['partData'][$job]['cadFile2'],
						 	'cadFile3' 	 		 => $partFile['partData'][$job]['cadFile3'],
						 	'lotControlExtent' 	 => $partFile['partData'][$job]['lotControlExtent'],
						 	'lotControlRequired' => $partFile['partData'][$job]['lotControlRequired'],
						 	'part' 				 => $partFile['partData'][$job]['part'],
						 	'partRev' 			 => $partFile['partData'][$job]['partRev'],
						 	'productClass' 		 => $partFile['partData'][$job]['productClass'],
						 	'unitOfMeasure' 	 => $partFile['partData'][$job]['unitOfMeasure'],
						 );


						$expectedData = array(
							'bomINum'				=> $subData['bomINum'],
							'cadFile1'				=> $partFile['partData'][$job]['cadFile1'],
							'cadFile2'				=> $partFile['partData'][$job]['cadFile2'],
							'cadFile3'				=> $partFile['partData'][$job]['cadFile3'],
							'deliverToWorkCenter'	=> $subData['deliverToWorkCenter'],
							'fac'					=> $subData['fac'],
							'fclotext'				=> $partFile['partData'][$job]['lotControlExtent'],
							//'fcudrev'				=> $subData['fcudrev'],
							'fddue_date'			=> $subData['fddue_date'],
							'fdesc'					=> $subData['fdesc'],
							'fdet_bom'				=> $subData['fdet_bom'],
							'fdet_rtg'				=> $subData['fdet_rtg'],
							//'fdstart'				=> $subData['fdstart'],
							'fhold_dt'				=> $subData['fhold_dt'],
							'fitems'				=> $subData['fitems'],
							'fitype'				=> $subData['fitype'],
							'fllotreqd'				=> $partFile['partData'][$job]['lotControlRequired'],
							'fmeasure'				=> $partFile['partData'][$job]['unitOfMeasure'],
							//'fmethod'				=> $subData['fmethod'],
							'fopen_dt'				=> $subData['fopen_dt'],
							'fpartno'				=> $subData['fpartno'],
							'fpartrev'				=> $subData['fpartrev'],
							'fpro_plan'				=> $subData['fpro_plan'],
//Product Class Important	'fprodcl'				=> $subData['fprodcl'],
//Are All Sub Jobs
//Product Class 60?
							'fquantity'				=> $subData['fquantity'],
							'frel_dt'				=> $subData['frel_dt'],
							'fschdprior'			=> $subData['fschdprior'],
							//'fstandpart'			=> $subData['fstandpart'],
							'fstatus'				=> $subData['fstatus'],
							'fsub_from'				=> $subData['fsub_from'],
							'fsummary'				=> $subData['fsummary'],
							'internal'				=> $subData['internal'],
							'ISTENN'				=> $subData['ISTENN'],
							'jobCreatorEmployeeId'	=> $subData['jobCreatorEmployeeId'],
							'jobType'				=> $subData['jobType'],
							'lock'					=> $subData['lock'],
							'newJob'				=> $subData['newJob'],
							'parentJob'				=> $subData['parentJob'],
							'reWorkDate'			=> $subData['reWorkDate'],
							'reWorkHot'				=> $subData['reWorkHot'],
							'reWorkInspCode'		=> $subData['reWorkInspCode'],
							'reWorkReason'			=> $subData['reWorkReason'],
							'reWorkReasonEmployeeId'=> $subData['reWorkReasonEmployeeId'],
							'routingProductionHours'=> $subData['routingProductionHours'],
							'salesOrder'			=> $subData['salesOrder'],
							'salesOrderCompany'		=> $subData['salesOrderCompany'],
							'salesOrderCustomerNo'	=> $subData['salesOrderCustomerNo'],
							'salesOrderJobName'		=> $subData['salesOrderJobName'],
							'salesOrderKey'			=> $subData['salesOrderKey'],
							'userChar1'				=> $subData['userChar1'],
							'userChar2'				=> $subData['userChar2'],
							'userMemo'				=> $subData['userMemo'],
						);

						JobCreateModel::insertJomastRecord(
							self::$sqlSrv,
							$insertJobData,
							$insertPartData,
							$subData['fquantity']
						);

						$jomastData = self::$sqlSrv->fetch_assoc(
							"SELECT
							  fjobno newJob,
							  fpartno,
							  fpartrev,
							  fstatus,
							  fopen_dt,
							  fhold_dt,
							  frel_dt,
							  fddue_date,
							  --fdstart,
							  fdesc,
							  --fsub_rel,
							  fdet_bom,
							  fdet_rtg,
							  fitems,
							  fitype,
							  fmeasure,
							  --fmethod,
							  --fhold_by,
							  --fprodcl,
							  fpro_plan,
							  fquantity,
							  frouting routingProductionHours,
							  fschdprior,
							  --fstandpart,
							  fsummary,
							  --ftot_assy,
							  ftype internal,
							  fllotreqd,
							  fclotext,
							  fac,
							  --fcudrev,
							  fcusrchr1 userChar1,
							  fcusrchr2 userChar2,
							  fbominum bomINum,
							  fsono salesOrder,
							  fcompany salesOrderCompany,
							  fcus_id salesOrderCustomerNo,
							  fjob_name salesOrderJobName,
							  fkey salesOrderKey,
							  fschbefjob parentJob,
							  fsub_from,
							  fccadfile1 cadFile1,
							  fccadfile2 cadFile2,
							  fccadfile3 cadFile3,
							  FMUSERMEMO userMemo,
							  reworkDelTo deliverToWorkCenter,
							  frework reWorkReason,
							  --fkey_id,
							  reworkReason reWorkInspCode,
							  Lock lock,
							  ISTENN,
							  hot reWorkHot,
							  jobtype jobType,
							  reworkresp reWorkReasonEmployeeId,
							  reworkby jobCreatorEmployeeId,
							  reworkdate reWorkDate
							FROM jomast
							JOIN jomast_ext on jomast.identity_column = jomast_ext.fkey_id
							WHERE fjobno = ?", array($subData['newJob']));

						$jomastData = self::trimArray($jomastData);

						$this->assertEquals($expectedData, $jomastData);
					}
				}
			}
		}
	}

	function testInsertJoitemRecord(){
		echo "\r\ntestInsertJoitemRecord\r\n";

		foreach(self::$testData as $partFile){
			foreach($partFile as $test => $data){
				if($test === 'joitem'){
					foreach($data as $job => $subData){
						$insertJobData = array(
						 	'newJob' 		=> $subData['fjobno'],
						 	'salesOrder' 	=> $subData['fsono'],
						 	'salesOrderKey'	=> $subData['finumber'].$subData['fkey'],
						);

						$insertPartData = array(
						 	'groupCode' 	  	 => $partFile['partData'][$job]['groupCode'],
						 	'lotControlRequired' => $partFile['partData'][$job]['lotControlRequired'],
						 	'part' 	 		 	 => $partFile['partData'][$job]['part'],
						 	'partDescription' 	 => $partFile['partData'][$job]['partDescription'],
						 	'partRev' 	 		 => $partFile['partData'][$job]['partRev'],
						 	'productClass' 	  	 => $partFile['partData'][$job]['productClass'],
						 	'source' 		  	 => $partFile['partData'][$job]['source'],
						 	'standardMemo' 	  	 => $partFile['partData'][$job]['standardMemo'],
						 	'unitOfMeasure'	  	 => $partFile['partData'][$job]['unitOfMeasure'],
						);

						$expectedData = array(
							'fjobno'	 => $subData['fjobno'],
							'fitem'		 => $subData['fitem'],
							'fpartno'	 => $subData['fpartno'],
							'fpartrev'	 => $subData['fpartrev'],
							//fgroup'	 => $subData['fgroup'],
							'fmeasure'	 => $subData['fmeasure'],
							'fmqty'		 => $subData['fmqty'],
							'forderqty'	 => $subData['forderqty'],
							//'fprodcl'	 => $subData['fprodcl'],
							//'fstandpart' => $subData['fstandpart'],
							'fdesc'		 => $partFile['partData'][$job]['partDescription'],
//Coming From configurator	'fdescmemo'	 => $subData['fdescmemo'],
							'fac'		 => $subData['fac'],
							//'fcudrev'	 => $subData['fcudrev'],
							'fsono'		 => $subData['fsono'],
							'finumber'   => $subData['finumber'],
							'fkey' 		 => $subData['fkey']
						);

						JobCreateModel::insertJoitemRecord(
							self::$sqlSrv,
							$insertJobData,
							$insertPartData,
							$subData['fmqty']
						);

						$joitemData = self::$sqlSrv->fetch_assoc(
							"SELECT fjobno,
							fitem,
							fpartno,
							fpartrev,
							--fgroup,
							fmeasure,
							fmqty,
							forderqty,
							--fprodcl,
							--fsource,
							--fstandpart,
							fdesc,
							--fdescmemo,
							fac,
							--fcudrev,
							fsono,
							finumber,
							fkey
							FROM joitem where fjobno = ?",array($subData['fjobno']));

						$joitemData = self::trimArray($joitemData);

						$this->assertEquals($expectedData,$joitemData);
					}
				}
			}
		}
	}

	function testInsertJopactRecord(){
		echo "\r\ntestInsertJopactRecord\r\n";

		foreach(self::$testData as $partFile){
			foreach($partFile as $test => $data){
				if($test === 'jopact'){
					foreach($data as $job => $subData){
						$testJobNumber = $subData['fjobno'];

						$expectedData = array(
							'fjobno'	 => $subData['fjobno'],
							'faddedocos' => $subData['faddedocos'],
							'faddedpcos' => $subData['faddedpcos'],
							'faddedltim' => $subData['faddedltim'],
							'faddedptim' => $subData['faddedptim'],
							'faddedscos' => $subData['faddedscos'],
							'faddedstim' => $subData['faddedstim'],
							'finumber'	 => $subData['finumber'],
							//'flabact'	 => $subData['flabact'],
							//'flabinv'	 => $subData['flabinv'],
							//'fmatlact'	 => $subData['fmatlact'],
							//'fmatlinv'	 => $subData['fmatlinv'],
							'fothract'	 => $subData['fothract'],
							'fothrinv'	 => $subData['fothrinv'],
							//'fovhdact'	 => $subData['fovhdact'],
							//'fovhdinv'	 => $subData['fovhdinv'],
							'frtgsetupa' => $subData['frtgsetupa'],
							'fsetupact'	 => $subData['fsetupact'],
							'fsubact'	 => $subData['fsubact'],
							'fsubinv'	 => $subData['fsubinv'],
							'ftoolact'	 => $subData['ftoolact'],
							//'ftotltime'	 => $subData['ftotltime'],
							//'ftotptime'	 => $subData['ftotptime'],
							'ftotstime'	 => $subData['ftotstime'],
							'faddedlsti' => $subData['faddedlsti'],
							'ftotlstime' => $subData['ftotlstime'],
							'fpmemo'	 => $subData['fpmemo']
						);

						JobCreateModel::insertJopactRecord(
							self::$sqlSrv,
							$testJobNumber
						);

						$jopactData = self::$sqlSrv->fetch_assoc(
							"SELECT
							 fjobno,
							 faddedocos,
							 faddedpcos,
							 faddedltim,
							 faddedptim,
							 faddedscos,
							 faddedstim,
							 rtrim(finumber) finumber,
							 --flabact,
							 --flabinv,
							 --fmatlact,
							 --fmatlinv,
							 fothract,
							 fothrinv,
							 --fovhdact,
							 --fovhdinv,
							 frtgsetupa,
							 fsetupact,
							 fsubact,
							 fsubinv,
							 ftoolact,
							 --ftotltime,
							 --ftotptime,
							 ftotstime,
							 faddedlsti,
							 ftotlstime,
							 fpmemo
							FROM jopact where fjobno = ?",array($subData['fjobno']));

						$jopactData = self::trimArray($jopactData);

						$this->assertEquals($expectedData,$jopactData);
					}
				}
			}
		}
	}

	function testInsertJobRoutingRecords(){
		echo "\r\ntestInsertJobRoutingRecords\r\n";

		foreach(self::$testData as $partFile){
			foreach($partFile as $test => $data){
				if($test === 'jodrtg'){
					foreach($data as $job => $subArray){
						$expectedData = $insertJobData = array();
						$testPart = $partFile['partData'][$job]['part'];
						$testPartRev = $partFile['partData'][$job]['partRev'];

						$insertJobData = array(
							'newJob'        => $subArray[0]['fjobno'],
							'dueDate'       => date('Y-m-d'),
							'bomINum'       => $subArray[0]['fbominum'],
							'salesOrderKey' => $subArray[0]['finumber'],
							'operationMemo' => $subArray[0]['fopermemo']
						);

						JobCreateModel::insertJobRoutingRecords(
							self::$sqlSrv,
							$insertJobData,
							$testPart,
							$testPartRev,
							1
						);

						foreach($subArray as $subData){
							$expectedData[] = array(
								'foperno' 	 => $subData['foperno'],
								'fchngrates' => $subData['fchngrates'],
								'felpstime'  => $subData['felpstime'],
								'ffixcost' 	 => $subData['ffixcost'],
								'flschedule' => $subData['flschedule'],
								'fstrtdate'  => $subData['fstrtdate'],
								'factschdst' => $subData['factschdst'],
								'fmovetime'  => $subData['fmovetime'],
								'fbominum' 	 => $subData['fbominum' ],
								'finumber' 	 => $subData['finumber'],
								'FNEED_dt' 	 => $subData['FNEED_dt'],
								//'fnqty_togo' => $subData['fnqty_togo'],
								//'foperqty' 	 => $subData['foperqty'],
								'fothrcost'  => $subData['fothrcost'],
								//'fpro_id' 	 => $subData['fpro_id'],
								'fresponse'  => $subData['fresponse'],
								'fsetuptime' => $subData['fsetuptime'],
								'fsource' 	 => $subData['fsource'],
								//'fulabcost'  => $subData['fulabcost'],
								//'fuovrhdcos' => $subData['fuovrhdcos'],
								//'fuprodtime' => $subData['fuprodtime'],
								'fusubcost'  => $subData['fusubcost'],
								'fllotreqd'  => $subData['fllotreqd'],
								'fcschdpct'  => $subData['fcschdpct'],
								//'fnsimulops' => $subData['fnsimulops'],
								'fccharcode' => $subData['fccharcode'],
								// /'fopermemo'  => $subData['fopermemo'],
								'FDPLANSTDT' => $subData['FDPLANSTDT'],
								'fac' 		 => $subData['fac']
							);
						}

						$routingData = self::$sqlSrv->fetch_all_assoc(
							"SELECT
							foperno,
							fchngrates,
							felpstime,
							ffixcost,
							flschedule,
							fstrtdate,
							factschdst,
							fmovetime,
							fbominum,
							finumber,
							FNEED_dt,
							--fnqty_togo,
							--foperqty,
							fothrcost,
							--fpro_id,
							fresponse,
							fsetuptime,
							fsource,
							--fulabcost,
							--fuovrhdcos,
							--fuprodtime,
							fusubcost,
							fllotreqd,
							fcschdpct,
							--fnsimulops,
							fccharcode,
							--fopermemo,
							FDPLANSTDT,
							fac
							FROM jodrtg
							WHERE fjobno = ?",array($insertJobData['newJob']));

						$routingData = self::trimArray($routingData);

						$this->assertEquals($expectedData,$routingData);
					}
				}
			}
		}
	}

	function testInsertJodBomRecord(){
		echo "\r\ntestInsertJodBomRecord\n";

		foreach(self::$testData as $partFile){
			foreach($partFile as $test => $data){
				if($test === 'jodbom'){
					foreach($data as $job => $subArray){
						$expectedData = array();
						$bomData = array();

						foreach($subArray as $subData){
							$testJob = $partFile['jomast'][$job]['newJob'];
							if($subData['fsub_job'] !== ''){
								$nextSubJob = $subData['fsub_job'];
							} else {
								$nextSubJob = '';
							}
							$testIdentityColumn = $subData['identity_column'];
							$testBomINum = $partFile['jomast'][$job]['bomINum'];

							$expectedData[$nextSubJob][] = array(
								'fbompart' 	 => $subData['fbompart'],
								'fbomrev' 	 => $subData['fbomrev'],
								'fbomdesc' 	 => $subData['fbomdesc'],
								'fparent' 	 => $subData['fparent'],
								//'fparentrev' => $subData['fparentrev'],
								//'factqty' 	 => $subData['factqty'],
								//'fbomLCost'  => $subData['fbomLCost'],
								'fbomMeas' 	 => $subData['fbomMeas'],
								//'fbomOCost'  => $subData['fbomOCost'],
								'fbomSource' => $subData['fbomSource'],
								//'fbook' 	 => $subData['fbook'],
								'fjobno' 	 => $subData['fjobno'],
								'fsub_job' 	 => $subData['fsub_job'],
								//'fsub_rel' 	 => $subData['fsub_rel'],
								'flextend' 	 => $subData['flextend'],
								'fltooling'	 => $subData['fltooling'],
								//'fmatlcost'  => $subData['fmatlcost'],
								//'fneed_Dt' 	 => $subData['fneed_Dt'],
								//'fbominum' 	 => $subData['fbominum'],
								'fresponse'  => $subData['fresponse'],
								//'ftotqty' 	 => $subData['ftotqty'],
								'fllotreqd'  => $subData['fllotreqd'],
								'fclotext' 	 => $subData['fclotext'],
								'fnoperno' 	 => $subData['fnoperno'],
								//'fstdmemo' 	 => $subData['fstdmemo'],
								'cfac' 		 => $subData['cfac'],
								//'fcbomudrev' => $subData['fcbomudrev'],
								//'fcparudrev' => $subData['fcparudrev'],
								'pfac' 		 => $subData['pfac'],
								//'forigqty' 	 => $subData['forigqty']
							);

							JobCreateModel::insertJodBomRecord(
								self::$sqlSrv,
								$testJob,
								$nextSubJob,
								$testIdentityColumn,
								1,
								1,
								$testBomINum
							);
						}

						foreach($subArray as $subData){
							$rawBomData[$subData['fsub_job']] = self::$sqlSrv->fetch_all_assoc(
								"SELECT
									fbompart,
									fbomrev,
									fbomdesc,
									fparent,
									--fparentrev,
									--factqty,
									--fbomLCost,
									fbomMeas,
									--fbomOCost,
									fbomSource,
									--fbook,
									fjobno,
									fsub_job,
									--fsub_rel,
									flextend,
									fltooling,
									--fmatlcost,
									--fneed_Dt,
									--fbominum,
									fresponse,
									--ftotqty,
									fllotreqd,
									fclotext,
									fnoperno,
									--fstdmemo,
									cfac,
									--fcbomudrev,
									--fcparudrev,
									pfac
									--forigqty
									FROM jodBom
									WHERE fsub_job = ?"
								,array($subData['fsub_job']));
						}

						$rawBomData = self::trimArray($rawBomData);

						$bomData = array();

						foreach($rawBomData as $subJob => $bom){
							if($subJob === ""){
								foreach($bom as $subBom){
									$bomData[$subJob][$subBom['fbompart']] = $subBom;
								}
							} else {
								$bomData[$subJob] = $bom;
							}
						}

						foreach($expectedData as $subJob => $expected){
							if($subJob !== ""){
								$this->assertEquals($expected,$bomData[$subJob]);
							}else{
								for($i = 0; $i < count($expected); $i++){
									$this->assertEquals($expected[$i], $bomData[$subJob][$expected[$i]['fbompart']]);
								}
							}
						}
					}
				}
			}
		}
	}

	//Possibly a Broken Function
	// function testAddAllBomPartsToJob(){
	// 	echo "\r\ntestAddAllBomPartsToJob\r\n";

	// 	self::$sqlSrv->query("TRUNCATE TABLE jodbom");

	// 	foreach(self::$testData as $partFile){
	// 		foreach($partFile as $test => $data){
	// 			if($test === 'jodbom'){
	// 				foreach($data as $job => $subArray){
	// 					if($partFile['jomast'][$job]['newJob'] === 'I1RW3-0000'){
	// 						$expectedData = array();

	// 						$insertJobData = array(
	// 							'newJob' 				 => $partFile['jomast'][$job]['newJob'],
	// 							'internal' 				 => $partFile['jomast'][$job]['internal'],
	// 							'dueDate' 				 => $partFile['jomast'][$job]['fddue_date'],
	// 							'userChar1' 			 => $partFile['jomast'][$job]['userChar1'],
	// 							'userChar2' 			 => $partFile['jomast'][$job]['userChar2'],
	// 							'salesOrder' 			 => $partFile['jomast'][$job]['salesOrder'],
	// 							'salesOrderCompany' 	 => $partFile['jomast'][$job]['salesOrderCompany'],
	// 							'salesOrderCustomerNo' 	 => $partFile['jomast'][$job]['salesOrderCustomerNo'],
	// 							'salesOrderJobName' 	 => $partFile['jomast'][$job]['salesOrderJobName'],
	// 							'salesOrderKey' 		 => $partFile['jomast'][$job]['salesOrderKey'],
	// 							'userMemo' 				 => $partFile['jomast'][$job]['userMemo'],
	// 							'jobType'				 => $partFile['jomast'][$job]['jobType'],
	// 							'lock'					 => $partFile['jomast'][$job]['lock'],
	// 							'reWorkReason'			 => $partFile['jomast'][$job]['reWorkReason'],
	// 							'reWorkInspCode' 		 => $partFile['jomast'][$job]['reWorkInspCode'],
	// 							'reWorkHot' 			 => $partFile['jomast'][$job]['reWorkHot'],
	// 							'reWorkDate' 			 => $partFile['jomast'][$job]['reWorkDate'],
	// 							'reWorkReasonEmployeeId' => $partFile['jomast'][$job]['reWorkReasonEmployeeId'],
	// 							'jobCreatorEmployeeId' 	 => $partFile['jomast'][$job]['jobCreatorEmployeeId'],
	// 							'operationMemo' 		 => "",
	// 						);

	// 						$testPartId = $partFile['partData'][$job]['identity_column'];
	// 						//$testPartId = '17861';

	// 						JobCreateModel::addAllBomPartsToJob(
	// 							self::$sqlSrv,
	// 							$insertJobData,
	// 							$testPartId,
	// 							1
	// 						);

	// 						foreach($subArray as $subData){
	// 							$expectedData[$subData['fsub_job']] = array(
	// 								'fbompart' 	 => $subData['fbompart'],
	// 								'fbomrev' 	 => $subData['fbomrev'],
	// 								'fbomdesc' 	 => $subData['fbomdesc'],
	// 								'fparent' 	 => $subData['fparent'],
	// 								//'fparentrev' => $subData['fparentrev'],
	// 								//'factqty' 	 => $subData['factqty'],
	// 								//'fbomLCost'  => $subData['fbomLCost'],
	// 								'fbomMeas' 	 => $subData['fbomMeas'],
	// 								//'fbomOCost'  => $subData['fbomOCost'],
	// 								'fbomSource' => $subData['fbomSource'],
	// 								//'fbook' 	 => $subData['fbook'],
	// 								'fjobno' 	 => $subData['fjobno'],
	// 								'fsub_job' 	 => $subData['fsub_job'],
	// 								//'fsub_rel' 	 => $subData['fsub_rel'],
	// 								'flextend' 	 => $subData['flextend'],
	// 								'fltooling'	 => $subData['fltooling'],
	// 								//'fmatlcost'  => $subData['fmatlcost'],
	// 								//'fneed_Dt' 	 => $subData['fneed_Dt'],
	// 								//'fbominum' 	 => $subData['fbominum'],
	// 								'fresponse'  => $subData['fresponse'],
	// 								//'ftotqty' 	 => $subData['ftotqty'],
	// 								'fllotreqd'  => $subData['fllotreqd'],
	// 								'fclotext' 	 => $subData['fclotext'],
	// 								'fnoperno' 	 => $subData['fnoperno'],
	// 								//'fstdmemo' 	 => $subData['fstdmemo'],
	// 								'cfac' 		 => $subData['cfac'],
	// 								//'fcbomudrev' => $subData['fcbomudrev'],
	// 								//'fcparudrev' => $subData['fcparudrev'],
	// 								'pfac' 		 => $subData['pfac'],
	// 								//'forigqty' 	 => $subData['forigqty']
	// 							);
	// 						}

	// 						foreach($subArray as $subData){
	// 							$rawBomData[$subData['fsub_job']] = self::$sqlSrv->fetch_all_assoc(
	// 								"SELECT
	// 									fbompart,
	// 									fbomrev,
	// 									fbomdesc,
	// 									fparent,
	// 									--fparentrev,
	// 									--factqty,
	// 									--fbomLCost,
	// 									fbomMeas,
	// 									--fbomOCost,
	// 									fbomSource,
	// 									--fbook,
	// 									fjobno,
	// 									fsub_job,
	// 									--fsub_rel,
	// 									flextend,
	// 									fltooling,
	// 									--fmatlcost,
	// 									--fneed_Dt,
	// 									--fbominum,
	// 									fresponse,
	// 									--ftotqty,
	// 									fllotreqd,
	// 									fclotext,
	// 									fnoperno,
	// 									--fstdmemo,
	// 									cfac,
	// 									--fcbomudrev,
	// 									--fcparudrev,
	// 									pfac
	// 									--forigqty
	// 									FROM jodBom
	// 									WHERE fsub_job = ?"
	// 								,array($subData['fsub_job']));
	// 						}

	// 						$rawBomData = self::trimArray($rawBomData);

	// 						print_r($rawBomData);

	// 						$bomData = array();

	// 						foreach($rawBomData as $subJob => $bom){
	// 							if($subJob === ""){
	// 								foreach($bom as $subBom){
	// 									$bomData[$subJob][$subBom['fbompart']] = $subBom;
	// 								}
	// 							} else {
	// 								$bomData[$subJob] = $bom;
	// 							}
	// 						}

	// 						//print_r($expectedData);
	// 						//print_r($bomData);
	// 						die();


	// 						foreach($expectedData as $subJob => $expected){
	// 							if($subJob !== ""){
	// 								$this->assertEquals($expected,$bomData[$subJob]);
	// 							}else{
	// 								for($i = 0; $i < count($expected); $i++){
	// 									$this->assertEquals($expected[$i], $bomData[$subJob][$expected[$i]['fbompart']]);
	// 								}
	// 							}
	// 						}
	// 					}
	// 				}
	// 			}
	// 		}
	// 	}
	// }


	function testInsertJopestRecords(){
		echo "\r\ntestInsertJopestRecords\r\n";

		foreach(self::$testData as $partFile){
			foreach($partFile as $test => $data){
				if($test === 'jopest'){
					foreach($data as $job => $subData){
						$testJobNumber = $subData['fjobno'];

						$expectedData = array(
							'fjobno'     => $subData['fjobno'],
							'fsuboper'   => $subData['fsuboper'],
							'finoper'    => $subData['finoper'],
							//'flabcost'   => $subData['flabcost'],
							'flastoper'  => $subData['flastoper'],
							//'fmovehrs'   => $subData['fmovehrs'],
							'fno_rtg'    => $subData['fno_rtg'],
							'fnumopers'  => $subData['fnumopers'],
							'fothrcost'  => $subData['fothrcost'],
							//'fovhdcost'  => $subData['fovhdcost'],
							'fovhdsc'    => $subData['fovhdsc'],
							//'fprodhrs'   => $subData['fprodhrs'],
							'fsetupcost' => $subData['fsetupcost'],
							'fsetuphrs'  => $subData['fsetuphrs'],
							'fsubcost'   => $subData['fsubcost'],
							'fsubhrs' 	 => $subData['fsubhrs']
						);

						JobCreateModel::insertJopestRecords(
							self::$sqlSrv,
							$testJobNumber,
							1
						);

						$jopestData = self::$sqlSrv->fetch_assoc(
				 			"SELECT
				 			fjobno,
				 			fsuboper,
				 			finoper,
				 			--flabcost,
				 			flastoper,
				 			--fmovehrs,
				 			fno_rtg,
				 			fnumopers,
				 			fothrcost,
				 			--fovhdcost,
				 			fovhdsc,
				 			--fprodhrs,
				 			fsetupcost,
				 			fsetuphrs,
				 			fsubcost,
				 			fsubhrs
				 			FROM jopest
				 			WHERE fjobno = ?",array($subData['fjobno'])
				 		);

						$jopestData = self::trimArray($jopestData);

						$this->assertEquals($expectedData,$jopestData);
					}
				}
			}
		}
	}

	function testGetNextJobRouteOperation(){
		echo "\r\ntestGetNextJobRouteOperation\r\n";

		foreach(self::$testData as $partFile){
			foreach($partFile as $test => $data){
				if($test === 'jodrtg'){
					foreach($data as $job => $subArray){
						foreach($subArray as $subData){
							$expectedOp = self::$sqlSrv->fetch_assoc(
								'select max(foperno) maxOp from jodrtg where fjobno=?', array($subData['fjobno']))['maxOp'];

							$nextOp = JobCreateModel::getNextJobRouteOperation(self::$sqlSrv,$subData['fjobno']);

							$this->assertEquals(intval($expectedOp)+5, $nextOp);
						}
					}
				}
			}
		}

		//self::$sqlSrv->query("TRUNCATE TABLE jodrtg");
		//$nextOp = JobCreateModel::getNextJobRouteOperation(self::$sqlSrv,$subData['fjobno']);
		//$this->assertEquals(10, $nextOp);
	}

	function testGetJobNextBomINum(){
		echo "\r\ntestGetJobNextBomINum\r\n";

		foreach(self::$testData as $partFile){
			foreach($partFile as $test => $data){
				if($test === 'jomast'){
					foreach($data as $job => $subData){
						$nextBomInum = JobCreateModel::getJobNextBomINum(self::$sqlSrv,$subData['newJob']);
						$bomINum = self::$sqlSrv->fetch_assoc(
							"SELECT max(fbominum+0) as lastBomINum FROM jodbom WHERE fjobno=?",array($subData['newJob']));

						$bomINum = intval($bomINum['lastBomINum']) + 1;

						$bomINum = str_pad($bomINum, 4, '0', STR_PAD_LEFT);

						$this->assertEquals($bomINum, $nextBomInum);
					}
				}
			}
		}

		self::$sqlSrv->query("TRUNCATE TABLE jodbom");
		$nextBomInum = JobCreateModel::getJobNextBomINum(self::$sqlSrv,'I1RW6-0000');
		//$this->assertEquals('0001', $nextBomInum);
	}

	function testInsertNextJodrtgRecord(){
		echo "\r\ntestInsertNextJodrtgRecord\r\n";

		self::$sqlSrv->query("TRUNCATE TABLE jodrtg");

		foreach(self::$testData as $partFile){
			foreach($partFile as $test => $data){
				if($test === 'jodrtg'){
					foreach($data as $job => $subArray){
						foreach($subArray as $subData){
							$insertData = array(
								'operationNo' => $subData['foperno'],
								'job'		  => $subData['fjobno'],
								'workcenter'  => $subData['fpro_id'],
								'startDate'   => $subData['fstrtdate']
							);

							$expectedData = array(
								//'foperno'    => $subData['foperno'],
								//'fchngrates' => $subData['fchngrates'],
								'felpstime'  => $subData['felpstime'],
								'ffixcost'   => $subData['ffixcost'],
								'flschedule' => $subData['flschedule'],
								'fstrtdate'  => $subData['fstrtdate'],
								//'factschdst' => $subData['factschdst'],
								'fmovetime'  => $subData['fmovetime'],
								//'fbominum'   => $subData['fbominum'],
								//'finumber'   => $subData['finumber'],
								'FNEED_dt'   => $subData['FNEED_dt'],
								//'fnqty_togo' => $subData['fnqty_togo'],
								//'foperqty'   => $subData['foperqty'],
								'fothrcost'  => $subData['fothrcost'],
								//'fpro_id'    => $subData['fpro_id'],
								'fresponse'  => $subData['fresponse'],
								'fsetuptime' => $subData['fsetuptime'],
								'fsource'    => $subData['fsource'],
								//'fulabcost'  => $subData['fulabcost'],
								//'fuovrhdcos' => $subData['fuovrhdcos'],
								//'fuprodtime' => $subData['fuprodtime'],
								'fusubcost'  => $subData['fusubcost'],
								'fllotreqd'  => $subData['fllotreqd'],
								'fcschdpct'  => $subData['fcschdpct'],
								//'fnsimulops' => $subData['fnsimulops'],
								'fccharcode' => $subData['fccharcode'],
								//'fopermemo'  => $subData['fopermemo'],
								'FDPLANSTDT' => $subData['FDPLANSTDT'],
								'fac'        => $subData['fac']
							);

							JobCreateModel::insertNextJodrtgRecord(
								self::$sqlSrv,
								$insertData
							);

							$routingData = self::$sqlSrv->fetch_assoc(
								"SELECT
								--foperno,
								--fchngrates,
								felpstime,
								ffixcost,
								flschedule,
								fstrtdate,
								--factschdst,
								fmovetime,
								--fbominum,
								--finumber,
								FNEED_dt,
								--fnqty_togo,
								--foperqty,
								fothrcost,
								--fpro_id,
								fresponse,
								fsetuptime,
								fsource,
								--fulabcost,
								--fuovrhdcos,
								--fuprodtime,
								fusubcost,
								fllotreqd,
								fcschdpct,
								--fnsimulops,
								fccharcode,
								--fopermemo,
								FDPLANSTDT,
								fac
								FROM jodrtg
								WHERE fjobno = ?",array($subData['fjobno']));

							$routingData = self::trimArray($routingData);

							$this->assertEquals($expectedData,$routingData);
						}
					}
				}
			}
		}
	}

	 /**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp #Could not get next sub job number for job xxxxx#
	 */
	function testGetNextSubJobNumber(){
		echo "\r\ntestGetNextSubJobNumber\r\n";

		foreach(self::$testData as $partFile){
			foreach($partFile as $test => $data){
				if($test === 'jomast'){
					foreach($data as $job => $subData){
						$nextSubJob = JobCreateModel::getNextSubJobNumber(self::$sqlSrv,$subData['newJob']);

						if($subData['parentJob'] === ""){
							$lastJobParent = substr($subData['newJob'],0,5);
						} else {
							$lastJobParent = substr($subData['parentJob'],0,5);
						}

						$subJob = self::$sqlSrv->fetch_assoc(
							"select max(fjobno) maxJob from jomast where fjobno like '".$lastJobParent."%'")['maxJob'];

						$subJob = intval(substr($subJob, - 4)) + 1 ;

						$newJob = substr($subData['newJob'], 0, 6) . str_pad($subJob,4,'0', STR_PAD_LEFT);

						$this->assertEquals($newJob, $nextSubJob);
					}
				}
			}
		}

		$job = "xxxxx";
		$nextSubJob = JobCreateModel::getNextSubJobNumber(self::$sqlSrv, $job);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp #Job#
	 */
	function testJomastException(){
		echo "\r\ntestJomastException\r\n";

		foreach(self::$testData as $partFile){
			foreach($partFile as $test => $data){
				if($test === 'jodrtg'){
					foreach($data as $job => $subArray){
						$jodrtgData = $subArray[0];
						$jodrtgData['operationNo'] = $subArray[0]['foperno'];
						$jodrtgData['job'] = $subArray[0]['fjobno'];
						$jodrtgData['workcenter'] = $subArray[0]['fpro_id'];
						$jodrtgData['startDate'] = $subArray[0]['fstrtdate'];

						self::$sqlSrv->query("DELETE FROM jomast WHERE fjobno = ?",array($jodrtgData['job']));

						JobCreateModel::insertNextJodrtgRecord(
							self::$sqlSrv,
							$jodrtgData
						);
					}
				}
			}
		}
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp #Work Center#
	 */
	function testInworkException(){
		echo "\r\ntestInworkException\r\n";

		$next = false;
		foreach(self::$testData as $partFile){
			foreach($partFile as $test => $data){
				if($test === 'jodrtg'){
					foreach($data as $job => $subArray){
						if(!$next){
							$next = true;
							continue;
						}

						$jodrtgData = $subArray[0];
						$jodrtgData['operationNo'] = $subArray[0]['foperno'];
						$jodrtgData['job'] = $subArray[0]['fjobno'];
						$jodrtgData['workcenter'] = $subArray[0]['fpro_id'];
						$jodrtgData['startDate'] = $subArray[0]['fstrtdate'];

						self::$sqlSrv->query("TRUNCATE TABLE inwork");

						JobCreateModel::insertNextJodrtgRecord(
							self::$sqlSrv,
							$jodrtgData
						);
					}
				}
			}
		}
	}

	// // function testCreateJob(){
	// // 	echo "\r\ntestCreateJob\r\n";

	// // 	$jobs = array();

	// // 	foreach(self::$testData as $partFile){
	// // 		foreach($partFile as $test => $data){
	// // 			if($test === 'jomast'){
	// // 				foreach($data as $job => $subData){

	// // 					if($subData['internal'] === 'I'){
	// // 						$subData['internal'] = true;
	// // 					} else{
	// // 						$subData['internal'] = false;
	// // 					}

	// // 					$createJobData = array(
	// // 						'part' => $subData['fpartno'],
	// // 						'partRev' => $subData['fpartrev'],
	// // 						'dueDate' => $subData['fddue_date'],
	// // 						'quantity' => 1,
	// // 						'jobType' => '',
	// // 						'userMemo' => $subData['userMemo'],
	// // 						'internal' => $subData['internal']
	// // 					);

	// // 					$jobs[$job] = JobCreateModel::createJob(self::$sqlSrv, $createJobData);
	// // 				}
	// // 			}
	// // 		}
	// // 	}

	// // 	foreach(self::$testData as $partFile){
	// // 		foreach($partFile as $test => $data){
	// // 			if($test === 'jomast'){
	// // 				foreach($data as $job => $subData){
	// // 					$expectedData = array(
	// // 						'bomINum'				=> $subData['bomINum'],
	// // 						'cadFile1'				=> $subData['cadFile1'],
	// // 						'cadFile2'				=> $subData['cadFile2'],
	// // 						'cadFile3'				=> $subData['cadFile3'],
	// // 						'deliverToWorkCenter'	=> $subData['deliverToWorkCenter'],
	// // 						'fac'					=> $subData['fac'],
	// // 						'fclotext'				=> $subData['fclotext'],
	// // 						'fcudrev'				=> $subData['fcudrev'],
	// // 						'fddue_date'			=> $subData['fddue_date'],
	// // 						'fdesc'					=> $subData['fdesc'],
	// // 						'fdet_bom'				=> $subData['fdet_bom'],
	// // 						'fdet_rtg'				=> $subData['fdet_rtg'],
	// // 						'fdstart'				=> $subData['fdstart'],
	// // 						'fhold_dt'				=> $subData['fhold_dt'],
	// // 						'fitems'				=> $subData['fitems'],
	// // 						'fitype'				=> $subData['fitype'],
	// // 						'fllotreqd'				=> $subData['fllotreqd'],
	// // 						'fmeasure'				=> $subData['fmeasure'],
	// // 						'fmethod'				=> $subData['fmethod'],
	// // 						'fopen_dt'				=> $subData['fopen_dt'],
	// // 						'fpartno'				=> $subData['fpartno'],
	// // 						'fpartrev'				=> $subData['fpartrev'],
	// // 						'fpro_plan'				=> $subData['fpro_plan'],
	// // 						'fprodcl'				=> $subData['fprodcl'],
	// // 						'fquantity'				=> $subData['fquantity'],
	// // 						'frel_dt'				=> $subData['frel_dt'],
	// // 						'fschdprior'			=> $subData['fschdprior'],
	// // 						'fstandpart'			=> $subData['fstandpart'],
	// // 						'fstatus'				=> $subData['fstatus'],
	// // 						'fsub_from'				=> $subData['fsub_from'],
	// // 						'fsummary'				=> $subData['fsummary'],
	// // 						'internal'				=> $subData['internal'],
	// // 						'ISTENN'				=> $subData['ISTENN'],
	// // 						'jobCreatorEmployeeId'	=> $subData['jobCreatorEmployeeId'],
	// // 						'jobType'				=> $subData['jobType'],
	// // 						'lock'					=> $subData['lock'],
	// // 						'newJob'				=> $subData['newJob'],
	// // 						'parentJob'				=> $subData['parentJob'],
	// // 						'reWorkDate'			=> $subData['reWorkDate'],
	// // 						'reWorkHot'				=> $subData['reWorkHot'],
	// // 						'reWorkInspCode'		=> $subData['reWorkInspCode'],
	// // 						'reWorkReason'			=> $subData['reWorkReason'],
	// // 						'reWorkReasonEmployeeId'=> $subData['reWorkReasonEmployeeId'],
	// // 						'routingProductionHours'=> $subData['routingProductionHours'],
	// // 						'salesOrder'			=> $subData['salesOrder'],
	// // 						'salesOrderCompany'		=> $subData['salesOrderCompany'],
	// // 						'salesOrderCustomerNo'	=> $subData['salesOrderCustomerNo'],
	// // 						'salesOrderJobName'		=> $subData['salesOrderJobName'],
	// // 						'salesOrderKey'			=> $subData['salesOrderKey'],
	// // 						'userChar1'				=> $subData['userChar1'],
	// // 						'userChar2'				=> $subData['userChar2'],
	// // 						'userMemo'				=> $subData['userMemo'],
	// // 					);

	// // 					$jomastData = self::$sqlSrv->fetch_assoc(
	// // 						"SELECT
	// // 						  fjobno newJob,
	// // 						  fpartno,
	// // 						  fpartrev,
	// // 						  fstatus,
	// // 						  fopen_dt,
	// // 						  fhold_dt,
	// // 						  frel_dt,
	// // 						  fddue_date,
	// // 						  fdstart,
	// // 						  fdesc,
	// // 						  --fsub_rel,
	// // 						  fdet_bom,
	// // 						  fdet_rtg,
	// // 						  fitems,
	// // 						  fitype,
	// // 						  fmeasure,
	// // 						  fmethod,
	// // 						  --fhold_by,
	// // 						  fprodcl,
	// // 						  fpro_plan,
	// // 						  fquantity,
	// // 						  frouting routingProductionHours,
	// // 						  fschdprior,
	// // 						  fstandpart,
	// // 						  fsummary,
	// // 						  --ftot_assy,
	// // 						  ftype internal,
	// // 						  fllotreqd,
	// // 						  fclotext,
	// // 						  fac,
	// // 						  fcudrev,
	// // 						  fcusrchr1 userChar1,
	// // 						  fcusrchr2 userChar2,
	// // 						  fbominum bomINum,
	// // 						  fsono salesOrder,
	// // 						  fcompany salesOrderCompany,
	// // 						  fcus_id salesOrderCustomerNo,
	// // 						  fjob_name salesOrderJobName,
	// // 						  fkey salesOrderKey,
	// // 						  fschbefjob parentJob,
	// // 						  fsub_from,
	// // 						  fccadfile1 cadFile1,
	// // 						  fccadfile2 cadFile2,
	// // 						  fccadfile3 cadFile3,
	// // 						  FMUSERMEMO userMemo,
	// // 						  reworkDelTo deliverToWorkCenter,
	// // 						  frework reWorkReason,
	// // 						  --fkey_id,
	// // 						  reworkReason reWorkInspCode,
	// // 						  Lock lock,
	// // 						  ISTENN,
	// // 						  hot reWorkHot,
	// // 						  jobtype jobType,
	// // 						  reworkresp reWorkReasonEmployeeId,
	// // 						  reworkby jobCreatorEmployeeId,
	// // 						  reworkdate reWorkDate
	// // 						FROM jomast
	// // 						JOIN jomast_ext on jomast.identity_column = jomast_ext.fkey_id
	// // 						WHERE fjobno = ?", array($jobs[$job]));

	// // 					$jomastData = self::trimArray($jomastData);

	// // 					$this->assertEquals($expectedData, $jomastData);
	// // 				}

	// // 				if($test === 'joitem'){
	// // 					foreach($data as $job => $subData){
	// // 						$expectedData = array(
	// // 							'fjobno'	 => $subData['fjobno'],
	// // 							'fitem'		 => $subData['fitem'],
	// // 							'fpartno'	 => $subData['fpartno'],
	// // 							'fpartrev'	 => $subData['fpartrev'],
	// // 							'fgroup'	 => $subData['fgroup'],
	// // 							'fmeasure'	 => $subData['fmeasure'],
	// // 							'fmqty'		 => $subData['fmqty'],
	// // 							'forderqty'	 => $subData['fcudrev'],
	// // 							'fprodcl'	 => $subData['fprodcl'],
	// // 							'fstandpart' => $subData['fstandpart'],
	// // 							'fdesc'		 => $subData['fdesc'],
	// // 							'fdescmemo'	 => $subData['fdescmemo'],
	// // 							'fac'		 => $subData['fac'],
	// // 							'fcudrev'	 => $subData['fcudrev'],
	// // 							'fsono'		 => $subData['fsono'],
	// // 							'finumber'   => $subData['finumber'],
	// // 							'fkey' 		 => $subData['fkey']
	// // 						);

	// // 						$joitemData = self::$sqlSrv->fetch_assoc(
	// // 							"SELECT fjobno,
	// // 							fitem,
	// // 							fpartno,
	// // 							fpartrev,
	// // 							fgroup,
	// // 							fmeasure,
	// // 							fmqty,
	// // 							forderqty,
	// // 							fprodcl,
	// // 							--fsource,
	// // 							fstandpart,
	// // 							fdesc,
	// // 							fdescmemo,
	// // 							fac,
	// // 							fcudrev,
	// // 							fsono,
	// // 							finumber,
	// // 							fkey
	// // 							FROM joitem where fjobno = ?",array($jobs[$job]));

	// // 						$joitemData = self::trimArray($joitemData);

	// // 						//$this->assertEquals($expectedData,$joitemData)
	// // 					}

	// // 					if($test === 'jopact'){
	// // 						foreach($data as $job => $subData){
	// // 							$expectedData = array(
	// // 								'fjobno'	 => $subData['fjobno'],
	// // 								'faddedocos' => $subData['faddedocos'],
	// // 								'faddedpcos' => $subData['faddedpcos'],
	// // 								'faddedltim' => $subData['faddedltim'],
	// // 								'faddedptim' => $subData['faddedptim'],
	// // 								'faddedscos' => $subData['faddedscos'],
	// // 								'faddedstim' => $subData['faddedstim'],
	// // 								'finumber'	 => $subData['finumber'],
	// // 								'flabact'	 => $subData['flabact'],
	// // 								'flabinv'	 => $subData['flabinv'],
	// // 								'fmatlact'	 => $subData['fmatlact'],
	// // 								'fmatlinv'	 => $subData['fmatlinv'],
	// // 								'fothract'	 => $subData['fothract'],
	// // 								'fothrinv'	 => $subData['fothrinv'],
	// // 								'fovhdact'	 => $subData['fovhdact'],
	// // 								'fovhdinv'	 => $subData['fovhdinv'],
	// // 								'frtgsetupa' => $subData['frtgsetupa'],
	// // 								'fsetupact'	 => $subData['fsetupact'],
	// // 								'fsubact'	 => $subData['fsubact'],
	// // 								'fsubinv'	 => $subData['fsubinv'],
	// // 								'ftoolact'	 => $subData['ftoolact'],
	// // 								'ftotltime'	 => $subData['ftotltime'],
	// // 								'ftotptime'	 => $subData['ftotptime'],
	// // 								'ftotstime'	 => $subData['ftotstime'],
	// // 								'faddedlsti' => $subData['faddedlsti'],
	// // 								'ftotlstime' => $subData['ftotlstime'],
	// // 								'fpmemo'	 => $subData['fpmemo']
	// // 							);

	// // 							$jopactData = self::$sqlSrv->fetch_assoc(
	// // 								"SELECT
	// // 								 fjobno,
	// // 								 faddedocos,
	// // 								 faddedpcos,
	// // 								 faddedltim,
	// // 								 faddedptim,
	// // 								 faddedscos,
	// // 								 faddedstim,
	// // 								 rtrim(finumber) finumber,
	// // 								 flabact,
	// // 								 flabinv,
	// // 								 fmatlact,
	// // 								 fmatlinv,
	// // 								 fothract,
	// // 								 fothrinv,
	// // 								 fovhdact,
	// // 								 fovhdinv,
	// // 								 frtgsetupa,
	// // 								 fsetupact,
	// // 								 fsubact,
	// // 								 fsubinv,
	// // 								 ftoolact,
	// // 								 ftotltime,
	// // 								 ftotptime,
	// // 								 ftotstime,
	// // 								 faddedlsti,
	// // 								 ftotlstime,
	// // 								 fpmemo
	// // 								FROM jopact where fjobno = ?",array($jobs[$job]));

	// // 							$jopactData = self::trimArray($jopactData);

	// // 							$this->assertEquals($expectedData,$jopactData);
	// // 						}
	// // 					}

	// // 					if($test === 'jopest'){
	// // 						foreach($data as $job => $subData){
	// // 							$expectedData = array(
	// // 								'fjobno'     => $subData['fjobno'],
	// // 								'fsuboper'   => $subData['fsuboper'],
	// // 								'finoper'    => $subData['finoper'],
	// // 								'flabcost'   => $subData['flabcost'],
	// // 								'flastoper'  => $subData['flastoper'],
	// // 								'fmovehrs'   => $subData['fmovehrs'],
	// // 								'fno_rtg'    => $subData['fno_rtg'],
	// // 								'fnumopers'  => $subData['fnumopers'],
	// // 								'fothrcost'  => $subData['fothrcost'],
	// // 								'fovhdcost'  => $subData['fovhdcost'],
	// // 								'fovhdsc'    => $subData['fovhdsc'],
	// // 								'fprodhrs'   => $subData['fprodhrs'],
	// // 								'fsetupcost' => $subData['fsetupcost'],
	// // 								'fsetuphrs'  => $subData['fsetuphrs'],
	// // 								'fsubcost'   => $subData['fsubcost'],
	// // 								'fsubhrs' 	 => $subData['fsubhrs']
	// // 							);

	// // 							$jopestData = self::$sqlSrv->fetch_assoc(
	// // 					 			"SELECT
	// // 					 			fjobno,
	// // 					 			fsuboper,
	// // 					 			finoper,
	// // 					 			flabcost,
	// // 					 			flastoper,
	// // 					 			fmovehrs,
	// // 					 			fno_rtg,
	// // 					 			fnumopers,
	// // 					 			fothrcost,
	// // 					 			fovhdcost ,
	// // 					 			fovhdsc,
	// // 					 			fprodhrs,
	// // 					 			fsetupcost,
	// // 					 			fsetuphrs,
	// // 					 			fsubcost,
	// // 					 			fsubhrs
	// // 					 			FROM jopest
	// // 					 			WHERE fjobno = ?",array($jobs[$job]));

	// // 							$jopestData = self::trimArray($jopestData);

	// // 							//$this->assertEquals($expectedData,$jopestData);
	// // 						}

	// // 						if($test === 'jodrtg'){
	// // 							foreach($data as $job => $subArray){
	// // 								foreach($subArray as $subData){
	// // 									$expectedData = array(
	// // 										'foperno'    => $subData['foperno'],
	// // 										'fchngrates' => $subData['fchngrates'],
	// // 										'felpstime'  => $subData['felpstime'],
	// // 										'ffixcost'   => $subData['ffixcost'],
	// // 										'flschedule' => $subData['flschedule'],
	// // 										'fstrtdate'  => $subData['fstrtdate'],
	// // 										'factschdst' => $subData['factschdst'],
	// // 										'fmovetime'  => $subData['fmovetime'],
	// // 										'fbominum'   => $subData['fbominum'],
	// // 										'finumber'   => $subData['finumber'],
	// // 										'FNEED_dt'   => $subData['FNEED_dt'],
	// // 										'fnqty_togo' => $subData['fnqty_togo'],
	// // 										'foperqty'   => $subData['foperqty'],
	// // 										'fothrcost'  => $subData['fothrcost'],
	// // 										'fpro_id'    => $subData['fpro_id'],
	// // 										'fresponse'  => $subData['fresponse'],
	// // 										'fsetuptime' => $subData['fsetuptime'],
	// // 										'fsource'    => $subData['fsource'],
	// // 										'fulabcost'  => $subData['fulabcost'],
	// // 										'fuovrhdcos' => $subData['fuovrhdcos'],
	// // 										'fuprodtime' => $subData['fuprodtime'],
	// // 										'fusubcost'  => $subData['fusubcost'],
	// // 										'fllotreqd'  => $subData['fllotreqd'],
	// // 										'fcschdpct'  => $subData['fcschdpct'],
	// // 										'fnsimulops' => $subData['fnsimulops'],
	// // 										'fccharcode' => $subData['fccharcode'],
	// // 										'fopermemo'  => $subData['fopermemo'],
	// // 										'FDPLANSTDT' => $subData['FDPLANSTDT'],
	// // 										'fac'        => $subData['fac']
	// // 									);

	// // 									$routingData = self::$sqlSrv->fetch_assoc(
	// // 										"SELECT
	// // 										foperno,
	// // 										fchngrates,
	// // 										felpstime,
	// // 										ffixcost,
	// // 										flschedule,
	// // 										fstrtdate,
	// // 										factschdst,
	// // 										fmovetime,
	// // 										fbominum,
	// // 										finumber,
	// // 										FNEED_dt,
	// // 										fnqty_togo,
	// // 										foperqty,
	// // 										fothrcost,
	// // 										fpro_id,
	// // 										fresponse,
	// // 										fsetuptime,
	// // 										fsource,
	// // 										fulabcost,
	// // 										fuovrhdcos,
	// // 										fuprodtime,
	// // 										fusubcost,
	// // 										fllotreqd,
	// // 										fcschdpct,
	// // 										fnsimulops,
	// // 										fccharcode,
	// // 										fopermemo,
	// // 										FDPLANSTDT,
	// // 										fac
	// // 										FROM jodrtg
	// // 										WHERE fjobno = ?",array($jobs[$job]));

	// // 									$routingData = self::trimArray($routingData);

	// // 									//$this->assertEquals($expectedData,$routingData);
	// // 								}
	// // 							}
	// // 						}
	// // 						if($test === 'jodbom'){
	// // 								foreach($data as $job => $subArray){
	// // 									$expectedData = array();

	// // 									foreach($subArray as $subData){
	// // 										$expectedData[] = array(
	// // 											'fbompart' 	 => $subData['fbompart'],
	// // 											'fbomrev' 	 => $subData['fbomrev'],
	// // 											'fbomdesc' 	 => $subData['fbomdesc'],
	// // 											'fparent' 	 => $subData['fparent'],
	// // 											'fparentrev' => $subData['fparentrev'],
	// // 											'factqty' 	 => $subData['factqty'],
	// // 											'fbomLCost'  => $subData['fbomLCost'],
	// // 											'fbomMeas' 	 => $subData['fbomMeas'],
	// // 											'fbomOCost'  => $subData['fbomOCost'],
	// // 											'fbomSource' => $subData['fbomSource'],
	// // 											'fbook' 	 => $subData['fbook'],
	// // 											'fjobno' 	 => $subData['fjobno'],
	// // 											'fsub_job' 	 => $subData['fsub_job'],
	// // 											'fsub_rel' 	 => $subData['fsub_rel'],
	// // 											'flextend' 	 => $subData['flextend'],
	// // 											'fltooling'	 => $subData['fltooling'],
	// // 											'fmatlcost'  => $subData['fmatlcost'],
	// // 											'fneed_Dt' 	 => $subData['fneed_Dt'],
	// // 											'fbominum' 	 => $subData['fbominum'],
	// // 											'fresponse'  => $subData['fresponse'],
	// // 											'ftotqty' 	 => $subData['ftotqty'],
	// // 											'fllotreqd'  => $subData['fllotreqd'],
	// // 											'fclotext' 	 => $subData['fclotext'],
	// // 											'fnoperno' 	 => $subData['fnoperno'],
	// // 											'fstdmemo' 	 => $subData['fstdmemo'],
	// // 											'cfac' 		 => $subData['cfac'],
	// // 											'fcbomudrev' => $subData['fcbomudrev'],
	// // 											'fcparudrev' => $subData['fcparudrev'],
	// // 											'pfac' 		 => $subData['pfac'],
	// // 											'forigqty' 	 => $subData['forigqty']
	// // 										);
	// // 									}

	// // 									$bomData = self::$sqlSrv->fetch_all_assoc(
	// // 										"SELECT
	// // 											fbompart,
	// // 											fbomrev,
	// // 											fbomdesc,
	// // 											fparent,
	// // 											fparentrev,
	// // 											factqty,
	// // 											fbomLCost,
	// // 											fbomMeas,
	// // 											fbomOCost,
	// // 											fbomSource,
	// // 											fbook,
	// // 											fjobno,
	// // 											fsub_job,
	// // 											fsub_rel,
	// // 											flextend,
	// // 											fltooling,
	// // 											fmatlcost,
	// // 											fneed_Dt,
	// // 											fbominum,
	// // 											fresponse,
	// // 											ftotqty,
	// // 											fllotreqd,
	// // 											fclotext,
	// // 											fnoperno,
	// // 											fstdmemo,
	// // 											cfac,
	// // 											fcbomudrev,
	// // 											fcparudrev,
	// // 											pfac,
	// // 											forigqty
	// // 											FROM jodBom
	// // 											WHERE fjobno = ?"
	// // 										,array($jobs[$job]));

	// // 									$bomData = self::trimArray($bomData);

	// // 									// /$this->assertEquals($expectedData, $bomData);
	// // 								}
	// // 							}
	// // 						}
	// // 					}
	// // 				}
	// // 			}
	// // 		}
	// // 	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp #Error getting next job number! IdNext failed!#
	 */
	function testSySequException(){
		echo "\r\ntestSySequException\r\n";

		self::$sqlSrv->query("truncate table SYSEQU");

		$jobNumberExternal = JobCreateModel::getNextJobNumberData(self::$sqlSrv, false);
	}
}
