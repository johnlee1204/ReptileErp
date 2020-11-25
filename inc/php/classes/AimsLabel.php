<?php

use Libraries\NotificationSender;

class AimsLabel {
	static $laserBarcodePrefix = '015';

	const waterJetWorkCenter = 'WTRJET';
	const laserWorkCenter = 'SLASERT';
	const laserEmployeeId = '1011';
	const laserEmployeeDept = '02';
	const laserEmployeeShift = '1';
	const notificationWaterJet = 'Water Jet Label Printer';
	const notificationLaser = 'Aims Label Printer';

	private $fillerPartLocation;
	private $fillerPartBin;
	private $processedParts;
	private $hotPrints;
	private $regularPrints;
	/* @var sqlsrv_helper */
	private $database;
	public $printer;
	public $printerHot;

	function __construct($database){
		$this->database = $database;
		if(Dev::isDev()){
			$this->printer = 'IT_4x0.75';
			$this->printerHot = 'IT_4x0.75';
			$this->printerWaterjet = 'IT_4x0.75';
		}else{
			$this->printer = 'Laser Labels 4x75';
			$this->printerHot = 'Laser HOT Labels 4x75';
			$this->printerWaterjet = 'Waterjet 4x.75';
		}
	}

	private function sendEmail($notificationName, $subject, $emailHTML){
		//$notificationName = 'Water Jet Label Printer'
		//or
		//$notificationName = 'Aims Label Printer'

		NotificationSender::send([
			'notificationName' => $notificationName,
			'dbc' => $this->database,
			'from' => $notificationName,
			'subject' => $subject,
			'message' => $emailHTML
		]);
	}

	function printSheetLaser($partsArray, $logTable = 'fwe_dev..laserPartLabels'){
		if(count($partsArray) == 0){
			throw new AgileUserMessageException("No parts!");
		}

		$this->fillerPartLocation= 'TN';
		$this->fillerPartBin = 'FILLER';

		$requiredColumns = array(
		//	"SheetNodeID",
			"SheetID",
			"PartName",
			"ExternalOrderID",
			"Quantity",
			"Printed",
			"MaterialName",
			"MaterialType",
			"MaterialCode",
			"MaterialThickness",
			"MaterialUtilization",
			"MaterialSizeX",
			"MaterialSizeY"
		);

		for($i=0;$i<count($partsArray);$i++){
			foreach($requiredColumns as $column){
				if(!array_key_exists($column, $partsArray[$i])){
					throw new AgileUserMessageException($column." is required on all rows");
				}
				$partsArray[$i][$column] = trim($partsArray[$i][$column]);
			}
		}

		$processedPartsArray = array();
		$output = array();

		$output['filler'] = 0;
		$output['nonFiller'] = 0;
		//Pre-process parts

		foreach($partsArray as &$part){
			//These are NOT jobs at all, they only exist in AIMS. We know they are AIMS fillers because they don't follow the job formatting "12345-0000" standard
			//This is only here just in case, but we shouldn't be doing fillers like this anymore
			if($this->isAimsNoJobFillerPart($part)){
				$this->execFweFillerAdjust($part['PartName'], $part['Quantity']);
				$output['filler']++;
			} else {
				$processedPartsArray[] = $this->formatPartArray($part);
			}
		}

		$hotPrints = array();
		$regularPrints = array();

		foreach($processedPartsArray as $part){
			$jobInfo = $this->getJobInfo($part['job']);

			if(NULL === $jobInfo){
				$this->sendEmail(self::notificationLaser,"No Job Record Found","No job record found for Job: {$part['job']}");
				continue;
			}

			//This is the main way we handle fillers now
			if($jobInfo['jobType'] === "FillerLaser"){
				$part['filler'] = 1;
				$this->execFweFillerAdjust($part['part'], $part['qty']);
				$output['filler']++;
			}else{
				$part['filler'] = 0;
			}

			$part['jomastIdentityColumn'] = $jobInfo['jomastIC'];
			$part['partDescription'] = trim($jobInfo['partDescription']);
			$part['hot'] = trim($jobInfo['hot']);

			if(NULL === $laserRouteInfo = $this->getLaserRoutingInfo($part['job'])){
				$this->sendEmail(self::notificationLaser,'No Job Laser Routing','No Job Laser Routing Record for job <b>'.$part['job'].'</b><br />Skipping This Label!');
				continue;
			}

			if($laserRouteInfo['qtyComplete'] + $laserRouteInfo['qtyScrap'] + $part['qty'] > $laserRouteInfo['qtyOperation']){

				//die("JOB: ".$row['job']."\r\nQTY: ".$row['qty']."\r\nAlready Completed ".($laserRouteInfo['qtyComplete'] + $laserRouteInfo['qtyScrap'])."\r\nNEEDED: ".$laserRouteInfo['qtyOperation']."\r\nDELTA: ".$row["jobQtyRemaining"]);

				$emailHTML = array();
				$emailHTML[] = '<h3>Qty Completed + Qty Trying to complete > Operation Qty !!</h3>';
				$emailHTML[] = 'Qty completed w/ scrap: <b>'.($laserRouteInfo['qtyComplete'] + $laserRouteInfo['qtyScrap']).'</b><br />';
				$emailHTML[] = 'Qty trying to complete: <b>'.$part['qty'].'</b><br /><br />';
				$emailHTML[] = 'Operation Qty: <b>'.SmartTruncate::truncate($laserRouteInfo['qtyOperation']).'</b><br /><br />';
				$emailHTML[] = 'Job: <b><a href="http://itx166.fwe.com/Job/?job='.$part['job'].'">'.$part['job'].'</a></b><br/>';
				$emailHTML[] = 'Part: <b>'.$part['part'].'</b><br />';
				$emailHTML[] = '<br /><br />Label will be marked EXTRA PART<br /><br />';

				$subject = 'Laser Label Qty Alert: '.$part['job'];

				$this->sendEmail(self::notificationLaser, $subject, implode("\r\n",$emailHTML));

				//We still want to print X labels, but if we were to adjust the qty by X, that would be more than needed.
				//lets at least max out the qty printed, even though we're printing more than we need left.
				//Some of the labels printed will show up as an "EXTRA PART"
				$part["jobQtyRemaining"] = $laserRouteInfo['qtyOperation'] - ($laserRouteInfo['qtyComplete'] + $laserRouteInfo['qtyScrap']);

				if($part["jobQtyRemaining"] < 0){
					$part["jobQtyRemaining"] = 0;
				}
				$orgQty = $part['qty'];
				//only mark the number of parts remaining complete on the job
				$part['qty'] = $part["jobQtyRemaining"];
				$part = $this->updateJob($part,$jobInfo,$laserRouteInfo, AimsLabel::laserWorkCenter);

				//print logic wants full qty, will handle showing "EXTRA PART" based on jobQtyRemaining
				$part['qty'] = $orgQty; //put back

			}else{
				$part = $this->updateJob($part,$jobInfo,$laserRouteInfo, AimsLabel::laserWorkCenter);
			}

			//Copy row into regularPrints or hotprints if hot.
			if($part['hot'] == 1){
				$hotPrints[] = $part;
			}else{
				$regularPrints[] = $part;
			}
		}

		if(count($hotPrints)>0){
			$output['hot'] = $this->printLabels($hotPrints,$this->printerHot,$logTable);
		}
		if(count($regularPrints)>0){
			$output['regular'] = $this->printLabels($regularPrints,$this->printer,$logTable);
		}
		return $output;
	}

