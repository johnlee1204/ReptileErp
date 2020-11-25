<?php
	/**
	 * mauiSoapClient is a class for communicating with the Amada AIMS Maui Server. The Maui Server is used to schedule parts to be cut on the laser.
	 * Copyright 2010 Andrew Rymarczyk All Rights Reserved
	 * version 1.0 2010-09-08	 	 
	 */	 
	 
class AimsMauiSoapClient {
	public $client;
	
	function __construct($address){

		ini_set('default_socket_timeout',360); // 5 minute socket connection timeout

		$this->client = new SoapClient(null, array(
			'uri'=>'urn:MauiSoap'
			,'location'=>$address //'http://192.168.100.225:8989'
			,'exceptions'=>false
			,'connection_timeout'=>3

		));
	}
	
	/**
	 * updateDueDate(String $jo, String $dueDate)
	 *   $jo should be in the format: 12345-0012	 
	 * 	 $dueDate should be in the format: 2010-09-08
	 * 	 	 
	 * returns TRUE on success
	 * returns error string on failure.
	 */	 	 	
	
	function updateDueDate($jo = '', $dueDate = ''){
		
		if(trim($jo) == '' || trim($dueDate) == '' ){
			return 'missing required parameters!';
		}
		$dueDate = substr($dueDate, 0, 10).' 00:00:00';
		
		$result = $this->client->SetFeatureValue( 
			new SoapVar(0,XSD_INT, null, null, 'SessionID') 
			,new SoapVar('AppName = MRPApp; Version = 2.2.12',XSD_STRING, null, null, 'ObjRef') 
			,new SoapVar("LotDueDateCsv = {$jo},{$dueDate},",XSD_STRING, null, null, 'FeatureValueList') //lotscsv = ,,{itemName},
		);
		
		if( is_soap_fault($result) ){
			return 'SOAP client error! '.$result->faultcode.' '.$result->faultstring;
		}
		
		switch($result){
			case 0:
				//no error!
			break;
			case -1:
				return 'Unable to communicate with AIMS';
			break;
			case -3:
				return 'AIMS internal error';
			break;
			default:
				return 'Unknown error #'.$result;
			break;
		}
		
		return TRUE;
	
	}

	/**
	 * getLotAndSheetStatus()
	 *   $jo should be in the format: 12345-0012	 
	 * 	 $dueDate should be in the format: 2010-09-08
	 * 	 	 
	 * returns a data array on success
	 * 	use is_array($returnValue) to check for success
	 * returns error string on failure
	 */	 
	 
	function getLotAndSheetStatus(){
	
		$result = $this->client->GetFeatureValue( 
			new SoapVar(0,XSD_INT, null, null, 'SessionID') 
			,new SoapVar('AppName = MRPApp; Version = 2.2.12',XSD_STRING, null, null, 'ObjRef') 
			,new SoapVar('SheetStatus = All; LotStatus = All; RemoveCompletedLots=False; RemoveCompletedSheets=False',XSD_STRING, null, null, 'FeatureSpecList') 
		);
		
		if( is_soap_fault($result) ){
			return 'SOAP client error! '.$result->faultcode.' '.$result->faultstring;
		}
		
		if(!isset($result['result']) ){
			return 'result data missing';
		}
		
		switch($result['result']){
			case 0:
				//no error!
			break;
			case -1:
				return 'Unable to communicate with AIMS';
			break;
			case -3:
				return 'AIMS internal error';
			break;
			default:
				return 'Unknown error';
			break;
		}
		
		$sheetAndLotData = explode(';', $result['FeatureValueList']);
		$dataSetCount = count($sheetAndLotData); 
		
		if( $dataSetCount < 2 ){
			return 'MAUI SOAP returned the incorrect number of resultsets. Expected 2 separated by a semicolon, '.$dataSetCount.' found.';
		}
		
		return $sheetAndLotData;
	}

}

?>