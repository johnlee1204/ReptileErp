<?php

class Validation
{
	/**
	 * @deprecated use validateData instead
	 */
	static function validate($validationSpec, $dataToValidate){
		return Validation::validateData($validationSpec, $dataToValidate);
	}

	/**
	 * @param $validationSpec
	 * @return array
	 * @throws AgileUserMessageException
	 */
	static function validateJsonInput($validationSpec){
		$rawData = file_get_contents("php://input");
		if(NULL ===$decodedJson = json_decode($rawData,true)){
			throw new Exception('json_decode on input failed');
		}
		return Validation::validateData($validationSpec, $decodedJson);
	}

	/**
	 * @param array $validationSpec
	 *
	 * @return array
	 * @throws Exception
	 * @throws AgileUserMessageException
	 */
	static function validatePost($validationSpec){
		return Validation::validateData($validationSpec, $_POST);
	}

	/**
	 * @param array $validationSpec
	 *
	 * @return array
	 * @throws Exception
	 * @throws AgileUserMessageException
	 */
	static function validateGet($validationSpec){
		return Validation::validateData($validationSpec, $_GET);
	}
	/**
	 * @param array $validationSpec
	 * @param array $data
	 *
	 * @return array
	 * @throws Exception
	 * @throws AgileUserMessageException
	 */
	static function validateData($validationSpec, $data){
		$validationResult = Validation::runValidationSpec($validationSpec, $data);
		if($validationResult['pass'] === TRUE ){
			return $validationResult['valueArray'];
		}
		throw new AgileUserMessageException(implode("<br />", $validationResult['errorArray']));
	}
	static function runValidationSpec($validationSpec, $data){

		if(!is_array($data)){
			throw new Exception("Validation failed, data is not an array");
		}

		$validationErrors = array();
		$validatedData = array();

		foreach ($validationSpec as $param => $paramConfig) {

			//If no array association (Param configs), only the existence of the data key is checked
			if(is_int($param)){ //This is an array index because it's not an assoc key.
				$param = $paramConfig;
				$paramConfig = array('tests' => array());
			}
			//If you just put a single test string as the config string, we'll just do that.
			//Example 'EmployeeId' => 'numeric'
			if(!is_array($paramConfig)){
				$paramConfig = array('tests' => $paramConfig);
			}

			if(!array_key_exists($param, $data)){
				//Param was not set and has a default.
				if(key_exists('default',$paramConfig)){
					$validatedData[$param] = $paramConfig['default'];
					continue;
				}
				if(key_exists('optional',$paramConfig)){
					continue;
				}
				$validationErrors[] = $param . ' is required';
				continue;
			}
			if(!isset($paramConfig['tests'])) {
				$paramConfig['tests'] = array();
			}
			if (!is_array($paramConfig['tests'])) {
				$paramConfig['tests'] = array($paramConfig['tests']);
			}
			if(count($paramConfig['tests']) < 1){
				$validatedData[$param] = $data[$param];
			}else{
				foreach ($paramConfig['tests'] as $test) {
					$testMethod = 'test_' . $test;
					if (method_exists(get_class(), $testMethod)) {
						$testValidation = Validation::$testMethod($param, $paramConfig, $data[$param]);

						if($testValidation['pass'] === true){
							$validatedData[$param] = $testValidation['value'];
						}else{
							$validationErrors[] = $testValidation['error'];
						}
					} else {
						throw new Exception("Validation error, test does not exist: {$testMethod}");
					}
				}
			}

		}
		if (count($validationErrors) > 0) {
			return array(
				'pass'=>false,
				'errorArray'=>$validationErrors
			);
		}

		return array(
			'pass'=>true,
			'valueArray'=>$validatedData
		);
	}

	private static function getValidationPassArray($value){
		return array(
			'pass'=>true,
			'value'=>$value
		);
	}
	private static function getValidationErrorArray($errorMsg){
		return array(
			'pass'=>false,
			'error'=>$errorMsg
		);
	}