	function printSheetWaterjet($partsArray){
		if(count($partsArray) === 0){
			throw new AgileUserMessageException("No parts!");
		}

		$output = array();

		$output['filler'] = 0;
		$output['nonFiller'] = 0;

		$prints = array();

		foreach($partsArray as $part){
			if($part['job'] === null){
				$output['filler']++;
				$this->execFweFillerAdjust($part['part'], $part['qty']);
				continue;
			} else {
				$output['nonFiller']++;
			}

			if(NULL === $jobInfo = $this->getJobInfo($part['job'])){
				$this->sendEmail(self::notificationWaterJet,'No Job Found','No Job Record for job <b>'.$part['job'].'</b><br />Skipping This Label!');
				continue;
			}

			$part['jomastIdentityColumn'] = $jobInfo['jomastIC'];
			$part['partDescription'] = trim($jobInfo['partDescription']);
			$part['hot'] = trim($jobInfo['hot']);

			if(NULL === $waterjetRouteInfo = $this->getWaterjetRoutingInfo($part['job'])){

				$this->sendEmail(self::notificationWaterJet,'No Job Water Jet Routing','No Job Water Jet Routing Record for job <b>'.$part['job'].'</b><br />Skipping This Label!');

				continue;
			}

			if($waterjetRouteInfo['qtyComplete'] + $waterjetRouteInfo['qtyScrap'] + $part['qty'] > $waterjetRouteInfo['qtyOperation']){
				$emailHTML = array();
				$emailHTML[] = '<h3>Qty Completed + Qty Trying to complete > Operation Qty !!</h3>';
				$emailHTML[] = 'Qty completed w/ scrap: <b>'.($waterjetRouteInfo['qtyComplete'] + $waterjetRouteInfo['qtyScrap']).'</b><br />';
				$emailHTML[] = 'Qty trying to complete: <b>'.$part['qty'].'</b><br /><br />';
				$emailHTML[] = 'Operation Qty: <b>'.SmartTruncate::truncate($waterjetRouteInfo['qtyOperation']).'</b><br /><br />';
				$emailHTML[] = 'Job: <b><a href="http://itx166.fwe.com/Job/?job='.$part['job'].'">'.$part['job'].'</a></b><br/>';
				$emailHTML[] = 'Part: <b>'.$part['part'].'</b><br />';
				$emailHTML[] = '<br /><br />Label will be marked EXTRA PART<br /><br />';

				$subject = 'Waterjet Label Qty Alert: '.$part['job'];

				$this->sendEmail(self::notificationWaterJet, $subject, implode("\r\n",$emailHTML));

				//We still want to print X labels, but if we were to adjust the qty by X, that would be more than needed.
				//lets at least max out the qty printed, even though we're printing more than we need left.
				//Some of the labels printed will show up as an "EXTRA PART"
				$part["jobQtyRemaining"] = $waterjetRouteInfo['qtyOperation'] - $waterjetRouteInfo['qtyComplete'] - $waterjetRouteInfo['qtyScrap'];

				if($part["jobQtyRemaining"] < 0){
					$part["jobQtyRemaining"] = 0;
				}
				$orgQty = $part['qty'];
				//only mark the number of parts remaining complete on the job
				$part['qty'] = $part["jobQtyRemaining"];
				$part = $this->updateJob($part,$jobInfo,$waterjetRouteInfo, AimsLabel::waterJetWorkCenter);

				//print logic wants full qty, will handle showing "EXTRA PART" based on jobQtyRemaining
				$part['qty'] = $orgQty; //put back

			}else{
				$part = $this->updateJob($part,$jobInfo,$waterjetRouteInfo, AimsLabel::waterJetWorkCenter);
			}

			$prints[] = $part;
		}
		$output['printedFiles'] = $this->printLabelsWaterjet($prints,$this->printerWaterjet);
		return $output;
	}

