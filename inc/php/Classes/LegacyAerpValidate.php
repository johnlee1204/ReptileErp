<?php
/**
 *	aerp_validate is a data validation class. The primary use is for validating $_POST data for direct input into a database.
 * @author Andrew Rymarczyk
 * @version 0.1  
 * @package aerp_validate
 * 
 *
 *array(
 *	 'varName'=>ValidationRuleSet
 *)
 *array(
 *	'varName'=>array(
 *		'required'=>true
 *		,'rules'=>array(
 *			'is_numeric'
 *			,'not_empty'
 *		)
 *		'error':'This value is invalid!'
 *	)
 *)
 * Based of the CakePHP Validation suite http://book.cakephp.org/complete/125/Data-Validation
 * The goal of this class is to provide the ability to validate POST data in any way necessary including:
 * character length (min / max)
 * numeric values
 * integer values
 * alphanumeric
 * no special characters
 * date formats
 * regex validation
 * email    
 *
 */     
//class aerp_validate {
/**
 * 	Check an array for a list of keys. 
 * @param array &$dataArray array of data that is to be validated
 * @param array &$checkKeysArray array of keys that the $dataArray will be checked for
 * @return bool If all $checkKeysArray are found in $dataArray, returns true, otherwise returns false.
 */
 	
	function aerp_validate_check_keys(&$dataArray, &$checkKeysArray){
		foreach( $checkKeysArray as $key ){
			if( !isset($dataArray[$key]) ){
				return false;
			}
		}
		return true;
	}
/**
 *  Validates a 1 dimensional array based on a data spec 
 * @param array &$dataAarray 1-d array of data that is to be validated
 * @param array &$dataSpecArray array of keys that map to data specs
 * @return bool If all $checkKeysArray are found and validated in $dataArray, returns true, otherwise returns false.
 */
	function aerp_validate(&$data, $validationSpec){

//		echo "Validating Data<BR>";

		$errorList = '';
		$errCount = 0;
		
		foreach( $validationSpec as $key=>$spec ){
			if( !isset($data[$key] ) ){
				
//				echo "Data not set<BR>";
				
				if( $spec['required'] === TRUE ){
				
//					echo "Required! Error!<BR>";
				
					$errCount++;
					$errorList .= $validKey.' is required.<BR>';
				}
				continue;
			}
			if( TRUE !== $valid = aerp_validate_check_spec($data[$key], $spec ) ){ // call this directly because the class may not be instantiated
//				echo "variable validation failed.<BR>";
				$errCount++;
//				echo "valid = ".$valid;
				$errorList .= $valid;
			}
		}
		if( $errCount > 0 ){
			return $errorList;
		}
		return TRUE;
	}
/**
 * 	Validate $data agaisnt a $dataSpec
 * @param variant &$data data that is to be validated
 * @param variant &$dataSpecarray array of validation parameters
 * @return boolean If &$data passes all validation parameters defined in &$dataSpec, returns true, otherwise returns false
 */
 	function aerp_validate_check_spec(&$valueToCheck, &$validationSpec){
	
		//echo var_dump($arrayToCheck);
		//echo var_dump($validationSpec);
		
		$errorList = '';
		$errorCount = 0;
		
		if( isset($validationSpec['rule'] ) ){
		
//			echo '----begin validation ---- (single-rule validation!)<BR>';
			aerp_validate_rule(array(
				'rule'=>array(
					'rule'=>&$validationSpec['rule']
					,'check'=>&$validationSpec['check']
					,'message'=>&$validationSpec['message']
				)
				,'value'=>&$valueToCheck
				,'errorCount'=>&$errorCount
				,'errorList'=>&$errorList
			));
//			echo '---- end validation ----<BR>';
			
		}elseif( isset($validationSpec['rules'] ) ){
//			echo '----begin validation ---- (multi-rule validation!)<BR>';
			
			foreach($validationSpec['rules'] as $rule ){
			
			aerp_validate_rule(array(
				'rule'=>array(
					'rule'=>&$rule[0]
					,'check'=>&$rule[1]
					,'message'=>&$rule[2]
				)
				,'value'=>$valueToCheck
				,'errorCount'=>&$errorCount
				,'errorList'=>&$errorList
			));
			/*
				if( !function_exists( $rule[0] ) ){
				//if( !method_exists( $this, $rule[0] ) ){
					echo '-- validation rule "'.$rule[0].'" method doesn\'t exist!<BR>';
					$errorCount++;
					$errorList[] = $rule . ' = Invalid validation spec!' ;
				}else{
					echo '-- validation rule "'.$rule[0].'" EXISTS, checking against!<BR>';
					$validationFunction = 'aerp_validate_'.$rule[0];
					if(!$validationFunction( $valueToCheck ) ){
						echo "-- -- variable validation failed!<BR>";
						$errorCount++;
						$errorList[] = $rule[2];
					}else{
						echo "-- -- variable validation passed!<BR>";
					}
				}
				*/
			}
//			echo '---- end validation ----<BR>';
		}else{
			$errorCount++;
			$errorList .= "No validation rules specified!";
		}
//		if( isset($validationSpec['regex'] )){
//			if(!aerp_validate::regex($validationSpec['regex'], $valueToCheck) ){
//				$errCount++;
//				$errorList[] = $validationSpec['errorMsg'] ;
//			}
//		}
	
		if($errorCount > 0){
			return $errorList;
		}
		return TRUE;
	}
	
	function aerp_validate_rule($params){
			//if( !method_exists( $this, $validationSpec['rule'] ) ){
			if( !function_exists( 'aerp_validate_'.$params['rule']['rule'] ) ){
//				echo '-- validation rule "'.$params['rule']['rule'].'" method doesn\'t exist!<BR>';
				
				if( function_exists( $params['rule']['rule'] ) ){
//					echo '-- validation rule "'.$params['rule']['rule'].'" function EXISTS, checking against!<BR>';
					if($params['rule']['rule']($params['value']) ){
//						echo "-- -- variable validation passed!<BR>";
					}else{
//						echo "-- -- variable validation failed!<BR>";
						$params['errorCount']++;
						$params['errorList'] .= $params['rule']['message'];
					}
				}else{
//					echo '-- validation rule "'.$params['rule']['rule'].'" function EXISTS, checking against!<BR>';
					$params['errorCount']++;
					$params['errorList'] .= $params['rule']['rule'] . ' = Invalid validation spec!' ;
				}
			}else{
//				echo '-- validation rule "'.$params['rule']['rule'].'" method EXISTS, checking against!<BR>';
				//if(aerp_validate::$validationSpec['rule']($valueToCheck) ){
				$validationFunction = 'aerp_validate_'.$params['rule']['rule'];
				if($validationFunction($params['value']) ){
//					echo "-- -- variable validation passed!<BR>";
				}else{
//					echo "-- -- variable validation failed!<BR>";
						$params['errorCount']++;
						$params['errorList'] .= $params['rule']['message'];
				}
			}
	}
	function aerp_validate_regex(&$regex,&$val){
		$return = preg_match($regex,$val);
		if( $return == FALSE ){
			return FALSE;
		}
		return TRUE;
	}
	
	function aerp_validate_is_numeric(&$val){
		return is_numeric($val);
	}
	function aerp_validate_not_empty(&$val){
		if( trim($val) === ''){
			return FALSE;
		}
		return TRUE;
	}
		
//} //end class aerp_validate

?>