	private static function test_barcode($paramName,$paramConfig,$value){
		if(!isset($paramConfig['prefix'])){
			throw new Exception("Test is missing prefix config with array of allowed prefixes");
		}
		if(strlen($value) < 4){
			return Validation::getValidationErrorArray($paramName . ' invalid length');
		}

		$prefix = substr($value,0,3);
		if(!in_array($prefix, $paramConfig['prefix']) ){
			return Validation::getValidationErrorArray($paramName . ' did not have valid prefix ('.implode(', ',$paramConfig['prefix']).')');
		}

		$barcodeValue = substr($value,3);

		//special double cast checks for leading zeros and decimals
		$castValue = (string)(int)$barcodeValue;
		if($castValue !== $barcodeValue){
			return Validation::getValidationErrorArray($paramName . ' barcode not numeric');
		}

		$intVal = (int)$barcodeValue;
		if($intVal <= 0 || $intVal >= MaxSqlInt::SqlMaxIntValue){
			return Validation::getValidationErrorArray($paramName . ' must be > 0 and < '.MaxSqlInt::SqlMaxIntValue);
		}
		return Validation::getValidationPassArray([
			'barcodePrefix' => $prefix,
			'barcodeValue' => $barcodeValue,
			'barcode' => $value
		]);
	}

	private static function test_sqlmaxint($paramName,$paramConfig,$value){
		if(MaxSqlInt::checkValidInt($value)){
			return Validation::getValidationPassArray($value);
		}
		return Validation::getValidationErrorArray($paramName . ' must be less than ' . MaxSqlInt::SqlMaxIntValue);
	}

	private static function test_trim($paramName,$paramConfig,$value){
		return Validation::getValidationPassArray(trim($value));
	}
	private static function test_pageStart($paramName,$paramConfig,$value){
		return self::test_integer($paramName,array('greaterThanOrEqualTo'=>0),$value);
	}
	private static function test_pageLimit($paramName,$paramConfig,$value){
		return self::test_integer($paramName,array('greaterThanOrEqualTo'=>1),$value);
	}
	private static function test_integer($paramName,$paramConfig,$value){
		$value = trim($value);

		if( filter_var($value, FILTER_VALIDATE_INT) === false ){
			return Validation::getValidationErrorArray($paramName . ' must be integer');
		}
		if (isset($paramConfig['equalTo']) && $value != $paramConfig['equalTo']) {
			return Validation::getValidationErrorArray($paramName . ' must be '.$paramConfig['equalTo']);
		}
		if (isset($paramConfig['notEqualTo']) && $value == $paramConfig['notEqualTo']) {
			return Validation::getValidationErrorArray($paramName . ' must not be '.$paramConfig['notEqualTo']);
		}
		if (isset($paramConfig['lessThan']) && !($value < $paramConfig['lessThan'])) {
			return Validation::getValidationErrorArray($paramName . ' must be less than '.$paramConfig['lessThan']);
		}
		if (isset($paramConfig['greaterThan']) && !($value > $paramConfig['greaterThan'])){
			return Validation::getValidationErrorArray($paramName . ' must be greater than '.$paramConfig['greaterThan']);
		}
		if (isset($paramConfig['lessThanOrEqualTo']) && !($value <= $paramConfig['lessThanOrEqualTo'])){
			return Validation::getValidationErrorArray($paramName . ' must be less than or equal to '.$paramConfig['lessThanOrEqualTo']);
		}
		if (isset($paramConfig['greaterThanOrEqualTo']) && !($value >= $paramConfig['greaterThanOrEqualTo'])){
			return Validation::getValidationErrorArray($paramName . ' must be greater than or equal to '.$paramConfig['greaterThanOrEqualTo']);
		}
		return Validation::getValidationPassArray($value);
	}
	private static function test_numeric($paramName,$paramConfig,$value){
		$value = trim($value);

		if (!is_numeric($value)){
			return Validation::getValidationErrorArray($paramName . ' must be numeric');
		}
		if (isset($paramConfig['equalTo']) && $value != $paramConfig['equalTo']) {
			return Validation::getValidationErrorArray($paramName . ' must be '.$paramConfig['equalTo']);
		}
		if (isset($paramConfig['notEqualTo']) && $value == $paramConfig['notEqualTo']) {
			return Validation::getValidationErrorArray($paramName . ' must not be '.$paramConfig['notEqualTo']);
		}
		if (isset($paramConfig['lessThan']) && !($value < $paramConfig['lessThan'])) {
			return Validation::getValidationErrorArray($paramName . ' must be less than '.$paramConfig['lessThan']);
		}
		if (isset($paramConfig['greaterThan']) && !($value > $paramConfig['greaterThan'])){
			return Validation::getValidationErrorArray($paramName . ' must be greater than '.$paramConfig['greaterThan']);
		}
		if (isset($paramConfig['lessThanOrEqualTo']) && !($value <= $paramConfig['lessThanOrEqualTo'])){
			return Validation::getValidationErrorArray($paramName . ' must be less than or equal to '.$paramConfig['lessThanOrEqualTo']);
		}
		if (isset($paramConfig['greaterThanOrEqualTo']) && !($value >= $paramConfig['greaterThanOrEqualTo'])){
			return Validation::getValidationErrorArray($paramName . ' must be greater than or equal to '.$paramConfig['greaterThanOrEqualTo']);
		}
		return Validation::getValidationPassArray($value);
	}

