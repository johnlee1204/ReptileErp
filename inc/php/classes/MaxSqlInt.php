<?php

class MaxSqlInt {
	const SqlMaxIntValue = 2147483647;
	const SqlMinIntValue = -2147483648;

	static function checkValidInt($value){
		if( filter_var($value, FILTER_VALIDATE_INT) === false ){
			die('omg!');
			return false;
		}
		if($value > MaxSqlInt::SqlMaxIntValue || $value < MaxSqlInt::SqlMinIntValue){
			return false;
		}
		return true;
	}

}
