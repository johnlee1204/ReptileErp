<?php

use Log\Models\LogModel;

class Log extends AgileBaseController {

	public static $AgilePermissions = [
		'index' => 'read',
		'getAppData' => 'read',
		'readApplicationLogMetadata' => 'anonymous',
		'readApplicationLog' => 'anonymous',
		'readExceptionLogs'  => 'read',
		'readAccessLogs'  => 'read',
		'exportToCSV' => 'read',
		'readPHPLog' => 'read',
		'readApplicationLogApps' => 'read',
		'readEmails' => 'read',
		'storePostfixEmail' => 'anonymous',
		'dedupEmailLog' => 'anonymous',
		'logConsoleError' => 'anonymous'
	];
	
	private $errorLog;
	private $logException;
	private $logAccess;

	public function init(){
		$this->logAccess = $this->AgileApp->systemConfigs['table']['logAccess'];
		$this->logException = $this->AgileApp->systemConfigs['table']['logException'];
	}

	public function getAppData(){

		$appsInDir = scandir($_SERVER['DOCUMENT_ROOT'], SCANDIR_SORT_ASCENDING);

		$appOutput = [
			['[ NO CLASS ]',''],
			['[ ALL ]','[ ALL ]']
		];
		foreach($appsInDir as $app){
			if($app === '.' || $app === '..' || is_dir($_SERVER['DOCUMENT_ROOT'].'/'.$app) === false){
				continue;
			}
			$appOutput[] = [$app, $app];
		}

		# Get Exception Log Column Names
		$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'logException'";
		$exceptionColumns = $this->database->fetch_all_row($query);

		# Get Access Log Column Names
		$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'logAccess'";
		$logColumns = $this->database->fetch_all_row($query);

		$this->outputJson([
			'success' => true,
			'exceptionData' => $appOutput,
			'exceptionColumns' => $exceptionColumns,
			'logColumns' => $logColumns,
			'logData' => $appOutput
		]);
	}

	public function readApplicationLogApps(){
		$this->outputSuccess([
			'appLogApps' => LogModel::readApplicationLogApps($this->database)
		]);
	}

	public function readApplicationLogMetadata(){
		$appName = Validation::validatePOST([
			'appName' => ['tests' => 'notBlank']
		]);
		$appName = $appName['appName'];

		if(false === $this->AgileApp->SessionManager->getUserDataFromSession()){
			throw new AgileUserMessageException('You are not logged in!');
		}
		if(!$this->AgileApp->SessionManager->checkSessionPermissionsForAppAction($appName,'read')){
			throw new AgileUserMessageException('You do not have access to view the '.$appName.' Log!');
		}
		$this->outputSuccess(LogModel::readApplicationLogMetadata($appName));
	}

	public function readApplicationLog(){
		$params = Validation::validatePOST(LogModel::readApplicationLogValidation());
		if(false === $this->AgileApp->SessionManager->getUserDataFromSession()){
			throw new AgileUserMessageException('You are not logged in!');
		}
		if(!$this->AgileApp->SessionManager->checkSessionPermissionsForAppAction($params['appName'],'read')){
			throw new AgileUserMessageException('You do not have access to view the '.$params['appName'].' Log!');
		}
		$this->outputSuccess(LogModel::readApplicationLog($this->database,$params));
	}