	private static function test_numericOrNull($paramName, $paramConfig, $value){
		if(trim($value) === ''){
			return Validation::getValidationPassArray(null);
		} else {
			return Validation::test_numeric($paramName, $paramConfig, $value);
		}
	}

	private static function test_trueFalse($paramName, $paramConfig, $value){
		if($value === true || strtoupper($value) === "TRUE"){
			return Validation::getValidationPassArray(true);
		}
		if($value === false || strtoupper($value) === "FALSE"){
			return Validation::getValidationPassArray(false);
		}

		return Validation::getValidationErrorArray($paramName . ' must be "true" or "false"');
	}

	private static function test_checkBox($paramName,$paramConfig,$value){
		if ($value === 1 || $value === '1' || $value === 0 || $value === '0'){
			return Validation::getValidationPassArray(intval($value));
		}
		return Validation::getValidationErrorArray($paramName . ' must be 1 or 0');
	}

	private static function test_notBlank($paramName,$paramConfig,$value){
		$value = trim($value);
		if(strlen($value) == 0){
			return Validation::getValidationErrorArray($paramName . ' cannot be blank');
		}
		return Validation::getValidationPassArray($value);
	}

	private static function test_inArray($paramName,$paramConfig,$value){
		if(!isset($paramConfig['options'])){
			throw new Exception('options parameter not set for inArray validation!');
		}
		if(!in_array($value,$paramConfig['options']) ){
			return Validation::getValidationErrorArray($paramName . ' must be one of ['.join(', ',$paramConfig['options']).']');
		}
		return Validation::getValidationPassArray($value);
	}

	private static function test_length($paramName, $paramConfig, $value){
		$value = trim($value);
		if (isset($paramConfig['length']) && strlen($value) != $paramConfig['length']) {
			return Validation::getValidationErrorArray($paramName . ' must be '.$paramConfig['length'].' characters');
		}
		if (isset($paramConfig['minLength']) && strlen($value) < $paramConfig['minLength']) {
			return Validation::getValidationErrorArray($paramName . ' must be at least '.$paramConfig['minLength'].' characters');
		}
		if (isset($paramConfig['maxLength']) && strlen($value) > $paramConfig['maxLength']) {
			return Validation::getValidationErrorArray($paramName . ' must be no more than '.$paramConfig['maxLength'].' characters');
		}
		return Validation::getValidationPassArray($value);
	}

	private static function test_json($paramName,$paramConfig,$value){
		if(null === $decoded = json_decode($value,true)){
			return Validation::getValidationErrorArray($paramName . ' must be valid json. error: '.json_last_error());
		}
		if(isset($paramConfig['jsonTests'])){

			$validationResult = Validation::runValidationSpec($paramConfig['jsonTests'], $decoded);
			if($validationResult['pass'] === true){
				return Validation::getValidationPassArray($validationResult['valueArray']);
			}else{
				return Validation::getValidationErrorArray(join("<br \>", $validationResult['errorArray']));
			}
		}
		return Validation::getValidationPassArray($decoded);
	}

	private static function test_jsonArray($paramName, $paramConfig, $value){
		if(null === $decoded = json_decode($value,true)){
			return Validation::getValidationErrorArray($paramName . ' must be valid json');
		}

		if(!is_array($decoded)){
			return Validation::getValidationErrorArray($paramName . ' must be valid array');
		}
		//empty array is invalid?
		if(count($decoded) < 1){
			return Validation::getValidationErrorArray($paramName . ' must have at least one row.');
		}

		$validatedData = array();
		$validationErrors = array();
		if(isset($paramConfig['arrayTests'])){

			foreach($decoded as $record){
				$validationResult = Validation::runValidationSpec($paramConfig['arrayTests'], $record);
				if($validationResult['pass'] === true){
					$validatedData[] = $validationResult['valueArray'];
				}else{
					$validationErrors = array_merge($validationErrors, $validationResult['errorArray']);
				}
			}
		}else{
			$validatedData = $decoded;
		}

		if (count($validationErrors) > 0) {
			return Validation::getValidationErrorArray(join("<br \>", $validationErrors));
		}
		return Validation::getValidationPassArray($validatedData);
	}

