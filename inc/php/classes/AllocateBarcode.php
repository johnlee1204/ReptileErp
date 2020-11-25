<?php

class AllocateBarcode {

	static function allocateCode( $dbc, $type='', $val1='', $val2='', $val3='', $val4='' ){
		
		$barcodesTable = 'fwe_dev..barcodes';
	
		$sql = "INSERT INTO {$barcodesTable} (type, val1, val2, val3, val4) VALUES ('{$type}', '{$val1}', '{$val2}', '{$val3}', '{$val4}')";
		if( FALSE === $dbc->query($sql ) ){
			return false;
		}
		if( $dbc->affected_rows() < 1 ){
			return false;
		}
		
		$sql = "SELECT @@IDENTITY";
		if( NULL === $row = $dbc->fetch_row($sql ) ){
			return false;
		}
		
		return $row[0];
	}
	
	static function getOrAllocateCode( $dbc, $type='', $val1='', $val2='', $val3='', $val4='' ){
	
		if( $val1 === '' || $type==='' ){
			return false;
		}
	
		$barcodesTable = 'fwe_dev..barcodes';
		
		$sql = "select barcodeId, type from {$barcodesTable} where type='{$type}' and val1='{$val1}' and val2='{$val2}' and val3='{$val3}' and val4='{$val4}'";
		
		//get the barcode, if it wasn't found, create a record
		if( NULL === $row = $dbc->fetch_assoc($sql ) ){
			$barcode = AllocateBarcode::allocateCode( $dbc, $type, $val1, $val2, $val3, $val4 );
		}else{
			$barcode = trim($row['barcodeId']);
		}
		
		return $barcode;
		
	}
}

?>