	public function readExceptionLogs(){
		$params = Validation::validatePOST([
			'appName' => ['tests' => 'notBlank','default' => '[ ALL ]'],
			'dateFrom' => ['tests' => 'notBlank','default' => '1900-01-01'],
			'dateTo' => ['tests' => 'notBlank','default' => date('Y-m-d')],
			'timeFrom' => ['tests' => 'notBlank','default' => '0:00'],
			'timeTo' => ['tests' => 'notBlank','default' => '23:59'],
			'error' => ['test' => 'notBlank', 'default' => 1],
			'exception' => ['tests' => 'numeric','default' => 1],
			'shutdown' => ['tests' => 'numeric','default' => 1],
			'usermsg' => ['tests' => 'numeric','default' => 1],
			'limit' => ['tests' => 'numeric','default' => 25],
			'start' => ['tests' => 'numeric','default' => 0],
			'sort' => ['tests' => 'notBlank','default' => 'date'],
			'dir' => ['tests' => 'notBlank','default' => 'DESC'],
			'searchColumn' => ['tests' => 'trim','default' => ''],
			'searchOperation' => ['tests' => 'trim','default' => ''],
			'searchTerm' => ['tests' => 'trim','default' => '']
		]);

		if(!$this->validateOperation($params['searchOperation'])){
			throw new AgileUserMessageException("Invalid Operation");
		}

		$params['errorTypes'] = [];

		if($params['error']){
			$params['errorTypes'][] = 'error';
		}

		if($params['exception']){
			$params['errorTypes'][] = 'exception';
		}

		if($params['shutdown']){
			$params['errorTypes'][] = 'shutdown';
		}

		if($params['usermsg']){
			$params['errorTypes'][] = 'usermsg';
		}

		$data = $this->queryLogException($params,true);

		$this->outputSuccess([
			'totalRows' => $data['total'],
			'limit' => $params['limit'],
			'data' => $data['data']
		]);
	}

	public function readAccessLogs(){
		$params = Validation::validatePOST([
			'appName' => ['tests' => 'notBlank','default' => '[ ALL ]'],
			'dateFrom' => ['tests' => 'notBlank','default' => '1900-01-01'],
			'dateTo' => ['tests' => 'notBlank','default' => date('Y-m-d')],
			'timeFrom' => ['tests' => 'notBlank','default' => '0:00'],
			'timeTo' => ['tests' => 'notBlank','default' => '23:59'],
			'limit' => ['tests' => 'numeric','default' => 25],
			'start' => ['tests' => 'numeric','default' => 0],
			'sort' => ['tests' => 'notBlank','default' => 'date'],
			'dir' => ['tests' => 'notBlank','default' => 'DESC'],
			'searchColumn' => ['tests' => 'trim','default' => ''],
			'searchOperation' => ['tests' => 'trim','default' => ''],
			'searchTerm' => ['tests' => 'trim','default' => '']
		]);

		if(!$this->validateOperation($params['searchOperation'])){
			throw new AgileUserMessageException("Invalid Operation");
		}

		$data = $this->queryLogAccess($params,true);

		$this->outputSuccess([
			'totalRows' => $data['total'],
			'limit' => $params['limit'],
			'data' => $data['data']
		]);
	}