	private static function test_jobLength($paramName,$paramConfig,$value){
		$job = trim($value);
		$jobLen = strlen($job);
		if($jobLen === 5 ){
			$job = $job . '-0000';
		} else if($jobLen !== 10){
			return Validation::getValidationErrorArray($paramName.' must be 5 or 10 characters');
		}
		return Validation::getValidationPassArray($job);
	}

	private static function test_dateYmd($paramName,$paramConfig,$value){

		if(FALSE === date_create_from_format('Y-m-d',$value)){
			return Validation::getValidationErrorArray($paramName.' is not a valid Y-M-D date');
		}
		return Validation::getValidationPassArray($value);
	}
	private static function test_email($paramName,$paramConfig,$value){
		$email = trim($value);
		if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
			return Validation::getValidationErrorArray($paramName.' is not a valid email address');
		}
		return Validation::getValidationPassArray($email);
	}

	private static function test_arrayOfIds($paramName,$paramConfig,$value){
		$err = $paramName.' is not an array of ints';
		if(!is_array($value)){
			return Validation::getValidationErrorArray($err);
		}
		foreach($value as $int){
			if(!is_int($int) || $int < 0 || !MaxSqlInt::checkValidInt($int)){
				return Validation::getValidationErrorArray($err);
			}
		}
		return Validation::getValidationPassArray($value);
	}

	/**
	 * You need to be using "Jake's field model" format for this function to be useful to you.
	 * Take a look at Service or Project for an example.
	 *
	 * @param $columns
	 * @param $filter
	 *
	 * @return array
	 * @throws Exception
	 */
	static function generateValidationSpec($columns, $filter){
		$spec = array();
		foreach($columns as $field => $columnData){
			switch($filter){
				case Agile\Column::REQUIRED_DELETE:
					if(isset($columnData[$filter]) && $columnData[$filter]){
						$spec[$field] = Validation::setValidationForField($columnData);
					}
					break;
				case Agile\Column::REQUIRED_CREATE:
				case Agile\Column::REQUIRED_UPDATE:
					if(!isset($columnData[$filter]) || $columnData[$filter]){
						$spec[$field] = Validation::setValidationForField($columnData);
					}
					break;
			}

		}
		return $spec;
	}

	static function generateSearchValidationSpec($columns){
		$spec = array();
		foreach($columns as $field => $columnData){
			if(!isset($columnData['searchable']) || $columnData['searchable']){
				$fieldValidation = Validation::setValidationForField($columnData);
				$fieldValidation['default'] = null;
				$spec[$field] = $fieldValidation;
			}
		}
		return $spec;
	}

	private static function setValidationForField($columnData){
		$specItem = array();
		if(!isset($columnData['type'])){
			$columnData['type'] = 'text';
		}
		switch($columnData['type']){
			case 'numeric':
				if(isset($columnData['maxLength'])){
					$specItem['tests'] = array('numericOrNull', 'length');
					$specItem['maxLength'] = $columnData['maxLength'];
				}else{
					$specItem['tests'] = array('numericOrNull', 'length');
					$specItem['lessThan'] = PHP_INT_MAX;
				}
				break;
			case 'text':
			case 'dataCheckTimestamp':
				if(isset($columnData['maxLength'])) {
					$specItem['tests'] = array('length');
					$specItem['maxLength'] = $columnData['maxLength'];
				}
				break;
			case 'date':
			case 'datetime':
				$specItem['tests'] = array('length');
				$specItem['maxLength'] = 21;
				break;
			case 'checkbox':
				$specItem['tests'] = array('checkbox');
				break;
			default:
				throw new Exception('Unknown Column Type!');
		}
		if(isset($columnData['default'])){
			$specItem['default'][] = $columnData['default'];
		}
		if(isset($columnData['allowBlank']) && !$columnData['allowBlank']){
			$specItem['tests'][] = 'notBlank';
		}
		return $specItem;
	}

}
