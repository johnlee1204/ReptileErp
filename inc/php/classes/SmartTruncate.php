<?php
	//namespace Cls;
	//Temp solution for non namespaced classes to be autoloaded.
	//class_alias("Cls\\SmartTruncate","SmartTruncate");

	class SmartTruncate{
		static function truncate($value){
			if(!is_numeric($value)){
				return $value;
			}
			$value = (string)$value;

			$zeroTest = '/^0*\.*0*$/U';
			$wholeNumberTest = '/^(-?[1-9][0-9]*)$/U';
			$wholeNumberTestTrailingZeros = '/^(-?[1-9][0-9]*0*).0*$/U';
			$decimalTest = '/^(-?0\.0*[1-9][0-9]*)0*$/U';
			$decimalGtZeroTest = '/^(-?[1-9][0-9]*\.0*[1-9]*[0-9]*)0*$/U';
			$decimalNoLead = '/^(\.0*[1-9]*[0-9]*)0*$/U';

			if( preg_match($zeroTest, $value, $matches) > 0){
				return '0';
			}
			if(false !== strpos(strtoupper($value),'E')){
				$value = number_format($value,10,'.','');
			}
			if( preg_match($wholeNumberTest, $value, $matches) > 0){
				return $matches[1];
			}
			if( preg_match($wholeNumberTestTrailingZeros, $value, $matches) > 0){
				return $matches[1];
			}
			if( preg_match($decimalTest, $value, $matches) > 0){
				return $matches[1];
			}
			if( preg_match($decimalGtZeroTest, $value, $matches) > 0){
				return $matches[1];
			}
			if( preg_match($decimalNoLead, $value, $matches) > 0){
				return '0'.$matches[1];
			}
			return $value;
		}
	}
?>