	public function exportToCSV(){
		$params = Validation::validateGET([
			'tab' => 'notBlank',
			'appName' => ['tests' => 'notBlank','default' => '[ ALL ]'],
			'dateFrom' => ['tests' => 'notBlank','default' => '1900-01-01'],
			'dateTo' => ['tests' => 'notBlank','default' => date('Y-m-d')],
			'timeFrom' => ['tests' => 'notBlank','default' => '0:00'],
			'timeTo' => ['tests' => 'notBlank','default' => '23:59'],
			'error' => ['tests' => 'numeric','default' => 0],
			'exception' => ['tests' => 'numeric','default' => 0],
			'shutdown' => ['tests' => 'numeric','default' => 0],
			'usermsg' => ['tests' => 'numeric','default' => 0],
			'searchColumn' => ['tests' => 'trim','default' => ''],
			'searchOperation' => ['tests' => 'trim','default' => ''],
			'searchTerm' => ['tests' => 'trim','default' => ''],
		]);

		if($params['tab'] === 'exception'){

			if($params['exception']){
				$params['errorTypes'][] = 'error';
			}

			if($params['exception']){
				$params['errorTypes'][] = 'exception';
			}

			if($params['shutdown']){
				$params['errorTypes'][] = 'shutdown';
			}

			if($params['usermsg']){
				$params['errorTypes'][] = 'usermsg';
			}

			$data = $this->queryLogException($params);

			$rowHeaders = ['date','ip','uri','class','method','referrer','httpType','query','loggedIn','userId',
			'userName','get','post','errorMessage','errorLineNumber','errorFile','errorStackTrace','errorExtraData',
			'errorType'];
		}
		else {
			$data = $this->queryLogAccess($params);

			$rowHeaders = ['date','ip','uri','class','method','referrer','httpType',
			'query','loggedIn','userId','userName','notFound','authorized','get','post'];
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename='.$params['appName'].'-'.$params['tab'].'-'.date('m-d-Y_g-i-sa').'.csv');
		$file = fopen('php://output', 'w');
		fputcsv($file,$rowHeaders);
		foreach($data['data'] as $row){
			fputcsv($file,$row);
		}
		fclose($file);
	}

	private function queryLogAccess($params,$paging = false){
		$output = [
			'data' => [],
			'total' => 0
		];

		$select =
		"SELECT
		date,
		ip,
		uri,
		class,
		method,
		referrer,
		httpType,
		query,
		loggedIn,
		userId,
		userName,
		notFound,
		authorized,
		`get`,
		post,
		json";

		if($params['appName'] === '[ ALL ]'){
			$where = "
			Where date >= ?
			AND date < ?";
			$queryParams = [
				$params['dateFrom'].' '.$params['timeFrom'],
				date('Y-m-d',strtotime($params['dateTo'])).' '.$params['timeTo']
			];
		}else{
			$where = "
			Where class = ?
			And date >= ?
			AND date < ?";
			$queryParams = [
				$params['appName'],
				$params['dateFrom'].' '.$params['timeFrom'],
				date('Y-m-d',strtotime($params['dateTo'])).' '.$params['timeTo']
			];
		}


		if(isset($params['searchColumn']) && $params['searchColumn'] !== '' && $params['searchTerm'] !== ''){
			if(in_array($params['searchColumn'], ['loggedIn', 'notFound', 'authorized'])) {
				if($params['searchTerm'] === 'X') {
					$params['searchTerm'] = 1;
				}
			}
			if($params['searchOperation'] === 'like') {
				$where .= " AND {$params['searchColumn']} {$params['searchOperation']} '%'+?+'%'";
			} else {
				$where .= " AND {$params['searchColumn']} {$params['searchOperation']} ?";
			}
			$queryParams[] = $params['searchTerm'];
		}

		if($paging){
			$pageEnd = $params['start']+$params['limit'];

			if($params['sort'] === 'date'){
				$sort = $params['sort']." ".$params['dir'];
			}
			else {
				$sort = $params['sort'] . " " . $params['dir'];
			}

			$query =
			"{$select}
			FROM (
			{$select},
			row_number() over(ORDER BY {$sort}) as rownum
			FROM {$this->logAccess}
			{$where}
			) as tableToPage
			Where rownum > {$params['start']}
			And rownum <= {$pageEnd}
			ORDER BY {$sort}";

			$output['total'] = intval($this->database->fetch_all_row(
				"SELECT count(date) as total FROM {$this->logAccess} ".$where,$queryParams)[0][0]);
		}
		else {
			$query = $select." FROM {$this->logAccess}".$where." ORDER BY date DESC";
		}

		$this->database->query($query,$queryParams);

		$rawData = $this->database->fetch_all_assoc();

		foreach($rawData as $record){
			$tempRecord = [];
			foreach($record as $column => $data){
				if($column === 'loggedIn' || $column === 'notFound' || $column === 'authorized'){
					if($data === 0){
						$data = '';
					}
					else {
						$data = 'X';
					}
				}
				$tempRecord[] = $data;
			}
			$output['data'][] = $tempRecord;
		}

		return $output;
	}

	private function queryLogException($params,$paging = false){
		$output = [
			'data' => [],
			'total' => 0
		];

		$select =
		"SELECT
		date,
		ip,
		uri,
		class,
		method,
		referrer,
		httpType,
		query,
		loggedIn,
		userId,
		userName,
		`get`,
		post,
		json,
		errorMessage,
		errorLineNumber,
		errorFile,
		errorStackTrace,
		errorExtraData,
		errorType";

		if($params['appName'] === '[ ALL ]'){
			$where = "
			Where date >= ?
			AND date < ?";
			$queryParams = [
				$params['dateFrom'].' '.$params['timeFrom'],
				date('Y-m-d',strtotime($params['dateTo'])).' '.$params['timeTo']
			];
		}
		else {
			$where = "
			Where class = ?
			And date >= ?
			AND date < ?";
			$queryParams = [
				$params['appName'],
				$params['dateFrom'].' '.$params['timeFrom'],
				date('Y-m-d',strtotime($params['dateTo'])).' '.$params['timeTo']
			];
		}

		if(isset($params['errorTypes'])){
			if(count($params['errorTypes']) > 0 && count($params['errorTypes']) < 4){
				$where .= " AND errorType In(";
				$first = true;
				foreach($params['errorTypes'] as $type){
					if($first){
						$where .= "?";
						$queryParams[] = $type;
						$first = false;
					}
					else {
						$where .= ",?";
						$queryParams[] = $type;
					}
				}
				$where .= ")";
			}
		}

		if(isset($params['searchColumn']) && $params['searchColumn'] !== '' && $params['searchTerm'] !== ''){
			$where .= " AND {$params['searchColumn']} {$params['searchOperation']} ?";
			$queryParams[] = $params['searchTerm'];
		}

		if($paging){
			$pageEnd = $params['start']+$params['limit'];

			if($params['sort'] === 'errorLineNumber' || $params['sort'] === 'date'){
				$sort = $params['sort']." ".$params['dir'];
			}
			else {
				$sort = $params['sort'] . " " . $params['dir'];
			}

			$query =
			"{$select}
			FROM (
			{$select},
			row_number() over(ORDER BY {$sort}) as rownum
			FROM {$this->logException}
			{$where}
			) as tableToPage
			Where rownum > {$params['start']}
			And rownum <= {$pageEnd}
			ORDER BY {$sort}";

			$output['total'] = intval($this->database->fetch_all_row(
				"SELECT count(date) as total FROM {$this->logException} ".$where,$queryParams)[0][0]);
		}
		else {
			$query = $select." FROM {$this->logException}".$where." ORDER BY date DESC";
		}

		$this->database->query($query,$queryParams);

		$rawData = $this->database->fetch_all_assoc();

		foreach($rawData as $record){
			$tempRecord = [];
			foreach($record as $column => $data){
				if($column === 'loggedIn'){
					if($data === 0){
						$data = '';
					}
					else {
						$data = 'X';
					}
				}
				if($column === 'errorFile'){
					if(strpos($data,'dev.local') !== false){
						$data = substr($data,36);
					}
					else if(strpos($data,'itx166.fwe.com') !== false){
						$data = substr($data,41);
					}
					else {
						$data = substr($data,26);
					}
				}
				$tempRecord[] = $data;
			}
			$output['data'][] = $tempRecord;
		}

		return $output;
	}

	function validateOperation($operation){
		$possibleOperations = ['=','<>','>','<','>=','<=','like'];
		if(!in_array($operation,$possibleOperations) && $operation !== ''){
			return false;
		}
		return true;
	}

	public function storePostfixEmail(){
		$params = Validation::validateJsonInput([
			'SrcIP' => ['tests' => 'notBlank'],
			'SrcHostname' => ['tests' => 'notBlank'],
			'SrcHelo' => ['tests' => 'notBlank'],
			'SrcPort' => ['tests' => 'numeric'],
			'SrcProto' => ['tests' => 'notBlank'],
			'Size' => ['tests' => 'trim', 'default' => "0"],
			'MailServer' => ['tests' => 'notBlank'],
			'PostfixID' => ['tests' => 'notBlank'],
			'PostfixRecipients' => ['tests' => 'notBlank'],
			'To' => ['tests' => 'notBlank'],
			'From' => ['tests' => 'notBlank'],
			'CC' => ['tests' => 'trim', 'default' => ''],
			'BCC' => ['tests' => 'trim', 'default' => ''],
			'SendDate' => ['tests' => 'notBlank'],
			'Subject' => ['tests' => 'notBlank'],
			'Content' => ['tests' => 'notBlank']
		]);

		$params['Content'] = base64_decode($params['Content']);

		$params['SendDate'] = date('Y-m-d H:i:s',  strtotime($params['SendDate'])); 

		$contentHash = md5($params['Content']);

		$this->database->query(
			"INSERT INTO AgileFwe..EmailLog VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
		,[
			$params['SrcIP'],
			$params['SrcHostname'],
			$params['SrcHelo'],
			$params['SrcPort'],
			$params['SrcProto'],
			$params['Size'],
			$params['MailServer'],
			$params['PostfixID'],
			$params['PostfixRecipients'],
			$params['To'],
			$params['From'],
			$params['CC'],
			$params['BCC'],
			$params['SendDate'],
			$params['Subject'],
			$contentHash
		]);

		$filePath = $this->emailLogDir.$contentHash.".eml";

		if(!file_exists($filePath)){
			$file = fopen($filePath, "w");
			fwrite($file, $params['Content']);
			fclose($file);
		}

		$this->outputSuccess();
	}

	function readEmails(){
		$params = Validation::validatePost([
			'limit' => ['tests' => 'numeric','default' => 30],
			'start' => ['tests' => 'numeric','default' => 0],
			'sort' => ['tests' => 'notBlank','default' => 'sendDate'],
			'dir' => ['tests' => 'notBlank','default' => 'DESC']
		]);

		$this->dedupEmailLog();

		$sort = $params['sort']." ".$params['dir'];

		$pageEnd = $params['start'] + $params['limit'];

		$output = $this->database->fetch_all_row(
			"SELECT sendDate,
				subject,
				[from],
				[to],
				CC,
				BCC,
				mailServer,
				srcIp,
				srcHostname,
				srcHelo,
				size,
				contentHash
			FROM (
				SELECT CONVERT(char(16),sendDate,121) AS sendDate,
					subject,
					[from],
					[to],
					CC,
					BCC,
					mailServer,
					srcIp,
					srcHostname,
					srcHelo,
					size,
					contentHash,
					row_number() OVER(ORDER BY {$sort}) AS rownum
					FROM AgileFwe..EmailLog
					) AS tableToPage
			Where rownum > {$params['start']}
			And rownum <= {$pageEnd}
			ORDER BY {$sort}");

		$total = $this->database->fetch_row(
			"SELECT count(*) FROM AgileFwe..EmailLog"
		)[0];

		$this->outputSuccess([
			'totalRows' => $total,
			'limit' => $params['limit'],
			'data' => $output
		]);
	}

	function dedupEmailLog(){
		$data = $this->database->fetch_all_assoc(
			"SELECT id,
			postfixRecipients,
			[to],
			CC,
			BCC,
			contentHash
			FROM (
				SELECT id,
				postfixRecipients,
				[to],
				CC,
				BCC,
				AgileFwe..EmailLog.contentHash
				FROM AgileFwe..EmailLog
				INNER JOIN (
					SELECT contentHash
					FROM AgileFwe..EmailLog
					GROUP BY contentHash
					HAVING count(*) > 1
				) temp
				ON AgileFwe..EmailLog.contentHash = temp.contentHash
			) AS temp2"
		);

		$formattedData = [];

		foreach($data as $row){
			$formattedData[$row['contentHash']][] = [
				'id' => $row['id'],
				'postfixRecipients' => $row['postfixRecipients'],
				'to' => $row['to'],
				'CC' => $row['CC'],
				'BCC' => $row['BCC']
			];
		}

		$idsToDel = [];
		$idsToChange = [];

		foreach($formattedData as $hash => $dups){
			$currentChangeId = 0;
			foreach($dups as $dup){
				if($currentChangeId === 0){
					$currentChangeId = $dup['id'];

					$idsToChange[$currentChangeId] = [
						'id' => $dup['id'],
						'postfixRecipients' => "",
						'to' => $dup['to'],
						'CC' => $dup['CC'],
						'BCC' => ""
					];
				} else {
					$idsToDel[] = $dup['id'];
				}

				$inPostfixRecipients = strpos($idsToChange[$currentChangeId]['postfixRecipients'],$dup['postfixRecipients']);
					
				if($inPostfixRecipients === false){
					if(strlen($idsToChange[$currentChangeId]['postfixRecipients']) > 1){
						$idsToChange[$currentChangeId]['postfixRecipients'] .= ", ".$dup['postfixRecipients'];
					} else {
						$idsToChange[$currentChangeId]['postfixRecipients'] = $dup['postfixRecipients'];
					}
				}

				$inTo = strpos($idsToChange[$currentChangeId]['to'], $dup['postfixRecipients']);
				$inCC = strpos($idsToChange[$currentChangeId]['CC'], $dup['postfixRecipients']);
				$inBCC = strpos($idsToChange[$currentChangeId]['BCC'], $dup['postfixRecipients']);

				if($inTo === false && $inCC === false && $inBCC === false){
					if(strlen($idsToChange[$currentChangeId]['BCC']) > 1){
						$idsToChange[$currentChangeId]['BCC'] .= ", ".$dup['postfixRecipients'];
					} else {
						$idsToChange[$currentChangeId]['BCC'] = $dup['postfixRecipients'];
					}
				}
			}
		}

		if(count($idsToDel) > 0){
			$this->database->query("DELETE FROM AgileFwe..EmailLog WHERE id=".implode(" OR id=", $idsToDel));
		}

		$query = "";
		$queryParams = [];

		foreach($idsToChange as &$row){
			$row['postfixRecipients'] =  array_filter(array_map('trim', explode(',', $row['postfixRecipients'])));
			asort($row['postfixRecipients']);
			$row['postfixRecipients'] = implode(', ', $row['postfixRecipients']);

			$row['BCC'] = array_filter(array_map('trim', explode(',', $row['BCC'])));
			asort($row['BCC']);
			$row['BCC'] = implode(', ', $row['BCC']);

			$query .= " UPDATE AgileFwe..EmailLog SET postfixRecipients = ?, BCC = ? WHERE id = ?;";
			$queryParams[] = $row['postfixRecipients'];
			$queryParams[] = $row['BCC'];
			$queryParams[] = $row['id'];
		}

		$this->database->query($query,$queryParams);
	}
//not being used yet
	function logConsoleError() {
		$inputs = Validation::validateJsonInput([
			'message' => 'notBlank',
			'source' => 'notBlank',
			'lineNumber' => 'notBlank',
			'error' => 'json'
		]);
		$inputs['app'] = str_replace('http://dev.local/','',$inputs['source']);
		$inputs['app'] = substr($inputs['app'],0,strpos($inputs['app'],'/'));
		$message = $inputs['message'] . '<br/> Error on Line: ' . $inputs['lineNumber'] . '<br/>' . implode('<br/>',$inputs['error']);
		Email::send([
			'to' => 'it-dev-alerts@fweco.net',
			'from' => 'Console Error Log',
			'message' => $message,
			'subject' => 'Console Error on ' . $inputs['app']
		]);
		$this->outputSuccess();
	}
}
