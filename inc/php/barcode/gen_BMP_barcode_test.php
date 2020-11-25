<?php


	include($_SERVER['DOCUMENT_ROOT'] . '/barcode/gen_BMP_barcode_main.php' );
	
	;
	
	$barcode = genbarcode('123452');
	header("Content-type: image/png"); // Automatic content type output
	imagepng($barcode); // Automatic image type output
	//imagepng(imagerotate($barcode, '90', imagecolorallocate($barcode, 255, 255, 255))); // Automatic image type output
?>