	private function execFweFillerAdjust($part, $qty){
		$this->database->query("exec m2mdata01..fweFillerAdj ?,?,?,?,?,?",array($part,'',$qty,$this->fillerPartLocation,$this->fillerPartBin,'- Filler Parts'));
	}

	private function formatPartArray($part){
		if(trim($part['ExternalOrderID']) == ''){
			throw new AgileUserMessageException("Missing ExternalOrderID!");
		}
		if(trim($part['Quantity']) == '' || !is_numeric($part['Quantity'])){
			throw new AgileUserMessageException("Quantity is missing or not numeric");
		}

		return array(
			'sheetId'			=> trim($part['SheetID']),
			'materialName'		=> trim($part['MaterialName']),
			'materialType'		=> trim($part['MaterialType']),
			'materialCode'		=> trim($part['MaterialCode']),
			'materialThickness'	=> trim($part['MaterialThickness']),
			'materialUtilization'	=> intval($part['MaterialUtilization']),
			'materialSizeX'		=> intval($part['MaterialSizeX']),
			'materialSizeY'		=> intval($part['MaterialSizeY']),
			'job'				=> trim($part['ExternalOrderID']),
			'part'				=> trim($part['PartName']),
			'qty'				=> intval($part['Quantity'])
		);
	}

	private function getJobInfo($job){
		//get parent, hot, job, default route
		$result = $this->database->fetch_assoc(
			"SELECT
			rtrim(jomast.fac) as facility,
			FSCHBEFJOB,
			JOITEM.FDESC partDescription,
			jomast_ext.hot,
			jomast.identity_column jomastIC,
			isnull(INMAST_EXT.RTGTO,'') Rtgto,
			rtrim(jobType) AS jobType
			FROM
			JOMAST
			JOIN JOITEM ON JOMAST.FJOBNO=JOITEM.FJOBNO
			LEFT JOIN jomast_ext on jomast.identity_column=fkey_id
			LEFT JOIN INMAST on inmast.fpartno=jomast.fpartno and inmast.frev=jomast.fpartrev
			LEFT JOIN INMAST_EXT on INMAST_EXT.FKey_ID=inmast.identity_column
			WHERE
			JOMAST.FJOBNO=?",array($job));

		if(NULL === $result){
			return NULL;
		}

		foreach($result as $column => &$value){
			$value = trim($value);
		}

		return $result;
	}

	private function getLastRoutingOperationForJob($job){
		//get last routing operation
		$result = $this->database->fetch_assoc("SELECT max(foperno) as lastOp from jodrtg where fjobno=?",array($job));
		if(is_null($result['lastOp'])){
			throw new AgileUserMessageException("Could not get last routing operation for job ".$job);
		}
		return $result['lastOp'];
	}

	private function getLaserRoutingInfo($job){
		//get laser routing info
		//get parent, job

		return $this->database->fetch_assoc(
			"SELECT
			foperno as operation,
			foperqty as qtyOperation,
			fnqty_comp as qtyComplete,
			ftot_scr as qtyScrap,
			fulabcost as costLabor,
			fuovrhdcos as costOverhead,
			fuprodtime as productionTime,
			convert(char(16),getdate(),121) as clockOffTime
			FROM
			JODRTG
			where
			JODRTG.fjobno=?
			and JODRTG.fpro_id='".AimsLabel::laserWorkCenter."'",array($job));

	}

	private function getWaterjetRoutingInfo($job){
		return $this->database->fetch_assoc(
			"SELECT
			foperno as operation,
			foperqty as qtyOperation,
			fnqty_comp as qtyComplete,
			ftot_scr as qtyScrap,
			fulabcost as costLabor,
			fuovrhdcos as costOverhead,
			fuprodtime as productionTime,
			convert(char(16),getdate(),121) as clockOffTime
			FROM
			JODRTG
			where
			JODRTG.fjobno=?
			and JODRTG.fpro_id='".AimsLabel::waterJetWorkCenter."' ",array($job));
	}

	/**
	 * @param $jobClock off record not found! Error 2 divide by zero. Someone deleted the record right after it was inserted???? THIS SHOULD NOT HAPPEN!
	 * @return string
	 */
	private function getTopLevelPart($job){
		//Get Top level part number
		$result = $this->database->fetch_assoc("select fpartno as topLevelPart from joitem where fjobno=?+'-0000'",array(substr($job,0,5)));
		return trim($result['topLevelPart']);
	}

	/**
	 * @param $job
	 * @return array
	 */
	private function getTopLevelRoutings($job){
		//Get assembly custom or welding for top level part
		$results = $this->database->fetch_all_assoc("SELECT FCPRO_ID,FCPRO_NAME FROM JODRTG JOIN INWORK ON FPRO_ID=FCPRO_ID WHERE not FCPRO_ID='".AimsLabel::laserWorkCenter."' and fjobno=?+'-0000' ORDER BY FOPERNO",array(substr($job,0,5)));

		$resultArr = array();
		foreach($results as $result){
			if(trim($result['FCPRO_ID']) == "ASMGREY" || trim($result['FCPRO_ID']) == "CUSTOM" || trim($result['FCPRO_ID']) == "ASMORNG" || trim($result['FCPRO_ID']) == "ASMBLUE" || substr($result['FCPRO_ID'],0,1)== "W"){
				$resultArr[] = trim($result['FCPRO_ID']);
			}
		}

		return $resultArr;
	}

	/**
	 * @param $job
	 * @param $parentJob
	 * @return string
	 */
	private function getPartAndParentRouting($job, $parentJob){
		//Get next work center for laser part
		$results = $this->database->fetch_all_assoc("SELECT FCPRO_ID,FCPRO_NAME FROM JODRTG JOIN INWORK ON FPRO_ID=FCPRO_ID WHERE not FCPRO_ID='".AimsLabel::laserWorkCenter."' and fjobno=? ORDER BY FOPERNO",array($job));

		$resultArr = array();
		foreach($results as $result){
			$resultArr[] = trim($result['FCPRO_NAME']);
		}
		$partAndParentRouting = implode(",",$resultArr)."/";

		//Get the parent job operation 10 route
		$results = $this->database->fetch_all_assoc("SELECT FCPRO_ID,FCPRO_NAME FROM JODRTG JOIN INWORK ON FPRO_ID=FCPRO_ID WHERE not FCPRO_ID='".AimsLabel::laserWorkCenter."' and foperno=10 and fjobno=? ORDER BY FOPERNO",array($parentJob));
		$resultArr = array();
		foreach($results as $result){
			$resultArr[] = trim($result['FCPRO_NAME']);
		}
		$partAndParentRouting .=  implode(" ",$resultArr);

		return $partAndParentRouting;
	}

	# Inserts data into all the Printed Log Tables without actually Printing
	function updateFillerJob($sheet,$logTable){

		$part = $this->formatPartArray($sheet);
		$jobInfo = $this->getJobInfo($part['job']);
		$laserRouteInfo = $this->getLaserRoutingInfo($part['job']);
		$part = $this->updateJob($part,$jobInfo,$laserRouteInfo, AimsLabel::laserWorkCenter);
		for($i=0;$i<$part['qty'];$i++) {
			if(isset($part["jobQtyRemaining"]) ){
				if($part["jobQtyRemaining"] > 0){
					$part["extraPart"] = "";
					$part["jobQtyRemaining"]--;
				}else{
					$part["extraPart"] = "EXTRA PART";
				}
			}else{
				$part["extraPart"] = "";
				$part['barcode'] = " ";
				$part["topLevelPart"] = " ";
				$part["deliverTo"] = " ";
				$part["partAndParentRouting"] = " ";
			}

			$part['datePrinted'] = 'getdate()';
			$labelData[] = $part;
		}

		//insert reprint data
		$cleanedLabelData = array();
		foreach($labelData as $label){
			unset($label["jobQtyRemaining"]);
			$label['partAndParentRouting'] = substr($label['partAndParentRouting'], 0, 255);
			$label['deliverTo'] = substr($label['deliverTo'], 0, 255);
			$this->database->insert($logTable, $label);
			$cleanedLabelData[] = $label;
		}
	}

	private function updateJob($row,$jobInfo,$laserRouteInfo, $laborWorkCenter){

		$lastRoutingOp = $this->getLastRoutingOperationForJob($row['job']);
		$parentJob = trim($jobInfo['FSCHBEFJOB']);
		$jobFacility = $jobInfo['facility'];

		$defaultRoute = trim($jobInfo['Rtgto']);

		$row['topLevelPart'] = $this->getTopLevelPart($row['job']);

		$TopLevelRoutings = implode(" ",$this->getTopLevelRoutings($row['job']));

		//if there's no default route, use the top level job routings
		if($defaultRoute == '') {
			$row['deliverTo'] = $TopLevelRoutings;
		}else{
			$row['deliverTo'] = $defaultRoute;
		}

		$row['partAndParentRouting'] = $this->getPartAndParentRouting($row['job'],$parentJob);

		$productionTimeTotal = $row['qty'] * $laserRouteInfo['productionTime'];

		$costLabor = round($laserRouteInfo['costLabor'] * $productionTimeTotal,4);
		$costOverHead = round($laserRouteInfo['costOverhead'] * $productionTimeTotal,4);

		EmployeeLaborModel::laborEntryInsertDetail(
			$this->database,
			array(
				'FEMPNO' => AimsLabel::laserEmployeeId,
				'FAC' => $jobFacility,
				'FJOBNO' => $row['job'],
				'FOPERNO' => $laserRouteInfo['operation'],
				'serial' => '', // serial
				'FCDEPT' => AimsLabel::laserEmployeeDept,
				'FPRO_ID' => $laborWorkCenter,
				'FCOMPQTY' => $row['qty'],
				'scrapQty' => 0, //qty scrapped
				'FDATE' => substr($laserRouteInfo['clockOffTime'],0,10), //labor entry date
				'FSDATETIME' => $laserRouteInfo['clockOffTime'], //clock on time
				'FEDATETIME' => $laserRouteInfo['clockOffTime'], //clock off time
				'FTOTOCOST' => $costOverHead,
				'FTOTPCOST' => $costLabor,
				'shift' => AimsLabel::laserEmployeeShift,
				'FLABTYPE' => '', // labor type. blank means direct
				'parallelLaborTime' => $productionTimeTotal,
				'parallelSequenceNo' => '0', // parallel sequence no, 0 for non parallel
				'lastJob' => false, // last job? (only for parallel)
				'fweLaborSrc' => 'Laser Labels'
			)
		);

		JobCostModel::postLaborAndOverheadCosts(
			$this->database,
			$row['job'],
			$costLabor,
			$costOverHead,
			$laserRouteInfo['clockOffTime'], //transaction time
			"Laser Label Printer" // transaction comment
		);

		$this->database->query("UPDATE JOMAST SET FNUSRQTY1=FNUSRQTY1+? WHERE FJOBNO=?",array($row['qty'], $row['job']));

		$updateQtyComplete = $row['qty'] + $laserRouteInfo['qtyComplete'] + $laserRouteInfo['qtyScrap'];
		if( $updateQtyComplete >= $laserRouteInfo['qtyOperation']){
			$updatePercentComplete = '100';
			$updateQtyToGo = '0';
			$updateQtyComplete = $laserRouteInfo['qtyOperation'];

			$updateDateCompletedString = ", FCOMP_DATE=getdate(), FNSH_DATE=getdate()";

			if($lastRoutingOp === $laserRouteInfo['operation']) {
				$this->database->query("UPDATE JOMAST SET fstatus='COMPLETED' WHERE FJOBNO=? ", array($row['job']));
			}
		}else{
			$updatePercentComplete = round((($laserRouteInfo['qtyComplete'] + $laserRouteInfo['qtyScrap']) / $laserRouteInfo['qtyOperation']) * 100,2);
			$updateQtyToGo = $laserRouteInfo['qtyOperation'] -  $updateQtyComplete;

			$updateDateCompletedString = "";
		}

		$updateJobRoutingRecordSql = "UPDATE jodrtg SET
			FLASTLAB=getdate(),
			FNPCT_COMP={$updatePercentComplete},
			FNQTY_COMP={$updateQtyComplete},
			FNQTY_TOGO={$updateQtyToGo},
			FLEAD_TIM=FLEAD_TIM+{$productionTimeTotal},
			FPROD_TIM=FPROD_TIM+{$productionTimeTotal},
			FPROD_VAL=FPROD_VAL+{$costLabor}
			{$updateDateCompletedString}
			WHERE
			fjobno='{$row['job']}' and foperno='{$laserRouteInfo['operation']}'";

		$this->database->query($updateJobRoutingRecordSql);

		return $row;
	}

	private function printLabels($partsToPrint,$printerName,$logTable){
		$labelData = array();
		foreach($partsToPrint as $part){

			for($i=0;$i<$part['qty'];$i++) {

				if(isset($part["jobQtyRemaining"]) ){ // jobQtyRemaining will only exist if this job has Extra Parts

					if($part["jobQtyRemaining"] > 0){
						$part["extraPart"] = "";
						$part["jobQtyRemaining"]--;
					}else{
						$part["extraPart"] = "EXTRA PART";
					}

				}else{
					$part["extraPart"] = "";
				}

				if($part["extraPart"] === ""){
					$this->database->insert('fwe_dev..laserPartBarcodes', array('jobOrderId' => $part['jomastIdentityColumn'], 'aimsSheetId' => $part['sheetId'], 'insertDate' => 'getDate()'));
					$insertResults = $this->database->fetch_assoc("select '*".self::$laserBarcodePrefix."'+cast(@@IDENTITY as varchar(max))+'*' as barcode");
					$part['barcode'] = $insertResults['barcode'];
				}else{
					$part['barcode'] = " ";

					$part["topLevelPart"] = " ";
					$part["deliverTo"] = " ";
					$part["partAndParentRouting"] = " ";
				}

				$part['datePrinted'] = 'getdate()';
				$labelData[] = $part;
			}
		}

		//insert reprint data
		$cleanedLabelData = array();
		foreach($labelData as $label){
			//table $logTable must match the array $label, jobQtyRemaining is not in this label
			unset($label["jobQtyRemaining"]);
			//TODO rather than unset, build array of exact columns in table

			//NOTE! substr will fail if these are empty strings, so we make them 1 space character above
			$label['partAndParentRouting'] = substr($label['partAndParentRouting'], 0, 255);
			$label['deliverTo'] = substr($label['deliverTo'], 0, 255);

			$this->database->insert($logTable, $label);

			//don't print labels for fillers
			if(isset($label['filler']) && $label['filler'] === 1){
				continue;
			}
			$cleanedLabelData[] = $label;
		}

		//if a sheet is only fillers, there will be no labels to print
		if(count($cleanedLabelData) < 1){
			return true;
		}

		$fp = new FoxProRunReport(array(
			'mssqlHelperInstance'   => $this->database,
			'printerName'           => $printerName,
			'template'              => 'laserPartLabel.frx',
			'cleanupTempTable'      => true,
			'printJobName'          => "Aims Sheet {$partsToPrint[0]['sheetId']}",
			'data'                  => $cleanedLabelData
		));
		$fp->runReport();
		return $fp->tableName;
	}

	function printLabelsWaterjet($partsToPrint,$printerName){
		$labelData = array();
		foreach($partsToPrint as $part){
			for($i=0;$i<$part['qty'];$i++) {

				if(isset($part["jobQtyRemaining"]) ){ // jobQtyRemaining will only exist if this job has Extra Parts

					if($part["jobQtyRemaining"] > 0){
						$part["extraPart"] = "";
						$part["jobQtyRemaining"]--;
					}else{
						$part["extraPart"] = "EXTRA PART";
					}

				}else{
					$part["extraPart"] = "";
				}

				if($part["extraPart"] === ""){
					$this->database->insert('fwe_dev..laserPartBarcodes', array('jobOrderId' => $part['jomastIdentityColumn'], 'aimsSheetId' => null, 'insertDate' => 'getDate()'));
					$insertResults = $this->database->fetch_assoc("select '*".self::$laserBarcodePrefix."'+cast(@@IDENTITY as varchar(max))+'*' as barcode");
					$part['barcode'] = $insertResults['barcode'];
				}else{
					$part['barcode'] = " ";

					$part["topLevelPart"] = " ";
					$part["deliverTo"] = " ";
					$part["partAndParentRouting"] = " ";
				}

				$currentLabel = array(
					'sheetUtilization' => $part['sheetUtil'],
					'sheetTimeToCut' => $part['sheetTimeToCut'],
					'sheetTimeCutStarted' => $part['started'],
					'sheetSize' => $part['sheetSize'],
					'materialName' => $part['material'],
					'materialThickness' => $part['thickness'],
					'waterjetId' => $part['id'],
					'part' => $part['part'],
					'job' => $part['job'],
					'qty' => $part['qty'] ,
					'jomastIdentityColumn' => $part['jomastIdentityColumn'],
					'partDescription' =>$part['partDescription'],
					'hot' => $part['hot'],
					'topLevelPart' => $part['topLevelPart'],
					'deliverTo' => substr($part['deliverTo'], 0, 255),
					'partAndParentRouting' => substr($part['partAndParentRouting'], 0, 255),
					'extraPart' => $part['extraPart'],
					'barcode' => $part['barcode'],
					'filename' => $part['fileName'],
					'datePrinted' => 'getdate()'
				);
				$labelData[$part['fileName']][] = $currentLabel;

				$this->database->insert('fwe_dev..waterjetPartLabels', $currentLabel);
			}
		}

		$output = array();
		foreach($labelData as $filename => $labelSheet){
			$output[] = $filename;
			$fp = new FoxProRunReport(array(
				'mssqlHelperInstance'   => $this->database,
				'printerName'           => $printerName,
				'template'              => 'waterjetpartlabel.frx',
				'cleanupTempTable'      => true,
				'printJobName'          => "Waterjet File {$labelSheet[0]['filename']}",
				'data'                  => $labelSheet
			));
			$fp->runReport();
		}
		return $output;
	}

	/**
	 * @param sqlsrv_helper $dbc
	 * @param $job
	 * @return int
	 */
	public static function getPrintedLabelCount($dbc,$job){
		$barcodeData = $dbc->fetch_all_assoc("
			SELECT
			b.laserPartBarcode barcode
			FROM fwe_dev..laserPartBarcodes b
			JOIN jomast jo ON jo.identity_column = b.jobOrderId
			WHERE jo.fjobno = ?
			AND isnull(b.reprinted,0) != 1
		",array($job));

		return count($barcodeData);
	}

	/**
	 * @param $job
	 * @param $qty
	 * @param $printer
	 *
	 * @throws AgileUserMessageException
	 * @throws Exception
	 */
	public function reprintLabel($job, $qty, $printer){
		$barcodeData = $this->database->fetch_all_assoc("
			SELECT
			jo.identity_column jobId,
			jo.fpartno part,
			joi.fdesc partDescription,
			jo.fjobno job,
			joe.HOT hot,
			isnull(inme.RTGTO,'') defaultRoute,
			jo.FSCHBEFJOB parentJob
			FROM fwe_dev..laserPartBarcodes b
			JOIN jomast jo ON jo.identity_column = b.jobOrderId
			JOIN jomast_ext joe ON jo.identity_column=joe.fkey_id
			JOIN JOITEM joi ON jo.FJOBNO=joi.FJOBNO
			LEFT JOIN INMAST inm on inm.fpartno=jo.fpartno and inm.frev=jo.fpartrev
			LEFT JOIN INMAST_EXT inme on inme.FKey_ID=inm.identity_column
			WHERE jo.fjobno = ?
			AND isnull(b.reprinted,0) != 1
		",array($job));

		if($qty > count($barcodeData)){
			throw new AgileUserMessageException("Cannot reprint more than ".count($barcodeData)." labels for job: ".$job);
		}

		$jobData = $barcodeData[0];

		$labelRecord = array(
			'job' => $jobData['job'],
			'part' => $jobData['part'],
			'partDescription' => $jobData['partDescription'],
			'hot' => $jobData['hot'],
			'defaultRoute' => $jobData['defaultRoute'],
			'parentJob' => $jobData['parentJob'],
			'topLevelPart' => $this->getTopLevelPart($jobData['job']),
			'partAndParentRouting' => $this->getPartAndParentRouting($jobData['job'],$jobData['parentJob']),
			'extraPart' => ''
		);

		$TopLevelRoutings = implode(" ",$this->getTopLevelRoutings($jobData['job']));
		//if there's no default route, use the top level job routings
		if($labelRecord['defaultRoute'] == '') {
			$labelRecord['deliverTo'] = $TopLevelRoutings;
		}else{
			$labelRecord['deliverTo'] = $labelRecord['defaultRoute'];
		}

		$printData = array();
		for($i=0;$i< $qty; $i++){
			$this->database->insert('fwe_dev..laserPartBarcodes', array('jobOrderId' => $jobData['jobId'], 'aimsSheetId' => 0, 'reprinted' => 1, 'insertDate' => 'getDate()'));
			$insertResults = $this->database->fetch_assoc("select '*".self::$laserBarcodePrefix."'+cast(@@IDENTITY as varchar(max))+'*' as barcode");
			$labelRecord['barcode'] = $insertResults['barcode'];
			$printData[] = $labelRecord;
		}

		$fp = new FoxProRunReport(array(
			'mssqlHelperInstance'   => $this->database,
			'printerName'           => $printer,
			'template'              => 'laserPartLabelNoHeaderFooter.frx',
			'cleanupTempTable'      => true,
			'printJobName'          => "Laser Reprint ".$jobData['job'],
			'data'                  => $printData
		));

		$fp->runReport();
	}

	/**
	 * @param sqlsrv_helper $dbc
	 * @param $job
	 * @param $operationNo
	 * @return array|false|null
	 */
	static function getSubAssemblyLabelInfo($dbc, $job, $operationNo){
		return $dbc->fetch_assoc("
			SELECT
			rtrim(iwe.ShopLabels) as ShopLabels,
			rtrim(iwe.shopLabelPr) as ShopLabelsPrinter,
			fcpro_name as workcenter
			FROM inwork iw
			JOIN INWORK_EXT iwe ON iw.identity_column = iwe.FKey_ID
			JOIN jodrtg on iw.fcpro_id = jodrtg.fpro_id
			WHERE jodrtg.fjobno = ?
			AND jodrtg.foperno = ?
			AND (iwe.ShopLabels = 'ONEPER' OR iwe.ShopLabels = 'BULK')
			",array($job,$operationNo));
	}

	//Returns true if filler
	function isAimsNoJobFillerPart(&$part)
	{
		$job = $part['ExternalOrderID'];
		//Is this an AIMS filler job number?
		if (!(strlen($job) === 10 && strpos($job, "-") === 5)) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $job
	 * @param string $operationNo
	 * @param string $qty
	 * @param string $printer
	 * @param bool $hugeJobNoBarcode
	 * @throws AgileUserMessageException
	 */
	function printSubAssemblyLabel($job, $operationNo, $qty, $printer){

		$jobData = $this->database->fetch_assoc("
			SELECT
			jo.identity_column jomastIdentityColumn,
			jo.fpartno part,
			joi.fdesc partDescription,
			jo.fjobno job,
			joe.HOT hot,
			isnull(inme.RTGTO,'') defaultRoute,
			jo.FSCHBEFJOB parentJob
			FROM inwork iw
			JOIN INWORK_EXT iwe ON iw.identity_column = iwe.FKey_ID
			JOIN jodrtg on iw.fcpro_id = jodrtg.fpro_id
			JOIN jomast jo ON jo.fjobno = jodrtg.fjobno
			JOIN jomast_ext joe ON jo.identity_column=joe.fkey_id
			JOIN JOITEM joi ON jo.FJOBNO=joi.FJOBNO
			LEFT JOIN INMAST inm on inm.fpartno=jo.fpartno and inm.frev=jo.fpartrev
			LEFT JOIN INMAST_EXT inme on inme.FKey_ID=inm.identity_column
			WHERE jo.fjobno = ?
			AND jodrtg.foperno = ?
			AND (iwe.ShopLabels = 'ONEPER' OR iwe.ShopLabels = 'BULK')
			",array($job,$operationNo));

		if($jobData == null){
			throw new AgileUserMessageException("Job Not Found");
		}

		$jobData['topLevelPart'] = $this->getTopLevelPart($job);
		$TopLevelRoutings = implode(" ",$this->getTopLevelRoutings($job));

		//if there's no default route, use the top level job routings
		if($jobData['defaultRoute'] == '') {
			$jobData['deliverTo'] = $TopLevelRoutings;
		}else{
			$jobData['deliverTo'] = $jobData['defaultRoute'];
		}

		$jobData['partAndParentRouting'] = $this->getPartAndParentRouting($job,$jobData['parentJob']);

		$printData = array();

		for($i=0;$i< $qty; $i++) {

			$this->database->insert('fwe_dev..laserPartBarcodes', array('jobOrderId' => $jobData['jomastIdentityColumn'], 'aimsSheetId' => '0','insertDate' => 'getDate()'));
			$insertResults = $this->database->fetch_assoc("select '*" . self::$laserBarcodePrefix . "'+cast(@@IDENTITY as varchar(max))+'*' as barcode");
			$jobData['barcode'] = $insertResults['barcode'];
			$jobData['extraPart'] = '';

			$printData[] = $jobData;
		}

		$fp = new FoxProRunReport(array(
			'mssqlHelperInstance'   => $this->database,
			'printerName'           => $printer,
			'template'              => 'laserPartLabelNoHeaderFooter.frx',
			'cleanupTempTable'      => true,
			'printJobName'          => "Sub Assembly Label ".$job,
			'data'                  => $printData
		));

		$fp->runReport();
	}
}
