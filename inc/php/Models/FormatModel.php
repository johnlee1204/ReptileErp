<?php

/*
 * This model is used by other models that use jake's new model field format.
 * This model contains a bunch of data formatter functions that read the field configs
 */
class FormatModel {

	 static function formatDatabaseInput($columnData,$value){
		if(isset($columnData['type']) && $columnData['type'] == 'numeric'){
			if(trim($value) == '' && (!isset($columnData['nulls']) || $columnData['nulls'])){
				return null;
			}else{
				return floatval($value);
			}
		}
		return $value;
	}

	static function formatAllCaps($columnData,$value){
		if(!isset($columnData['upperCase']) || $columnData['upperCase']){
			$value = strtoupper($value);
		}
		return $value;
	}

	/**
	 * @param $date
	 *
	 * @return bool
	 */
	static function nullDateCheck($date){
		if($date == NULL || FALSE !== strpos($date,'1900')){
			return true;
		}
		return false;
	}

	/**
	 * @param $results
	 * @param $columnsArray
	 *
	 * @return array
	 */
	 static function formatQueryOutput($results, $columnsArray){
		if($results === NULL){
			return NULL;
		}

		foreach($results as $column => $value){
			if(isset($columnsArray[$column]['smartTruncate']) && $columnsArray[$column]['smartTruncate']){
				$results[$column] = SmartTruncate::truncate($results[$column]);
			}
			if(isset($columnsArray[$column]['type']) && $columnsArray[$column]['type'] == 'date'){
				if(FormatModel::nullDateCheck($results[$column])){
					$results[$column] = null;
				}else{
					$results[$column] = date('Y-m-d',strtotime($results[$column]));
				}
			}
			if(isset($columnsArray[$column]['type']) && $columnsArray[$column]['type'] == 'datetime'){
				if(FormatModel::nullDateCheck($results[$column])){
					$results[$column] = null;
				}else{
					$results[$column] = date('Y-m-d H:i:s',strtotime($results[$column]));
				}
			}
			if(isset($columnsArray[$column]['type']) && $columnsArray[$column]['type'] == 'dataCheckTimestamp'){
				$results[$column] = "0x".strtoupper(bin2hex($results[$column]));
			}
			if(isset($columnsArray[$column]['type']) && $columnsArray[$column]['type'] == 'checkbox'){
				if($results[$column] == null || $results[$column] == ''){
					$results[$column] = 0;
				}
			}
			if(isset($columnsArray[$column]['formatFunction'])){
				$results[$column] = $columnsArray[$column]['formatFunction']($results[$column]);
			}
			$results[$column] = trim($results[$column]);
		}

		return $results;
	}

	/**
	 * @param $value
	 * @return string
	 *
	 * This function will convert a number to show a minimum of 2 decimal places
	 * Example - Dave called me asking why his $5.50 entry changed to $5.5 (It was SmartTruncate)
	 * Without SmartTruncate, the M2M field had a precision of 5, so it showed up like $5.50000, which also looks really weird.
	 * This little function will show dollar amounts in a nicer way, while still "smart truncating" where necessary
	 * 5.5 -> 5.50
	 * 5.000000 -> 5.00
	 * 0 -> 0.00
	 * Null or blank string -> ''
	 * (some fields in M2M with low precision will still round your number if you have more than it's defined precision)
	 */
	static function formatDollars($value){
		if($value === NULL || strlen($value) === 0){
			return '';
		}else{
			$value = SmartTruncate::truncate($value);
			$splitNumber = explode(".",$value);
			if(count($splitNumber) === 2){
				return $splitNumber[0].'.'.str_pad($splitNumber[1],2,"0");
			}else{
				return $splitNumber[0].'.00';
			}
		}
	}
}